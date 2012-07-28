function log(tab_id, prefix, logfile) {
    this.prefix = prefix;
    this.container = $("#"+prefix+"_container").get(0);

    /* Div which has scroll bars (you can't just put the scroll bars on
     * the pre, because of technical details when adjusting the
     * position of the scroll bars) */
    this.div = document.createElement("div");
    this.container.appendChild(this.div);
    this.div.id = this.prefix + "_div";
    this.div.style.overflow = "scroll";
    this.div.style.height = "100%";

    /* The pre which contains the actual log */
    this.pre = document.createElement("pre");
    this.div.appendChild(this.pre);
    this.pre.id = prefix + "_pre";
    this.pre.appendChild(document.createTextNode("(loading log)"));

    /* A paragraph below this.div which shows a status message if
     * there is an error fetching the log. */
    this.status_p = document.createElement("p");
    this.container.appendChild(this.status_p);
    this.status_p.id = this.prefix + "_status";

    /* Set to true after the first chunk of the log has been received. */
    this.inited = false;
    this.visible = $("a[rel="+tab_id+"].selected").length > 0;
    this.at_bottom_state = true; //scrolled to the bottom
    this.scrollTop = null;

    /* Number of characters already displayed in the log, to know
     * where to continue when receiving more. */
    this.log_fetched_length = 0;

    window[tab_id + "_afterShow"] = function() {tthis.afterShow();};
    window[tab_id + "_beforeHide"] = function() {tthis.beforeHide();};

    var tthis = this;
    $(document).ready(function() {tthis.resize_log();});
    $(window).resize(function() {tthis.resize_log();});

    this.xmlhttp = new XMLHttpRequest();
    this.xmlhttp.onreadystatechange = function() {tthis.onreadystatechange();};
    this.xmlhttp.open("POST","index.php?page=action_ajax", true);
    this.xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    this.xmlhttp.send("stream_log="+logfile);
}

log.prototype.afterShow = function() {
    if (!this.visible) {
	this.visible = true;
	this.resize_log();
	if (this.at_bottom_state) {
	    this.scroll_to_bottom();
	} else if (this.scrollTop !== null){
	    this.div.scrollTop = this.scrollTop;
	}
    }
}

log.prototype.beforeHide = function() {
    this.visible = false;
    this.at_bottom_state = this.at_bottom();
    this.scrollTop = this.div.scrollTop;
}

//are we scrolled to bottom?
log.prototype.at_bottom = function() {
    var elem = $(this.div);
    var inner = $(this.pre);

    var at_bottom = Math.abs(inner.offset().top) + elem.height() + elem.offset().top >= this.pre.scrollHeight;
    return at_bottom;
}

log.prototype.onreadystatechange = function() {
    if (this.xmlhttp.readyState==4 || this.xmlhttp.readyState==3) {
	if (!this.log_inited) {
	    this.pre.innerHTML = "";
	    this.log_inited = true;
	}

	var at_bottom_state = this.at_bottom();

	var num_new = this.xmlhttp.responseText.length - this.log_fetched_length;
	var text = document.createTextNode(this.xmlhttp.responseText.substring(this.log_fetched_length));
	this.pre.appendChild(text);
	this.log_fetched_length = this.xmlhttp.responseText.length

	if (at_bottom_state) {
	    this.scroll_to_bottom();
	}
    }
    if (this.xmlhttp.readyState==4) {
	$(this.status_p).append("Log terminated with status " +  this.xmlhttp.status);
    }
};

log.prototype.scroll_to_bottom = function() {
    this.div.scrollTop = this.div.scrollHeight;
};

log.prototype.resize_log = function() {
    if (this.visible) {
	var try_set_height = $(window).height() - ($(document.body).outerHeight(true) - $(this.pre).height());
	this.pre.style.height = try_set_height + "px";
    }
};
