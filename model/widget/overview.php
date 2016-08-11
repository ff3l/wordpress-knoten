<?php

class OverviewWidgetModel
{

 public function __construct()
 {

 }

 public function checkViewRights()
 {
  return true;
 }

 public function getData()
 {
  $nodes = FF3L_data::getAllNodes();

  $ret = array(
   "totalNodes" => count($nodes),
   "lastChanged" => array()
  );

  uasort($nodes, function($a, $b)
  {
   return $b->lastChangeDate > $a->lastChangeDate;
  });

  $ret["lastChanged"] = array_slice($nodes, 0, 5);
  return $ret;
 }

}
?>