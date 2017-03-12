<?php
/*
  PURPOSE: drop-in descriptor for stock management
  TODO: menu
  HISTORY:
    2013-12-07 started
    2017-01-15 moved from VbzCart to Ferreteria
*/

// CONSTANTS

define('KS_CLASS_EVENT_LOG','fctEvents_admin');

define('KS_ACTION_SYSTEM_LOG','event');

// MENU

$om = $oRoot->SetNode(new fcDropinLink(
  KS_ACTION_SYSTEM_LOG,
  KS_CLASS_EVENT_LOG,
  'Syslog','system event log management'));
  $om->SetPageTitle('System Log');
  $om->SetRequiredPrivilege(KS_PERM_EVENTS_VIEW);

/* 2016-12-11 old dropin version
$om = new fcMenuLink($oRoot, 'syslog','Syslog','System Log','system event log management');
  $om->Controller(KS_CLASS_EVENT_LOG);
  $om->NeedPermission(KS_PERM_DATA_ADMIN);
*/

// MODULE SPEC ARRAY

$arDropin = array(
  'name'	=> 'syslog',
  'descr'	=> 'system event logging',
  'version'	=> '0.0',
  'date'	=> '2013-12-07',
  'URL'		=> NULL,	// nothing yet
  'classes'	=> array(	// list of files and the classes they contain
    'syslog.php'	=> array(KS_CLASS_EVENT_LOG)
     ),
  'menu'	=> $om,
  );
