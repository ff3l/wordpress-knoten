<?php

class FF3L_git
{

 /**
  * nennt den übergebenen Knoten in den anderen übergebenen Knoten im lokalen Repository um
  *
  * @param string $node
  */
 public static function rename($old, $new)
 {
  chdir(FF3L::$gitRepository);
  exec("git rm " . FF3L::$gitRepository . $old);
  exec("git add " . FF3L::$gitRepository . $new);
  echo exec("git commit -m 'Renamed {$old} into {$new}' --author=\"" . FF3L::$author . "\"");
  
 }

 /**
  * löscht den übergebenen Knoten aus dem lokalen Repository
  *
  * @param string $node
  */
 public static function remove($node)
 {
  chdir(FF3L::$gitRepository);
  exec("git rm " . FF3L::$gitRepository . $node);
  exec("git commit -m 'Removed Node {$node}' --author=\"" . FF3L::$author . "\"");
 }

 /**
  * Commit eines Knotens im lokalen Repository,
  * je nach Wert das isNew-Flags wird ein anderer Kommentar übermittelt
  *
  * @param string $node
  * @param boolean $isNew
  */
 public static function update($node, $isNew = false)
 {
  chdir(FF3L::$gitRepository);
  $msg = ($isNew ? "Added Node " : "Updated Node ") . $node;
  exec("git add " . FF3L::$gitRepository . $node);
  exec("git commit -m '{$msg}' --author=\"" . FF3L::$author . "\"");
 }

 /**
  * Gibt den Git-Status in Html formatiert zurück
  *
  * @return string
  */
 public static function getStatus()
 {
  chdir(FF3L::$gitRepository);
  $status = array();
  $temp=exec("git fetch origin");
  exec("git status", $status);
  if(count($status) > 2)
  {
   $ret = "";
   $hash = false;
   foreach($status as $line)
   {
    if(strpos($line, "#") === 0)
    {
     $line = substr($line, 2);
     if($hash === false)
     {
      $ret .= "<p>";
     }
     $hash = true;
    }
    elseif($hash)
    {
     $ret .= "</p><br />";
    }
    $ret .= htmlentities($line) . "<br />";
   }
   return $ret;
  }

  return "";
 }

 /**
  * lokale Änderungen rückgängig machen
  */
 public static function revertToOrigin()
 {
  chdir(FF3L::$gitRepository);
  exec("git fetch origin");
  exec("git reset --hard origin/master");
 }

 public static function pull() {
       chdir(FF3L::$gitRepository);

        $logLines = array();
        exec("git pull origin master", $logLines);

        $error = false;
        foreach ($logLines as $line) {
            if (stripos($line, "CONFLICT") !== false || strpos($line, "have diverged") !== false || strpos($line, "failed") !== false) { // Fehler
                $error = true;
                break;
            }
        }

        if ($error) {
            return implode("<br />", $logLines);
        } else {
            return true;
        }
    }

 /**
  * lokale Änderungen an das Remote-Repository übermitteln
  *
  * @return boolean|string
  */
 public static function push()
 {
  chdir(FF3L::$gitRepository);

  $logLines = array();
  exec("git push origin master", $logLines);
  
  $datetime = new DateTime();
  $datetime->setTimezone(new DateTimeZone("UTC"));
  $datum = $datetime->format("Y-m-d\TH:i:s\Z");

  $error = false;
  foreach($logLines as $line)
  {
   if(stripos($line, "CONFLICT") !== false || strpos($line, "have diverged") !== false || strpos($line, "failed") !== false) // Fehler
   {
    $error = true;
    break;
   }
  }

  if($error)
  {
   return implode("<br />", $logLines);
  }
  else
  {
   return true;
  }
 }

}
?>
