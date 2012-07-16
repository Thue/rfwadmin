<html>
<head>

</head>

<body>
<pre>
<?php

function get_tmp_dir() {
  $tmp = tempnam("/tmp", "minecraft_");
  unlink($tmp) ||die("error unlinking tmp file\n");
  mkdir($tmp);
  return $tmp;
}

function install_map($parent_dir, $filename_hint) {
  //remove .zip ending
  $filename_hint = preg_replace('/\A(.+)\.(zip|rar)\z/', '\\1', $filename_hint);
  //restrict to limited character set
  $filename_hint = preg_replace('/[^a-zA-Z0-9_\-#\'"]/', '_', $filename_hint);

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

      var_dump($filename_hint);
      if ($name === null) {
	if ($filename_hint == "") {
	  die("unable to guess map name!");
	}
	$name = $filename_hint;
      }

      $name = preg_replace('/[^a-zA-Z0-9_\-#\'"]/', '_', $name);

      if (is_dir($full_path . "/region")) {
	$target = minecraft_map::$map_dir . "/" . $name;
	echo "found minecraft save '" . $name . "'\n";
	if (file_exists($target)) {
	  echo "failed to install map - a map with that name already existed\n";
	} else {
	  $cmd = sprintf("mv %s %s",
			 escapeshellarg($full_path),
			 escapeshellarg($target)
			 );
	  passthru($cmd);
	  echo "installed!\n";
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

function handle_zip($path) {
  $tmp = get_tmp_dir();

  $zip = new ZipArchive;
  if ($zip->open($path) === TRUE) {
    $zip->extractTo($tmp);
    $zip->close();
    echo "zip opened\n";
  } else {
    die("failed opening zip\n");
  }

  return $tmp;
}

function handle_rar($path) {
  $tmp = get_tmp_dir();

  $rar_archive = rar_open($path);
  $entries = $rar_archive->getEntries();
  foreach ($entries as $entry) {
    $entry->extract($tmp); // extract to the current dir
  }
  rar_close($rar_archive);

  return $tmp;
}

function unpack_file($path) {
  switch (mime_content_type($path)) {
  case "application/zip":
    $tmp = handle_zip($path);
    break;
  case "application/x-rar":
    $tmp = handle_rar($path);
    break;
  default:
    die("unknown file type " . mime_content_type($path));
  }

  return $tmp;
}

if (isset($_POST["upload_file"])) {
  $file = $_FILES["file"];
  if ($file["error"] !== 0) {
    echo "Upload failed!\n";
    if ($file["error"] === 1) {
      die("upload_max_filesize in php.ini is too small (php.init probably located at /etc/php5/apache2/php.ini in the filesystem)");
    } else {
      die("error " . $file["error"] . " ( http://www.php.net/manual/en/features.file-upload.errors.php )\n");
    }
  }

  $tmp = unpack_file($file["tmp_name"]);
  install_map($tmp, $file["name"]);
} else if (isset($_POST["upload_link"])) {
  echo "fetching file... ";
  $ch = curl_init($_POST["link"]);
  $path = tempnam("/tmp", "minecraft_curl_");
  $fp = fopen($path, "w");

  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS) || die("failed to limit protocol");

  curl_exec($ch) || die("failed to download '" . $_POST["link"] . "'");
  curl_close($ch);
  fclose($fp);
  echo "fetched!\n";

  $filename_hint = null;
  if (preg_match('/\/([^\/]+?)\.(zip|rar)\z/', $_POST["link"], $matches)) {
    $filename_hint = $matches[1];
  }

  $tmp = unpack_file($path);

  install_map($tmp, $filename_hint);
} else {
  echo "unknown command!";
}


?>

</pre>

</body>

</html>