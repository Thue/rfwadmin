<?php
require_once(dirname(__FILE__) . "/real_csv.php");

class minecraft_map {
  public static $map_dir; //set in index.php
  public $name; //file name
  public $path;

  public $map_name = null; //map name, fx "Direct Fire" (may be null)
  public $map_version = null; //Map version, fx "1.0" (may be null)
  public $map_md5sum = null; //md5sum of direct_fire.zip, which the map came from (may be null)
  public $from_armory = false;

  function __construct($name) {
    $this->path = self::validate($name);
    self::assert_map_existence($name);
    $this->name = $name;

    $map_name_path = $this->path . "/rfwadmin_map_name";
    if (file_exists($map_name_path)) {
      $this->map_name = trim(file_get_contents($map_name_path));
    }

    $map_version_path = $this->path . "/rfwadmin_map_version";
    if (file_exists($map_version_path)) {
      $this->map_version = trim(file_get_contents($map_version_path));
    }

    $map_md5sum_path = $this->path . "/rfwadmin_map_md5sum";
    if (file_exists($map_md5sum_path)) {
      $this->map_md5sum = trim(file_get_contents($map_md5sum_path));
    }

    $this->from_armory = preg_match('/\(ARMoRy\)$/', $this->name);
  }

  public function __toString() {
    return $this->name;
  }

  public function delete($in_window) {
    global $mc;
    $cmd = sprintf("rm -rfv %s 2>&1", escapeshellarg($this->path));
    if ($in_window) {
      echo $cmd . "\n";
      $retval = $mc->my_passthru($cmd);
      echo "\nDeleted!\n";
    } else {
      $retval = null;
      $output = exec($cmd, $dummy, $retval);
    }

    return $retval === 0;
  }

  public static function validate($map) {
    preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9#_\-"\'\s.\[\]\(\)]*\Z/', $map) || die("bad characters in map name '$map'!");
    $full_path = self::$map_dir . "/" . $map;
    return $full_path;
  }

  public static function get_map_list() {
    $myDirectory = opendir(self::$map_dir);

    // get each entry
    $dir_array = Array();
    while ($entryName = readdir($myDirectory)) {
      if (!in_array($entryName, Array(".", "..", "armory_last_synced.txt"))) {
	$dir_array[] = new minecraft_map($entryName);
      }
    }
    // close directory
    closedir($myDirectory);

    natcasesort($dir_array);
    return $dir_array;
  }

  public static function map_exists($name) {
    $path = self::validate($name);
    return file_exists($path);
  }

  public static function assert_map_existence($name) {
    if (!self::map_exists($name)) {
      die("There is already a map with the name " . $name);
    }
  }
  
  public static function assert_map_nonexistence($name) {
    if (self::map_exists($name)) {
      die("There is already a map with the name " . $name);
    }
  }

