<?php

class plugin {
  public $plugins;
  public $name; //a subdir name string
  public $versions = Array(); //version => path
  public $has_configuration = false;

  function __construct(plugins $plugins, $name, $has_configuration=false) {
    $this->plugins = $plugins;
    $this->name = $name;
    $this->has_configuration = $has_configuration;
  }

  public function add_version($version, $path) {
    $this->versions[$version] = $path;
  }

  public function install($version, $use_configuration) {
    $plugins_dir = $this->plugins->get_plugins_dir();

    //Check that no version of the plugin is already installed
    if ($this->get_installed_version() !== null) {
      printf("There is already an installed version of the '%s' version.", $this->name);
      return false;
    }

    //find and validate source of plugin
    if (!isset($this->versions[$version])) {
      printf("The plugin '%s' has no version %s.", e($this->name), e($version));
      return false;
    }
    $source = $this->versions[$version];

    $target = sprintf("%s/%s", $plugins_dir, $this->name . "_" . $version . ".jar");
    if (!symlink($source, $target)) {
      echo "enabling plugin failed - check write permissions.";
      return false;
    }

    printf("Plugin '%s' version '%s' has been installed.", e($this->name), e($version));
    return true;
  }

  public function get_installed_version(&$path=null) {
    $plugins_dir = $this->plugins->get_plugins_dir();

    $links = scandir($plugins_dir);
    foreach ($links as $link) {
      $dir = $plugins_dir . "/" . $link;
      if (is_link($dir)) {
	$path = $dir;
	$jarpath = readlink($dir);
	$version = basename(dirname($jarpath));
	$name = basename(realpath(dirname($jarpath) . "/.."));
	if ($name === $this->name) {
	  return $version;
	}
      }
    }

    return null;
  }

  public function uninstall($version=null) {
    $path = null;
    $installed = $this->get_installed_version($path);
    if ($installed !== null) {
      if ($version !== null && $version !== $installed) {
	printf("Asked to uninstall '%s' version '%s', but version '%s' was installed!",
	       e($this->name), e($version), e($installed)
	       );
      } else if (!unlink($path)) {
	printf("Failed to uninstalled plugin '%s'.", e($this->name));
	exit(1);
      }
    } else {
      printf("The plugin '%s' is not installed.", e($this->name));
      exit(1);
    }

    printf("Plugin '%s' has been uninstalled.", e($this->name));
  }
}

class plugins {
  public $mc;
  public static $plugins_dir; //set in index.php

  function __construct(minecraft $mc) {
    $this->mc = $mc;
  }

  public function get_all(Array &$unexpected=Array()) {
    $plugins = Array();

    $dirs = scandir(self::$plugins_dir);
    foreach ($dirs as $plugin_name) {
      if (in_array($plugin_name, Array(".", "..", "README"))) {
	  continue;
      }

      $path = self::$plugins_dir . "/" . $plugin_name;
      if (!is_dir($path)) {
	  $unexpected[] = $path;
      } else {
	$plugin = $this->get_all_one_plugin_all_versions($plugin_name, $unexpected);
	if ($plugin !== null) {
	  $plugins[] = $plugin;
	}
      }
    }

    return $plugins;
  }

  public function get_from_name($name) {
    $ps = $this->get_all();

    foreach ($ps as $p) {
      if ($p->name === $name) {
	return $p;
      }
    }

    return null;
  }

  public function install_plugin($name, $version) {
    $plugin = $this->get_from_name($name);
    if ($plugin === null) {
      printf("There is no plugin with the name '%s'.", e($name));
      exit(1);
    } else {
      $plugin->install($version, false);
    }
  }

  public function uninstall_plugin($name, $version) {
    $plugin = $this->get_from_name($name);
    if ($plugin === null) {
      printf("There is no plugin with the name '%s'.", e($name));
      exit(1);
    } else {
      $plugin->uninstall($version);
    }
  }

  //may return null if no versions found
  public function get_all_one_plugin_all_versions($plugin_name, Array &$unexpected) {
    $dir = self::$plugins_dir . "/" . $plugin_name;

    $versions = scandir($dir);
    $configuration = null;
    foreach ($versions as $version) {
      if (in_array($version, Array(".", ".."))) {
	continue;
      }

      $subdir = $dir . "/" . $version;
      if ($version === "configuration") {
	$conf_dir_array = scandir($subdir);
	foreach($conf_dir_array as $i => $d) {
	  if (in_array($d, Array(".", ".."))) {
	    unset($conf_dir_array[$i]);
	  }
	}
	$conf_dir_array = array_values($conf_dir_array);

	$conf_dir = $subdir . "/" . $conf_dir_array[0];
	if (is_dir($subdir)
	    && (sizeof($conf_dir_array) === 1)
	    && is_dir($conf_dir)
	    ) {
	  $configuration = $conf_dir;
	} else {
	  echo "here";
	  $unexpected[] = $subdir;
	}
      }
    }

    //get versions
    $plugin = new plugin($this, $plugin_name, $configuration);
    foreach ($versions as $version) {
      if (in_array($version, Array(".", "..", "configuration"))) {
	continue;
      }

      $subdir = $dir . "/" . $version;
      $jars = scandir($subdir);
      foreach ($jars as $i => $jar) {
	if (in_array($jar, Array(".", ".."))) {
	  unset($jars[$i]);
	}
      }
      $jars = array_values($jars);

      if (sizeof($jars) !== 1) {
	$unexpected[] = $subdir;
      } else if (!preg_match('/\.jar$/', $jars[0])) {
	$unexpected[] = $subdir . "/" . $jars[0];
      } else {
	$plugin->add_version($version, $subdir . "/" . $jars[0]);
      }
    }

    if (sizeof($plugin->versions) === 0) {
      $plugin = null;
      $unexpected[] = $dir;
    }

    return $plugin;
  }

  public function get_plugins_dir() {
    //get/create plugins dir
    $plugins_dir = sprintf("%s/server/plugins", $this->mc->server_dir);
    if (!is_dir($plugins_dir)) {
      if (!file_exists($plugins_dir)) {
	if (!mkdir($plugins_dir)) {
	  printf("Failed to create directory '%s'.", e($plugins_dir));
	  exit(1);
	}
      } else {
	printf("'%s' is not a directory!", e($plugins_dir));
	exit(1);
      }
    }

    return $plugins_dir;
  }


}

?>
