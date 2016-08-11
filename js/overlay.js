(function ($) {
 window.overlay = {};

 window.overlay.Confirm = function (param) {
  var overlay = buildOverlay();

  if (param.overlayClass) {
   $("section#overlay").addClass(param.overlayClass);
  }

  overlay.html("<div id='confirm'>\
                 <h2>" + (param.headline || param.text) + "</h2>\
                 <div>\
                  <a href='#' class='confirmYes button button-primary'>" + (param.confirmText || "") + "</a>\
                  <a href='#' class='confirmNo button'>" + (param.cancelText || "") + "</a>\
                 <div>\
                </div>");

  if (param.text && param.headline) {
   $("section#overlay > div > h2").after("<p>" + param.text + "</p>");
  }
  $("section#overlay a.confirmYes").data("callback", param.callback);
 };

 window.overlay.Alert = function (param) {
  var overlay = buildOverlay();

  overlay.html("<div id='alert'>\
                 <h2>" + (param.headline || param.text) + "</h2>\
                 <div>\
                  <a href='#' class='alertOk button button-primary'>" + (param.okText || "") + "</a>\
                 <div>\
                </div>");

  if (param.text && param.headline) {
   $("section#overlay > div > h2").after("<p>" + param.text + "</p>");
  }
 };

 var buildOverlay = function () {
  $("section#overlay").remove();
  return $("<section id='overlay' />").fadeIn(200).appendTo("body");
 };

 $(function () {
  $("body").on("click", "a.confirmYes", function (e) {
   e.preventDefault();
   var callback = $(this).data("callback");
   if (typeof callback !== "undefined" && $.isFunction(callback)) {
    callback();
   }
   $(this).siblings("a.confirmNo").trigger("click");
  }).on("click", "a.confirmNo, a.alertOk", function (e) {
   e.preventDefault();
   $("section#overlay").fadeOut(200, function () {
    $(this).remove();
   });
  }).on("click", "section#overlay", function () {
   $("a.confirmNo, a.alertOk").first().trigger("click");
  }).on("click", "div#confirm, div#alert", function (e) {
   e.stopPropagation();
  });
 });
}(jQuery));