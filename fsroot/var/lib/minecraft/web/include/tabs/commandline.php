<div id="screenlog_container"></div>
<script type="text/javascript">
   $(document).ready(function() {document.screenlog = new log("commandline", "screenlog", "screen.log");});
</script>

<form method="post" action="index.php?page=action" target="_blank">
  <input type="text" name="commandline" value="">
  <input type="submit" name="submit_commandline" value="Send">
  <input type="hidden" name="input_complete" value="1" />
</form>