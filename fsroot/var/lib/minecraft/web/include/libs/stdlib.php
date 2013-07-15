<?php

class stdlib {
  public static $PASSWORD_CHARS = "23456789abcdefghkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ";
  public static function get_random_string($len) {
    $clen = strlen(self::$PASSWORD_CHARS);

    $s = "";
    for ($i=0; $i<$len; $i++) {
      $r = mt_rand(0, $clen-1);
      $s .= self::$PASSWORD_CHARS{$r};
    }

    return $s;
  }

  //sizeof array must be > 0                                                                   
  public static function array_first_key($array) {
    if (sizeof($array) == 0){
      kwarn();
      return null;
    }

    $keys = array_keys($array);
    $first_key = $keys[0];

    return $first_key;
  }

}

?>
