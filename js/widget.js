(function ($) {
 $(function () {

  var scrollbarWidth = (function () {
   var parent = $('<div style="width:50px;height:50px;overflow:auto"><div/></div>').appendTo('body');
   var child = parent.children();
   var width = child.innerWidth() - child.height(99).innerWidth();
   parent.remove();
   return width;
  }());

  if (scrollbarWidth > 0) {
   $("div#ff3l-mapWidget iframe").css("width", "calc(100% + "+scrollbarWidth+"px)");
  }

  $("div#ff3l-overviewWidget table a.toggleDetails").on("click", function (e) {
   e.preventDefault();
   $(this).toggleClass("expanded");
   $(this).next("ul").slideToggle(250);
  });

 });
}(jQuery));