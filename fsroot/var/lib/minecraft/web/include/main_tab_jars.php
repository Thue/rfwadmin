<h1>Plugins</h1>

<?php

$plugins = $mc->get_plugins();

$unexpected = Array();
$ps = $plugins->get_all($unexpected);

$fs = "<form method=\"post\" action=\"index.php?page=action\" target=\"_blank\">\n";
$fe = "</form>";

echo "<table>\n";
foreach ($ps as $p) {
  echo "<tr>\n";
  $version_lines = Array();
  $installed_version = $p->get_installed_version();
  foreach ($p->versions as $version => $path) {

    if ($installed_version === null) {
      $version_lines[] = sprintf('<td>%s'.
				 '<input type="submit" name="install_plugin" value="install version %s">'.
				 '<input type="hidden" name="name" value="%s">'.
				 '<input type="hidden" name="version" value="%s">'.
				 '%s</td>'.
				 '<td></td>',
				 $fs,
				 e($version),
				 e($p->name),
				 e($version),
				 $fe
				);      
    } else if ($installed_version === $version) {
      $version_lines[] = sprintf('<td></td><td>%s'.
				 '<input type="submit" name="uninstall_plugin" value="Uninstall version %s">'.
				 '<input type="hidden" name="name" value="%s">'.
				 '<input type="hidden" name="version" value="%s">'.
				 '%s</td>',
				 $fs,
				 e($version),
				 e($p->name),
				 e($version),
				 $fe
				);      
    } else {
      $version_lines[] = '<td colspan="2">(Other version already installed)</td>';
    }

  }

  printf("<tr>\n".
	 "<td rowspan=\"%d\">%s</td>\n".
	 "%s\n".
	 "</tr>\n",
	 sizeof($p->versions),
	 e($p->name),
	 $version_lines[0]
	 );
  for ($i=1; $i<sizeof($version_lines); $i++) {
    $printf("<tr>%s</tr>\n", $version_lines[$i]);
  }
}
echo "</table>\n";

foreach ($unexpected as $path) {
  printf("Unexpected file '%s'<br>\n", e($path));
}

?>