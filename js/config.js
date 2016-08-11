(function ($) {
 $(function () {
  // Responsive Table
  $("table.wp-list-table").cardtable();
  $("table.wp-list-table.small-only").each(function () {
   $(this).find("td.st-key:first").remove();
   $(this).find("td.st-val:first").attr("colspan", "2").addClass("column-role");
  });

  $("table.rights input[type='checkbox']").on("click", function (e) {
   var row = $(this).parents("tr:first");

   if ($(this).parents("table.wp-list-table.small-only:first").length) {
    row = $(this).parents("table.wp-list-table.small-only:first");
   }

   if ($(this).is(":checked")) {
    if (!$(this).hasClass("ff3l_view")) { // egal welches Recht gesetzt wurde -> View-Recht ebenfalls auswählen
     row.find("input.ff3l_view").attr("checked", "checked");
    }

    if ($(this).hasClass("ff3l_edit")) { // Recht Knoten zu bearbeiten -> Umbenennen-Recht ebenfalls auswählen
     row.find("input.ff3l_rename").attr("checked", "checked");
    }
   } else if ($(this).hasClass("ff3l_rename") && row.find("input.ff3l_edit").is(":checked")) { // Umbenennen-Recht kann nur entfernt werden, wenn das Bearbeiten-Recht nicht gesetzt wurde
    e.preventDefault();
   } else if ($(this).hasClass("ff3l_view") && row.find("input[type='checkbox']:checked").length > 0) { // View-Recht kann nur entfernt werden, wenn kein anderes Recht ausgewählt wurde
    e.preventDefault();
   }
  });

  // Werte aus nicht sichtbaren Tabellen nicht mitsenden
  $("form").on("submit", function () {
   $("table.rights:not(:visible) input[name]").each(function () {
    $(this).attr("data-name", $(this).attr("name"));
    $(this).removeAttr("name");
   });
   $("table.rights:visible input[data-name]").each(function () {
    $(this).attr("name", $(this).attr("data-name"));
   });
  });
 });
}(jQuery));