<?php

class serverjar {
  public $mc;
  public $path_base = null;
  function __construct($mc) {
    $this->mc = $mc;

    $minecraft_sh = $this->get_minecraft_sh();
    $lines = file($minecraft_sh);
    foreach ($lines as $line) {
      if (preg_match('/^\s*PATH_BASE\s*=\s*"(\\/[\\.a-zA-Z_\\-0-9\\/]+)"\s*$/', $line, $matches)) {
	$this->path_base = $matches[1];
      }
    }

    if ($this->path_base === null) {
      echo "Failed to determine PATH_BASE in " . e($minecraft_sh);
      exit(1);
    }
  }

  public function get_jars() {
    $dir = $this->get_dir();
    $files = scandir($dir);
    $paths = Array();
    foreach ($files as $file) {
      if (preg_match('/\.jar$/', $file)) {
	$paths[] = $dir . "/" . $file;
      }
    }

    return $paths;
  }

  public function get_dir() {
    return $this->path_base . "/jars/serverjars";
  }

  public function get_minecraft_sh() {
    $minecraft_sh = $this->mc->server_dir . "/minecraft.sh";
    return $minecraft_sh;
  }

  public $file_jar_regex = '/^\s*FILE_JAR\s*=\s*["\'](\\$PATH_BASE)?(\\/[\\.a-zA-Z_\\-0-9\\/]+)["\']\s*$/';
  public function get_installed_path() {
    $minecraft_sh = $this->get_minecraft_sh();
    $lines = file($minecraft_sh);
    $path = null;
    foreach ($lines as $line) {
      if (preg_match($this->file_jar_regex, $line, $matches)) {
	if ($matches[1] === "") {
	  $path = $matches[2];
	} else {
	  $path = $this->path_base . "/" . $matches[2];
	}
      }
    }

    if ($path === null) {
      echo "Unable to determine installed jar in " . e($minecraft_sh);
      exit(1);
    }

    return $path;
  }

  public function path_ok($path) {
    $all_jars = $this->get_jars();
    $found = false;
    foreach ($all_jars as $one_jar) {
      if ($one_jar === $path) {
	$found = true;
	break;
      }
    }
    if (!$found) {
      echo e($path) . " is not on the list of available server jars!";
      exit(1);
    }
  }

  public function activate($path) {
    //check that $path is valid
    $this->path_ok($path);

    $minecraft_sh = $this->get_minecraft_sh();
    $lines = file($minecraft_sh);
    foreach ($lines as $i => $line) {
      if (preg_match($this->file_jar_regex, $line, $matches)) {
	$lines[$i] = sprintf("FILE_JAR=%s\n", escapeshellarg($path));
	$text = implode("", $lines);
	$res = file_put_contents($minecraft_sh, $text);
	if ($res === false) {
	  echo "Failed to write new FILE_JAR ".e($path)." to " . e($minecraft_sh);
	}
	echo "new jar " . e($path) . " installed!";
	return;
      }
    }

    echo "Failed to find FILE_JAR line!";
    exit(1);
  }

  public function install($type, $id) {
    echo "Checking... ";
    $list = serverjar_list::get_type($type);
    $version = $list->get_from_id($id);
    if ($version === null) {
      die("did not find binary with id ".$id);
    }
    if (file_exists($this->get_dir() . "/" . $version["filename"])) {
      die("There already exists a file with the name ".$version["filename"]);
    }
    echo "checked!\n";
    echo "Downloading... ";
    $data = file_get_contents($version["url"]);
    if ($data === null  || $data === false) {
      die("Failed to download ".$version["url"]);
    }
    echo "downloaded!\n";
    $res = file_put_contents($this->get_dir()."/".$version["filename"], $data);
    if ($res === false) {
      die("Failed to write file to disk");
    }
    echo "successfully installed new serverjar ".$version["filename"];
  }

  public function delete($path) {
    //check that $path is valid
    $this->path_ok($path);

    if ($path === $this->get_installed_path()) {
      echo "Can't delete active server binary!";
      exit(1);
    }

    echo "Deleting $path";
    unlink($path);
  }
}

?>
