<?php

abstract class serverjar_list {
  public $versions = Array();

  public function get_json() {
    return json_encode($this->versions);
  }

  public static function get_type($type) {
    switch ($type) {
    case "vanilla_release":
      $class = new serverjar_list_vanilla_release();
      break;
    case "vanilla_snapshot":
      $class = new serverjar_list_vanilla_snapshot();
      break;
    default:
      die(1);
    }

    return $class;
  }

  public function get_from_id($id) {
    foreach ($this->versions as $version) {
      if ($version["id"] === $id) {
	return $version;
      }
    }

    return null;
  }

  public static function fetch_cached($url, $name) {
    global $mc;
    $serverjar = $mc->get_serverjar();
    $cache_dir = $serverjar->path_base . "/jars/cache";
    if (!file_exists($cache_dir)) {
      mkdir($cache_dir);
    }

    if (!is_dir($cache_dir) || !is_writable($cache_dir)) {
      printf("Cache dir '%s' not writable\n", e($cache_dir));
    }

    $filename = $cache_dir . "/" . $name;

    if (file_exists($filename) && time()-filemtime($filename) < 1800) {
      $raw = file_get_contents($filename);
    } else {
      $raw = file_get_contents($url);
      if ($raw !== false) {
	$res = file_put_contents($filename, $raw);
	if ($res === false) {
	  printf("Failed to write cache file '%s'.", e($filename));
	}
      } else {
	printf("Failed to download %s from %s", e($name), e($url));
      }
    }

    return $raw;
  }
}


abstract class serverjar_list_vanilla extends serverjar_list {
  private static $json_link = 'https://s3.amazonaws.com/Minecraft.Download/versions/versions.json';
  private static $json_fetched = null;

  public static function fetch_json() {
    if (self::$json_fetched === null) {
      $raw = self::fetch_cached(self::$json_link, "vanilla");
      self::$json_fetched = json_decode($raw);
    }

    return self::$json_fetched;
  }

  public static function get_list() {
    $json = serverjar_list_vanilla::fetch_json();
    if (!is_object($json)) {
      die(1);
    }
    return $json->versions;
  }

  public function get_by_type($type) {
    $versions = serverjar_list_vanilla::get_list();
    $versions_filtered = Array();
    foreach ($versions as $version) {
      if ($version->type === $type) {
	$url = sprintf("https://s3.amazonaws.com/Minecraft.Download/versions/%s/minecraft_server.%s.jar", $version->id, $version->id);
	$versions_filtered[] = Array("id" => $version->id,
				     "releaseTime" => date("Y-m-d", strtotime($version->releaseTime)),
				     "url" => $url,
				     "filename" => "minecraft_server.".$version->id.".jar",
				     );
      }
    }

    return $versions_filtered;
  }
}

class serverjar_list_vanilla_release extends serverjar_list_vanilla {
  function __construct() {
    $this->versions = $this->get_by_type("release");
  }
}

class serverjar_list_vanilla_snapshot extends serverjar_list_vanilla {
  function __construct() {
    $this->versions = $this->get_by_type("snapshot");
  }
}

?>
