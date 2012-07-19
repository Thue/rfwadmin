<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title><?php echo htmlspecialchars($mc->html_title); ?></title>

    <link rel="stylesheet" type="text/css" href="rfwadmin_files/main.css" />
    <script type="text/javascript" src="rfwadmin_files/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="rfwadmin_files/jquery.autosize.js"></script>
    <script type="text/javascript" src="rfwadmin_files/main.js"></script>
  </head>

<body>

<div id="wrapper">
  <ul class="tabs">
    <li><a href="#" class="defaulttab" rel="control">Control</a></li>
    <li><a href="#" rel="configuration">Configuration</a></li>
    <li><a href="#" rel="accesslists">Access lists</a></li>
  </ul>
 
  <div class="tab-content" id="control"><?php require(dirname(__FILE__) . "/main_tab_control.php");?></div>
  <div class="tab-content" id="configuration"><?php require(dirname(__FILE__) . "/main_tab_configuration.php");?></div>
  <div class="tab-content" id="accesslists"><?php require(dirname(__FILE__) . "/main_tab_accesslists.php");?></div>
</div>
</body>
</html>
