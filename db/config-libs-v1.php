<?php
/*
  PURPOSE: Ferreteria database libraries, version 1
  HISTORY:
    2015-03-14 split off from main Ferreteria config-libs
*/

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

$om = new clsModule(__FILE__, 'data.php');
  $om->AddClass('clsDatabase_abstract');
  $om->AddClass('clsDatabase');
  $om->AddFunc('SQLValue');
  $om->AddFunc('NzArray');
$om = new clsModule(__FILE__, 'data-chained.php');
  $om->AddClass('clsTable_chained');
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
$om = new clsModule(__FILE__, 'data-table.php');
  $om->AddClass('clsTable_abstract');
  $om->AddClass('clsTable');
  $om->AddClass('clsTable_keyed_abstract');
  $om->AddClass('clsTable_key_single');
