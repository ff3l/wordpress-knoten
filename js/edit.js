(function ($) {
 $(function () {
  // Fix Menü-Highlighting wenn zwar Recht auf Knoten bearbeiten oder umbenennen, aber nicht auf Erstellen
  $("ul#adminmenu > li#toplevel_page_ff3l-overview").removeClass("wp-not-current-submenu").addClass("wp-has-current-submenu");
  $("ul#adminmenu > li#toplevel_page_ff3l-overview > a:first").removeClass("wp-not-current-submenu").addClass("wp-has-current-submenu");

  if (FF3L.currentPage === "ff3l-edit" && FF3L.currentNode) { // "Knoten anlegen"-Menüpunkt nicht als aktiv markieren, wenn ein Knoten bearbeitet/umbenannt wird
   $("ul#adminmenu > li#toplevel_page_ff3l-overview li.current").removeClass("current");
  }
 });
}(jQuery));