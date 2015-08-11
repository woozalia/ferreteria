<?php
/*
  PURPOSE: Ferreteria MediaWiki library, version 1
  HISTORY:
    2013-08-29 created
    2014-10-25 modified for MW libs on Cloud1
    2015-03-14 renamed config-libs -> config-libs-v1
    2015-05-08 moved common (version-independent) files into config-libs-both.php
*/
$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

require_once($fp.'/config-libs-both.php');

$om = new clsModule(__FILE__, 'admin.php');
  $om->AddClass('clsAdminTable');
  $om->AddClass('clsAdminData');
$om = new clsModule(__FILE__, 'app-mw.php');
  $om->AddClass('clsApp_MW');
  $om->AddClass('cDataRecord_MW');
$om = new clsModule(__FILE__, 'data-mw.php');
  $om->AddClass('clsMWData');

