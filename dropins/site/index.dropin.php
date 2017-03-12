<?php
/*
  PURPOSE: drop-in descriptor for site management
  HISTORY:
    2014-02-24 started
    2016-12-05 this was moved back into Ferreteria awhile ago; renaming accordingly
*/

// CONSTANTS

//define('KS_PAGE_KEY_SITE',	'site');
define('KS_PAGE_KEY_DROPINS',	'dropins');

// -- class names

define('KS_CLASS_SITE_DROPIN_MANAGER',	'fcaDropInManager');
define('KS_CLASS_SITE_DROPIN_MODULE',	'fcaDropInModule');

// MENU

$om = $oRoot->SetNode(new fcMenuFolder('Site'));

  $omi = $om->SetNode(new fcDropinLink(
    KS_PAGE_KEY_DROPINS,
    KS_CLASS_SITE_DROPIN_MANAGER,
    'Dropins','manage drop-in modules'));
    $omi->SetPageTitle('Drop-in Modules');
    $omi->SetRequiredPrivilege(KS_PERM_SITE_VIEW_CONFIG);
    $omi->SetRequiredPrivilege(NULL);	// debugging

/* 2016-12-11 old dropin version
$om = new fcMenuFolder($oRoot,'*site','Site','Site Management','site configuration and management');
  $om->NeedPermission(KS_PERM_SITE_VIEW_CONFIG);
  //$om->NeedPermission(NULL);
  $omi = new fcMenuLink($om,KS_PAGE_KEY_DROPINS,'Dropins','Drop-in Modules','manage drop-in modules');
    $omi->Controller(KS_CLASS_SITE_DROPIN_MANAGER);
    $omi->NeedPermission(KS_PERM_SITE_VIEW_CONFIG);
*/

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
  );
