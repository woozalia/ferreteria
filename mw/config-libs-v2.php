<?php
/*
  PURPOSE: Ferreteria MediaWiki library, version 2
  REQUIRES: SMW_SCHEMA_VERSION must be defined as '2' or '3'
  HISTORY:
    2015-03-14 this library index file created
*/

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

$om = new clsModule(__FILE__, 'db-conn-mw.php');
  $om->AddClass('fcDataConn_MW');
$om = new clsModule(__FILE__, 'menu.php');
  $om->AddClass('SpecialPageApp');
$om = new clsModule(__FILE__, 'richtext.php');
  $om->AddClass('clsRT_HTML');
  $om->AddClass('clsRT_Wiki');
$om = new clsModule(__FILE__, 'smw/SMWv'.SMW_SCHEMA_VERSION.'/db-conn-smw.php');
  $om->AddClass('fcDataConn_SMW');
  $om->AddClass('w3smwPage');
  