  public function download() {
    $path = self::validate($this->name);

    $zip_path = tempnam("/tmp", "minecraft_zip_");
    $cmd = sprintf("cd %s; zip -r %s %s",
		   escapeshellarg(minecraft_map::$map_dir),
		   escapeshellarg($zip_path),
		   escapeshellarg($this->name)
		   );
    exec($cmd, $output, $return_var);
    if ($return_var !== 0) {
      die("failed zipping");
    }

    // Date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    // Always modified
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    // HTTP/1.1
    // Not no-store or no-cache, which messes up IE as described 5
    // lines below!
    header("Cache-Control: must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    // HTTP/1.0
    header("Pragma: no-cache");

    header(sprintf("Content-Disposition: attachment; filename=\"%s\"", $this->name . ".zip"));

    echo file_get_contents($zip_path . ".zip");
  }

  public static function armory_installed_maps() {
    $maps = self::get_map_list();
    foreach ($maps as $i => $map) {
      if (!$map->from_armory) {
	unset($maps[$i]);
      }
    }

    $maps = array_values($maps);
    return $maps;
  }

  public static function armory_sync() {
    $base = "http://s3.amazonaws.com/autoreferee/maps";
    $list_url = $base . "/list.csv";
    $list_csv = file_get_contents($list_url);
    if ($list_csv === false) {
      echo "error: failed to get map list.\n";
      exit(1);
    }

    try {
      $matrix = real_csv::parse($list_csv, ";");
    } catch (exception $ex) {
      echo "error: " . $ex->getMessage() . "\n";
    }

    $old_maps = self::armory_installed_maps();
    foreach ($matrix as $line) {
      if (sizeof($line) > 0) {
	$name = $line[0];
	$version = $line[1];
	$url = $base . "/" . $line[2];
	$md5sum = $line[3];
	$rfwadmin_vars = Array("name" => $name,
			       "version" => $version,
			       "md5sum" => $md5sum);
	$armory_name = self::armory_get_name($name, $version);

	echo "debug: $name\n";

	$found_old_map = null;
	$found = false;
	foreach ($old_maps as $i => $old_map) {
	  if ($old_map->map_name === $name) {
	    $found_old_map = $old_map;
	    unset($old_maps[$i]);
	    break;
	  }
	}
	if ($found_old_map === null) {
	  echo "debug: new map " . $name . "\n";
	  self::fetch_and_install($url, false, $armory_name, $rfwadmin_vars);
	  $json = Array("name" => $name,
			"version" => $version,
			"filename" => $armory_name,
			);
	  echo "new_map: " . json_encode($json) . "\n";
	} else {
	  //update existing map
	  $changed = $old_map->map_name !== $name
	    || $old_map->map_version !== $version
	    || $old_map->map_md5sum !== $md5sum
	    || self::armory_get_name($name, $version) !== $old_map->name;
	  echo "debug: old map " . $name .  "," . ($changed ? " " : " not ") . "updated\n";
	  if ($changed) {
	    self::armory_update_map($old_map, $url, $rfwadmin_vars);
	    $json = Array("name" => $name,
			  "old_version" => $old_map->map_version,
			  "new_version" => $version,
			  "old_filename" => $old_map->map_name,
			  "new_filename" => $armory_name,
			  );
	    echo "map_updated: " . json_encode($json) . "\n";
	  } //else do nothing
	}
      }

      ob_flush();
      flush();
    }

    /* Any maps left in list are no longer at the armory. Rename them (instead of deleting them) */
    foreach ($old_maps as $old_map) {
      $new_name = preg_replace('/\(ARMoRy\)/', '(ARMoRy deleted)', $old_map->name);
      if (self::map_exists($new_name)) {
	$old_map->delete(false);
	$new_name = "deleted";
      } else {
	$old_map->rename($new_name, false);
      }
      $json = Array("old_full_name" => $old_map->name,
		    "new_full_name" => $new_name);
      echo "map_deleted: " . json_encode($json) . "\n";
    }

    file_put_contents(self::get_sync_file(), time());
    echo "done\n";
  }

  public static function get_sync_file() {
    return self::$map_dir . "/armory_last_synced.txt";
  }

  public static function armory_update_map(minecraft_map $old_map, $url, Array $rfwadmin_vars) {
    $old_map->delete(false);
    $armory_name = self::armory_get_name($rfwadmin_vars["name"], $rfwadmin_vars["version"]);
    self::fetch_and_install($url, false, $armory_name, $rfwadmin_vars);
  }

  public static function armory_get_name($name, $version) {
    $armory_name = sprintf("%s %s (ARMoRy)", $name, $version);
    return $armory_name;
  }

  public static function fetch_and_install($url, $in_window, $installed_name=null, $rfwadmin_vars=Array()) {
    if ($in_window) echo "fetching file... ";
    $ch = curl_init($url);
    $path = tempnam("/tmp", "minecraft_curl_");
    $fp = fopen($path, "w");

    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS) || die("failed to limit protocol");

    curl_exec($ch) || die("failed to download '" . $url . "'");
    curl_close($ch);
    fclose($fp);
    if ($in_window) echo "fetched!\n";


    if ($installed_name !== null) {
      $filename_hint = $installed_name;
    } else {
      $filename_hint = null;
      if (preg_match('/\/([^\/]+?)\.(zip|rar)\z/', $url, $matches)) {
	$filename_hint = $matches[1];
      }
    }

    if (mime_content_type($path) === "text/html") {
      echo "Not a zip file!\n";

      echo "</pre>";
      printf('The link <a href="%1$s">%1$s</a> points to a HTML document suitable for viewing in a '.
	     "browser, and not to a zip file. Usually this a download site trying to ".
	     "earn ad money by having an intermediate download page. ".
	     "One fairly sure way of getting the actual download link is to actually start ".
	     "the download in the browser <a href=\"http://www.google.com/chrome/\">Google Chrome</a>, then pushing CTRL-j, and then ".
	     "copying the link displayed below the file name.",
	     e($_POST["link"])
	     );
      exit(1);
    }

    $tmp = self::unpack_file($path, $in_window);

    self::install_map($tmp, $filename_hint, $in_window, $installed_name !== null, $rfwadmin_vars);
  }

