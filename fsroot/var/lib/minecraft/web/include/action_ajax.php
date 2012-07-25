<?php

//Work around incremential xhr bug in Chrome: http://code.google.com/p/chromium/issues/detail?id=2016
header('Content-type: application/octet-stream');
ob_flush();
flush();

if (isset($_POST["save_properties"])) {
  $mc->save_properties();
} else if (isset($_POST["save_textareas"])) {
  $mc->save_textareas();
} else if (isset($_POST["start"])) {
  $mc->start();
} else if (isset($_POST["stop"])) {
  $mc->stop();
} else if (isset($_POST["restart"])) {
  $mc->restart();
} else if (isset($_POST["reload"])) {
  $mc->reload();
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

