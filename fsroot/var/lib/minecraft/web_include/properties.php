<?php
class properties {
  public $property_lines = Array();

  public $vars = Array("motd" => "property_string",
		       "difficulty" => "property_select",

		       "gamemode" => "property_select",
		       "level-seed" => "property_string",
		       "level-type" => "property_select",

		       "server-port" => "property_int",
		       "online-mode" => "property_bool",

		       "max-players" => "property_int",
		       "view-distance" => "property_int",
		       "white-list" => "property_bool",

		       "allow-flight" => "property_bool",
		       "pvp" => "property_bool",
		       "generate-structures" => "property_bool",
		       "spawn-animals" => "property_bool",
		       "spawn-monsters" => "property_bool",
		       "spawn-npcs" => "property_bool",
		       );

  function __construct($path) {
    $this->path = $path;

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
      echo "Failed to read server.properties!";
      exit(1);
    }

    foreach ($lines as $line) {
      if (preg_match('/^#.*$/', $line, $matches)) {
	$p = new property_comment($line);
      } else if (preg_match('/^\s*$/', $line, $matches)) {
	$p = new property_emptyline();
      } else if (preg_match('/^\s*([a-zA-Z\\-_0-9]+)\s*=(.*)$/', $line, $matches)) {
	$key = $matches[1];
	$value = $matches[2];
	if (isset($this->vars[$key])) {
	  try {
	    $property_class = $this->vars[$key];
	    $p = new $property_class($key, $value);
	  } catch (exception $e) {
	    $p = new property_parseerror($line, $key);
	  }
	} else {
	  $p = new property_unhandled($line, $key);
	}
      } else {
	$p = new property_parseerror($line);
      }

      $this->property_lines[] = $p;
    }
  }

  public function var_get_property_line($key, &$index=null) {
    $property_line = null;
    foreach ($this->property_lines as $i => $line) {
      if ($line instanceof property_changeable
	  && $line->key === $key) {
	$index = $i;
	$property_line = $line;
	break;
      }
    }

    return $property_line;
  }

  public function to_html() {
    $html = "<table>\n";

    foreach ($this->vars as $key => $dummy) {
      $property_line = $this->var_get_property_line($key);
      if ($property_line instanceof property_changeable) {
	$html2 = sprintf('<tr><td>%s</td><td>%s</td></tr>', e($key), $property_line->get_value_html());
      } else {
	$html2 = sprintf('<tr><td colspan="2">Failed to find and/or parse setting "%s" in server.properties.</td></tr>', e($var));
      }
      $html .= "  " . $html2 . "\n";
    }

    $html .= "</table>";

    return $html;
  }

  public function save() {
    $this->set_from_post();
    $this->save_to_file();
  }

  private function set_from_post() {
    foreach ($this->property_lines as $line) {
      if ($line instanceof property_changeable) {
	$line->set_from_post();
      }
    }
  }

  private function save_to_file() {
    $text = "";
    foreach ($this->property_lines as $line) {
      $text .= $line->get_line() . "\n";
    }

    if (!file_put_contents($this->path, $text)) {
      echo "Failed to save server.properties. The web server user probably doesn't have permission to write that file.";
      exit(1);
    }

    echo sprintf("Settings saved to file %s. Restart the server to make the new settings take effect.", e($this->path));
  }

}

abstract class property_line {
  abstract public function get_line();
}

abstract class property_changeable extends property_line {
  public $key;
  protected $value;

  public function set($value) {
    $this->value = $value;
  }

  abstract public function get_value_html();

  abstract public function set_from_post();
}

class property_bool extends property_changeable {
  function __construct($key, $value) {
    $this->key = $key;
    if ($value !== "true" && $value !== "false") {
      throw new exception(sprintf("Value of '%s' was '%s', must be 'true' or 'false'",
				  e($key),
				  e($value)
				  )
			  );
    }
    $this->value = $value === "true";
  }

  public function get_value_html() {
    $html = sprintf('<input type="hidden" name="%1$s_checkbox_in_post" value="1">'."\n".
		    '<input type="checkbox" name="%1$s"%2$s>',
		    e($this->key),
		    $this->value ? 'checked="checked"' : ''
		    );
    return $html;
  }

  public function get_line() {
    $line = sprintf("%s=%s", $this->key, $this->value ? "true" : "false");
    return $line;
  }

  public function set($value) {
    assert(is_bool($value));
    $this->value = $value;
  }

  public function set_from_post() {
    if (!isset($_POST[$this->key."_checkbox_in_post"])) {
      throw new exception(sprintf("Checkbox for setting '%s' was not found in post!",
				  e($this->key)));
    }
    $this->set(isset($_POST[$this->key]));
  }
}

