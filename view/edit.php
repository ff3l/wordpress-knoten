<div class="wrap">
 <header>
  <h2>
   <?php
   if(self::$modelData["isNew"])
   {
    echo FF3L::i18n("Add node");
   }
   elseif(self::checkRight("edit"))
   {
    echo FF3L::i18n("Edit node");
   }
   else
   {
    echo FF3L::i18n("Rename node");
   }
   ?>
  </h2>

  <?php
  if(!empty(self::$modelData["handlePost"]["errors"])) // Fehlermeldung anzeigen
  {
   echo "<div class='error'>";
   echo FF3L::i18n("Error: Save failed");
   echo "<ul class='ul-disc'>";
   foreach(self::$modelData["handlePost"]["errors"] as $error) // alle Fehler durchlaufen
   {
    echo "<li>{$error}</li>";
   }
   echo "</ul>";
   echo "</div>";
  }
  ?>
 </header>

 <?php
 $formActionParams = array();
 if(!empty(self::$modelData["currentNode"]))
 {
  $formActionParams["node"] = self::$modelData["currentNode"];
 }
 ?>
 <form method="POST" action="<?php echo FF3L::getHref("edit", $formActionParams);?>" class="nodeEditForm">
  <h3><?php echo FF3L::i18n("General");?></h3>
  <ul>
   <li>
    <label for="name"><?php echo FF3L::i18n("Name");?> <span class="required">*</span></label>
    <input type="text" name="name" id="name" value="<?php echo self::$modelData["node"]["name"];?>" required />
   </li>
   <li>
    <label for="nodeKey"><?php echo FF3L::i18n("NodeKey");?> <span class="required">*</span></label>
    <?php
    if(self::$modelData["onlyRename"])
    {
     echo '<span class="inputReadonly">' . self::$modelData["node"]["nodeKey"] . "</span>";
    }
    else
    {
     echo '<input type="text" name="nodeKey" id="nodeKey" pattern="[a-fA-F\d]{64}" title="' . FF3L::i18n("NodeKey pattern") . '" value="' . self::$modelData["node"]["nodeKey"] . '" required />';
    }
    ?>
   </li>
  </ul>

  <h3><?php echo FF3L::i18n("Contact");?></h3>
  <ul>
   <li>
    <label for="email"><?php echo FF3L::i18n("Mail");?> <span class="required">*</span></label>
    <?php
    if(self::$modelData["onlyRename"])
    {
     echo '<span class="inputReadonly">' . self::$modelData["node"]["email"] . "</span>";
    }
    else
    {
     echo '<input type="email" name="email" id="email" value="' . self::$modelData["node"]["email"] . '" required />';
    }
    ?>
   </li>
   <li>
    <label for="telefon"><?php echo FF3L::i18n("Phone");?></label>
    <?php
    if(self::$modelData["onlyRename"])
    {
     echo '<span class="inputReadonly">' . self::$modelData["node"]["telefon"] . "</span>";
    }
    else
    {
     echo '<input type="text" name="telefon" id="telefon" value="' . self::$modelData["node"]["telefon"] . '" />';
    }
    ?>
   </li>
  </ul>

  <div id="major-publishing-actions">
   <button type="submit" class="button button-primary"><?php echo FF3L::i18n("Save node");?></button>&emsp;
   <a href="<?php echo FF3L::getHref("overview");?>" class="button cancel"><?php echo FF3L::i18n("Goto overview");?></a>
  </div>
 </form>
</div>