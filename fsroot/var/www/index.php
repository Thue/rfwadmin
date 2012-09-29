<?php

/*** Changable settings start here ***/

error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

$include_base = "/var/lib/minecraft";
$server_dir = $include_base . "/servers/default";
require_once($include_base . "/web/include/libs/minecraft.php");
$mc = new minecraft($include_base . "/servers/default" /* server dir */);
//$mc->html_title = "Custom title here";
//$mc->armory_enabled = true; //auto-download rfw maps from AuthorBlues autoref. Default false.

minecraft_map::$map_dir = $include_base . "/maps";
plugins::$plugins_dir = $include_base . "/jars/plugins";

/*** Changable settings end here ***/

//POST same origin check
if ($_POST !== Array()) {
  $regexp = sprintf('/https?:\\/\\/%s\\/[^@]*/', preg_quote($_SERVER["HTTP_HOST"]));
  if (!preg_match($regexp, $_SERVER['HTTP_REFERER'])) {
    echo "Bad referer! Cross-site request forgery?";
    exit(1);
  }
}

require_once($include_base . "/web/include/dispatcher.php");
dispatch($mc, isset($_GET["page"]) ? $_GET["page"] : "main");

?>
