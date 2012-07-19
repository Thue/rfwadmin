<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title><?php echo htmlspecialchars($mc->html_title); ?></title>

    <script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
    <link rel="stylesheet" type="text/css" href="rfwadmin_files/main.css" />
    <script type="text/javascript" src="rfwadmin_files/main.js"></script>
    <script type="text/javascript" src="rfwadmin_files/main.js"></script>

  </head>

<body>

<div id="wrapper">
  <ul class="tabs">
    <li><a href="#" class="defaulttab" rel="control">Control</a></li>
    <li><a href="#" rel="configuration">Configuration</a></li>
  </ul>
 
  <div class="tab-content" id="control"><?php require(dirname(__FILE__) . "/main_tab_control.php");?></div>
  <div class="tab-content" id="configuration"><?php require(dirname(__FILE__) . "/main_tab_configuration.php");?></div>
</div>
</body>
</html>
