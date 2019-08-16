<?php

class FF3L
{
 public static $gitRepository = null;
 public static $mapUrl = null;
 public static $langPaketAvailable = true;
 public static $author = "Freifunk Dreiländereck <admin@freifunk-3laendereck.net>";
 //
 private static $textDomain = "ff3l";
 private static $minWordpressVersion = "4.2.0";
 private static $modelData = array();

 /**
  * Plugin initialisieren,
  * Output buffern, damit ggf. Weiterleitungen, Cookies, etc. noch funktionieren
  */
 public static function init()
 {
  ob_start();
  load_plugin_textdomain(self::$textDomain, false, self::$textDomain . '/lang/');
  self::init_hooks();
 }

 /**
  * Buffer ausgeben
  */
 public static function shutdown()
 {
  echo ob_get_clean();
 }

 /**
  * Korrigiert die aktuelle Sprache, in der das Plugin dargestellt wird, falls das Plugin nicht in der aktuelle Sprache verfügbar ist
  *
  * @param string $locale
  * @param string $domain
  * @return string
  */
 public static function localeFilter($locale, $domain)
 {
  if($domain === self::$textDomain && !file_exists(FF3L_PLUGIN_DIR . 'lang/' . self::$textDomain . "-" . $locale . ".mo")) // Language-Files für die aktuelle Sprache existiert nicht -> englisches Sprachpaket laden
  {
   $locale = 'en_US';
   self::$langPaketAvailable = false;
  }
  return $locale;
 }

 /**
  * Gibt alle Berechtigungen, die das Plugin verwendet zurück
  *
  * @param boolean $labels Label der Berechtigungen mit zurückgeben, oder nicht
  * @return array
  */
 public static function getRights($labels = true)
 {
  $rights = array(
   "view" => self::i18n("Overview"),
   "rename" => self::i18n("Rename node"),
   "edit" => self::i18n("Edit node"),
   "create" => self::i18n("Create node"),
   "remove" => self::i18n("Remove node")
  );

  if($labels === false)
  {
   return array_keys($rights);
  }
  else
  {
   return $rights;
  }
 }

 /**
  * Gibt den Link-Href der übergebenen Plugin-Seite zurück,
  * falls Parameter übergeben werden, werden diese an den Link angehängt
  *
  * @param string $type z.B. 'overview', 'edit', ...
  * @param array $params z.B. array("node" => "freifunk-123")
  * @return string
  */
 public static function getHref($type, $params = array())
 {
  $link = "admin.php?page=ff3l-" . $type;
  if(!empty($params))
  {
   foreach($params as $param => $val)
   {
    $link .= "&" . $param . "=" . $val;
   }
  }
  return admin_url($link);
 }

 /**
  * Übersetzt den übergebenen String, ersetzt falls vorhanden die übergebenen Platzhalter
  *
  * @param string $text
  * @param string|array $opts Ersetzungen die für die Platzhalter im String (z.B. %s, %d) eingesetzt werden sollen
  * @return string
  */
 public static function i18n($text, $opts = "")
 {
  if(empty($opts)) // einfache Übersetzung
  {
   return __($text, self::$textDomain);
  }
  else // Übersetzung mit Ersetzungen -> Aufruf von 'sprintf'
  {
   if(!is_array($opts))
   {
    $opts = array($opts);
   }

   array_unshift($opts, __($text, self::$textDomain));
   return call_user_func_array("sprintf", $opts);
  }
 }

 /**
  * Initialisiert die Hooks
  */
 private static function init_hooks()
 {
  self::$gitRepository = get_option("ff3l_git_repo", null);
  self::$mapUrl = get_option("ff3l_map_url", null);

  $userID = get_current_user_id();
  $userInfo = get_userdata($userID);
  self::$author = $userInfo->first_name . " " . $userInfo->last_name . " <" . $userInfo->user_email . ">";

  add_action('admin_menu', array('FF3L', 'addMenuEntries'));
  add_action('admin_enqueue_scripts', array('FF3L', 'loadResources'));
  add_action('wp_ajax_remove_nodes', array('FF3L', 'ajaxRemoveNodes'));
  add_action('admin_notices', array('FF3L', 'showActivationNotice'));

  if(!is_null(self::$gitRepository))
  {
   add_action('wp_dashboard_setup', array('FF3L', 'addWidgets'));
  }

  add_filter('plugin_action_links_' . self::$textDomain . '/' . self::$textDomain . '.php', array('FF3L', 'addPluginOverviewLinks'));
  add_filter('set-screen-option', array('FF3L', 'setNodesPerPage'), 10, 3);
 }

