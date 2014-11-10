<?php
require_once("include.php");

switch($_GET["log"]) {
case "tmux.log":
  $file = sprintf("%s/tmux.log", $mc->server_dir);
  break;
case "server.log":
  $file = sprintf("%s/server/server.log", $mc->server_dir);
  break;
default:
  echo "unknown log file ".htmlspecialchars($_GET["log"]);
  die(1);
}

$cmd = sprintf("tail -f -n 100 %s", escapeshellarg($file));
echo "<pre>";
$handle =  popen($cmd, "r");
while (($output = fread($handle, 10)) !== false) {
  echo htmlspecialchars($output);
  if ($output === "") {
    flush();
    sleep(1);
  }
}

?>