$(document).ready(function() {
  $('.tabs a').click(function() {
    switch_tabs($(this));
  });

  switch_tabs($('.defaulttab'));
});

function switch_tabs(obj) {
  $('.tab-content').hide();
  $('.tabs a').removeClass("selected");
  var id = obj.attr("rel");

  $('#'+id).show();
  obj.addClass("selected");
}
