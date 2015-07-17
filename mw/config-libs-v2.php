<?php
/*
  PURPOSE: Ferreteria MediaWiki library, version 2
  REQUIRES: SMW_SCHEMA_VERSION must be defined as '2' or '3'
  HISTORY:
    2015-03-14 this library index file created
    2015-04-29 added check for SMW_SCHEMA_VERSION - SMW library is optional
    2015-05-08 moved common (version-independent) files into config-libs-both.php
*/

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

require_once($fp.'/config-libs-both.php');

$om = new clsModule(__FILE__, 'db-conn-mw.php');
  $om->AddClass('fcDataConn_MW');

  if (defined('SMW_SCHEMA_VERSION')) {
    $om = new clsModule(__FILE__, 'smw/SMWv'.SMW_SCHEMA_VERSION.'/db-conn-smw.php');
        $om->AddClass('fcDataConn_SMW');
        $om->AddClass('w3smwPage');
}