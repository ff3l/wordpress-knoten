<div class="wrap">
 <header>
  <h2><?php echo FF3L::i18n("Configuration");?></h2>
  <?php
  if(is_null(FF3L::$gitRepository))
  {
   echo "<p class='description'>" . FF3L::i18n("No repository message") . "</p>";
  }

  if(isset(self::$modelData["error"])) // Fehlermeldung
  {
   echo "<div class='error'>" . self::$modelData["error"] . "</div>";
  }
  elseif(self::$modelData["saved"]) // Konfiguration wurde geändert -> Meldung anzeigen
  {
   echo "<div class='updated'>" . FF3L::i18n("Configuration saved") . "</div>";
  }
  ?>
 </header>
 <form method="POST" action="<?php echo FF3L::getHref("config");?>">
  <h3><?php echo FF3L::i18n("General");?></h3>
  <table class="form-table">
   <tbody>
    <tr>
     <th scope="row"><label for="gitRepository"><?php echo FF3L::i18n("Local Git-Repository");?> <span class="required">*</span></label></th>
     <td>
      <input name="gitRepository" id="gitRepository" required type="text" placeholder="<?php echo FF3L::i18n("Git-Repository placeholder");?>" value="<?php echo FF3L::$gitRepository;?>" class="regular-text" />
      <p class="description"><?php echo FF3L::i18n("Git-Repository description");?></p>
     </td>
    </tr>
    <tr>
     <th scope="row"><label for="mapUrl"><?php echo FF3L::i18n("Map link");?></label></th>
     <td>
      <input name="mapUrl" id="mapUrl" type="text" placeholder="<?php echo FF3L::i18n("Map placeholder");?>" value="<?php echo FF3L::$mapUrl;?>" class="regular-text" />
      <p class="description"><?php echo FF3L::i18n("Map description");?></p>
     </td>
    </tr>
   </tbody>
  </table>

  <h3><?php echo FF3L::i18n("Rights");?></h3>
  <table class='rights wp-list-table widefat fixed striped'>
   <thead>
    <tr><th></th>
     <?php
     foreach(self::$modelData["caps"] as $label)
     {
      echo "<th>{$label}</th>";
     }
     ?>
    </tr>
   </thead>
   <tbody>
    <?php
    foreach(self::$modelData["rights"] as $role)
    {
     echo "<tr><td>" . $role["label"] . "</td>";
     foreach($role["rights"] as $right => $val)
     {
      echo "<td class='center'><input type='checkbox' class='{$right}' name='right[{$role["name"]}][{$right}]' " . ($val ? "checked" : "") . "/></td>";
     }
     echo "</tr>";
    }
    ?>
   </tbody>
  </table>
  <br />
  <button type="submit" class="button button-primary"><?php echo FF3L::i18n("Save configuration");?></button>
 </form>
</div>
