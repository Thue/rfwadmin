<?php

class textareas {
  public $path;
  public $files = Array("ops.txt", "white-list.txt", "banned-players.txt", "banned-ips.txt");
  function __construct($path) {
    $this->path = $path;
  }

  public function file_to_name($file) {
    $file = preg_replace('/\\./', "dot", $file);
    $file = preg_replace('/-/', "slash", $file);
    return $file;
  }

  public function get_html() {
    foreach ($this->files as $file) {
      $file_path = sprintf("%s/%s", $this->path, $file);
      $name = $this->file_to_name($file);
      $text = file_get_contents($file_path);
      $text = $this->list_to_text($this->text_to_list($text));
      printf('<h1>%1$s</h1>', e($file));
      printf('<textarea name="%2$s">%3$s</textarea>'."\n".
	     '<script type="text/javascript">$(\'textarea[name=%2$s]\').autosize();</script>',
	     e($file),
	     e($name),
	     e($text)
	     );
    }
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

  public function save_from_post() {
    foreach ($this->files as $file) {
      $file_path = sprintf("%s/%s", $this->path, $file);
      $name = $this->file_to_name($file);
      $new_list = $this->text_to_list($_POST[$name]);
      $old_list = $this->text_to_list(file_get_contents($file_path));

      echo e($file) . ": ";
      if ($old_list === $new_list) {
	echo "Nothing changed\n";
      } else if (!$this->validate_list($file, $new_list, $error)) {
	echo e($error);
      } else if (file_put_contents($file_path, $this->list_to_text($new_list)) !== false) {
	echo "Updated!\n";
      } else {
	echo "Failed - do you have write permissions?";
      }
    }
  }

}


?>
