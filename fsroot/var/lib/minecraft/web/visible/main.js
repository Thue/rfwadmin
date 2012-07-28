$(document).ready(function() {
    $('.tabs a').click(function() {
	switch_tabs($(this));
    });

    var tab = null;
    var tabid = get_cookie("active_tab");
    if (tabid) {
	tab = $('a[rel="'+tabid+'"]');
    }
    if (!tab) {
	var tab = $('.defaulttab');
    }

    switch_tabs(tab);
    document.random_load_id = Math.random();
});

function switch_tabs(obj) {
    //call tab onhide function - while still visible
    var old_id = $("a.selected").attr("rel");
    var fname = old_id + "_beforeHide";
    if (typeof(window[fname]) == "function") {
	window[fname]();
    }


    $('.tab-content').hide();
    $('.tabs a').removeClass("selected");
    var id = obj.attr("rel");

    $('#'+id).show();
    obj.addClass("selected");

    //call tab onshow function
    var fname = id + "_afterShow";
    if (typeof(window[fname]) == "function") {
	window[fname]();
    }

    set_cookie("active_tab", id, 60*60);
}

function set_cookie(name, value, expire_seconds) {
    var expire_date = new Date();
    expire_date.setTime(expire_date.getTime() + expire_seconds*1000);
    var cookie_string = escape(value) + "; expires="+expire_date.toUTCString();
    document.cookie = name + "=" + cookie_string;
}

function get_cookie(c_name) {
    var i,x,y,ARRcookies = document.cookie.split(";");
    for (i=0; i<ARRcookies.length; i++) {
	x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
	y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
	x=x.replace(/^\s+|\s+$/g,"");
	if (x==c_name) {
	    return unescape(y);
	}
    }
    return null;
}
