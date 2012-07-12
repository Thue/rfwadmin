<?php
error_reporting(E_ALL | E_STRICT);
require_once("include.php");

if (isset($_POST["download_map"])) {
  $map = new minecraft_map($_POST["map"]);
  $map->download();
  exit();
}

?>

<html>
<head>

</head>

<body>
<pre>
<?php
if (isset($_POST["start"])) {
  $mc->start();
} else if (isset($_POST["stop"])) {
  $mc->stop();
} else if (isset($_POST["restart"])) {
  $mc->restart();
} else if (isset($_POST["kill"])) {
  $mc->kill();
} else if (isset($_POST["nuke"])) {
  $mc->nuke();
} else if (isset($_POST["nuke_and_delete"])) {
  $mc->nuke_and_delete();
} else if (isset($_POST["save"])) {
  $mc->save($_POST["save_as"], isset($_POST["paranoid_save"]));
} else if (isset($_POST["change_map"])) {
  $mc->change_map($_POST["map"]);
} else if (isset($_POST["rename_map"])) {
  $mc->rename_map($_POST["map"], $_POST["rename_to"]);
} else if (isset($_POST["delete_map"])) {
  $mc->delete_map($_POST["map"]);
} else {
  echo "unrecognized command";
}

?>

</pre>

</body>

</html>