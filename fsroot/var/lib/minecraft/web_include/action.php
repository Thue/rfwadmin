<?php

if (isset($_POST["download_map"])) {
  $map = new minecraft_map($_POST["map"]);
  $map->download();
  exit();
}

?>

<html>
<head>

</head>

<body>
<pre id="pre">
</pre>

<script type="text/javascript">
<?php
  $qs = "";
  foreach ($_POST as $key => $value) {
    if ($qs !== "") {
      $qs .= "&";
    }
    $qs .= sprintf("%s=%s", urlencode($key), urlencode($value));
}
printf("query_string = '%s';", $qs);

?>

  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    console.log()
    if (xmlhttp.readyState==4 || xmlhttp.readyState==3) {
      var pre = document.getElementById("pre");
      var text = document.createTextNode(xmlhttp.responseText);
      console.log(text);
      pre.innerHTML = "";
      pre.appendChild(text);
      }
  }
  xmlhttp.open("POST","index.php?page=action_ajax",true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send(query_string);
</script>

</body>

</html>