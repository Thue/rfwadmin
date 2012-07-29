<?php

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

?>
