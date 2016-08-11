<?php

class ConfigModel
{
 private $handlePost = array();

 public function __construct()
 {

 }

 public function checkViewRights()
 {
  return current_user_can('manage_options');
 }

 /**
  * Gibt false im Fehlerfall zurück und true, falls das Repository korrekt ist,
  * speichert die Fehlermeldungen in '$this->handlePost'
  *
  * @param string $repo
  * @return boolean
  */
 private function repositoryErrorCheck($repo)
 {
  if(empty($repo) || !file_exists($repo) || !is_writable($repo)) // Repository existiert nicht, oder Apache hat keine Leserechte
  {
   $this->handlePost["error"] = FF3L::i18n("Repository not found");
   return false;
  }

  $repo = rtrim($repo, "/") . "/";

  if(!file_exists($repo . ".git/")) // Repository besitzt kein .git-Verzeichnis
  {
   $this->handlePost["error"] = FF3L::i18n("Repository no git");
   return false;
  }

  return true;
 }

 public function handlePost()
 {
  $postVal = FF3L::getFilteredPostValues();
  $gitRepo = $postVal["gitRepository"];

  if($this->repositoryErrorCheck($gitRepo)) // keine Fehler mit dem Repository
  {
   if($gitRepo !== FF3L::$gitRepository) // Git-Repository hat sich geändert -> Wert in der Datenbank aktualisieren
   {
    update_option("ff3l_git_repo", $gitRepo, false);
    FF3L::$gitRepository = $gitRepo;
    FF3L_git::revertToOrigin();
   }

   if(isset($postVal["mapUrl"]) && !empty($postVal["mapUrl"])) // Map-Url wurde angegeben
   {
    if($postVal["mapUrl"] !== FF3L::$mapUrl) // Map-Url hat sich geändert -> Flag in der Datenbank anpassen
    {
     update_option("ff3l_map_url", $postVal["mapUrl"], false);
     FF3L::$mapUrl = $postVal["mapUrl"];
    }
   }
   else // keine Map-Url angegeben -> Flag löschen
   {
    delete_option("ff3l_map_url");
    FF3L::$mapUrl = null;
   }

   foreach($GLOBALS['wp_roles']->roles as $name => $role) // Berechtigungen für die verschiedenen Benutzerrollen speichern
   {
    $roleObj = get_role($name);

    foreach(array("view", "edit", "remove", "create", "rename") as $right)
    {
     $val = isset($postVal["right"][$name]["ff3l_" . $right]) && !empty($postVal["right"][$name]["ff3l_" . $right]);
     $roleObj->add_cap("ff3l_" . $right, $val);
    }
   }
   $this->handlePost["saved"] = true;
  }
 }

 public function getData()
 {
  $defaults = array(
   "saved" => false,
   "rights" => array(),
   "caps" => FF3L::getRights(),
  );

  foreach($GLOBALS['wp_roles']->roles as $name => $role)
  {
   $elm = array(
    "name" => $name,
    "label" => translate_user_role($role["name"]),
    "rights" => array()
   );

   foreach($defaults["caps"] as $cap => $val)
   {
    $elm["rights"]["ff3l_" . $cap] = $role["capabilities"]["ff3l_" . $cap];
   }

   $defaults["rights"][] = $elm;
  }

  return array_merge($defaults, $this->handlePost);
 }

}
?>