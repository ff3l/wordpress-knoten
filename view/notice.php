<div class='ff3l-wrapper ff3l-notice updated'>
 <div class='main'>
  <h3><?php echo FF3L::i18n("Activation success");?></h3>
  <p><?php echo FF3L::i18n("Activation info");?></p>
  <p><?php echo FF3L::i18n("Multilingual plugin info: %s", "<a href='https://wordpress.org/plugins/wp-native-dashboard/' target='_blank'>WP Native Dashboard</a>");?></p>
 </div>

 <?php
 if(current_user_can("manage_options"))
 {
  echo '<div id="major-publishing-actions">';
  echo "<a href='" . FF3L::getHref("config") . "' class='button button-primary'>" . FF3L::i18n("Change configuration") . "</a>";
  echo '</div>';
 }
 ?>
</div>