<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>
      <?php echo htmlspecialchars($mc->html_title) . " - Uploading map";?>
    </title>
    <script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
  </head>

  <body>
    <pre>
<?php

function reload_main($map) {
  ?>
  <script type="text/javascript">

    var old_random = self.opener.document.random_load_id;
    self.opener.location.reload();
    function try_set_map(map, time_left) {
      if (self.opener.document.random_load_id !== undefined
	  && self.opener.document.random_load_id !== old_random) {
	self.opener.$("#map").val(map);
      } else if (time_left > 0) {
	setTimeout(function() {try_set_map(map, time_left-100)}, 100);	
      }
    }
    setTimeout(function() {try_set_map(<?echo json_encode($map); ?>, 10000)}, 200);
  </script>
  <?php
}

if (isset($_POST["upload_file"])) {
  $file = $_FILES["file"];
  if ($file["error"] !== 0) {
    echo "Upload failed!\n";
    if ($file["error"] === 1) {
      die("upload_max_filesize in php.ini is too small (php.init probably located at /etc/php5/apache2/php.ini in the filesystem)");
    } else {
      die("error " . $file["error"] . " ( http://www.php.net/manual/en/features.file-upload.errors.php )\n");
    }
  }

  $tmp = minecraft_map::unpack_file($file["tmp_name"], true);
  minecraft_map::install_map($tmp, $file["name"], true);
} else if (isset($_POST["upload_link"])) {
  minecraft_map::fetch_and_install($_POST["link"], true);
} else {
  echo "unknown command!";
}


?>

</pre>

</body>

</html>