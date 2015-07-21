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

  public static function curl_get($url) {
    $ch = curl_init($url);
    $path = tempnam("/tmp", "minecraft_curl_");
    $fp = fopen($path, "w");

    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS) || die("failed to limit protocol");
    self::$progress = 0;
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, Array("stdlib", "progress"));
    curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work

    curl_exec($ch) || die("failed to download '" . $url . "'");
    curl_close($ch);
    fclose($fp);

    return $path;
  }

  public static $progress = 0;
  public static function progress($curl, $download_size, $downloaded, $upload_size, $uploaded) {
    $do_flush = false;
    while ($download_size > 0 && 100*$downloaded/$download_size > self::$progress) {
      self::$progress++;
      echo ".";
      $do_flush = true;

      //infinite loop sanity check
      if (self::$progress > 10000) {
          kassert(false);
          break;
      }
    }

    if ($do_flush) {
      ob_flush();
      flush();
    }
  }


}

?>
