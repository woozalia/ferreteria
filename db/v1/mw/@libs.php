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
fcCodeModule::BasePath($fp.'/');

$om = new fcCodeModule(__FILE__, 'admin.php');
  $om->AddClass('clsAdminTable');
  $om->AddClass('clsAdminData');
$om = new fcCodeModule(__FILE__, 'app-mw-data.php');
  $om->AddClass('cDataRecord_MW');
$om = new fcCodeModule(__FILE__, 'data-mw.php');
  $om->AddClass('clsMWData');

