<?php

$plugins = $mc->get_plugins();

$unexpected = Array();
$ps = $plugins->get_all($unexpected);

$fs = "<form method=\"post\" action=\"index.php?page=action\" target=\"_blank\">\n";
$fe = "<input type=\"hidden\" name=\"input_complete\" value=\"1\" /></form>";

echo "<table class='plugins'>\n";
$color = "color2";
foreach ($ps as $p) {
  $color = $color === "color2" ? "color1" : "color2";
  echo "<tr class='$color'>\n";
  $version_lines = Array();
  $installed_version = $p->get_installed_version();
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

    if ($installed_version === null) {
      $version_lines[] = sprintf('<td>%s'.
				 '<input type="submit" name="install_plugin" value="Enable version %s">'.
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
    } else if ($installed_version === $version) {
      $version_lines[] = sprintf('<td></td><td>%s'.
				 '<input type="submit" name="uninstall_plugin" value="Disable version %s">'.
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
				 '<input type="submit" name="install_plugin" value="Enable version %s" disabled>'.
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