 /**
  * Ergänzt die Links der Plugin-Übersicht zur Knotenverwaltung um eigene Links
  *
  * @param array $links
  * @return array
  */
 public static function addPluginOverviewLinks($links)
 {
  if(is_null(self::$gitRepository))
  {
   array_unshift($links, '<a href="' . FF3L::getHref("config") . '">' . self::i18n("Configuration") . '</a>');
  }
  else
  {
   array_unshift($links, '<a href="' . FF3L::getHref("overview") . '">' . self::i18n("Overview") . '</a>');
  }
  return $links;
 }

 /**
  * Registiert die Widgets für das Dashboard
  */
 public static function addWidgets()
 {
  wp_add_dashboard_widget('ff3l-overviewWidget', self::i18n('Nodes'), array('FF3L', 'showOverviewWidget'));
  if(!empty(self::$mapUrl)) // Map-Widget nur anzeigen, wenn eine Map-Url konfiguriert wurde
  {
   wp_add_dashboard_widget('ff3l-mapWidget', self::i18n('Map'), array('FF3L', 'showMapWidget'));
  }
 }

 /**
  * Zeigt das Map-Widget an
  */
 public static function showMapWidget()
 {
  $pageModel = self::getPageModel("widget/map", "MapWidget");
  echo "<div class='ff3l-wrapper'>";
  self::view("widget/map", $pageModel->getData());
  echo "</div>";
 }

 /**
  * Zeigt das Statistik-Widget mit Infos über die letzten Änderungen und der Anzahl erfasster Knoten an
  */
 public static function showOverviewWidget()
 {
  $pageModel = self::getPageModel("widget/overview", "OverviewWidget");
  echo "<div class='ff3l-wrapper'>";
  self::view("widget/overview", $pageModel->getData());
  echo "</div>";
 }

 /**
  * Speichert den Wert für die Anzahl Knoten in der Übersicht ab
  *
  * @param boolean $status
  * @param string $option
  * @param mixed $value
  * @return int|false
  */
 public static function setNodesPerPage($status, $option, $value)
 {
  return $option === "nodes_per_page" ? intval($value) : $status;
 }

 /**
  * Methode, die bei vom Javascript bei Angabe des 'action'-Parameters 'remove_nodes' aufgerufen wird,
  * löscht die Knoten mit den übergebenen Namen ('nodes'-Parameter)
  *
  * @throws Exception
  */
 public static function ajaxRemoveNodes()
 {
  try
  {
   if(self::checkRight('remove'))
   {
    $nodeString = filter_input(INPUT_POST, "nodes", FILTER_DEFAULT);
    $nodes = json_decode($nodeString, true);

    if(!empty($nodes) && is_array($nodes))
    {
     foreach($nodes as $node) // alle übergebenen Knoten durchlaufen und auf Existenz prüfen
     {
      if(!file_exists(self::$gitRepository . $node))
      {
       throw new Exception(self::i18n("Node not found: %s", $node));
      }
     }

     foreach($nodes as $node) // alle übergebenen Knoten durchlaufen und löschen
     {
      unlink(self::$gitRepository . $node);
      FF3L_data::dbRemoveNode($node);
      FF3L_git::remove($node);
     }

     FF3L_git::push();
     echo "success";
    }
    else
    {
     throw new Exception(self::i18n("No node for deletetion selected"));
    }
   }
   else
   {
    throw new Exception(self::i18n("No right to delete node"));
   }
  }
  catch(Exception $e) // Fehlermeldung ausgeben
  {
   echo $e->getMessage();
  }
  exit();
 }

 /**
  * Lädt die Stylesheets und Javascripts für das Plugin
  */
 public static function loadResources()
 {
  global $pagenow;

  wp_register_style('style.css', plugins_url() . "/" . self::$textDomain . '/css/style.css', array());
  wp_enqueue_style('style.css');

  $scripts = array('main.js'); // Default-Javascripts
  $currentPage = filter_input(INPUT_GET, "page", FILTER_SANITIZE_STRING);

  if($pagenow === "index.php") // Widget-Javascript
  {
   $scripts[] = "widget.js";
  }

  switch($currentPage)
  {
   case "ff3l-overview":
    $scripts[] = "stacktable.js";
    $scripts[] = "overlay.js";
    $scripts[] = "overview.js";
    break;
   case "ff3l-edit":
    $scripts[] = "edit.js";
    break;
   case "ff3l-config":
    $scripts[] = "stacktable.js";
    $scripts[] = "config.js";
    break;
  }

  foreach($scripts as $script)
  {
   wp_register_script($script, plugins_url() . "/" . self::$textDomain . '/js/' . $script, array());
   wp_enqueue_script($script);
  }

  wp_localize_script("main.js", 'FF3L', array(
   'ajaxUrl' => admin_url('admin-ajax.php'),
   'currentPage' => $currentPage,
   'currentNode' => filter_input(INPUT_GET, "node", FILTER_SANITIZE_STRING),
   'mapUrl' => self::$mapUrl,
   'lang' => array(
    'removeNode' => self::i18n("Remove node"),
    'removeNodeConfirm' => self::i18n("Remove node confirm"),
    'removeNodesConfirm' => self::i18n("Remove nodes confirm"),
    'cancel' => self::i18n("Cancel"),
    'error' => self::i18n("Error"),
    'close' => self::i18n("Close")
   )
  ));
 }

