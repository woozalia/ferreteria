<?php
/*
  PURPOSE: Ferreteria MediaWiki library, version 2
  REQUIRES: SMW_SCHEMA_VERSION must be defined as '2' or '3'
  HISTORY:
    2015-03-14 this library index file created
    2015-04-29 added check for SMW_SCHEMA_VERSION - SMW library is optional
    2015-05-08 moved common (version-independent) files into config-libs-both.php
    2017-10-30 some renaming in mw-props.php
*/

$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');

$om = new fcCodeModule(__FILE__, 'db-conn-mw.php');
  $om->AddClass('fcDataConn_MW');
$om = new fcCodeModule(__FILE__, 'mw-props.php');
  $om->AddClass('fcMWSiteProperties');

if (defined('SMW_SCHEMA_VERSION')) {
    $om = new fcCodeModule(__FILE__, 'smw/SMWv'.SMW_SCHEMA_VERSION.'/db-conn-smw.php');
        $om->AddClass('fcDataConn_SMW');
        $om->AddClass('w3smwPage');
}