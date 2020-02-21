<?php
require_once FF3L_PLUGIN_DIR . "class/nodeTable.php";

$tableList = new NodeTable();
$selectedAction = '<div class="selected-action">';
if(FF3L::checkRight('edit') || FF3L::checkRight('rename'))
{
 $selectedAction .= '<a href="' . FF3L::getHref("edit") . '" data-min="1" data-max="1" class="button editNode">' . (FF3L::checkRight('edit') ? FF3L::i18n("Edit node") : FF3L::i18n("Rename node")) . '</a>';
}
if(FF3L::checkRight('remove'))
{
 $selectedAction .= '<a href="#" data-min="1" class="button removeNode">' . FF3L::i18n("Remove node") . '</a>';
}
if(!empty(FF3L::$mapUrl))
{
 //$selectedAction .= '<a href="#" data-min="1" data-max="1" class="button map showNodeOnMap">' . FF3L::i18n("View on map") . '</a>';
}
$selectedAction .= '</div>';
?>
<div class="wrap">
 <header>
  <h2><?php echo FF3L::i18n("Overview");?></h2>
  <?php
  if(FF3L::checkRight('create'))
  {
   echo "<a href='" . FF3L::getHref("edit") . "' class='left button button-primary add'>" . FF3L::i18n("Add new node") . "</a>";
  }
  echo "<form method='get' class='searchWrapper'>";
  echo "<input type='hidden' name='page' value='ff3l-overview' />";
  $tableList->search_box(FF3L::i18n('Search'), "main");
  echo "</form>";

  if(self::$modelData["nodeSaved"]) // Knoten wurden gespeichert
  {
   echo "<br class='clear' /><div class='updated overviewMessage'>" . FF3L::i18n("Node saved") . "</div>";
  }

  if(!FF3L::$langPaketAvailable)
  {
   echo "<p class='languageInfo'>" . FF3L::i18n("Language paket not available: %s", get_locale()) . "</p>";
  }
  ?>
 </header>

 <?php
// if(current_user_can('manage_options'))
 {
  $gitStatus = FF3L_git::getStatus();
  if(!empty($gitStatus))
  {
   echo "<div class='gitStatus'><h3>" . FF3L::i18n("Git status") . "</h3><pre>" . $gitStatus . "</pre></div>";
  }
 }

 echo $selectedAction;
 echo "<form method='get' class='tableForm'>";
 echo "<input type='hidden' name='page' value='ff3l-overview' />";
 if(!empty(self::$modelData["orderBy"]))
 {
  echo "<input type='hidden' name='orderby' value='" . self::$modelData["orderBy"] . "' />";
 }
 if(!empty(self::$modelData["order"]))
 {
  echo "<input type='hidden' name='order' value='" . self::$modelData["order"] . "' />";
 }
 $tableList->display();
 echo "</form>";
 echo $selectedAction;
 ?>
</div>
