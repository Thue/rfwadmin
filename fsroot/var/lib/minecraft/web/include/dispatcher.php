<?php
$include_root = dirname(__FILE__);
header('X-Frame-Options: DENY');

function dispatch(minecraft $mc, $page) {
  //POST same origin check
  if ($_POST !== Array()) {
    $regexp = sprintf('/https?:\\/\\/%s\\/[^@]*/', preg_quote($_SERVER["HTTP_HOST"]));
    if (!preg_match($regexp, $_SERVER['HTTP_REFERER'])) {
      echo "Bad referer! Cross-site request forgery?";
      exit(1);
    }
  }

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
