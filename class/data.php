<?php

class FF3L_data
{
 private static $init = false;
 private static $lockTimeout = 1800;
 private static $tableName = "";
 private static $dbHandle = null;

 public static function init()
 {
  if(self::$init === false)
  {
   global $wpdb;
   self::$init = true;
   self::$dbHandle = $wpdb;
   self::$tableName = $wpdb->prefix . "node_info";
  }
 }

 public static function getMysqlDate()
 {
  return current_time("Y-m-d H:i:s");
 }

 public static function createNodeTable()
 {
  $sql = "CREATE TABLE " . self::$tableName . " (
           ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
           name varchar(255) NOT NULL UNIQUE,
           createdBy bigint(20) unsigned NOT NULL,
           createdDate datetime NOT NULL,
           changedBy bigint(20) unsigned DEFAULT NULL,
           changedDate datetime DEFAULT NULL,
           lockedBy bigint(20) unsigned DEFAULT NULL,
           lockedDate datetime DEFAULT NULL,
           PRIMARY KEY (ID)
          ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
  require_once ABSPATH . "wp-admin/includes/upgrade.php";
  dbDelta($sql);
 }

 public static function removeNodeTable()
 {
  try // Try-Catch falls Datenbank-User keine Tabellen löschen darf
  {
   self::$dbHandle->query("DROP TABLE IF EXISTS " . self::$tableName);
  }
  catch(Exception $e)
  {
   //print_r($e);
  }
 }

 public static function dbRemoveNode($node)
 {
  self::$dbHandle->delete(self::$tableName, array("name" => $node));
 }

 public static function removeExpiredLockStates()
 {
  $query = self::$dbHandle->prepare("UPDATE " . self::$tableName . " SET lockedBy = NULL, lockedDate = NULL WHERE DATE_ADD(lockedDate, INTERVAL %d SECOND) < NOW()", self::$lockTimeout);
  self::$dbHandle->query($query, ARRAY_A);
 }

 public static function removeLockStateByUser($user)
 {
  $query = self::$dbHandle->prepare("UPDATE " . self::$tableName . " SET lockedBy = NULL, lockedDate = NULL WHERE lockedBy = %d", $user);
  self::$dbHandle->query($query, ARRAY_A);
 }

 public static function dbGetNodeInfos($name)
 {
  $query = self::$dbHandle->prepare("SELECT * FROM " . self::$tableName . " WHERE name = %s", $name);
  return self::$dbHandle->get_row($query, ARRAY_A);
 }

 public static function dbUpdateNode($name, $values)
 {
  $existingName = null;

  // den bisherigen Knotennamen ermitteln
  if(is_string($name)) // nur ein Namen übergeben -> Wert setzen
  {
   $existingName = $name;
  }
  elseif(is_array($name) && !empty($name["oldName"])) // Name als Array übergeben -> oldName-Eintrag nehmen
  {
   $existingName = $name["oldName"];
  }
  elseif(is_array($name) && !empty($name["name"])) // Name als Array übergeben aber kein oldName-Eintrag vorhanden -> name-Eintrag nehmen
  {
   $existingName = $name["name"];
  }

  if(!empty($existingName))
  {
   $result = self::dbGetNodeInfos($existingName);

   if(!empty($result))
   {
    $values = array_merge($result, $values);
   }

   $nodeName = null;
   if(is_string($name))
   {
    $nodeName = $name;
   }
   elseif(is_array($name) && !empty($name["name"]))
   {
    $nodeName = $name["name"];
   }

   if(!empty($nodeName))
   {
    $values["name"] = $nodeName;
    self::$dbHandle->replace(self::$tableName, $values);

    if($nodeName !== $existingName)
    {
     self::$dbHandle->delete(self::$tableName, array("name" => $existingName));
    }
    return true;
   }
  }
  return false;
 }

 /**
  * Gibt alle vorhandenen Knoten aus dem Git-Repository zurück
  *
  * @return array
  */
 public static function getAllNodes()
 {
  $ret = array();
  if($handle = opendir(FF3L::$gitRepository))
  {
   while(false !== ($entry = readdir($handle)))
   {
    if(strpos($entry, ".") !== 0)
    {
     $lastChange = filemtime(FF3L::$gitRepository . $entry) + (get_option("gmt_offset") * 3600); // letzte Änderung an der Datei
     $fileData = self::getNodeDataFromFile($entry);

     $obj = new stdClass();
     $obj->name = $entry;
     $obj->nodeKey = strlen($fileData["nodeKey"]) === 64 ? $fileData["nodeKey"] : "<span class='error'>" . FF3L::i18n("Invalid key") . "</span>";
     //$obj->nodeId = "14cc2071544e"; // @toDo ID ermitteln
     $obj->email = empty($fileData["email"]) ? "" : "<a href='mailto:{$fileData["email"]}'>{$fileData["email"]}</a>";
     $obj->telefon = $fileData["telefon"];
     $obj->lastChange = FF3L::formatTimestamp($lastChange);
     $obj->lastChangeDate = $lastChange;
     $ret[] = $obj;
    }
   }
   closedir($handle);
  }
  return $ret;
 }

 /**
  * Holt sich den Schlüssel des übergebenen Knoten aus dem Git-Repository
  *
  * @param string $node
  * @return string|null
  */
 public static function getNodeDataFromFile($node)
 {
  $ret = array(
   "nodeKey" => null,
   "email" => null,
   "telefon" => null
  );

  $content = file_get_contents(FF3L::$gitRepository . $node);

  $keyMatch = array();
  preg_match("/key\s\"([a-f\d]{64})\"/i", $content, $keyMatch);
  if(!empty($keyMatch) && isset($keyMatch[1]))
  {
   $ret["nodeKey"] = $keyMatch[1];
  }

  $mailMatch = array();
  preg_match("/E\-Mail\:\s([A-Za-z0-9\_\-\.]{0,62}@[A-Za-z0-9\-\.]{0,62}\.[A-Za-z0-9\-]{0,62})\n/", $content, $mailMatch);
  if(!empty($mailMatch) && isset($mailMatch[1]))
  {
   $ret["email"] = $mailMatch[1];
  }

  $telefonMatch = array();
  preg_match("/Telefon\:\s(.*)\n/", $content, $telefonMatch);
  if(!empty($telefonMatch) && isset($telefonMatch[1]))
  {
   $ret["telefon"] = $telefonMatch[1];
  }
  return $ret;
 }

 /**
  * Gibt zurück, ob der übergebene Knoten gesperrt ist, oder nicht,
  * falls der Knoten gesperrt ist werden die Infos über den entsprechenden Nutzer und der Sperrzeitpunkt zurückgegeben
  *
  * @param string $node
  * @return array|false
  */
 public static function isLocked($node)
 {
  $result = self::dbGetNodeInfos($node);

  if(!empty($result))
  {
   $lockedTime = strtotime($result["lockedDate"]);
   if($result["lockedBy"] != get_current_user_id() && time() - $lockedTime < self::$lockTimeout)
   {
    $user = get_user_by("id", $result["lockedBy"]);
    return array(
     "user" => $user->display_name,
     "time" => date("H:i", $lockedTime + self::$lockTimeout)
    );
   }
  }
  return false;
 }

}
?>
