<div class="main">
 <div class="mapWrapper">
  <iframe src="<?php echo FF3L::$mapUrl;?>"></iframe>
 </div>
</div>

<?php
echo '<div class="sub">';
echo "<a href='" . FF3L::$mapUrl . "' class='button map' target='_blank'>" . FF3L::i18n("Goto map") . "</a>";
if(FF3L::checkRight('create'))
{
 echo "<a href='" . FF3L::getHref("edit") . "' class='right button button-primary add'>" . FF3L::i18n("Add new node") . "</a>";
}
echo '<br class="clear" /></div>';
?>