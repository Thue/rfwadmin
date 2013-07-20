<?php

abstract class access_list {
  public $file;
  public $path;

  function __construct() {
    global $mc;
    $this->path = sprintf("%s/server/%s", $mc->server_dir, $this->file);
  }

  public static function get_all() {
    $list = Array();
    $list[] = new access_list_ops();
    $list[] = new access_list_whitelist();
    $list[] = new access_list_bannedplayers();
    $list[] = new access_list_bannedips();
    return $list;
  }

  public static function from_filename($file) {
    $classes = self::get_all();
    foreach ($classes as $class) {
      if ($class->file === $file) {
	return $class;
      }
    }

    die("didn't find file $file");
  }

  public function file_to_name($file) {
    $file = preg_replace('/\\./', "dot", $file);
    $file = preg_replace('/-/', "slash", $file);
    return $file;
  }

  public function text_to_list($text) {
    $list = explode("\n", $text);
    foreach ($list as $key => $value) {
      $list[$key] = $value = rtrim($value);
      if ($value === "") {
	unset($list[$key]);
      }
    }
    natcasesort($list);
    $list = array_values($list);
    return $list;
  }

  public function list_to_text(Array $list) {
    $text = implode($list, "\n");
    return $text;
  }

  public static function save_all_from_post() {
    $classes = self::get_all();
    foreach ($classes as $class) {
      $class->save_from_post();
    }
  }

  abstract public function save_from_post();

  abstract public function get_html();
}

abstract class access_list_simple extends access_list {
  function __construct() {
    parent::__construct();
  }

  public function get_html() {
    $text = file_get_contents($this->path);
    $text = $this->list_to_text($this->text_to_list($text));
    printf('<textarea name="%1$s">%2$s</textarea>'."\n".
	   '<script type="text/javascript">$(\'textarea[name=%1$s]\').autosize();</script>',
	   e($this->file_to_name($this->file)),
	   e($text)
	   );
  }

  public function save_from_post() {
    $name = $this->file_to_name($this->file);
    $new_list = $this->text_to_list($_POST[$name]);
    $old_list = $this->text_to_list(file_get_contents($this->path));

    echo e($this->file) . ": ";
    if ($old_list === $new_list) {
      echo "Nothing changed\n";
    } else if (!$this->validate_list($this->file, $new_list, $error)) {
      echo e($error);
    } else if (file_put_contents($this->path, $this->list_to_text($new_list)) !== false) {
      echo "Updated!\n";
    } else {
      echo "Failed - do you have write permissions?";
    }
  }

  public function validate_list($file, Array $list, &$error) {
    foreach ($list as $item) {
      if ($file !== "banned-ips.txt") {
	if (!preg_match('/^[a-zA-Z0-9_]+$/', $item)) {
	  $error = sprintf("invalid Minecraft username '%s'.", $item);
	  return false;
	}
      } else {
	assert($file === "banned-ips.txt");
	$ipv4_regexp = '/^\d+\.\d+\.\d+\.\d+$/';
	//From http://www.mebsd.com/coding-snipits/php-regex-ipv6-with-preg_match-revisited.html
	$ipv6_regexp = '/^(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:(?!$)|$)|\2))(?4){5}((?4){2}|(25[0-5]|(2[0-4]|1\d|[1-9])?\d)(\.(?7)){3})\z/i';
	if (!preg_match($ipv4_regexp, $item)
	    && !preg_match($ipv6_regexp, $item)) {
	  $error = sprintf("invalid ipv4/ipv6 address '%s'.", $item);
	  return false;
	}
      }
    }
    return true;
  }
}

abstract class access_list_table extends access_list {
  public $header = "# Updated 7/19/13 10:45 AM by Minecraft 1.5.2\n# victim name | ban date | banned by | banned until | reason\n";
  function __construct() {
    parent::__construct();
  }