class property_int extends property_changeable {
  function __construct($key, $value) {
    $this->key = $key;
    if (!preg_match('/\A\s*(\d+)\s*\z/', $value, $matches)) {
      throw new exception(sprintf("Value of '%s' was '%s', doesn't look like an integer",
				  e($key),
				  e($value)
				  )
			  );
    }
    $this->value = (int) $matches[1];
  }

  public function get_value_html() {
    $html = sprintf('<input type="text" name="%s" value="%d">',
		    e($this->key),
		    $this->value
		    );
    return $html;
  }

  public function get_line() {
    $line = sprintf("%s=%d", $this->key, $this->value);
    return $line;
  }

  public function set($value) {
    assert(is_int($value));
    $this->value = $value;
  }

  public function set_from_post() {
    if (!isset($_POST[$this->key])) {
      throw new exception(sprintf("Value for setting '%s' was not found in post!",
				  e($this->key)));
    }
    if (!preg_match('/\\A\s*(\d+)\s*\\z/', $_POST[$this->key], $matches)) {
      throw new exception(sprintf("Value '%s' for setting '%s' doesn't seem to be an integer.",
				  e($_POST[$this->key]),
				  e($this->key)));
    }
    $this->set((int) $matches[1]);
  }
}

class property_string extends property_changeable {
  function __construct($key, $value) {
    $this->key = $key;
    $this->value = rtrim($value);
  }

  public function get_value_html() {
    $html = sprintf('<input type="text" name="%s" value="%s">',
		    e($this->key),
		    e($this->value)
		    );
    return $html;
  }

  public function get_line() {
    $line = sprintf("%s=%s", $this->key, $this->value);
    return $line;
  }

  public function set($value) {
    $this->value = (string) $value;
  }

  public function set_from_post() {
    if (!isset($_POST[$this->key])) {
      throw new exception(sprintf("Checkbox for setting '%s' was not found in post!",
				  e($this->key)));
    }
    if (preg_match('/\\A\\n\\r\\z/u', $_POST[$this->key])) {
      throw new exception(sprintf("Newlines found in value '%s' for setting '%s'.",
				  e($_POST[$this->key]),
				  e($this->key)));
    }


    $this->set(rtrim($_POST[$this->key]));
  }
}

class property_select extends property_changeable {
  function __construct($key, $value) {
    $this->key = $key;
    $map = $this->get_map();
    $rmap = array_flip($map);
    if (!isset($rmap[$value])) {
      throw new exception(sprintf("Unknown value '%s' for select '%s'.",
				  e($value),
				  e($this->key))
			  );
    }
    $this->set($rmap[$value]);
  }

  public $maps = Array("difficulty" => Array("Peaceful" => 0,
					     "Easy" => 1,
					     "Normal" => 2,
					     "Hard" => 3,
					     ),
		       "gamemode" => Array("Survival" => 0,
					   "Creative" => 1,
					   "Adventure" => 2,
					   ),
		       "level-type" => Array("DEFAULT" => "DEFAULT",
					     "FLAT" => "FLAT",
					     "LARGEBIOMES" => "LARGEBIOMES",
					     ),
		       );

  public function get_map() {
    if (!isset($this->maps[$this->key])) {
      throw new exception(sprintf("Unknown select %s.",  $this->key));
    }
    return $this->maps[$this->key];
  }

  public function get_value_html() {
    $options_html = "";
    $map = $this->get_map();
    foreach ($map as $name => $dummy) {
      $options_html .= sprintf('  <option value="%1$s"%2$s>%1$s</option>'."\n",
			       e($name),
			       $this->value === $name ? ' selected="selected"' : '');
    }

    $html = sprintf('<select name="%s">'."\n".'%s</select>'."\n", e($this->key), $options_html);
    return $html;
  }

  public function get_line() {
    $map = $this->get_map();
    $line = sprintf("%s=%s", $this->key, $map[$this->value]);
    return $line;
  }

  public function set($value) {
    $map = $this->get_map();
    if (!isset($map[$value])) {
      throw new exception(sprintf("'%s' is not a valid value for %s.",
				  e($value),
				  e($this->key)
				  )
			  );
    }
    $this->value = $value;
  }

  public function set_from_post() {
    $value = $_POST[$this->key];
    $this->set($value);
  }
}


abstract class property_unchangeable extends property_line {
  public function get_line() {
    return $this->line;
  }
}

class property_comment extends property_unchangeable {
  public $line;
  public function __construct($line) {
    $this->line = $line;
  }
}

class property_emptyline extends property_unchangeable {
  public $line = "";
  function __construct() {
    //nothing
  }
}

class property_parseerror extends property_unchangeable {
  public $line;
  public $key;
  function __construct($line, $key=null) {
    $this->line = $line;
    $this->key = $key;
  }
}

class property_unhandled extends property_unchangeable {
  public $line;
  public $key;
  function __construct($line, $key) {
    $this->line = $line;
    $this->key = $key;
  }  
}

?>
