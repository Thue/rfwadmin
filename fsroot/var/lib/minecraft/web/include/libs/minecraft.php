<?php
require_once(dirname(__FILE__) . "/ansi_shell_to_html.php");
require_once(dirname(__FILE__) . "/properties.php");
require_once(dirname(__FILE__) . "/plugins.php");
require_once(dirname(__FILE__) . "/access_lists.php");
require_once(dirname(__FILE__) . "/map.php");
require_once(dirname(__FILE__) . "/serverjar.php");
require_once(dirname(__FILE__) . "/serverjar_list.php");
require_once(dirname(__FILE__) . "/stdlib.php");

function e($text) {
  return htmlspecialchars($text, ENT_QUOTES);
}

//Work around PHP's ridiculous(?) refusal to use the system timezone
ob_start();
date_default_timezone_get();
$maybe_error = ob_get_contents();
ob_end_clean();
if ($maybe_error != "") {
  $tz = exec('date +"%Z"');
  if (!@date_default_timezone_set($tz)) {
    $tz = timezone_name_from_abbr($tz);
    date_default_timezone_set($tz);
  }
}

class minecraft {
  public $server_dir; //Set in index.php, fx "/var/lib/minecraft/servers/default"
  public $msh; //Path to Minecraft.sh
  public $map_name_file; //Name of the currently loaded map
  public $html_title = "rfwadmin"; //Shown in title of all HTML pages

  /* Allow Web interface users to upload (and run!) plugins with
   * arbitrary code! Possibly set =true from /var/www/rfwadmin/index.php */
  public $allow_plugin_upload = false;

  /* ARMoRy is the collection of Race for Wool (rfw) maps which
   * Authorblues keeps up to date for the autoref plugin. If enabled,
   * rfwadmin will automatically download all maps on the list, and
   * redownload when new versions are made available. */
  public $armory_enabled = false;

  public $properties; //set in construct

  function __construct($server_dir) {
    $this->server_dir = $server_dir;
    $this->msh = sprintf("%s/minecraft.sh", $this->server_dir);
    $this->map_name_file = sprintf("%s/server/rfwadmin_map_full_name", $this->server_dir);

    $this->properties = $this->get_properties();

    if (getenv("UI_HTML_TITLE")!=FALSE) {
      $this->html_title = trim(getenv("UI_HTML_TITLE"),'"');
    }

    if (getenv("UI_ARMORY_ENABLED")=="1" || getenv("UI_ARMORY_ENABLED")=='"1"') {
      $this->armory_enabled = true; //auto-download rfw maps from AuthorBlues autoref. Default false.
    }

    if (getenv("UI_PHP_TIMEZONE")!=FALSE) {
      date_default_timezone_set(trim(getenv("UI_PHP_TIMEZONE"),'"')); //various times displayed in files. Default `date +"%Z"`
    }
  }

  function cmd(Array $args) {
    $cmd = $this->msh;
    foreach ($args as $arg) {
      $cmd .= " " . escapeshellarg($arg);
    }
    $cmd .= " 2>&1";
    return $cmd;
  }

  public function get_status() {
    $cmd = $this->cmd(Array("status"));
    $output = Array();
    exec($cmd, $output, $res);
    $html = ansi_shell_to_html::cmdline_to_html(implode("", $output));
    $html = preg_replace('/(\<span style="[^"]+"\>(?:online|offline)\<\/span\>)/', '<b>\1</b>', $html);
    return $html;
  }

  public function get_users_html() {
    $users = $this->get_connected_users();
    $html = "";
    if ($users !== null) {
      foreach ($users as $user) {
	if ($html !== "") {
	  $html .= ", ";
	}
	$html .= sprintf("<b>%s</b>", e($user));
      }
    } else {
      $html = "(list failed)";
    }

    if ($html === "") {
      $html = "(none)";
    }

    return $html;
  }

  public function get_difficulty_html() {
    return strtolower($this->properties->get_one("difficulty"));
  }

  public function get_server_html() {
    $serverjar = $this->get_serverjar();
    $path = $serverjar->get_installed_path();
    $jar = preg_replace('/^.*\/([^\/]+)$/', '\1', $path);
    $jar = "<b>" . htmlentities($jar) . "</b>";

    $plugins = $this->get_plugins();
    $ps = $plugins->get_all();
    $list = Array();
    foreach ($ps as $p) {
      if ($v = $p->get_activated_version()) {
	$list[] = "<b>".htmlentities($p->name) . "</b>-" . htmlentities($p->get_activated_version());
      }
    }

    if ($list === Array()) {
      $jar .= " with no plugins";
    } else {
      $jar .= " with plugins: ";
      $first = true;
      foreach ($list as $jar_text) {
	if (!$first) {
	  $jar .= ", ";
	}
	$first = false;
	$jar .= $jar_text;
      }
    }

    return $jar;
  }