 /**
  * Fügt die Menü-Elemente für das Plugin hinzu
  */
 public static function addMenuEntries()
 {
  if(is_null(self::$gitRepository)) // Repository nicht definiert -> nur Link zur Konfiguration anzeigen
  {
   add_menu_page(self::i18n('Administration'), self::i18n('Nodes'), 'manage_options', 'ff3l-config', null, "dashicons-share");
   add_submenu_page('ff3l-overview', self::i18n('Title: Configuration'), self::i18n('Menu: Configuration'), 'manage_options', 'ff3l-config', array('FF3L', 'displayPage'));
  }
  else // Repository wurde definiert -> alle vorhandenen Seiten anzeigen
  {
   add_menu_page(self::i18n('Administration'), self::i18n('Nodes'), 'ff3l_view', 'ff3l-overview', null, "dashicons-share");
   $hookOverview = add_submenu_page('ff3l-overview', self::i18n('Title: Overview'), self::i18n('Menu: Overview'), 'ff3l_view', 'ff3l-overview', array('FF3L', 'displayPage'));
   add_submenu_page(self::checkRight("create") ? 'ff3l-overview' : 'options.php', self::i18n('Title: Edit'), self::i18n('Add node'), 'ff3l_rename', 'ff3l-edit', array('FF3L', 'displayPage'));
   add_submenu_page('ff3l-overview', self::i18n('Title: Configuration'), self::i18n('Menu: Configuration'), 'manage_options', 'ff3l-config', array('FF3L', 'displayPage'));
   add_action("load-" . $hookOverview, array('FF3L', 'addScreenOptionTab'));
  }

  add_action("current_screen", array('FF3L', 'addHelpTab'));
 }

 /**
  * Zeigt die aktuelle Seite an,
  * zieht sich die entsprechende Info aus der URL (page-Parameter)
  */
 public static function displayPage()
 {
  $pageName = str_replace("ff3l-", "", filter_input(INPUT_GET, "page", FILTER_SANITIZE_STRING));
  $pageModel = self::getPageModel($pageName);

  if(empty($pageModel) || !method_exists($pageModel, "checkViewRights") || $pageModel->checkViewRights() === false) // Seiten-Model existiert nicht, oder der User hat keine Berechtigungen
  {
   self::noAccessDie();
  }
  else
  {
   if(!empty($_POST) && method_exists($pageModel, "handlePost")) // gepostete Werte behandeln -> je nach aktueller Seite unterschiedlich
   {
    $pageModel->handlePost();
   }

   echo "<div class='ff3l-wrapper'>";
   self::view($pageName, $pageModel->getData());
   echo "</div>";
  }
 }

 /**
  * Gibt die POST-Variable gefiltert zurück,
  * wendet 'filter_var' bzw. 'filter_var_array' an
  *
  * @return array
  */
 public static function getFilteredPostValues()
 {
  $ret = array();

  foreach($_POST as $key => $val)
  {
   if(is_array($val))
   {
    $val = filter_var_array($val);
   }
   else
   {
    $val = filter_var($val, FILTER_SANITIZE_STRING);
   }
   if ($key==="name") {
    $val = ltrim($val,"#"); 
   }
   $val = trim($val);
   $ret[$key] = $val;
   $_POST[$key] = $val;
  }

  return $ret;
 }

 /**
  * Gibt das Model zur übergebenen Seite zurück
  *
  * @param string $page
  * @return Model|null
  */
 private static function getPageModel($file, $className = null)
 {
  $modelFile = FF3L_PLUGIN_DIR . 'model/' . $file . '.php';
  if(file_exists($modelFile))
  {
   if(is_null($className))
   {
    $className = $file;
   }
   require_once $modelFile;
   $modelName = ucfirst($className . "Model");
   return new $modelName();
  }
  return null;
 }

 public static function formatTimestamp($time)
 {
  return "<span class='date'>" . date_i18n("d. M Y", $time) . " <span>" . date_i18n("H:i", $time) . " Uhr</span>";
 }

 /**
  * Zeigt die View der übergebenen Seite an
  *
  * @param string $name
  * @param array $args
  */
 private static function view($name, array $args = array())
 {
  self::$modelData = $args;
  $file = FF3L_PLUGIN_DIR . 'view/' . $name . '.php';

  if(file_exists($file))
  {
   include $file;
  }
  else
  {
   self::noAccessDie();
  }
 }

