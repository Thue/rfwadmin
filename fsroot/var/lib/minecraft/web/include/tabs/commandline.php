<div id="tmuxlog_container"></div>
<script type="text/javascript">
   $(document).ready(function() {document.tmuxlog = new log("commandline", "tmuxlog", "tmux.log");});
</script>

<form method="post" action="index.php?page=action" target="_blank">
  <input type="text" name="commandline" value="">
  <input type="submit" name="submit_commandline" value="Send">
  <input type="hidden" name="input_complete" value="1" />
</form>