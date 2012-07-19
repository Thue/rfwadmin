<?php
$include_root = dirname(__FILE__);

function dispatch(minecraft $mc, $page) {
  switch ($page) {
  case "main":
    require_once("main.php");
    break;
  case "action":
    require_once("action.php");    
    break;
  case "action_ajax":
    require_once("action_ajax.php");    
    break;
  case "upload_file":
    require_once("upload_file.php");
    break;
  default:
    die("No recognizable dispatch target given!");
  }
}

?>
