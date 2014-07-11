<?php

/*** Changable settings start here ***/

error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

$include_base = "/var/lib/minecraft"; //grepped from install.sh
$server_dir = $include_base . "/servers/default";
require_once($include_base . "/web/include/libs/minecraft.php");
$mc = new minecraft($include_base . "/servers/default" /* server dir */);

if (getenv("UI_HTML_TITLE")!=FALSE)
  $mc->html_title = getenv("UI_HTML_TITLE");

if (getenv("UI_ARMORY_ENABLED")=="1")
  $mc->armory_enabled = true; //auto-download rfw maps from AuthorBlues autoref. Default false.

if (getenv("UI_PHP_TIMEZONE")!=FALSE)
  date_default_timezone_set(getenv("UI_PHP_TIMEZONE")); //various times displayed in files. Default `date +"%Z"`

minecraft_map::$map_dir = $include_base . "/maps";
plugins::$plugins_dir = $include_base . "/jars/plugins";

/*** Changable settings end here ***/

require_once($include_base . "/web/include/dispatcher.php");
dispatch($mc, isset($_GET["page"]) ? $_GET["page"] : "main");

?>
