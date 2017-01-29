<?php
/*
  PURPOSE: Ferreteria database libraries, version 1
  HISTORY:
    2015-03-14 split off from main Ferreteria config-libs
*/

$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');

$oL = new fcCodeLibrary('ferreteria.db.mw','mw/@libs.php');

// CORE DATABASE CLASSES

// data
$om = new fcCodeModule(__FILE__, 'data.php');
  $om->AddClass('fcDatabase_abstract');
  $om->AddClass('fcDatabase');

// table records-cache
$om = new fcCodeModule(__FILE__, 'widgets/data-cache.php');
  $om->AddClass('clsTableCache');
  
// data-chained
$om = new fcCodeModule(__FILE__, 'data-chained.php');
  $om->AddClass('fcTable_chained');

// data-engine
  $om = new fcCodeModule(__FILE__, 'data-engine.php');
  $om->AddClass('fcDataEngine');

// data-engine-clisrv
$om = new fcCodeModule(__FILE__, 'data-engine-clisrv.php');
  $om->AddClass('fcDataEngine_CliSrv');

// data-engine-mysqli
$om = new fcCodeModule(__FILE__, 'mysqli/data-engine-mysqli.php');
  $om->AddClass('fcDataEngine_MySQLi');

// data-indexed
$om = new fcCodeModule(__FILE__, 'data-indexed.php');
  $om->AddClass('fcTable_indexed');

// data-indexer
$om = new fcCodeModule(__FILE__, 'data-indexer.php');
  $om->AddClass('fcIndexer_Table');
  $om->AddClass('fcIndexer_Table_single_key');
  $om->AddClass('fcIndexer_Table_multi_key');

// data-records
$om = new fcCodeModule(__FILE__, 'data-records.php');
  $om->AddClass('fcDataSet');
  $om->AddClass('fcRecs_keyed_abstract');
  $om->AddClass('fcRecs_key_single');

// data-result
$om = new fcCodeModule(__FILE__, 'data-result.php');
  $om->AddClass('fcDataResult');

// data-result-mysqli
$om = new fcCodeModule(__FILE__, 'mysqli/data-result-mysqli.php');
  $om->AddClass('fcDataResult_MySQLi');

// data-table
$om = new fcCodeModule(__FILE__, 'data-rec-savable.php');
  $om->AddClass('ftSaveableRecord');
$om = new fcCodeModule(__FILE__, 'data-table.php');
  $om->AddClass('fcTable_abstract');
  $om->AddClass('fcTable');
  $om->AddClass('fcTable_keyed_abstract');
  $om->AddClass('fcTable_key_single');

// db-SQL
$om = new fcCodeModule(__FILE__, 'data-sql-trait.php');
  $om->AddClass('QueryableTable');

// db-ui
$om = new fcCodeModule(__FILE__, 'db-ui.php');
  $om->AddClass('ftShowableRecord');

// DEPENDENT CLASSES

$om = new fcCodeModule(__FILE__, 'events.php');
  $om->AddClass('clsSysEvents');
  $om->AddClass('clsSysEvents_abstract');
  $om->AddClass('clsLogger_DataSet');
  
$om = new fcCodeModule(__FILE__, 'form-data.php');
  $om->AddClass('fcForm_DB');
  $om->AddClass('fcForm_blob');
  $om->AddClass('fcBlobField');

$om = new fcCodeModule(__FILE__, 'menu-data.php');
  $om->AddClass('fcDataTable_Menu');
  $om->AddClass('fcDataRecord_Menu');
  $om->AddClass('fcDataRecord_admin');
  $om->AddClass('ftLoggableRecord');

$om = new fcCodeModule(__FILE__, 'object-urls.php');
  $om->AddClass('ftLinkableRecord');
  $om->AddClass('ftLinkableTable');
