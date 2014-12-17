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

  //If password access check enable, check if the user is logged in,
  //and if not show a login form. NOTE: this function may exit()
  password_check($mc);

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
  case "upload_plugin":
    require_once("upload_plugin.php");
    break;
  default:
    die("No recognizable dispatch target given!");
  }
}

function password_check(minecraft $mc) {
  //Password access check
  global $passwords; //Array of valid passwords, from index.php
  global $password_message; //written here, read by the password.php include()'d page
  if (!isset($passwords)) {
    //No password access control! Show full web interface
    return;
  }

  session_start() || exit("Failed to start session. Perhaps cookies are disabled in your browser?");

  if (isset($_SESSION["logged_in"])) {
    if (isset($_POST["logout"])) {
      //We come from the logout page
      session_destroy(); //log out
      session_start();
      $password_message = "You are now logged out.";
      goto show_password_dialog;
    } else {
      $password_found = false;
      foreach ($passwords as $password) {
	if ($password === $_SESSION["used_password"]) {
	  $password_found = true;
	  break;
	}
      }

      if ($password_found) {
	//Password is still valid!
	goto show_main_interface;
      } else {
	$password_message = "The password you used to log in is no longer valid.";
	session_destroy(); //log out
	session_start();
	goto show_password_dialog;	
      }
    }
  }

  if (isset($_POST["password"])) {
    //The user come from the login page, posted password
    $login_succeeded = false;
    foreach ($passwords as $password) {
      if ($password === trim($_POST["password"])) {
	$_SESSION["logged_in"] = true;
	$_SESSION["used_password"] = $password;
	$login_succeeded = true;;
	break;
      }
    }

    if ($login_succeeded) {
      goto show_main_interface;
    } else {
      $password_message = "Login failed. Bad password?";
      goto show_password_dialog;
    } 
  } else {
    //The user is visiting for the first time, show 'input password' page
    goto show_password_dialog;
  }

  trigger_error("This code place should be unreachable", E_USER_WARN);

show_password_dialog:
  session_write_close();
  require_once("password.php");
  exit();

show_main_interface:
  session_write_close();
  return;
}

?>
