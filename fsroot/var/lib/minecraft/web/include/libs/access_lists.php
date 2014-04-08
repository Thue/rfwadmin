<?php

abstract class access_list {
  public $fname;
  public $file;
  public $path;
  public $use_json;

  function __construct() {
    global $mc;
    $path_txt = sprintf("%s/server/%s.txt", $mc->server_dir,
			$this->fname==="whitelist" ? "white-list" : $this->fname
			);
    if (file_exists($path_txt)) {
      /* Running a version of the minecraft server which supports json
       * will automatically delete the .txt file. So if the text file
       * exists, then it is supported (or will be converted to a
       * supported format on the next run).
       */
      $this->file = $this->fname . ".txt";
      $this->path = $path_txt;
      $this->use_json = false;
    } else {
      $path_json = sprintf("%s/server/%s.json", $mc->server_dir, $this->fname);
      $this->file = $this->fname . ".json";
      $this->path = $path_json;
      $this->use_json = true;
    }
    
  }

  public static function get_all() {
    $list = Array();
    $list[] = new access_list_ops();
    $list[] = new access_list_whitelist();
    $list[] = new access_list_bannedplayers();
    $list[] = new access_list_bannedips();
    return $list;
  }

  public static function from_fname($fname) {
    $classes = self::get_all();
    foreach ($classes as $class) {
      if ($class->fname === $fname) {
	return $class;
      }
    }

    die("didn't find file $fname");
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

  public static function name_to_uuid() {
    $postData = array(
		      'name' => 'thuejk',
		      'agent' => 'minecraft',
		      );

    // Setup cURL
    $ch = curl_init('https://api.mojang.com/profiles/page/1');
    curl_setopt_array($ch, array(
				 CURLOPT_POST => true,
				 CURLOPT_RETURNTRANSFER => true,
				 CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
				 CURLOPT_POSTFIELDS => json_encode($postData)
				 ));

    // Send the request
    $response = curl_exec($ch);
    if ($response === false) {
      return null;
    }

    $responseData = json_decode($response, true);
    if (isset($responseData) && $responseData["size"] === 1) {
      $uuid_raw = $responseData["profiles"][0]["id"];
      //example:       8f7b3387-e959-4b3b-84c8-0a27a2991b7d
      $uuid = sprintf("%s-%s-%s-%s-%s",
		      substr($uuid_raw, 0, 8),
		      substr($uuid_raw, 8, 4),
		      substr($uuid_raw, 12, 4),
		      substr($uuid_raw, 16, 4),
		      substr($uuid_raw, 20)
		      );
      return $uuid;
    } else {
      return null;
    }
  }
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
	   e($this->file_to_name($this->fname)),
	   e($text)
	   );
  }

  public function save_from_post() {
    $name = $this->file_to_name($this->fname);
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

abstract class access_list_positive extends access_list {
  function __construct() {
    parent::__construct();
  }

  public $header = "";

  private function parse_json() {
    $text = file_get_contents($this->path);
    return json_decode($text);
  }

  private function parse_old() {
    $text = file_get_contents($this->path);
    $lines = explode("\n", $text);
    $list = Array();
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === "" || $line[0] === "#") {
	continue;
      }
      $list[] = (object) Array("name" => $line,
			       "uuid" => null);
    }
    return $list;
  }

  private function parse() {
    return $this->use_json ? $this->parse_json() : $this->parse_old();
  }

  public function get_html() {
    $table_id = e($this->file_to_name($this->fname));
    $items = $this->parse();
    printf('<table id="%s" border>'."\n", $table_id);
    echo "<tr><th>Name</th>";
    if ($this->use_json) {
      echo "<th>uuid</th>";
    }
      echo "<th>Delete row</th>";
    echo "</tr>";
    foreach ($items as $i => $item) {
      echo "<tr>\n";
      printf('<td><input type="text" name="%1$s[%2$s][name]" value="%3$s" autocomplete="off" /></td>'."\n",
	     e($table_id), $i, e($item->name));
      if ($this->use_json) {
	printf('<td><input type="text" name="%1$s[%2$s][uuid]" value="%3$s" autocomplete="off" class="text-uuid" /></td>'."\n",
	       e($table_id), $i, e($item->uuid));
      }
      echo '<td><input type="submit" onclick="$(this.parentNode.parentNode).remove();return false" value="Delete" /></td>'."\n";

      echo "</tr>\n";
    }
    echo "</table>\n";
    printf('<p><input type="submit" onclick="add_access_line_positive(\'%s\', %s); return false" value="Add new line" /></p>',
	   e($this->file_to_name($this->fname)),
	   $this->use_json ? "true" : "false");
    printf('<script type="text/javascript">$(\'#%s textarea\').autosize()</script>', $table_id);
  }

  public function save_from_post() {
    $table_id = $this->file_to_name($this->fname);
    $items = Array();
    if (isset($_POST[$table_id])) {
      foreach ($_POST[$table_id] as $row) {
	$name = trim($row["name"]);
	if (!preg_match('/^[a-zA-Z0-9_\\.\\:]+$/', $name)) {
	  printf("Name is invalid: %s\n", e($name));
	  continue;
	}
	$uuid = isset($row["uuid"]) ? $row["uuid"] : null;

	if ($uuid === null) {
	  $uuid = self::name_to_uuid($name);
	  if ($uuid === null) {
	    printf("Couldn't look up UUID of player '%s' - Mojang's servers are down, or the player doesn't exist.\n",
		    e($name));
	    continue;
	  }
	}

	$item = new stdClass();
	$item->name = $name;
	$item->uuid = $uuid;
	if ($this->fname === "ops") {
	  $item->level = 4;
	}
	$items[] = $item;
      }
    }

    if ($this->use_json) {
      $new = json_encode($items);
    } else {
      $new = $this->header;
      foreach ($items as $item) {
	$new .= $item->name . "\n";
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


abstract class access_list_ban extends access_list {
  public $header = "# victim name | ban date | banned by | banned until | reason\n";
  function __construct() {
    parent::__construct();
  }

  private function parse_json() {
    $text = file_get_contents($this->path);
    $items = json_decode($text);

    return $items;
  }

  private function parse_old() {
    $text = file_get_contents($this->path);
    $lines = explode("\n", $text);
    $items = Array();
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === "" || $line[0] === "#") {
	continue;
      }

      $fields = explode("|", $line);
      if (sizeof($fields) !== 5) {
	printf("<tr><td colspan=\"6\">Failed to parse line '%s'</td></tr>\n",
	       e($line));
      }


      $item = Array();
      if ($this->fname === "banned-ips") {
	$item["ip"] = trim($fields[0]);
      } else {
	$item["name"] = trim($fields[0]);
      }
      $item["uuid"] = null;
      $item["created"] = trim($fields[1]);
      $item["source"] = trim($fields[2]);
      $item["expires"] = trim($fields[3]);
      $item["reason"] = trim($fields[4]);


      $items[] = (object) $item;
    }
    return $items;
  }

  private function parse() {
    return $this->use_json ? $this->parse_json() : $this->parse_old();
  }

  public function get_html() {
    $table_id = e($this->file_to_name($this->fname));
    $items = $this->parse();
    printf('<table id="%s" border>'."\n", $table_id);
    echo "<tr><th>Name</th>".
      ($this->use_json && $this->fname!=="banned-ips" ? "<th>uuid</th>" : "").
      "<th>Created</th><th>Source</th><th>Expires</th><th>Reason</th><th>Delete row</th></tr>";
    foreach ($items as $i => $item) {
      printf('<tr>'."\n".
	     '<td><input type="text" name="%1$s[%2$s]['.($this->fname === "banned-ips" ? "ip" :"name").']" value="%3$s" autocomplete="off" /></td>'."\n".
	     ($this->use_json && $this->fname!=="banned-ips" ? '<td><input type="text" name="%1$s[%2$s][uuid]" value="%8$s" autocomplete="off" class="text-uuid" /></td>'."\n" : "").	     
	     '<td><input type="text" name="%1$s[%2$s][created]" value="%4$s" autocomplete="off" /></td>'."\n".
	     '<td><input type="text" name="%1$s[%2$s][source]" value="%5$s" autocomplete="off" /></td>'."\n".
	     '<td><input type="text" name="%1$s[%2$s][expires]" value="%6$s" autocomplete="off" /></td>'."\n".
	     '<td><textarea name="%1$s[%2$s][reason]" autocomplete="off">%7$s</textarea></td>'."\n".
	     '<td><input type="submit" onclick="$(this.parentNode.parentNode).remove();return false" value="Delete" /></td>'."\n".
	     '</tr>'."\n",
	     e($table_id),
	     $i,
	     e($this->fname==="banned-ips" ? $item->ip : $item->name),
	     e($item->created),
	     e($item->source),
	     e($item->expires),
	     e($item->reason),
	     e($this->fname==="banned-ips" ? null : $item->uuid)
	     );
    }
    echo "</table>\n";
    printf('<p><input type="submit" onclick="add_access_line_ban(\'%s\', %s); return false" value="Add new line" /></p>',
	   e($this->file_to_name($this->fname)),
	   $this->use_json ? "true" : "false");
    printf('<script type="text/javascript">$(\'#%s textarea\').autosize()</script>', $table_id);
  }

  public function save_from_post() {
    $items = Array();

    $table_id = $this->file_to_name($this->fname);
    if (isset($_POST[$table_id])) {
      foreach ($_POST[$table_id] as $row) {
	if ($this->fname === "banned-ips") {
	  $ip = trim($row["ip"]);
	  $valid = filter_var($ip, FILTER_VALIDATE_IP);
	  if (!$valid) {
	    printf("%s is not a valid ip address.", e($ip));
	    continue;
	  }
	} else {
	  $name = trim($row["name"]);
	  if (!preg_match('/^[a-zA-Z0-9_\\.\\:]+$/', $name)) {
	    printf("Victim name is invalid: %s\n", e($name));
	    continue;
	  }
	}
	$created = trim($row["created"]);
	if (strtolower($created) === "now") {
	  $created = date("Y-m-d H:i");
	}
	$source = trim($row["source"]);
	if (!preg_match('/^[a-zA-Z0-9_ \\-\\(\\)]+$/', $source)) {
	  printf("source name is invalid: %s\n", e($source));
	  continue;	  
	}
	$expires = trim($row["expires"]);
	$reason = trim($row["reason"]);
	if (preg_match('/\\|/', $reason)) {
	  printf("Reason can't contain |: %s", e($row["reason"]));
	  continue;
	}

	$item_array = Array();

	if ($this->fname === "banned-players") {
	  $item_array["name"] = $name;
	  $uuid = self::name_to_uuid($name);
	  if ($uuid === null) {
	    printf("Couldn't look up UUID of player '%s' - Mojang's servers are down, or the player doesn't exist.\n",
		    e($name));
	    continue;
	  }
	  $item_array["uuid"] = $uuid;
	} else {
	  $item_array["ip"] = $ip;
	}

	//Make sure the field sequence is the same as created by minecraft_server.ja
	//                             2012-10-12 22:48:53 +0200
	$item_array["created"] = $created;
	$item_array["source"] = $source;
	$item_array["expires"] = $expires;
	$item_array["reason"] = $reason;

	$items[] = (object) $item_array;
      }
    }

    if ($this->use_json) {
      $new = json_encode($items);
    } else {
      $new = $this->header;
      foreach ($items as $item) {
	$new .= sprintf("%s|%s|%s|%s|%s\n",
			isset($item->ip) ? $item->ip : $item->name,
			$item->created,
			$item->source,
			$item->expires,
			$item->reason
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

class access_list_ops extends access_list_positive {
  function __construct() {
    $this->fname = "ops";
    parent::__construct();
  }
  
}

class access_list_whitelist extends access_list_positive {
  function __construct() {
    $this->fname = "whitelist";
    parent::__construct();
  }
  
}

class access_list_bannedplayers extends access_list_ban {
  function __construct() {
    $this->fname = "banned-players";
    parent::__construct();
  }
  
}

class access_list_bannedips extends access_list_ban {
  function __construct() {
    $this->fname = "banned-ips";
    parent::__construct();
  }
  
}

?>
