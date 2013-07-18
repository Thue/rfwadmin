<?php

if (isset($_POST["download_map"])) {
  $map = new minecraft_map($_POST["map"]);
  $map->download();
  exit();
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>
      <?php echo htmlspecialchars($mc->html_title) . " - Action request";?>
    </title>
  </head>

  <body>
    <pre id="pre">
    </pre>
    <p id="loading_msg">
      <b>Processing...</b>
    </p>

    <script type="text/javascript">
<?php
  $qs = "";
  $args = $_POST;
  $args["time_limit"] = time() + 30;
  foreach ($args as $key => $value) {
    if ($qs !== "") {
      $qs .= "&";
    }
    if (is_array($value)) {
      foreach ($value as $key2 => $value2) {
	$qs .= sprintf("%s[%s]=%s", urlencode($key), urlencode($key2), urlencode($value2));
      }
    } else {
      $qs .= sprintf("%s=%s", urlencode($key), urlencode($value));
    }
  }
  printf("query_string = '%s';", $qs);

?>
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState==4 || xmlhttp.readyState==3) {
      var pre = document.getElementById("pre");
      var text = document.createTextNode(xmlhttp.responseText);
      pre.innerHTML = "";
      pre.appendChild(text);
    }
    if (xmlhttp.readyState==4) {
      var loading_msg = document.getElementById("loading_msg");
      loading_msg.parentNode.removeChild(loading_msg);
      self.opener.location.reload();
    }
  }
  xmlhttp.open("POST","index.php?page=action_ajax",true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send(query_string);
    </script>

  </body>

</html>