<?php
/*
  PURPOSE: Ferreteria database libraries, version 1
  HISTORY:
    2015-03-14 split off from main Ferreteria config-libs
*/

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

$oL = new clsLibrary('ferreteria.db.mw','mw/config-libs.php');

// CORE DATABASE CLASSES

$om = new clsModule(__FILE__, 'data.php');
  $om->AddClass('clsDatabase_abstract');
  $om->AddClass('clsDatabase');
  //$om->AddFunc('SQLValue');
  //$om->AddFunc('NzArray');
$om = new clsModule(__FILE__, 'data-chained.php');
  $om->AddClass('clsTable_chained');
$om = new clsModule(__FILE__, 'data-engine.php');
  $om->AddClass('clsDataEngine');
$om = new clsModule(__FILE__, 'data-engine-clisrv.php');
  $om->AddClass('clsDataEngine_CliSrv');
$om = new clsModule(__FILE__, 'mysqli/data-engine-mysqli.php');
  $om->AddClass('clsDataEngine_MySQLi');
$om = new clsModule(__FILE__, 'data-result.php');
  $om->AddClass('clsDataResult');
$om = new clsModule(__FILE__, 'mysqli/data-result-mysqli.php');
  $om->AddClass('clsDataResult_MySQLi');
$om = new clsModule(__FILE__, 'data-indexed.php');
  $om->AddClass('clsTable_indexed');
$om = new clsModule(__FILE__, 'data-indexer.php');
  $om->AddClass('clsIndexer_Table');
  $om->AddClass('clsIndexer_Table_single_key');
  $om->AddClass('clsIndexer_Table_multi_key');
$om = new clsModule(__FILE__, 'data-records.php');
  $om->AddClass('clsDataSet');
  $om->AddClass('clsRecs_keyed_abstract');
  $om->AddClass('clsRecs_key_single');
$om = new clsModule(__FILE__, 'data-rec-savable.php');
  $om->AddClass('ftSaveableRecord');
$om = new clsModule(__FILE__, 'data-table.php');
  $om->AddClass('clsTable_abstract');
  $om->AddClass('clsTable');
  $om->AddClass('clsTable_keyed_abstract');
  $om->AddClass('clsTable_key_single');
$om = new clsModule(__FILE__, 'db-ui.php');
  $om->AddClass('ftShowableRecord');

// DEPENDENT CLASSES

$om = new clsModule(__FILE__, 'menu-data.php');
  $om->AddClass('clsDataTable_Menu');
  $om->AddClass('clsDataRecord_Menu');
  $om->AddClass('clsDataRecord_admin');
  $om->AddClass('ftLoggableRecord');
