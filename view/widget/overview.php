<div class="main">
 <div class="totalNodes">
  <span><?php echo self::$modelData["totalNodes"];?></span> <?php echo FF3L::i18n("Total nodes");?>
 </div>

 <?php
 if(!empty(self::$modelData["lastChanged"]))
 {
  ?>
  <h4><?php echo FF3L::i18n("Last changed:");?></h4>
  <table class="wp-list-table widefat striped">
   <thead>
    <tr>
     <th><?php echo FF3L::i18n("Name");?></th>
     <th><?php echo FF3L::i18n("Changed");?></th>
    </tr>
   </thead>
   <tbody>
    <?php
    foreach(self::$modelData["lastChanged"] as $node)
    {
     $details = "";
     if(!empty($node->email) || !empty($node->telefon))
     {
      $details = "<ul class='details'>";
      if(!empty($node->email))
      {
       $details .= "<li class='email'>{$node->email}</li>";
      }
      if(!empty($node->telefon))
      {
       $details .= "<li class='phone'>{$node->telefon}</li>";
      }
      $details .= "</ul>";
     }

     $classAdd = empty($details) ? "empty" : "";

     echo <<< EOT
      <tr>
       <td><a href='#' class='toggleDetails {$classAdd}'>{$node->name}</a>{$details}</td>
       <td class="column-lastChange">{$node->lastChange}</td>
      </tr>
EOT;
    }
    ?>
   </tbody>
  </table>
  <?php
 }
 ?>
</div>

<?php
if(FF3L::checkRight('view'))
{
 echo '<div class="sub">';
 echo "<a href='" . FF3L::getHref("overview") . "' class='left button overview'>" . FF3L::i18n("Goto node overview") . "</a>";

 if(FF3L::checkRight('create'))
 {
  echo "<a href='" . FF3L::getHref("edit") . "' class='right button button-primary add'>" . FF3L::i18n("Add new node") . "</a>";
 }
 echo '<br class="clear" /></div>';
}
?>