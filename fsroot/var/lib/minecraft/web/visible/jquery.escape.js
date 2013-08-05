(function ($) {
     $.escape = $.prototype.escape = function(str) {
	str = str.replace("&", "&amp;");
	str = str.replace("\"", "&quot;");
	str = str.replace("'", "&#039;");
	str = str.replace("<", "&lt;");
	str = str.replace(">", "&gt;");
	return str;
    };
}(jQuery));
