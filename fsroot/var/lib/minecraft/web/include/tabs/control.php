<h1>Status</h1>

<p>
Server: <b><?php echo htmlentities($mc->properties->get_one("motd"));?></b>, using port <?php $server_port = $mc->properties->get_one("server-port"); echo htmlentities($mc->properties->get_one("server-port")); if ($server_port === 25565) {echo " (standard)";}?><br>
Status: <?php echo $mc->get_status() . " on <b>" . $mc->get_difficulty_html() . "</b> difficulty" ?><br>
Users online: <?php echo $mc->get_users_html(); ?><br>
Currently loaded map: <b><?php echo htmlspecialchars($mc->get_current_map(true)); ?></b>
<?php if (($loaded = $mc->get_map_age(true)) !== null) {echo htmlspecialchars(" (loaded ".$loaded.")");}?><br>
Server version: <?php echo $mc->get_server_html() ?>
</p>

<form method="post" action="index.php?page=action" target="_blank">
<p>
<input type="submit" name="start" value="Start">
<input type="submit" name="restart" value="Restart">
<input type="submit" name="reload" value="Reload (bukkit)">
<input type="submit" name="stop" value="Stop">
<input type="submit" name="kill" value="Kill">
<input type="submit" name="nuke" value="Nuke (kill -9)">
<input type="submit" name="nuke_and_delete" value="Nuke from orbit (kill -9 and delete map)">
</p>

<p>
  <input type="submit" name="new_blank" value="Discard"> current active map state and start a new blank map with seed <input type="text" name="new_seed" value=""> 
</p>

<p>
  <input type="submit" name="save" value="Save"> current active map state as <input type="text" name="save_as" value=""> 
  <!--<input type="checkbox" name="paranoid_save"> Stop server while saving map, which I assume is more safe.-->
</p>

<hr>
<h1>Maps</h1>

<p>
<select name="map" id="map">
<?php
$maps = minecraft_map::get_map_list();
$current = $mc->get_current_map(false);
foreach ($maps as $map) {
?>
  <option value="<?php echo htmlspecialchars($map->name); ?>" <?php if ($current === $map->name) {?>selected="selected"<?php } ?>>
    <?php echo htmlspecialchars($map->name); ?>
  </option>
<?php
}
?>
</select>
<br><input type="submit" name="change_map" value="Restart server with selected map">
<br><input type="submit" name="rename_map" value="Rename"> selected map to <input type="text" name="rename_to" value="">
<br><input type="submit" name="delete_map" value="Delete"> selected map.
<br><input type="submit" name="download_map" value="Download"> selected map.
<br>
<?php
  if ($mc->armory_enabled) {
    if ($mc->sync_armory_now()) {
?>
<div id="armory_header">(Syncing ARMoRy maps in the background...)</div>
<span id="armory_span"></span>
<script type="text/javascript">$(document).ready(function() {document.my_sync_armory = new sync_armory();});</script>
<?php
    } else {
?>
<div id="armory_header" style="display:none">(Syncing ARMoRy maps in the background...)</div>
<span id="armory_span"></span>
<input type="submit" name="sync_with_armory" value="Fetch updated list of maps from ARMoRy" onclick="$('#armory_header')[0].style.display='';this.style.display='none';document.my_sync_armory = new sync_armory();return false">
<?php
    }
  }
?>

</p>
  <input type="hidden" name="input_complete" value="1" />
</form>

<hr>
<h1>Upload new map</h1>

<form action="index.php?page=upload_file" method="post" enctype="multipart/form-data" target="_blank">
<label for="file">Upload zip file with map:</label>
<input type="file" name="file" id="file" />
<input type="submit" name="upload_file" value="Upload" />
</form>
<br>
<form action="index.php?page=upload_file" method="post" enctype="multipart/form-data" target="_blank">
  Or fetch from a direct link to a zip file: <input type="text" name="link" value=""> <input type="submit" name="upload_link" value="fetch">
  <input type="hidden" name="input_complete" value="1" />
</form>