 /**
  * Screen-Options für die Übersichtsseite
  */
 public static function addScreenOptionTab()
 {
  $currentScreen = get_current_screen();
  $currentScreen->add_option('per_page', array(
   'label' => self::i18n('Nodes'),
   'default' => 20,
   'option' => 'nodes_per_page'
  ));
 }

 /**
  * Konfiguration des Hilfe-Tabs
  */
 public static function addHelpTab()
 {
  $currentScreen = get_current_screen();

  if(!is_null(self::$gitRepository))
  {
   $currentScreen->add_help_tab(array(
    'id' => 'overview',
    'title' => self::i18n('Overview'),
    'content' => '<p><h3>' . self::i18n('Overview') . '</h3></p>' . self::i18n('Help: Overview')
   ));

   $currentScreen->add_help_tab(array(
    'id' => 'edit',
    'title' => self::i18n('Edit node'),
    'content' => '<p><h3>' . self::i18n('Edit node') . '</h3></p>' . self::i18n('Help: Edit')
   ));
  }

  if(current_user_can('manage_options'))
  {
   $currentScreen->add_help_tab(array(
    'id' => 'config',
    'title' => self::i18n('Git-Repository'),
    'content' => '<p><h3>' . self::i18n('Git-Repository') . '</h3></p>' . self::i18n("Help: Git")
   ));

   $currentScreen->add_help_tab(array(
    'id' => 'rights',
    'title' => self::i18n('Rights'),
    'content' => '<p><h3>' . self::i18n('Rights') . '</h3></p>' . self::i18n("Help: Rights")
   ));
  }
 }

 /**
  *
  *
  * @param string $right
  */
 public static function checkRight($right)
 {
  if(current_user_can("ff3l_" . $right)) // User hat Recht auf übergebenen Typ
  {
   return true;
  }
  elseif($right == "rename" && current_user_can("ff3l_edit")) // User darf zwar nicht explizit umbenennen aber bearbeiten -> umbenennen also auch möglich
  {
   return true;
  }

  return false;
 }

 /**
  * Zeigt eine Meldung an, um den Benutzer auf seine fehlenden Berechtigungen aufmerksam zu machen
  */
 public static function noAccessDie()
 {
  wp_die(self::i18n('Access denied'));
 }

 /**
  * Wird nur aufgerufen, wenn das Plugin aktiviert wird,
  * Check, ob die Wordpress Version kompatibel mit dem Plugin ist
  */
 public static function activation()
 {
  if(version_compare($GLOBALS['wp_version'], self::$minWordpressVersion, '<')) // Wordpress-Version nicht kompatibel
  {
   echo self::i18n('Activation Error minVersion: %s', self::$minWordpressVersion);
   exit();
  }
  elseif(exec("git --version") === "") // Git nicht installiert
  {
   echo self::i18n('Activation Error git', self::$minWordpressVersion);
   exit();
  }
  else
  {
   $i = 1;
   foreach($GLOBALS['wp_roles']->roles as $name => $role) // Standard-Berechtigungen für die einzelnen Benutzergruppen setzen
   {
    $roleObj = get_role($name);
    $rights = self::getRights(false);
    $x = count($rights);
    foreach($rights as $right)
    {
     $roleObj->add_cap("ff3l_" . $right, $i <= $x);
     $x--;
    }
    $i++;
   }

   update_option("ff3l_activation_notice", 0, false); // Flag der nach dem Anzeigen der Aktivierungsmeldung auf true gesetzt wird -> notwendig, damit Meldung nur einmal angezeigt wird
   FF3L_data::createNodeTable();
  }
 }

 /**
  * Zeigt eine Meldung nach dem Aktivieren des Plugins an
  *
  * @global string $pagenow
  */
 public static function showActivationNotice()
 {
  global $pagenow;
  $noticeShown = get_option("ff3l_activation_notice", 0);

  if($pagenow == 'plugins.php' && $noticeShown == 0)
  {
   update_option("ff3l_activation_notice", 1, false);
   self::view("notice");
  }
 }

 /**
  * Wird nur aufgerufen, wenn das Plugin deaktiviert wird
  */
 public static function deactivation()
 {
  foreach($GLOBALS['wp_roles']->roles as $name => $role) // alle gesetzten Caps entfernen
  {
   $roleObj = get_role($name);
   $rights = self::getRights(false);

   foreach($rights as $right)
   {
    $roleObj->remove_cap("ff3l_" . $right);
   }
  }

  delete_metadata('user', 0, 'nodes_per_page', '', true);
  delete_option("ff3l_activation_notice");
  delete_option("ff3l_git_repo");
  delete_option("ff3l_map_url");

  FF3L_data::removeNodeTable();
 }

}
