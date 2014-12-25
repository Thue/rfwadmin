<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title><?php echo e($mc->html_title); ?></title>

    <link rel="stylesheet" type="text/css" href="rfwadmin_files/main.css" />
    <link rel="shortcut icon" href="rfwadmin_files/favicon.ico">
    <script type="text/javascript" src="rfwadmin_files/jquery-1.10.2.min.js"></script>
    <script type="text/javascript" src="rfwadmin_files/jquery.autosize.js"></script>
    <script type="text/javascript" src="rfwadmin_files/jquery.escape.js"></script>
    <script type="text/javascript" src="rfwadmin_files/main.js"></script>
  </head>

<body style="text-align:center">

<?php if (isset($password_message)) {echo "<p>". e($password_message) . "</p>";};?>

<p style="height:3em;"></p>

<p>
   <!--The ?check_password part is remove via a redirect after checking, since the URL needs to be unique to avoid POST resend warnings upon reloads. -->
   <form method="post" action="index.php?check_password">
   Password: <input style="width:10em;" type="password" name="password" value="">
   <input type="submit" name="login" value="Log in">
   </form>
</p>

<p>
   Passwords can be changed or disabled by editing the source code of <?php echo e($_SERVER["SCRIPT_NAME"]);?> on the server.
</p>

</body>
</html>
