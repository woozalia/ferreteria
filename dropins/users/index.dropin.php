<?php
/*
  PURPOSE: VbzCart drop-in descriptor for user access management
  HISTORY:
    2013-12-18 started
    2015-04-18 removed inclusion of const.php; file has been moved to site config folder.
*/

// ACTIONS

define('KS_ACTION_USER_ACCOUNT',	'uacct');
define('KS_ACTION_USER_GROUP',		'ugrp');
define('KS_ACTION_USER_PERMISSION',	'uperm');
define('KS_ACTION_USER_SESSION',	'usess');
define('KS_ACTION_USER_CLIENT',	'ucli');

// CLASS NAMES

define('KS_CLASS_ADMIN_USER_ACCOUNTS',		'fctUserAccts_admin');
define('KS_CLASS_ADMIN_USER_ACCOUNT',		'fcrUserAcct_admin');
define('KS_CLASS_ADMIN_USER_GROUPS',		'fctAdminUserGroups');
define('KS_CLASS_ADMIN_USER_GROUP',		'fcrAdminUserGroup');
define('KS_CLASS_ADMIN_USER_PERMISSIONS',	'fctAdminUserPermits');
define('KS_CLASS_ADMIN_USER_PERMISSION',	'fcrAdminUserPermit');
define('KS_CLASS_ADMIN_UGROUPS_FOR_UACCT',	'fctUGroups_for_UAcct_admin');
define('KS_CLASS_ADMIN_UPERMITS_FOR_UGROUP',	'fctUPermits_for_UGroup_admin');
define('KS_CLASS_ADMIN_USER_SESSIONS',		'fctAdminUserSessions');
define('KS_CLASS_ADMIN_USER_SESSION',		'fcrAdminUserSession');
define('KS_CLASS_ADMIN_USER_CLIENTS',		'fctUserClientsAdmin');
define('KS_CLASS_ADMIN_USER_CLIENT',		'fcrUserClientAdmin');


// MENU

$om = $oRoot->SetNode(new fcMenuFolder('User Permissions','User Access Management','user/group security management'));

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_USER_ACCOUNT,
    KS_CLASS_ADMIN_USER_ACCOUNTS,
    'Users','user account management'));
    
    $omi->SetPageTitle('User Accounts');
    $omi->SetRequiredPrivilege(KS_PERM_SEC_USER_VIEW);
    //$omi->SetRequiredPrivilege(NULL);

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_USER_GROUP,
    KS_CLASS_ADMIN_USER_GROUPS,
    'Groups','security groups to which users can belong'));

    $omi->SetPageTitle('Security Groups');
    $omi->SetRequiredPrivilege(KS_USER_SEC_GROUP_VIEW);

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_USER_PERMISSION,
    KS_CLASS_ADMIN_USER_PERMISSIONS,
    'Perms','security permissions assignable to groups'));

    $omi->SetPageTitle('Security Permissions');
    $omi->SetRequiredPrivilege(KS_USER_SEC_PERM_VIEW);

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_USER_SESSION,
    KS_CLASS_ADMIN_USER_SESSIONS,
    'Sess','user session records'));

    $omi->SetPageTitle('User Sessions');
    $omi->SetRequiredPrivilege(KS_PERM_USER_CONN_DATA);

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_USER_CLIENT,
    KS_CLASS_ADMIN_USER_CLIENTS,
    'Clients','user web client records'));

    $omi->SetPageTitle('User Clients');
    $omi->SetRequiredPrivilege(KS_PERM_USER_CONN_DATA);

// MODULE SPEC ARRAY

$arDropin = array(
  'name'	=> 'ferreteria.users',
  'descr'	=> 'user/group security management',
  'version'	=> '0.9.2',
  'date'	=> '2017-03-25',
  'URL'		=> NULL,	// nothing yet
  'classes'	=> array(	// list of files and the classes they contain
    'uacct.php'			=> array(KS_CLASS_ADMIN_USER_ACCOUNTS),
    'ugroup.php'		=> array(KS_CLASS_ADMIN_USER_GROUPS,KS_CLASS_ADMIN_USER_GROUP),
    'uperm.php'			=> array(KS_CLASS_ADMIN_USER_PERMISSIONS,KS_CLASS_ADMIN_USER_PERMISSION),
    'usess.php'			=> array(KS_CLASS_ADMIN_USER_SESSIONS),
    'uclient.php'		=> array(KS_CLASS_ADMIN_USER_CLIENTS),
    'uacct-x-ugroup.php'	=> array(KS_CLASS_ADMIN_UGROUPS_FOR_UACCT),
    'ugroup-x-uperm.php'	=> array(KS_CLASS_ADMIN_UPERMITS_FOR_UGROUP),
     ),
  'menu'	=> $om,
  'features'	=> array(
    KS_FEATURE_USER_SECURITY,
    KS_FEATURE_USER_ACCOUNT_ADMIN,
    KS_FEATURE_USER_SECURITY_ADMIN,
    KS_FEATURE_USER_SESSION_ADMIN
    ),
  );
