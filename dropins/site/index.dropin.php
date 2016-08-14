<?php
/*
  PURPOSE: VbzCart drop-in descriptor for site management
  HISTORY:
    2014-02-24 started
*/

// CONSTANTS

//define('KS_PAGE_KEY_SITE',	'site');
define('KS_PAGE_KEY_DROPINS',	'dropins');

// -- class names

define('KS_CLASS_SITE_DROPIN_MANAGER',	'VCM_DropIn_Manager');
define('KS_CLASS_SITE_DROPIN_MODULE',	'VCI_DropIn_Module');

// MENU

$om = new clsMenuFolder($oRoot,'*site','Site','Site Management','site configuration and management');
  $om->NeedPermission(KS_PERM_SITE_VIEW_CONFIG);
  //$om->NeedPermission(NULL);
  $omi = new clsMenuLink($om,KS_PAGE_KEY_DROPINS,'Dropins','Drop-in Modules','manage drop-in modules');
    $omi->Controller(KS_CLASS_SITE_DROPIN_MANAGER);
    $omi->NeedPermission(KS_PERM_SITE_VIEW_CONFIG);

// MODULE SPEC ARRAY

$arDropin = array(
  'name'	=> 'ferreteria.dropins',
  'descr'	=> 'drop-in module management functions',
  'version'	=> '0.9',
  'date'	=> '2015-06-30',
  'URL'		=> NULL,	// nothing yet
  'classes'	=> array(	// list of files and the classes they contain
    'dropin.php'		=> array(KS_CLASS_SITE_DROPIN_MANAGER),
     ),
  'menu'	=> $om,
  //'permit'	=> array('admin'),	// groups who are allowed access
  );
