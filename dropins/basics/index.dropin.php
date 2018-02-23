<?php
/*
  PURPOSE: drop-in descriptor for site management
  HISTORY:
    2013-12-07 drop-in admin module started
    2014-02-24 event log admin module started
    2016-12-05 event log admin module  was moved back into Ferreteria awhile ago; renaming accordingly
    2017-01-15 drop-in admin module moved from VbzCart to Ferreteria
    2017-04-14 merged drop-in admin module and event log admin module into a single drop-in
*/

// CONSTANTS

define('KS_ACTION_KEY_DROPINS',	'dropins');
define('KS_ACTION_SYSTEM_LOG','event');

// -- class names

define('KS_CLASS_SITE_DROPIN_MANAGER',	'fctDropInManager');
define('KS_CLASS_SITE_DROPIN_MODULE',	'fcrAdminDropInModule');
define('KS_CLASS_EVENT_LOG_ADMIN',	'fctAdminEventPlex');

// UTILITY CLASS UPGRADES - not sure if this is the best way to do this...
//	2018-02-19 let's say "not"
//fcApp::Me()->SetEventsClass(KS_CLASS_EVENT_LOG_ADMIN);

// MENU

$om = $oRoot->SetNode(new fcMenuFolder('Ferreteria basics'));

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_KEY_DROPINS,
    KS_CLASS_SITE_DROPIN_MANAGER,
    'Dropins','view all drop-in modules'));
    //$omi->SetRequiredPrivilege(KS_PERM_SITE_VIEW_CONFIG);
    $omi->SetRequiredPrivilege(NULL);	// debugging

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_SYSTEM_LOG,
    KS_CLASS_EVENT_LOG_ADMIN,
    'Event log','Ferreteria event log stuff'));
    $omi->SetRequiredPrivilege(KS_PERM_EVENTS_VIEW);

// MODULE SPEC ARRAY

$arDropin = array(
  'name'	=> 'ferreteria.basics',
  'descr'	=> 'Ferreteria admin functions',
  'version'	=> '0.91',
  'date'	=> '2017-04-14',
  'URL'		=> NULL,	// nothing yet
  'classes'	=> array(	// list of files and the classes they contain
    'dropin.php'	=> array(KS_CLASS_SITE_DROPIN_MANAGER),
    'log.php'		=> array(KS_CLASS_EVENT_LOG_ADMIN),
    'log-forms.php'	=> array('fcForm_EventPlex')
     ),
  'menu'	=> $om,
  );
