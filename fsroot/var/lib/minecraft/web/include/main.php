<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title><?php echo htmlspecialchars($mc->html_title); ?></title>

    <link rel="stylesheet" type="text/css" href="rfwadmin_files/main.css" />
    <link rel="shortcut icon" href="rfwadmin_files/favicon.ico">
    <script type="text/javascript" src="rfwadmin_files/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="rfwadmin_files/jquery.autosize.js"></script>
    <script type="text/javascript" src="rfwadmin_files/main.js"></script>
    <script type="text/javascript" src="rfwadmin_files/log.js"></script>
<?php if ($mc->armory_enabled) { ?>
    <script type="text/javascript" src="rfwadmin_files/armory.js"></script>
<?php } ?>
  </head>

<body>

<div id="wrapper">
  <ul class="tabs">
    <li><a href="#" class="defaulttab" rel="control">Control</a></li>
    <li><a href="#" rel="configuration">Configuration</a></li>
    <li><a href="#" rel="accesslists">Access lists</a></li>
    <li><a href="#" rel="commandline">Command line</a></li>
    <li><a href="#" rel="log">Server log</a></li>
    <li><a href="#" rel="serverjar">Server version</a></li>
    <li><a href="#" rel="plugins">Bukkit plugins</a></li>
  </ul>
 
  <div class="tab-content" id="control"><?php require(dirname(__FILE__) . "/tabs/control.php");?></div>
  <div class="tab-content" id="configuration"><?php require(dirname(__FILE__) . "/tabs/configuration.php");?></div>
  <div class="tab-content" id="accesslists"><?php require(dirname(__FILE__) . "/tabs/accesslists.php");?></div>
  <div class="tab-content" id="commandline"><?php require(dirname(__FILE__) . "/tabs/commandline.php");?></div>
  <div class="tab-content" id="log"><?php require(dirname(__FILE__) . "/tabs/log.php");?></div>
  <div class="tab-content" id="serverjar"><?php require(dirname(__FILE__) . "/tabs/serverjar.php");?></div>
  <div class="tab-content" id="plugins"><?php require(dirname(__FILE__) . "/tabs/plugins.php");?></div>
</div>
</body>
</html>
