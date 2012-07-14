<?php
require_once(dirname(__FILE__) . "/ansi_shell_to_html.php");

class minecraft_map {
  public static $map_dir; //set in index.php
  public $name;
  public $path;

  function __construct($name) {
    $this->path = self::validate($name);
    self::assert_map_existence($name);
    $this->name = $name;
  }

  public function __toString() {
    return $this->name;
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
      if (!in_array($entryName, Array(".", ".."))) {
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
}

class minecraft {
  public $server_dir; //set in index.php
  public $msh;
  public $map_name_file;
  public $level_dat_location;

  function __construct($server_dir) {
    $this->server_dir = $server_dir;
    $this->msh = sprintf("%s/minecraft.sh", $this->server_dir);
    $this->map_name_file = sprintf("%s/server/world/map_name.txt", $this->server_dir);
  }

  function cmd(Array $args) {
    $cmd = $this->msh;
    foreach ($args as $arg) {
      $cmd .= " " . escapeshellarg($arg) . " 2>&1";
    }
    return $cmd;
  }

  public function get_status() {
    $cmd = $this->cmd(Array("status"));
    $output = Array();
    exec($cmd, $output, $res);
    return ansi_shell_to_html::cmdline_to_html(implode("", $output));
  }

  public function get_current_map($as_text) {
    $map_name = null;
    if (file_exists($this->map_name_file)) {
      $map_name = file_get_contents($this->map_name_file);
    }

    if ($as_text) {
      $text = $map_name === null ? "(Randomly generated map)" : $map_name;
      return $text;
    } else {
      return $map_name;
    }
  }

  //may return null
  public function get_map_loaded_date() {
    if (file_exists($this->map_name_file)) {
      $loaded = filemtime($this->map_name_file);
    } else {
      $loaded = null;
    }

    return $loaded;
  }

  public function get_map_age($as_text) {
    $loaded = $this->get_map_loaded_date();
    if ($loaded === null) {
      $loaded_text = null;
    } else {
      $diff = time() - $loaded;
      $minutes = (int) ($diff / 60);
      $hours = (int) ($minutes / 60);
      $minutes %= 60;
      $days = (int) ($hours / 24);
      $hours %= 24;
      if ($days > 0) {
	$text = sprintf("%d days and %d hours ago", $days, $hours);
      } else if ($hours > 0) {
	$text = sprintf("%d hours and %d minutes ago", $hours, $minutes);
      } else {
	$text = sprintf("%d minutes ago", $minutes);
      }
      return $text;
    }
  }

  public function is_online() {
    //hacky
    $raw = $this->get_status();
    $is_online = preg_match('/online/', $raw) === 1;
    return $is_online;
  }

  /* A version of the built-in passthru() without buffering.
   * 
   * Will not work on Chrome because of a bug in Chrome, unless you manually do a 
   *  header('Content-type: application/octet-stream');
   *  ob_flush();
   *  flush();
   * before calling this function (that is done in action_ajax.php).
   * See http://code.google.com/p/chromium/issues/detail?id=2016
   */
  public function my_passthru($cmd) {
    $handle = popen($cmd, "r");
    while (!feof($handle) && ($read = fread($handle, 1000)) !== false) {
      echo $read;
      ob_flush();
      flush();
    }
    pclose($handle);
  }

  public function start() {
    $cmd = $this->cmd(Array("start"));
    $this->my_passthru($cmd);
  }

  public function stop() {
    $cmd = $this->cmd(Array("stop"));
    $this->my_passthru($cmd);
  }

  public function restart() {
    $cmd = $this->cmd(Array("restart"));
    $this->my_passthru($cmd);
  }

  public function kill() {
    $cmd = $this->cmd(Array("kill"));
    $this->my_passthru($cmd);
  }

  public function nuke() {
    $cmd = $this->cmd(Array("nuke"));
    $this->my_passthru($cmd);
  }

  public function nuke_and_delete() {
    $cmd = $this->cmd(Array("nuke_and_delete"));
    $this->my_passthru($cmd);
  }

  public function save($target, $paranoid) {
    minecraft_map::validate($target);
    minecraft_map::assert_map_nonexistence($target);

    $is_online = $this->is_online();
    $do_stop = $paranoid && $is_online;
    if ($do_stop) {
      $this->stop();
    } else if ($is_online) {
      $cmd = $this->cmd(Array("save"));
      $this->my_passthru($cmd);
    }

    echo "Saving... ";
    $world_file = sprintf("%s/server/world",
			  $this->server_dir);
    $target_full_path = minecraft_map::validate($target);
    $cmd = sprintf("cp -rp %s %s",
		   escapeshellarg($world_file),
		   escapeshellarg($target_full_path)
		   );
    $this->my_passthru($cmd);
    echo "Saved!";

    if ($do_stop) {
      $this->start();
    }
  }

  public function change_map($map) {
    minecraft_map::validate($map);
    $cmd = $this->cmd(Array("changemap", $map));
    $this->my_passthru($cmd);
  }

  public function delete_map($map) {
    $full_path = minecraft_map::validate($map);
    $cmd = sprintf("rm -rfv %s 2>&1", escapeshellarg($full_path));
    echo $cmd . "\n";
    $this->my_passthru($cmd);
    echo "\nDeleted!\n";
  }

  public function rename_map($map, $target) {
    $full_path = minecraft_map::validate($map);
    $full_path_target = minecraft_map::validate($target);
    minecraft_map::assert_map_nonexistence($target);
    echo "Renaming...\n";
    $cmd = sprintf("mv %s %s", 
		   escapeshellarg($full_path),
		   escapeshellarg($full_path_target)
		   );
    $this->my_passthru($cmd);
    echo "Renamed!";
  }
}

?>
