<h1>Locally available server versions</h1>
<p>
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
	 '        <input type="submit" name="activate_serverjar" value="%s" %s>'.
	 '        <input type="hidden" name="jar" value="%s">'.
         '        <input type="hidden" name="input_complete" value="1" />'.
	 '      </form>'.
	 "    </td>\n".
	 "    <td>\n".
	 '      <form method="POST" target="_blank" action="index.php?page=action">'.
	 '        <input type="submit" name="delete_serverjar" value="Delete" %s>'.
	 '        <input type="hidden" name="jar" value="%s">'.
         '        <input type="hidden" name="input_complete" value="1" />'.
	 '      </form>'.
	 '    </td>'.
	 "  </tr>\n",
	 e($jar),
	 $installed ? "Installed" : "Install",
	 $installed ? 'disabled="disabled"' : "",
	 e($jar),
	 $installed ? 'disabled="disabled"' : "",
	 e($jar)
	 );
}
?>
</table>
</p>

<hr>
<h1>Download new server version</h1>

<p>
<form action="index.php?page=action" method="post" target="_blank">
<table>
  <tr>
    <td>Vanilla releases</td>
    <td><select name="serverjar_list_vanilla_release" id="serverjar_list_vanilla_release"></select></td>
    <td><input type="submit" name="download_serverjar[vanilla_release]" value="Download"></td>
  </tr>

  <tr>
    <td>Vanilla snapshots</td>
    <td><select name="serverjar_list_vanilla_snapshot" id="serverjar_list_vanilla_snapshot"></select></td>
    <td><input type="submit" name="download_serverjar[vanilla_snapshot]" value="Download"></td>
  </tr>

  <tr>
    <td>Bukkit recommendeds</td>
    <td><select name="serverjar_list_bukkit_recommended" id="serverjar_list_bukkit_recommended"></select></td>
    <td><input type="submit" name="download_serverjar[bukkit_recommended]" value="Download"></td>
  </tr>

  <tr>
    <td>Bukkit betas</td>
    <td><select name="serverjar_list_bukkit_beta" id="serverjar_list_bukkit_beta"></select></td>
    <td><input type="submit" name="download_serverjar[bukkit_beta]" value="Download"></td>
  </tr>

  <tr>
    <td>Sportbukkit latest build</td>
    <td><select name="serverjar_list_sportbukkit" id="serverjar_list_sportbukkit"></select></td>
    <td><input type="submit" name="download_serverjar[sportbukkit]" value="Download"></td>
  </tr>

</table>
<input type="hidden" name="input_complete" value="1">
</form>
</p>

<!-- Download jar lists from 3rd-party sources -->
<script>
  var types = ["vanilla_release", "vanilla_snapshot", "bukkit_recommended", "bukkit_beta", "sportbukkit"];

  for (var i=0;i<types.length; i++) {
    var type = types[i];
    $.post('index.php?page=action_ajax',
      {'get_serverjars': type,
       'input_complete': 1},
	   function(type_) {
             return function(data) {
	       var versions = eval(data);
	       $.each(versions, function(key, value) {
		   var text = value.id + " (" + value.releaseTime + ")";
		   $("#serverjar_list_"+type_).append('<option value="'+$.escape(value.id)+'">'+$.escape(text)+'</option>');
	       });
	     };
	   }(type),
	 "text");
  }
</script>

<!-- For debugging fetching jar lists.-->
<!--
<form method="post" action="index.php?page=action_ajax" target="_blank">
<input type="submit" name="get_serverjars" value="sportbukkit">
<input type="hidden" name="input_complete" value="1">
</form>
-->
