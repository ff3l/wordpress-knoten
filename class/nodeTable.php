<?php
if(is_admin() && !class_exists('WP_List_Table'))
{
 require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class NodeTable extends WP_List_Table
{
 private $order;
 private $orderby;
 private $nodesPerPage;
 private $searchVal;
 private $user;

 public function __construct()
 {
  parent::__construct();

  $this->user = get_current_user_id();
  $screen = get_current_screen();
  $screenOption = $screen->get_option('per_page', 'option');
  $perPage = get_user_meta($this->user, $screenOption, true);

  if(empty($perPage) || $perPage < 1 || $perPage > 999) // Default-Wert verwenden
  {
   $this->nodesPerPage = 20;
  }
  else // Wert aus der Screen-Konfiguration nehmen
  {
   $this->nodesPerPage = $perPage;
  }

  $this->searchVal = filter_input(INPUT_GET, "s", FILTER_SANITIZE_STRING);
  $this->set_order();
  $this->set_orderby();
  $this->prepare_items();
 }

 private function getAllNodes()
 {
  $ret = array();
  $nodes = FF3L_data::getAllNodes();

  foreach($nodes as $node) // Knoten anzeigen, wenn entweder kein Suchbegriff angegeben wurde, oder der Suchbegriff im Namen, E-Mail, Telefon-Nr, oder Schlüssel vorkommt
  {
   if((!isset($this->searchVal) || empty($this->searchVal)) //
    || (strpos($node->name, $this->searchVal) !== false || strpos(strip_tags($node->email), $this->searchVal) !== false || strpos($node->telefon, $this->searchVal) !== false || strpos(strip_tags($node->nodeKey), $this->searchVal) !== false))
   {
    $node->nameRaw = $node->name;
    $node->name = "<span class='nodeName'>{$node->name}</span>";
    $node->actions = "<input type='checkbox' class='itemSelect' />";
    $ret[] = $node;
   }
  }
  return $ret;
 }

 protected function get_table_classes()
 {
  return array('widefat', 'nodes', 'striped', $this->_args['plural']);
 }

 public function set_order()
 {
  $this->order = 'ASC';
  $getVal = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING);
  if(isset($getVal) && !empty($getVal) && in_array($getVal, array('ASC', 'DESC', 'asc', 'desc')))
  {
   $this->order = strtoupper($getVal);
  }
 }

 public function set_orderby()
 {
  $orderby = 'name';
  if(isset($_GET['orderby']) && !empty($_GET['orderby']))
  {
   $orderby = filter_input(INPUT_GET, 'orderby', FILTER_SANITIZE_STRING);
  }
  $this->orderby = $orderby;
 }

 /**
  * @see WP_List_Table::ajax_user_can()
  */
 public function ajax_user_can()
 {
  return FF3L::checkRight("view");
 }

 /**
  * @see WP_List_Table::no_items()
  */
 public function no_items()
 {
  echo FF3L::i18n('No nodes');
 }

 /**
  * @see WP_List_Table::get_columns()
  */
 public function get_columns()
 {
  $ret = array(
   'name' => FF3L::i18n('Name'),
   'email' => FF3L::i18n('Mail'),
   'telefon' => FF3L::i18n('Phone'),
   'nodeKey' => FF3L::i18n('NodeKey'),
   'created' => FF3L::i18n('Created'),
   'lastChange' => FF3L::i18n('Changed')
  );

  if(FF3L::checkRight('edit') || FF3L::checkRight('rename') || FF3L::checkRight('remove'))
  {
   $ret = array_merge(array("actions" => ""), $ret);
  }

  return $ret;
 }

 /**
  * @see WP_List_Table::get_sortable_columns()
  */
 public function get_sortable_columns()
 {
  return array(
   'name' => array('name', false),
   'email' => array('email', false),
   'lastChange' => array('lastChange', true)
  );
 }

 private function sortItems(&$items)
 {
  uasort($items, function($a, $b) // Sortierung anwenden
  {
   if(!isset($a->{$this->orderby})) // Default-Sortierung -> nach Knoten-Name
   {
    $this->orderby = "name";
   }
   elseif($this->orderby === "lastChange") // Datum Hilfe des Timestamps sortieren
   {
    $this->orderby .= "Date";
   }

   $ret = strnatcasecmp($a->{$this->orderby}, $b->{$this->orderby});
   if($this->order === "DESC")
   {
    $ret *= -1;
   }
   return $ret;
  });
 }

 /**
  * Prepare data for display
  * @see WP_List_Table::prepare_items()
  */
 public function prepare_items()
 {
  $this->_column_headers = array(
   $this->get_columns(),
   array(),
   $this->get_sortable_columns()
  );

  $nodes = $this->getAllNodes();
  empty($nodes) && $nodes = array();

  $currentPage = $this->get_pagenum();
  $totalNodes = count($nodes);

  $this->set_pagination_args(array(
   'total_items' => $totalNodes,
   'per_page' => $this->nodesPerPage,
   'total_pages' => ceil($totalNodes / $this->nodesPerPage)
  ));

  $this->sortItems($nodes);
  $firstIndex = min($currentPage * $this->nodesPerPage, $nodes) - $this->nodesPerPage;
  $this->items = array_slice($nodes, $firstIndex, $this->nodesPerPage);

  $this->extendItems($this->items);
 }

 private function extendItems(&$items)
 {
  foreach($items as $i => $item)
  {
   $nodeInfos = FF3L_data::dbGetNodeInfos($item->nameRaw);
   $lockState = FF3L_data::isLocked($item->nameRaw);

   $items[$i]->created = "";
   $items[$i]->createdDate = 0;

   if(!empty($nodeInfos["createdBy"]) && !empty($nodeInfos["createdDate"]) && $nodeInfos["createdDate"] !== "0000-00-00 00:00:00") // Infos über das Erstellungsdatum vorhanden
   {
    $createdBy = get_user_by("id", $nodeInfos["createdBy"]);
    $createdDate = strtotime($nodeInfos["createdDate"]);
    $items[$i]->created = "<a href='mailto:" . $createdBy->user_email . "' class='user'>" . $createdBy->display_name . "</a>" . FF3L::formatTimestamp($createdDate);
    $items[$i]->createdDate = $createdDate;
   }

   if(!empty($nodeInfos["changedBy"]) && !empty($nodeInfos["changedDate"]) && $nodeInfos["changedDate"] !== "0000-00-00 00:00:00") // Änderungsinfos in der Datenbank vorhanden
   {
    $changedDate = strtotime($nodeInfos["changedDate"]);
    if($changedDate > $item->lastChangeDate - 60)
    {
     $changedBy = get_user_by("id", $nodeInfos["changedBy"]);
     $items[$i]->lastChange = "<a href='mailto:" . $changedBy->user_email . "' class='user'>" . $changedBy->display_name . "</a>" . FF3L::formatTimestamp($changedDate);
     $items[$i]->lastChangeDate = $changedDate;
    }
   }

   if($items[$i]->createdDate > $items[$i]->lastChangeDate - 60) // Erstellungsdatum entspricht der letzten Änderung -> "Geändert"-Spalte ohne Inhalt anzeigen
   {
    $items[$i]->lastChange = "";
    $items[$i]->lastChangeDate = 0;
   }

   if(is_array($lockState)) // Knoten wird gerade von einem anderen Benutzer bearbeitet
   {
    $items[$i]->actions = "<span class='locked' data-msg='" . FF3L::i18n('Edited by %1$s. Unlocked at %2$s', array($lockState["user"], $lockState["time"])) . "'></span>";
   }
  }
 }

 public function print_column_headers($with_id = true)
 {
  if(!isset($_GET['orderby'])) // Default-Sortierung -> korrekte Darstellung des Spalten-Headers
  {
   $_GET['orderby'] = "name";
   $_GET['order'] = "asc";
  }
  echo parent::print_column_headers($with_id);
 }

 /**
  * A single column
  */
 public function column_default($item, $columnName)
 {
  return $item->$columnName;
 }

}
