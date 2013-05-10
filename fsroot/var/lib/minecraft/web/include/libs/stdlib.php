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

}

?>