  public function get_connected_users() {
    $cmd = $this->cmd(Array("list"));
    $output = Array();
    exec($cmd, $output, $res);
    $users = null;
    if ($res === 0) {
      assert(sizeof($output) === 1);
      $users_string = trim($output[0]);
      if ($users_string === "") {
	$users = Array();
      } else {
	$users = explode(" ", $users_string);
	foreach ($users as $i => $user) {
	  $users[$i] = trim($user);
	}
      }
    }

    return $users;
  }

  public function get_current_map($as_text) {
    $map_name = null;
    if (file_exists($this->map_name_file)) {
      $map_name = file_get_contents($this->map_name_file);
    }

    if ($as_text) {
      if ($map_name === null) {
	$text = sprintf("(Randomly generated map with seed '%s')", $this->properties->get_one("level-seed"));
      } else {
	$text = $map_name;
      }
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
    $stream = popen($cmd, "r");
    $dummy_array = null;
    $time_of_last_output = time();

    /* The documentation for fread lines (PHP bug #51056), it will not
     * return for each packet, but is blocking. So we need to
     * explicitly set it non-blocking
     */
    stream_set_blocking($stream, 0);
    while (!feof($stream)) {
      /* stream_select will return when $stream contains output
       * (unlike what the PHP manual currently say, "when the stream
       * changes status") */
      $stream_array = Array($stream);
      $res = stream_select($stream_array, $dummy_array, $dummy_array, 30);
      if ($res === false) {
	//seems to happen sometimes, so do nothing special
      }

      if (time() - $time_of_last_output > 10) {
	$time_of_last_output = time();
	/* Send a nul-character to the browser (which is not
	 * displayed), to discover if the browser is still there.
	 *
	 * PHP will automatically abort when the output stream is
	 * gone.
	 */
	echo chr(0);
	ob_flush();
	flush();
      }

      $read = stream_get_contents($stream);
      if ($read === false) {
	break;
      }
      if ($read === "") {
	continue;
      }
      $time_of_last_output = time();
      echo $read;
      ob_flush();
      flush();
    }

    $retval = pclose($stream);
    if ($retval === -1) {
      echo "error in pclose";
    }
    return $retval;
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

  public function new_blank($new_seed = null) {
    if ($this->is_online()) {
      $cmd = $this->cmd(Array("stop_nosave"));
      $retval = $this->my_passthru($cmd);
      if ($retval !== 0) {
	exit($retval);
      }
    }

    //Delete old map state
    $cmd = $this->cmd(Array("delete_map"));
    $this->my_passthru($cmd);

    //Set map seed
    if ($new_seed === null) {
      $new_seed = stdlib::get_random_string(10);
    }
    $this->properties->set_one("level-seed", $new_seed);
    $this->properties->save_to_file(false);

    //Start server
    $this->start();
  }

  public function reload() {
    echo "Sending reload command to server... ";
    ob_flush();
    flush();
    $cmd = $this->cmd(Array("reload"));
    $retval = $this->my_passthru($cmd);
    if ($retval === 0) {
      echo "successfully reloaded!";
    }
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

    echo htmlspecialchars("Copying to '$target'... ");
    $target_full_path = minecraft_map::validate($target);
    mkdir($target_full_path);

    $open_server_dir = opendir($this->server_dir . "/server");
    while ($entryName = readdir($open_server_dir)) {
      if (!in_array($entryName, Array(".", ".."))) {
	$testdir = $this->server_dir . "/server/" . $entryName;
	if (minecraft_map::is_map_dir($testdir)) {
	  $cmd = sprintf("cp -rp %s %s",
			 escapeshellarg($testdir),
			 escapeshellarg($target_full_path)
			 );
	  $this->my_passthru($cmd);
	}
      }
    }
    closedir($open_server_dir);

    $cmd = sprintf("rm -fv %s/rfwadmin_map_*",
		   escapeshellarg($target_full_path)
		   );
    $this->my_passthru($cmd);

    //Save level seed
    file_put_contents($target_full_path . "/rfwadmin_map_level-seed",
		      $this->properties->get_one("level-seed"))
      !== false || exit(1);

    echo "Copied!";

    if ($do_stop) {
      $this->start();
    }
  }

  public function change_map($map) {
    minecraft_map::validate($map);
    $cmd = $this->cmd(Array("changemap", $map));
    $this->my_passthru($cmd);
  }

  public function delete_map($map_name) {
    $full_path = minecraft_map::validate($map_name);
    $map = new minecraft_map($map_name);
    $map->delete(true);
  }

  public function get_server_log_path() {
    $cmd = $this->cmd(Array("logfile_path"));
    $output = Array();
    exec($cmd, $output, $res);
    $path = $output[0];
    return $path;
  }

  public function get_tmux_log_path() {
    $path = sprintf("%s/tmux.log", $this->server_dir);
    return $path;
  }

  public function stream_tmux_log() {
    $path = $this->get_tmux_log_path();

    //If bigger than 2MB, then truncate to empty file
    if (file_exists($path) && filesize($path) > 2000000) {
      //overwrite, so people already having it open will keep the old file for now
      $f = tempnam("/tmp", "rfwadmin_log_truncate");
      file_put_contents($f, "Truncating log\n");
      rename($f, $path);
    }

    $this->stream_log($path, false);;
  }

  public function stream_server_log() {
    $path = $this->get_server_log_path();
    $do_time_limit = !preg_match('/logs\\/latest.log$/', $path);
    $this->stream_log($path, $do_time_limit);
  }

  public function stream_log($path, $do_time_limit) {
    /* Since this is a persistent connection it can in principle keep
     * working forever. So raise the time limit a bit. Note that
     * waiting on read in my_passthru doesn't count towards the time
     * limit, and I haven't actually seen it run out of time yet. */
    if (ini_get("max_execution_time") < 300) {
      ini_set("max_execution_time", 300);
    }

    $lines = file($path);

    //Per default we start with the last 1000 lines
    $num_lines_skipped = max(0, sizeof($lines)-999);

    if ($do_time_limit) {
      //Skip lines older than 24 hours
      for ($i=1000; $i>0; $i--) {
	$line = $lines[sizeof($lines) - $i];
	if (!preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d) /", $line, $matches)) {
	  echo "Failed to extract datetime from log line - this shouldn't be possible!\n";
	  break;
	} else {
	  $timestamp = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
	  if (time() - $timestamp > 24*60*60) {
	    $num_lines_skipped++;
	  }
	}
      }
    }

    if (sizeof($lines) - $num_lines_skipped > 1000) {
      echo "error: " . $num_lines_skipped;
      $num_lines_skipped = sizeof($lines) - 1000;
    }

    $cmd = sprintf("tail -f -n +%d %s",
		   $num_lines_skipped,
		   escapeshellarg($path)
		   );
    $this->my_passthru($cmd);
  }

  public function get_properties_path() {
    $path = $this->server_dir . "/server/server.properties";
    if (!file_exists($path)) {
      echo sprintf("server.properties file %s not found!", $path);
      exit(1);
    }
    return $path;
  }

  public function get_properties() {
    $path = $this->get_properties_path();
    $properties = new properties($path);
    return $properties;
  }

  public function save_properties() {
    $path = $this->get_properties_path();
    $properties = new properties($path);
    $properties->save();
  }

  public $plugins = null;
  public function get_plugins() {
    if ($this->plugins === null) {
      $this->plugins = new plugins($this);
    }

    return $this->plugins;
  }

  public function submit_commandline($commandline) {
    $cmd = $this->cmd(Array("send_command", $commandline));
    echo $cmd;
    $this->my_passthru($cmd);
  }

  public $serverjar = null;
  public function get_serverjar() {
    if ($this->serverjar === null) {
      $this->serverjar = new serverjar($this);
    }

    return $this->serverjar;
  }

  public function sync_armory_now() {
    $file = minecraft_map::get_sync_file();
    $sync = true;
    if (file_exists($file)) {
      $last_synced = file_get_contents($file); // a timestamp
      if (!is_numeric($last_synced)) {
	unlink($file);
      } else {
	$last_synced = (int) $last_synced;
	if ($last_synced > time()) {
	  unlink($last_synced);
	} else if (time() - $last_synced < 60*60) {
	  $sync = false;
	}
      }
    }

    return $sync;
  }
}

?>