  public static function install_map($parent_dir, $filename_hint, $in_window, $prefer_filename_hint=false, Array $rfwadmin_vars=Array()) {
    //restrict to limited character set
    $filename_hint = preg_replace('/[^ a-zA-Z0-9_\-#\'"\(\)\.]/', '_', $filename_hint);

    $myDirectory = opendir($parent_dir);

    // get each entry
    $dir_array = Array();
    $moved = false;
    while ($entryName = readdir($myDirectory)) {
      $full_path = $parent_dir . "/" . $entryName;
      if (!in_array($entryName, Array(".", ".."))
	  && is_dir ($full_path)) {
	$name = $entryName;
	if (is_dir($full_path . "/world/region")) {
	  $full_path .= "/world";
	} else if ($entryName === "region") {
	  $full_path = $parent_dir;
	  $name = null;
	}

	if ($entryName === "world"
	    && $filename_hint != "") {
	  $name = $filename_hint;
	}

	if ($name === null) {
	  if ($filename_hint == "") {
	    die("unable to guess map name!");
	  }
	  $name = $filename_hint;
	}

	if ($prefer_filename_hint) {
	  $name = $filename_hint;
	}

	$name = preg_replace('/[^ a-zA-Z0-9_\-#\'"\(\)\.]/', '_', $name);

	if (is_dir($full_path . "/region")) {
	  $target = minecraft_map::$map_dir . "/" . $name;
	  if ($in_window) echo "found minecraft save '" . $name . "'\n";
	  if (file_exists($target)) {
	    echo "failed to install map - a map with that name already existed\n";
	  } else {
	    $cmd = sprintf("mv %s %s",
			   escapeshellarg($full_path),
			   escapeshellarg($target)
			   );
	    passthru($cmd);
	    foreach ($rfwadmin_vars as $key => $value) {
	      file_put_contents($target . "/rfwadmin_map_" . $key, $value);
	    }

	    if ($in_window) {
	      echo "installed!\n";
	      reload_main($name);
	    }
	  }
	  $moved = true;
	  break;
	}
      }
    }
    if (!$moved) {
      echo "didn't find a minecraft save in the unpacked file\n";
    }
    // close directory
    closedir($myDirectory);
  }

  private static function get_tmp_dir() {
    $tmp = tempnam("/tmp", "minecraft_");
    unlink($tmp) ||die("error unlinking tmp file\n");
    mkdir($tmp);
    return $tmp;
  }

  private static function handle_zip($path, $in_window) {
    $tmp = self::get_tmp_dir();

    $zip = new ZipArchive;
    if ($zip->open($path) === TRUE) {
      $zip->extractTo($tmp);
      $zip->close();
      if ($in_window) echo "zip opened\n";
    } else {
      die("failed opening zip\n");
    }

    return $tmp;
  }

  private static function handle_rar($path, $in_window) {
    $tmp = self::get_tmp_dir();

    $rar_archive = rar_open($path);
    $entries = $rar_archive->getEntries();
    foreach ($entries as $entry) {
      $entry->extract($tmp); // extract to the current dir
    }
    rar_close($rar_archive);

    return $tmp;
  }

  public static function unpack_file($path, $in_window) {
    switch (mime_content_type($path)) {
    case "application/zip":
      $tmp = self::handle_zip($path, $in_window);
      break;
    case "application/x-rar":
      $tmp = self::handle_rar($path, $in_window);
      break;
    default:
      die("unknown file type " . mime_content_type($path));
    }

    return $tmp;
  }

  public function rename($target, $in_window) {
    global $mc;
    $full_path_target = minecraft_map::validate($target);
    minecraft_map::assert_map_nonexistence($target);
    if ($in_window) echo "Renaming...\n";
    $cmd = sprintf("mv %s %s", 
		   escapeshellarg($this->path),
		   escapeshellarg($full_path_target)
		   );
    $mc->my_passthru($cmd);
    if ($in_window) echo "Renamed!";
  }

}

?>
