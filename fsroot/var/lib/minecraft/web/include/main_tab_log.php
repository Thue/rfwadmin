<div id="log_div" style="overflow:scroll; height:100%">
  <pre id="log_pre">
   (Loading log)
  </pre>
</div>
<p id="log_status"></id>
<script type="text/javascript">
  var log_inited = false;
  var log_pre = $("#log_pre").get(0)
  var log_div = $("#log_div").get(0)
  var log_fetched_length = 0;



  function resize_log() {
    var try_set_height = $(window).height() - $("ul.tabs > li").height()-100;
    log_pre.style.height = try_set_height + "px";
  }
  $(document).ready(resize_log);
  $(window).resize(resize_log);

  var getlog = new XMLHttpRequest();
  getlog.onreadystatechange = function() {
    if (getlog.readyState==4 || getlog.readyState==3) {
      if (!log_inited) {
	log_pre.innerHTML = "";
	log_inited = true;
      }

      //are we scrolled to bottom?
      var elem = $('#log_div');
      var inner = $('#log_pre');
      var at_bottom = Math.abs(inner.offset().top) + elem.height() + elem.offset().top >= log_pre.scrollHeight;

      var num_new = getlog.responseText.length - log_fetched_length;
      var text = document.createTextNode(getlog.responseText.substring(log_fetched_length));
      log_pre.appendChild(text);
      log_fetched_length = getlog.responseText.length

      if (at_bottom) {
	//If I don't do it with the timeout, it doesn't work in Chrome!
	setTimeout("log_div.scrollTop = log_div.scrollHeight", 2);
      }
    }
    if (getlog.readyState==4) {
      $("#log_status").append("Log terminated with status " +  getlog.status);
    }
  }
  getlog.open("POST","index.php?page=action_ajax",true);
  getlog.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  getlog.send("stream_log=1");
</script>