<table>
<?php
   $serverjar = $mc->get_serverjar();
$jars = $serverjar->get_jars();
$installed_path = $serverjar->get_installed_path();
foreach ($jars as $jar) {
  $installed = realpath($jar) === realpath($installed_path);
  printf("  <tr>\n".
	 "    <td>%s</td>\n".
	 "    <td>".
	 '      <form method="POST" target="_blank" action="index.php?page=action">'.
	 '        <input type="submit" name="install_serverjar" value="%s" %s>'.
	 '        <input type="hidden" name="jar" value="%s">'.
         '        <input type="hidden" name="input_complete" value="1" />'.
	 '      </form>'.
	 "    </td>\n".
	 "  </tr>\n",
	 e($jar),
	 $installed ? "Installed" : "Install",
	 $installed ? 'disabled="disabled"' : "",
	 e($jar)
	 );
}
?>
</table>
