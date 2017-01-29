<?php
/*
PURPOSE: define locations for libraries using modloader.php
FILE SET: user-access
HISTORY:
  2014-01-11 created
*/
define('KS_CLASS_USER_SESSIONS',	'fctUserSessions');
define('KS_CLASS_USER_SESSION',		'fcrUserSession');
define('KS_CLASS_USER_GROUPS',		'fctUserGroups');
define('KS_CLASS_USER_GROUP',		'fcrUserGroup');
define('KS_CLASS_USER_PERMISSIONS',	'fctUserPerms');
define('KS_CLASS_USER_PERMISSION',	'fcrUserPermit');
define('KS_CLASS_GROUPS_FOR_UACCT',	'fctUGroups_for_UAcct');
define('KS_CLASS_UPERMITS_FOR_UGROUP',	'fctUPermits_for_UGroup');

$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');

$om = new fcCodeModule(__FILE__, 'user-acct.php');
  $om->AddClass('fctUserAccts');
$om = new fcCodeModule(__FILE__, 'user-acct-x-group.php');
  $om->AddClass(KS_CLASS_GROUPS_FOR_UACCT);
$om = new fcCodeModule(__FILE__, 'user-client.php');
  $om->AddClass('clsUserClients');
$om = new fcCodeModule(__FILE__, 'user-group.php');
  $om->AddClass(KS_CLASS_USER_GROUPS);
  $om->AddClass(KS_CLASS_USER_GROUP);
$om = new fcCodeModule(__FILE__, 'user-group-x-perm.php');
  $om->AddClass(KS_CLASS_UPERMITS_FOR_UGROUP);
$om = new fcCodeModule(__FILE__, 'user-perm.php');
  $om->AddClass(KS_CLASS_USER_PERMISSIONS);
  $om->AddClass(KS_CLASS_USER_PERMISSION);
$om = new fcCodeModule(__FILE__, 'user-query-perm.php');
  $om->AddClass('fcqtUserPerms');
$om = new fcCodeModule(__FILE__, 'user-session.php');
  $om->AddClass(KS_CLASS_USER_SESSIONS);
$om = new fcCodeModule(__FILE__, 'user-token.php');
  $om->AddClass('fcUserTokens');
