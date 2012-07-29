<?php

$textareas = $mc->get_textareas();
?>

<form action="index.php?page=action" target="_blank" method="post">
  <input type="submit" name="save_textareas" value="save">

<?php $textareas->get_html();?>
</form>
