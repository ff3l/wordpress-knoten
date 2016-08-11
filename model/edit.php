<?php

class EditModel
{
 private $handlePost;

 public function __construct()
 {

 }

 public function checkViewRights()
 {
  $node = filter_input(INPUT_GET, "node", FILTER_SANITIZE_STRING);
  $isNew = !isset($node) || empty($node);

  if(($isNew && FF3L::checkRight("create")) || (!$isNew && (FF3L::checkRight("edit") || FF3L::checkRight("rename"))))
  {
   if(is_array(FF3L_data::isLocked($node))) // der aktuelle Knoten wird gerade von einem anderen Benutzer bearbeitet
   {
    wp_die(FF3L::i18n("Node locked") . "<br /><a href='" . FF3L::getHref("overview") . "'>" . FF3L::i18n("Goto overview") . "</a>");
   }
   return true;
  }
  else
  {
   return false;
  }
 }

 public function handlePost()
 {
  $postVal = FF3L::getFilteredPostValues();
  $this->handlePost = array("errors" => array());

  $nodeName = filter_input(INPUT_GET, "node", FILTER_SANITIZE_STRING);
  $isNew = !isset($nodeName) || empty($nodeName);

  if($isNew)
  {
   $fileData = array(
    "nodeKey" => null,
    "email" => null,
    "telefon" => null
   );
  }
  else
  {
   $fileData = FF3L_data::getNodeDataFromFile($nodeName);
  }

  if(empty($postVal["name"]))
  {
   $this->handlePost["errors"][] = FF3L::i18n("Error: No name");
  }
  elseif(file_exists(FF3L::$gitRepository . trim($postVal["name"])) && $nodeName != trim($postVal["name"]))
  {
   $this->handlePost["errors"][] = FF3L::i18n("Error: Name already in use");
  }

  if($isNew || FF3L::checkRight("edit")) // wenn User nur umbenennen darf werden sonstige übertragene Werte ignoriert
  {
   $fileData["nodeKey"] = $postVal["nodeKey"];
   $fileData["email"] = $postVal["email"];
   $fileData["telefon"] = $postVal["telefon"];

   if(empty($fileData["nodeKey"]))
   {
    $this->handlePost["errors"][] = FF3L::i18n("Error: no nodeKey");
   }
   else
   {
    $keyTmp = preg_replace("/[^a-f\d]/i", "", $fileData["nodeKey"]);
    if(strlen($fileData["nodeKey"]) != 64 || strlen($keyTmp) != 64)
    {
     $this->handlePost["errors"][] = FF3L::i18n("Error: invalid nodeKey");
    }
    else // Schlüsselformat ist korrekt -> Check auf Duplikate
    {
     $nodeList = FF3L_data::getAllNodes();
     foreach($nodeList as $node)
     {
      if($node->nodeKey == $fileData["nodeKey"] && $node->name != $nodeName) // Schlüssel bei anderem Knoten gefunden
      {
       $this->handlePost["errors"][] = FF3L::i18n("Error: NodeKey already in use: %s", $node->name);
       break;
      }
     }
    }
   }

   if(empty($fileData["email"]))
   {
    $this->handlePost["errors"][] = FF3L::i18n("Error: no mail");
   }
   elseif(strlen($_POST["email"]) != strlen($fileData["email"]))
   {
    $this->handlePost["errors"][] = FF3L::i18n("Error: invalid mail");
   }

   if(!empty($fileData["telefon"]))
   {
    $telTmp = preg_replace("/[^0-9\-\+\/\s]*/", "", $fileData["telefon"]);
    if(strlen($fileData["telefon"]) != strlen($telTmp))
    {
     $this->handlePost["errors"][] = FF3L::i18n("Error: invalid phone");
    }
   }
  }

  if(empty($this->handlePost["errors"])) // keine Fehler aufgetreten -> Knoten speichern
  {
   $content = "# " . trim($postVal["name"]) . "\n";
   if(!empty($fileData["email"]))
   {
    $content .= "# E-Mail: " . $fileData["email"] . "\n";
   }
   if(!empty($fileData["telefon"]))
   {
    $content .= "# Telefon: " . $fileData["telefon"] . "\n";
   }
   $content .= "key \"" . $fileData["nodeKey"] . "\";";
   file_put_contents(FF3L::$gitRepository . trim($postVal["name"]), $content);

   if($isNew)
   {
    $dbFields = array(
     "createdBy" => get_current_user_id(),
     "createdDate" => FF3L_data::getMysqlDate()
    );
   }
   else
   {
    $dbFields = array(
     "changedBy" => get_current_user_id(),
     "changedDate" => FF3L_data::getMysqlDate()
    );
   }

   if(FF3L_data::dbUpdateNode(array("name" => trim($postVal["name"]), "oldName" => $nodeName), $dbFields))
   {
    if(!$isNew && trim($postVal["name"]) != $nodeName) // Knoten wurde umbenannt -> alte Datei löschen
    {
     FF3L_git::rename($nodeName, trim($postVal["name"]));
    }
    else
    {
     FF3L_git::update(trim($postVal["name"]), $isNew);
    }

    $pushResult = FF3L_git::push();

    if($pushResult === true) // Git-Push hat geklappt -> Weiterleitung auf die Übersicht
    {
     wp_redirect(FF3L::getHref("overview", array("nodeSaved" => true)), 302);
     exit();
    }
    else // Fehler beim Push -> Fehlermeldung ausgeben
    {
     $this->handlePost["errors"][] = FF3L::i18n("Error: Git push: %s", $pushResult);
    }
   }
   else
   {
    $this->handlePost["errors"][] = FF3L::i18n("Error: Database update");
   }
  }
 }

 public function getData()
 {
  $ret = array(
   "isNew" => true,
   "onlyRename" => false,
   "currentNode" => null,
   "handlePost" => $this->handlePost,
   "node" => array(
    "name" => "",
    "nodeKey" => "",
    "email" => "",
    "telefon" => ""
   )
  );

  $node = filter_input(INPUT_GET, "node", FILTER_SANITIZE_STRING);

  if(isset($node) && !empty($node) && file_exists(FF3L::$gitRepository . $node))
  {
   $ret["node"] = FF3L_data::getNodeDataFromFile($node);
   $ret["node"]["name"] = $node;

   $ret["isNew"] = false;
   $ret["currentNode"] = $node;
   FF3L_data::dbUpdateNode($node, array(
    "lockedBy" => get_current_user_id(),
    "lockedDate" => FF3L_data::getMysqlDate()
   ));
  }

  $postVal = FF3L::getFilteredPostValues();

  if(isset($postVal["name"]) && !empty($postVal["name"]))
  {
   $ret["node"]["name"] = trim($postVal["name"]);
  }

  if($ret["isNew"] || FF3L::checkRight("edit")) // wenn User nur umbenennen darf werden sonstige übertragene Werte ignoriert
  {
   if(isset($postVal["nodeKey"]) && !empty($postVal["nodeKey"]))
   {
    $ret["node"]["nodeKey"] = $postVal["nodeKey"];
   }

   if(isset($postVal["email"]) && !empty($postVal["email"]))
   {
    $ret["node"]["email"] = $postVal["email"];
   }

   if(isset($postVal["telefon"]) && !empty($postVal["telefon"]))
   {
    $ret["node"]["telefon"] = $postVal["telefon"];
   }
  }
  else // User darf nur umbenennen
  {
   $ret["onlyRename"] = true;
  }

  return $ret;
 }

}
?>
