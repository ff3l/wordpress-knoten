(function ($) {
 $(function () {
  if ($("div.ff3l-notice").length) { // eigene Plugin-Aktivierungsmeldung anstelle der Default-Meldung
   $("div#message").remove();
  }

  if (FF3L.currentPage) { // richtigen Help-Tab auswählen
   $("div.contextual-help-tabs > ul > li#tab-link-" + (FF3L.currentPage.replace("ff3l-", "")) + " > a").trigger("click");
  }
 });
}(jQuery));