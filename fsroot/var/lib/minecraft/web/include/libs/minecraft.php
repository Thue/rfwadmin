<?php
require_once(dirname(__FILE__) . "/ansi_shell_to_html.php");
require_once(dirname(__FILE__) . "/properties.php");
require_once(dirname(__FILE__) . "/plugins.php");
require_once(dirname(__FILE__) . "/textareas.php");
require_once(dirname(__FILE__) . "/map.php");
require_once(dirname(__FILE__) . "/serverjar.php");

function e($text) {
  return htmlspecialchars($text);
}

class minecraft {
  public $server_dir; //Set in index.php, fx "/var/lib/minecraft/servers/default"
  public $msh; //Path to Minecraft.sh
  public $map_name_file; //Name of the currently loaded map
  public $html_title = "rfwadmin"; //Shown in title of all HTML pages

  function __construct($server_dir) {
    $this->server_dir = $server_dir;
    $this->msh = sprintf("%s/minecraft.sh", $this->server_dir);
    $this->map_name_file = sprintf("%s/server/world/map_name.txt", $this->server_dir);
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
    return ansi_shell_to_html::cmdline_to_html(implode("", $output));
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
    $stream = popen($cmd, "r");
    $dummy_array1 = Array();
    $dummy_array2 = Array();
    $time_of_last_output = time();

    /* The documentation for fread lines (PHP bug #51056), it will not
     * return for each packet, but is blocking. So we need to
     * explicitly set it non-blocking
     */
    stream_set_blocking($stream, 0);
    while (!feof($stream)) {
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

      $read = fread($stream, 10000);
      if ($read === false) {
	break;
      }
      if ($read === "") {
	$stream_array = Array($stream);
	/* There is a race here, if the stream changed between fread
	 * and stream_select. In that case we have to wait for the 1
	 * second timeout, whereafter fread will be called again. So
	 * it is important that the timeout is not much higher than 1
	 * second. */
	if (!stream_select($stream_array, $dummy_array1, $dummy_array2, 1) /*blocking*/) {
	  //seems to happen sometimes, so do nothing special
	}
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
    $world_file = sprintf("%s/server/world",
			  $this->server_dir);
    $target_full_path = minecraft_map::validate($target);
    $cmd = sprintf("cp -rp %s %s",
		   escapeshellarg($world_file),
		   escapeshellarg($target_full_path)
		   );
    $this->my_passthru($cmd);
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

  public function get_server_log_path() {
    $path = sprintf("%s/server/server.log", $this->server_dir);
    return $path;
  }

  public function get_screen_log_path() {
    $path = sprintf("%s/screen.log", $this->server_dir);
    return $path;
  }

  public function stream_screen_log() {
    $path = $this->get_screen_log_path();
    $this->stream_log($path, false);;
  }

  public function stream_server_log() {
    $path = $this->get_server_log_path();
    $this->stream_log($path, true);
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

  public function get_textareas() {
    $path = $this->server_dir . "/server";
    $textareas = new textareas($path);
    return $textareas;
  }

  public function save_textareas() {
    $path = $this->server_dir . "/server";
    $textareas = new textareas($path);
    $textareas->save_from_post();
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
}

?>
