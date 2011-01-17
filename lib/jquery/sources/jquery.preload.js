jQuery.preloadImages = function() {
  for(var i = 0; i<arguments.length; i++) {
    jQuery("<img>").attr("src", arguments[i]);
  }
}

jQuery.preloadContent = function(){
  for(var i = 0; i<arguments.length; i++) {
    jQuery("<div>").html(arguments[i]);
  }
}