  public function get_html() {
    $text = file_get_contents($this->path);
    $lines = explode("\n", $text);
    $table_id = e($this->file_to_name($this->file));
    printf('<table id="%s" border>'."\n", $table_id);
    echo "<tr><th>Victim name</th><th>Ban date</th><th>Banned by</th><th>Banned until</th><th>Reason</th><th>Delete row</th></tr>";
    foreach ($lines as $i => $line) {
      $line = trim($line);
      if ($line === "" || $line[0] === "#") {
	continue;
      }

      $fields = explode("|", $line);
      if (sizeof($fields) !== 5) {
	printf("<tr><td colspan=\"6\">Failed to parse line '%s'</td></tr>\n",
	       e($line));
      }

      $victim_name = trim($fields[0]);
      $ban_date = strtotime($fields[1]);
      $banned_by = trim($fields[2]);
      $banned_until = trim(strtolower($fields[3])) === "forever" ? trim($fields[3]) : strtotime($fields[3]);
      $reason = trim($fields[4]);

      printf('<tr>'."\n".
	     '<td><input type="text" name="%1$s[%2$s][victim_name]" value="%3$s" autocomplete="off" /></td>'."\n".
	     '<td><input type="text" name="%1$s[%2$s][ban_date]" value="%4$s" autocomplete="off" /></td>'."\n".
	     '<td><input type="text" name="%1$s[%2$s][banned_by]" value="%5$s" autocomplete="off" /></td>'."\n".
	     '<td><input type="text" name="%1$s[%2$s][banned_until]" value="%6$s" autocomplete="off" /></td>'."\n".
	     '<td><textarea name="%1$s[%2$s][reason]" autocomplete="off">%7$s</textarea></td>'."\n".
	     '<td><input type="submit" onclick="$(this.parentNode.parentNode).remove();return false" value="Delete" /></td>'."\n".
	     '</tr>'."\n",
	     e($table_id),
	     1,
	     e($victim_name),
	     e(date("Y-m-d H:i", $ban_date)),
	     e($banned_by),
	     e(strtolower($banned_until) === "forever" ? $banned_until : date("Y-m-d H:i", $banned_until)),
	     e($reason)
	     );
    }
    echo "</table>\n";
    printf('<p><input type="submit" onclick="add_access_line(\'%s\'); return false" value="Add new line" /></p>',
	   e($this->file_to_name($this->file)));
    printf('<script type="text/javascript">$(\'#%s textarea\').autosize()</script>', $table_id);
  }

  public function save_from_post() {
    $new = $this->header;
    $table_id = $this->file_to_name($this->file);
    if (isset($_POST[$table_id])) {
      foreach ($_POST[$table_id] as $row) {
	$victim_name = trim($row["victim_name"]);
	if (!preg_match('/^[a-zA-Z0-9_\\.\\:]+$/', $victim_name)) {
	  printf("Victim name is invalid: %s\n", e($victim_name));
	  continue;
	}
	$ban_date = strtotime($row["ban_date"]);
	if (!is_int($ban_date)) {
	  printf("Failed to parse ban_date: %s\n", $row["ban_date"]);
	  continue;
	}
	$banned_by = trim($row["banned_by"]);
	if (!preg_match('/^[a-zA-Z0-9_ \\-]+$/', $banned_by)) {
	  printf("banned_by name is invalid: %s\n", e($banned_by));
	  continue;	  
	}
	if (strtolower(trim($row["banned_until"])) === "forever") {
	  $banned_until = "Forever";
	} else {
	  $banned_until = strtotime($row["banned_until"]);
	  if (!is_int($banned_until)) {
	    printf("Failed to parse banned_until: %s\n", $row["banned_until"]);
	    continue;
	  }
	}
	$reason = trim($row["reason"]);
	if (preg_match('/\\|/', $reason)) {
	  printf("Reason can't contain |: %s", e($row["reason"]));
	  continue;
	}

	$new .= sprintf("%s|%s|%s|%s|%s\n",
			$victim_name,
			//2012-10-12 22:48:53 +0200
			date("Y-m-d H:i:s O", $ban_date),
			$banned_by,
			$banned_until === "Forever" ? "Forever" : date("Y-m-d H:i:s O", $banned_until),
			$reason
			);
      }
    }

    if (file_get_contents($this->path) !== $new) {
      file_put_contents($this->path, $new);
      printf("%s updated!\n", $this->file);
    } else {
      printf("%s: nothing changed\n", $this->file);
    }
  }
}

class access_list_ops extends access_list_simple {
  function __construct() {
    $this->file = "ops.txt";
    parent::__construct();
  }
  
}

class access_list_whitelist extends access_list_simple {
  function __construct() {
    $this->file = "white-list.txt";
    parent::__construct();
  }
  
}

class access_list_bannedplayers extends access_list_table {
  function __construct() {
    $this->file = "banned-players.txt";
    parent::__construct();
  }
  
}

class access_list_bannedips extends access_list_table {
  function __construct() {
    $this->file = "banned-ips.txt";
    parent::__construct();
  }
  
}

?>
