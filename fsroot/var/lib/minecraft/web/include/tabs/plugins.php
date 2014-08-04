<?php

$plugins = $mc->get_plugins();

$unexpected = Array();
$ps = $plugins->get_all($unexpected);

$fs = "<form method=\"post\" action=\"index.php?page=action\" target=\"_blank\">\n";
$fe = "<input type=\"hidden\" name=\"input_complete\" value=\"1\" /></form>";

echo "<h1>Locally available plugins</h1>";

echo "<table class='plugins'>\n";
$color = "color2";
foreach ($ps as $p) {
  $color = $color === "color2" ? "color1" : "color2";
  echo "<tr class='$color'>\n";
  $version_lines = Array();
  $activated_version = $p->get_activated_version();
  foreach ($p->versions as $version => $path) {
    $version = (string) $version; //May have been implicitly converted to int when used as array key
    $delete = sprintf('<td>%s'.
		      '<input type="hidden" name="name" value="%s">'.
		      '<input type="hidden" name="version" value="%s">'.
		      '<input type="submit" name="delete_plugin" value="Delete" />'.
		      '%s</td>',
		      $fs,
		      e($p->name),
		      e($version),
		      $fe);

    if ($activated_version === null) {
      $version_lines[] = sprintf('<td>%s'.
				 '<input type="submit" name="activate_plugin" value="Enable version %s">'.
				 '<input type="hidden" name="name" value="%s">'.
				 '<input type="hidden" name="version" value="%s">'.
				 '%s</td>'.
				 '<td></td>'.
				 $delete,
				 $fs,
				 e($version),
				 e($p->name),
				 e($version),
				 $fe
				);      
    } else if ($activated_version === $version) {
      $version_lines[] = sprintf('<td></td><td>%s'.
				 '<input type="submit" name="deactivate_plugin" value="Disable version %s">'.
				 '<input type="hidden" name="name" value="%s">'.
				 '<input type="hidden" name="version" value="%s">'.
				 '%s</td>'.
				 '<td><input type="submit" name="delete_plugin" value="Delete" disabled="disabled" /></td>',
				 $fs,
				 e($version),
				 e($p->name),
				 e($version),
				 $fe
				);      
    } else {
      $version_lines[] = sprintf('<td colspan="2">'.
				 '<input type="submit" name="activate_plugin" value="Enable version %s" disabled>'.
				 '(Other version already enabled)</td>'.
				 $delete,
				 e($version));
    }

  }

  printf("<td rowspan=\"%d\">%s</td>\n".
	 "%s\n".
	 "</tr>\n",
	 sizeof($p->versions),
	 e($p->name),
	 $version_lines[0]
	 );
  for ($i=1; $i<sizeof($version_lines); $i++) {
    printf("<tr class='$color'>%s</tr>\n", $version_lines[$i]);
  }
}
echo "</table>\n";

foreach ($unexpected as $path) {
  printf("Unexpected file '%s'<br>\n", e($path));
}

?>

<hr />
<h1>Upload new plugin</h1>

<?php
if (!$mc->allow_plugin_upload) {
  echo '<p>Plugin upload disabled. Set "$mc->allow_plugin_upload=true;", probably in /var/www/rfwadmin/index.php .</p>';
} else {
?>

<form action="index.php?page=upload_plugin" method="post" enctype="multipart/form-data" target="_blank">
  <label for="file">Upload plugin jar:</label>
  <input type="file" name="file" id="file" /><br />
  Plugin name: <input type="text" name="plugin_name" /><br />
  Plugin version: <input type="text" name="plugin_version" /><br />
  <input type="submit" name="upload_plugin" value="Upload" />
  <input type="hidden" name="input_complete" value="1" />
</form>

<?php
}
?>