<?php
/*
  Plugin Name: Node Administration
  Description: @Description
  Plugin URI: http://www.lorenz-it.eu/
  Version: 2.0
  Author: Philipp K&ouml;nig
  Text Domain: ff3l
  Domain Path: /lang/
 */
if(function_exists('add_action') && is_admin()) // nur im Administrationsbereich ausführen
{
 define('FF3L_PLUGIN_DIR', plugin_dir_path(__FILE__));

 require_once(FF3L_PLUGIN_DIR . 'class/ff3l.php');
 require_once(FF3L_PLUGIN_DIR . 'class/data.php');
 require_once(FF3L_PLUGIN_DIR . 'class/git.php');
 FF3L_data::init();

 register_activation_hook(__FILE__, array('FF3L', 'activation'));
 register_deactivation_hook(__FILE__, array('FF3L', 'deactivation'));

 add_filter('plugin_locale', array('FF3L', 'localeFilter'), 10, 2);
 add_action('init', array('FF3L', 'init'));
 add_action('shutdown', array('FF3L', 'shutdown'));
}
