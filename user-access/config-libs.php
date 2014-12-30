<?php
/*
PURPOSE: define locations for libraries using modloader.php
FILE SET: user-access
HISTORY:
  2014-01-11 created
*/

define('KS_CLASS_USER_GROUPS',		'clsUserGroups');
//define('KS_CLASS_USER_GROUP',		'clsUserGroup');
define('KS_CLASS_USER_PERMISSIONS',	'clsUserPerms');
define('KS_CLASS_USER_PERMISSION',	'clsUserPerm');
define('KS_CLASS_UACCT_X_UGROUP',	'clsUAcct_x_UGroup');
define('KS_CLASS_UGROUP_X_UPERM',	'clsUGroup_x_UPerm');

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

$om = new clsModule(__FILE__, 'user-acct.php');
  $om->AddClass('clsUserAccts');
$om = new clsModule(__FILE__, 'user-group.php');
  $om->AddClass(KS_CLASS_USER_GROUPS);
  $om->AddClass('clsUserGroup');
$om = new clsModule(__FILE__, 'user-perm.php');
  $om->AddClass(KS_CLASS_USER_PERMISSIONS);
  $om->AddClass('clsUserPerm');
$om = new clsModule(__FILE__, 'user-acct-x-group.php');
  $om->AddClass(KS_CLASS_UACCT_X_UGROUP);
$om = new clsModule(__FILE__, 'user-client.php');
  $om->AddClass('clsUserClients');
$om = new clsModule(__FILE__, 'user-session.php');
  $om->AddClass('clsUserSessions');
$om = new clsModule(__FILE__, 'user-token.php');
  $om->AddClass('clsUserTokens');
$om = new clsModule(__FILE__, 'user-group-x-perm.php');
  $om->AddClass(KS_CLASS_UGROUP_X_UPERM);
