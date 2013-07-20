<form action="index.php?page=action" target="_blank" method="post">
  <input type="submit" name="save_textareas" value="save">

  <h1>ops.txt</h1>
<?php access_list::from_filename("ops.txt")->get_html(); ?>

  <h1>white-list.txt</h1>
<?php access_list::from_filename("white-list.txt")->get_html(); ?>

  <h1>banned-players.txt</h1>
<?php access_list::from_filename("banned-players.txt")->get_html(); ?>

  <h1>banned-ips.txt</h1>
<?php access_list::from_filename("banned-ips.txt")->get_html(); ?>

  <input type="hidden" name="input_complete" value="1" />
</form>
