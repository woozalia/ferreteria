<?php
/*
PURPOSE: Ferreteria MediaWiki library, version 1
HISTORY:
  2013-08-29 created
  2014-10-25 modified for MW libs on Cloud1
  2015-03-14 renamed config-libs -> config-libs-v1
*/
$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');
//clsModule::DebugMode(TRUE);

$om = new clsModule(__FILE__, 'admin.php');
  $om->AddClass('clsAdminData');
$om = new clsModule(__FILE__, 'app-mw.php');
  $om->AddClass('clsApp_MW');
$om = new clsModule(__FILE__, 'data-mw.php');
  $om->AddClass('clsMWData');
$om = new clsModule(__FILE__, 'menu.php');
  $om->AddClass('SpecialPageApp');
$om = new clsModule(__FILE__, 'richtext.php');
//  $om->AddClass('clsRichText');
  $om->AddClass('clsRT_HTML');
  $om->AddClass('clsRT_Wiki');
