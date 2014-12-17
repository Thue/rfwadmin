function sync_armory() {
    this.span = $("#armory_span")[0];
    this.div = $("#armory_header")[0];

    tthis = this;

    this.span_empty = true;

    this.lines_handled = 0;
    this.changed = false;
    this.xmlhttp = new XMLHttpRequest();
    this.xmlhttp.onreadystatechange = function() {tthis.onreadystatechange();};
    this.xmlhttp.open("POST","index.php?page=action_ajax", true);
    this.xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    this.xmlhttp.send("sync_armory=1&input_complete=1");
}

sync_armory.prototype.onreadystatechange = function() {
    if (this.xmlhttp.readyState==4 || this.xmlhttp.readyState==3) {
	var lines = this.xmlhttp.responseText.split("\n");
	if (lines.length > this.lines_handled) {
	    for (var line_no=this.lines_handled; line_no<lines.length-1; line_no++) {
		line = lines[line_no];
		if (line.match(/^new_map: (.*)$/)) {
		    this.changed = true;

		    var json = line.replace(/^new_map: (.*)$/, '$1');
		    var vars = eval('('+json+')');
		    this.new_map(vars["name"], vars["version"], vars["filename"]);
		} else if (line.match(/^map_updated:/)) {
		    this.changed = true;

		    var json = line.replace(/^map_updated: (.*)$/, '$1');
		    var vars = eval('('+json+')');
		    this.map_updated(vars["name"], vars["old_version"], vars["new_version"], vars["old_filename"], vars["new_filename"]);
		} else if (line.match(/^map_deleted:/)) {
		    this.changed = true;

		    var json = line.replace(/^map_deleted: (.*)$/, '$1');
		    var vars = eval('('+json+')');
		    this.map_deleted(vars["old_full_name"], vars["new_full_name"]);
		} else if (line.match(/^error:/)) {
		    this.add_line(line);
		} else if (line.match(/^debug:/)) {
		    //console.log(line);
		} else if (line.match(/^done/)) {
		    this.sync_done();
		} else if (line == "") {
		    //nothing
		} else {
		    console.log("error parsing line: " + line);
		}

		this.lines_handled++;
	    }
	}
    }
    if (this.xmlhttp.readyState==4) {
	$(this.status_p).append("Log terminated with status " +  this.xmlhttp.status);
    }
};

sync_armory.prototype.get_full_name = function(name, version) {
    var full_name = name + " " + version + " (from ARMoRy)";
}

sync_armory.prototype.new_map = function(map, version) {
    var text = "New map '" + map + "' version '" + version + "' now available";

    var full_name = this.get_full_name(map, version);
    this.select_add_map(full_name);

    this.add_line(text);
}

sync_armory.prototype.map_updated = function(map, old_version, new_version, old_filename, new_filename) {
    var text = "Map '" + map + "' updated from version '" + old_version + "' to version '" + new_version + "'";

    var old_full_name = this.get_full_name(map, old_version);
    this.select_remove_map(old_full_name);

    var new_full_name = this.get_full_name(map, new_version);
    this.select_add_map(new_full_name);

    this.add_line(text);
}

sync_armory.prototype.map_deleted = function(old_full_name, new_full_name) {
    this.select_remove_map(old_full_name);

    if (new_full_name === "deleted") {
	var text = "Map '" + old_full_name + "' no longer present in ARMoRy, was deleted";	
    } else {
	var text = "Map '" + old_full_name + "' no longer present in ARMoRy, renamed to '"+new_full_name+"'";
	this.select_add_map(new_full_name);
    }

    this.add_line(text);
}


sync_armory.prototype.sync_done = function() {
    var text = "Done syncing ARMoRy";
    if (!this.changed) {
	text += " (no updates found)";
    }
    this.add_line(text);

    $(this.div).remove();
}

sync_armory.prototype.add_line = function(text) {
    if (!this.span_empty) {
	var br = document.createElement("br");
	this.span.appendChild(br);
    }

    var textnode = document.createTextNode(text);
    this.span.appendChild(textnode);
    this.span_empty = false;
}

sync_armory.prototype.get_select = function() {
    return $("#map")[0];
}

sync_armory.prototype.select_remove_map = function(name) {
    var select = this.get_select();
    for (var i=0; i<select.options.length; i++) {
	if (select.options[i].text === name) {
	    select.remove(i);
	}
    }
}

sync_armory.prototype.select_add_map = function(name) {
    var select = this.get_select();
    var option = document.createElement("option");
    option.text = name;
    option.value = name;
    for (var i=0; i<select.options.length; i++) {
	if (select.options[i].text > name) {
	    select.add(option, select.options[i]);
	    break;
	}
    }
}
