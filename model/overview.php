<?php

class OverviewModel
{

 public function __construct()
 {
  FF3L_git::pull();
  FF3L_data::removeLockStateByUser(get_current_user_id());
  FF3L_data::removeExpiredLockStates();
 }

 public function checkViewRights()
 {
  return FF3L::checkRight("view");
 }

 public function getData()
 {
  return array(
   "nodeSaved" => isset($_GET["nodeSaved"]),
   "orderBy" => filter_input(INPUT_GET, "orderby", FILTER_SANITIZE_STRING),
   "order" => filter_input(INPUT_GET, "order", FILTER_SANITIZE_STRING),
  );
 }

}
?>
