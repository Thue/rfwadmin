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
    case "bukkit_recommended":
      $class = new serverjar_list_bukkit_recommended();
      break;
    case "bukkit_beta":
      $class = new serverjar_list_bukkit_beta();
      break;
    case "sportbukkit":
      $class = new serverjar_list_sportbukkit();
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
}


abstract class serverjar_list_vanilla extends serverjar_list {
  private static $json_link = 'https://s3.amazonaws.com/Minecraft.Download/versions/versions.json';
  private static $json_fetched = null;

  public static function fetch_json() {
    if (self::$json_fetched === null) {
      self::$json_fetched = json_decode(file_get_contents(self::$json_link));
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

abstract class serverjar_list_bukkit extends serverjar_list {
  public function get_files($type) {
    $bukkit_json_string = file_get_contents("https://dl.bukkit.org/api/1.0/downloads/projects/craftbukkit/artifacts/$type/?_accept=application/json");
    $json = json_decode($bukkit_json_string);
    $versions = Array();
    foreach ($json->results as $result) {
      $versions[] = Array("id" => $result->version,
			  "releaseTime" => date("Y-m-d", strtotime($result->created)),
			  "url" => "https://dl.bukkit.org".$result->file->url,
			  "filename" => "craftbukkit_".$result->version.".jar",
			  );
    }

    return $versions;
  }
}


class serverjar_list_bukkit_recommended extends serverjar_list_bukkit {
  function __construct() {
    $this->versions = $this->get_files("rb");
  }
}

class serverjar_list_bukkit_beta extends serverjar_list_bukkit {
  function __construct() {
    $this->versions = $this->get_files("beta");
  }
}


class serverjar_list_sportbukkit extends serverjar_list {
  function __construct() {
    $sbukkit_json_string = file_get_contents("http://jenkins.musclecraft.net:8080/job/SportBukkit/lastSuccessfulBuild/api/json?pretty=true");
    $json = json_decode($sbukkit_json_string);

    $result = $json->artifacts[0];
    $versions = Array();
    if (preg_match('/sportbukkit-([\d\.]+-R[\d\.]+(?:-SNAPSHOT)?).jar/', $result->fileName, $matches)) {
      $vstring = $matches[1];
    } else {
      $vstring = "(Couldn't parse version)";
    }
    $git_version = $json->actions[2]->lastBuiltRevision->SHA1;
    $vstring .= " (" . substr($git_version, 0, 8). "...)";
    $versions[] = Array("id" => $vstring,
			"releaseTime" => date("Y-m-d", $json->timestamp/1000),
			"url" => "http://jenkins.musclecraft.net:8080/job/SportBukkit/lastSuccessfulBuild/artifact/".$result->relativePath,
			"filename" => $result->fileName,
			);
  
    $this->versions = $versions;
  }
}

?>
