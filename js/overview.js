(function ($) {
 $(function () {

  setTimeout(function () { // Erfolgsmeldung nach 2s ausblenden
   $("div.ff3l-wrapper header > div.updated").addClass("fade");
   setTimeout(function () {
    $("div.ff3l-wrapper header > div.updated").remove();
   }, 500);
   window.history.pushState({}, document.title, location.pathname + location.search.replace(/[?&]nodeSaved=(true|1)/, "")); // nodeSaved-Parameter entfernen
  }, 2000);

  // Responsive Table
  $("table.wp-list-table").cardtable();
  $("table.wp-list-table.small-only").each(function () {
   $(this).find("td.st-key:first").remove();
   $(this).find("td.column-actions").attr("colspan", "2");
   if ($(this).find("span.nodeName").length) {
    $(this).prepend($(this).find("span.nodeName")[0].outerHTML);
   }
   $(this).find("td.column-name").parent("tr").remove();
  });

  // Auswahlboxen
  function updateSelectedAction() {
   var checkedAmount = $("table.wp-list-table input.itemSelect").filter(":checked").length;

   $("div.selected-action > a").removeClass("visible");

   $("div.selected-action > a").each(function () {
    var min = $(this).attr("data-min") || 0;
    var max = $(this).attr("data-max") || 999;

    if (checkedAmount >= min && checkedAmount <= max) {
     $(this).addClass("visible");
    }
   });
  }

  updateSelectedAction();

  $("table.wp-list-table input.itemSelect").on("change", function () {
   updateSelectedAction();
  });

  $("div.selected-action > a").on("click", function (e) {
   e.preventDefault();
   var _self = this;
   var selectedNames = [];
   var selectedRows = [];

   $("table.wp-list-table input.itemSelect").filter(":checked").each(function () {
    var row = $(this).parents("tr:first");
    selectedRows.push(row);
    selectedNames.push(row.find("td.column-name > span.nodeName").text());
   });

   if (selectedNames.length > 0) {
    if ($(_self).hasClass("editNode")) { // Knoten bearbeiten -> Aufruf der Bearbeitungsseite
     var href = $(_self).attr("href");
     window.location.href = href + "&node=" + selectedNames[0];
    } else if ($(_self).hasClass("showNodeOnMap") && FF3L.mapUrl) { // Knoten auf der Karte anzeigen
     var mapUrl = FF3L.mapUrl.replace(/\/$/, "") + "/";
     var nodeId = selectedRows[0].find("span.nodeName").attr("data-id");
     if (nodeId) {
      mapUrl += "#!n:" + nodeId;
     }
     window.open(mapUrl, '_blank');
    } else if ($(_self).hasClass("removeNode")) { // Knoten löschen -> per Ajax
     window.overlay.Confirm({
      overlayClass: "removeNodeOverlay",
      headline: FF3L.lang.removeNode,
      text: selectedNames.length === 1 ? FF3L.lang.removeNodeConfirm : FF3L.lang.removeNodesConfirm,
      confirmText: FF3L.lang.removeNode,
      cancelText: FF3L.lang.cancel,
      callback: function () {
       $.each(selectedRows, function (idx, row) {
        row.addClass("loading");
       });
       $.ajax({
        type: 'POST',
        url: FF3L.ajaxUrl,
        data: {
         action: 'remove_nodes',
         nodes: JSON.stringify(selectedNames)
        }
       }).done(function (msg) {
        if (msg === "success") { // Löschen erfolgreich
         $("div.selected-action > a").removeClass("visible");
         $.each(selectedRows, function (idx, row) {
          row.addClass("removed").fadeOut(500, function () {
           $(this).remove();
          });
         });
        } else { // Fehler beim Löschen
         $.each(selectedRows, function (idx, row) {
          row.removeClass("loading");
         });
         window.overlay.Alert({
          headline: FF3L.lang.error,
          text: msg,
          okText: FF3L.lang.close
         });
        }
       });
      }
     });
    }
   }
  });

  // Suchbegriff in Liste markieren
  var searchVal = (function () {
   var results = new RegExp('[\?&]s=([^&#]*)').exec(window.location.href);
   if (results === null) {
    return null;
   } else {
    return results[1] || 0;
   }
  }());

  if (searchVal) {
   $("table.wp-list-table td").filter(".column-name, .column-email, .column-telefon, .column-nodeKey").each(function () {
    var regex = new RegExp(searchVal.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), "gi");
    $(this).contents().filter(function () {
     return this.nodeType === 3 && regex.test(this.nodeValue);
    }).replaceWith(function () {
     return (this.nodeValue || "").replace(regex, function (match) {
      return "<span class=\"highlight\">" + match + "</span>";
     });
    });
   });
  }
 });
}(jQuery));