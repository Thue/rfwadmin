<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>
      <?php echo htmlspecialchars($mc->html_title) . " - Uploading plugin";?>
    </title>
    <script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
  </head>

  <body>
    <pre>
<?php

function reload_main() {
  ?>
  <script type="text/javascript">
    self.opener.location.reload();
  </script>
  <?php
}

if (!$mc->allow_plugin_upload) {
  die('$mc->allow_plugin_upload is set to false, probably in /var/www/rfwadmin/index.php!');
}

if (!isset($_POST["input_complete"])) {
  echo "Input seems to be incomplete. If you are trying to upload a big save file, then try increasing post_max_size in the http server php.ini file (usually /etc/php5/apache2/php.ini).";
} else if (isset($_POST["upload_plugin"])) {
  $file = $_FILES["file"];
  if ($file["error"] !== 0) {
    echo "Upload failed!\n";
    if ($file["error"] === 1) {
      die("upload_max_filesize in php.ini is too small (php.init probably located at /etc/php5/apache2/php.ini in the filesystem)");
    } else {
      die("error " . $file["error"] . " ( http://www.php.net/manual/en/features.file-upload.errors.php )\n");
    }
  }

  $plugins = $mc->get_plugins();
  if ($plugins->install_plugin($_FILES["file"], $_POST["plugin_name"], $_POST["plugin_version"])) {
    printf("new plugin %s-%s uploaded!", e($_POST["plugin_name"]), e($_POST["plugin_version"]));
    reload_main();
  }
} else {
  echo "unknown command!";
}


?>

</pre>

</body>

</html>