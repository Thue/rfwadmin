<?php

//Work around incremential xhr bug in Chrome: http://code.google.com/p/chromium/issues/detail?id=2016
header('Content-type: application/octet-stream');
ob_flush();
flush();

if (isset($_POST["time_limit"]) && $_POST["time_limit"] < time()) {
  echo "Time for starting action exceeded.";
  exit();
}

if (!isset($_POST["input_complete"])) {
  echo "Input seems to be incomplete. If you are trying to upload a big save file, then try increasing post_max_size in the http server php.ini file (usually /etc/php5/apache2/php.ini).";
  exit();
}

if (isset($_POST["save_properties"])) {
  $mc->save_properties();
} else if (isset($_POST["save_textareas"])) {
  access_list::save_all_from_post();
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
} else if (isset($_POST["new_blank"])) {
  $new_seed = trim($_POST["new_seed"]);
  if ($new_seed === "") {
    $new_seed = null;
  }
  $mc->new_blank($new_seed);
} else if (isset($_POST["change_map"])) {
  $mc->change_map($_POST["map"]);
} else if (isset($_POST["rename_map"])) {
  $map = new minecraft_map($_POST["map"]);
  $map->rename($_POST["rename_to"], true);
} else if (isset($_POST["delete_map"])) {
  $mc->delete_map($_POST["map"]);
} else if (isset($_POST["stream_log"]) && $_POST["stream_log"] === "server.log") {
  $mc->stream_server_log();
} else if (isset($_POST["stream_log"]) && $_POST["stream_log"] === "screen.log") {
  $mc->stream_screen_log();
} else if (isset($_POST["submit_commandline"])) {
  $mc->submit_commandline($_POST["commandline"]);
} else if (isset($_POST["activate_plugin"])) {
  $plugins = $mc->get_plugins();
  $plugins->activate_plugin($_POST["name"], $_POST["version"]);
} else if (isset($_POST["deactivate_plugin"])) {
  $plugins = $mc->get_plugins();
  $plugins->deactivate_plugin($_POST["name"], $_POST["version"]);
} else if (isset($_POST["delete_plugin"])) {
  $plugins = $mc->get_plugins();
  $plugins->delete_plugin($_POST["name"], $_POST["version"]);
} else if (isset($_POST["activate_serverjar"])) {
  $serverjar = $mc->get_serverjar();
  $serverjar->activate($_POST["jar"]);
} else if (isset($_POST["delete_serverjar"])) {
  $serverjar = $mc->get_serverjar();
  $serverjar->delete($_POST["jar"]);
} else if (isset($_POST["sync_armory"])) {
  minecraft_map::armory_sync();
} else if (isset($_POST["get_serverjars"])) {
  $class = serverjar_list::get_type($_POST["get_serverjars"]);
  echo $class->get_json();
} else if (isset($_POST["download_serverjar"])) {
  $type = stdlib::array_first_key($_POST["download_serverjar"]);
  $id = $_POST["serverjar_list_".$type];
  $serverjar = $mc->get_serverjar();
  $serverjar->install($type, $id);
} else {
  echo "unrecognized command";
}

?>

