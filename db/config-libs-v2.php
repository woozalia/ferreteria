<?php
/*
  PURPOSE: Ferreteria database libraries, version 2
  HISTORY:
    2015-03-14 created
*/

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

$om = new clsModule(__FILE__, 'db.php');
  $om->AddClass('fcDBOFactory');
$om = new clsModule(__FILE__, 'db-conn.php');
  $om->AddClass('fcDataConn');
  $om->AddClass('fcDataConn_CliSrv');
$om = new clsModule(__FILE__, 'db-conn-mysqli.php');
  $om->AddClass('fcDataConn_MySQL');
//$om = new clsModule(__FILE__, 'db-result.php');
//  $om->AddClass('fcDataResult');
//$om = new clsModule(__FILE__, 'db-result-mysqli.php');
//  $om->AddClass('fcDataResult_MySQL');
$om = new clsModule(__FILE__, 'db-table.php');  
  $om->AddClass('fcDataTable');
$om = new clsModule(__FILE__, 'db-table-indexed.php');  
  $om->AddClass('fcDataTable_indexed');
$om = new clsModule(__FILE__, 'db-records.php');  
  $om->AddClass('fcDataRecord');
  