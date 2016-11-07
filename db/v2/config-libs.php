<?php
/*
  PURPOSE: Ferreteria database libraries, version 2
  HISTORY:
    2015-03-14 created
*/

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');
clsLibrary::BasePath($fp.'/');

$oL = new clsLibrary('ferreteria.db.mw','mw/config-libs.php');

$om = new clsModule(__FILE__, 'db.php');
  $om->AddClass('fcDBOFactory');
$om = new clsModule(__FILE__, 'db-conn.php');
  $om->AddClass('fcDataConn');
  $om->AddClass('fcDataConn_CliSrv');
$om = new clsModule(__FILE__, 'db-conn-mysqli.php');
  $om->AddClass('fcDataConn_MySQL');
$om = new clsModule(__FILE__, 'db-records.php');
  $om->AddClass('fcDataRecord');
$om = new clsModule(__FILE__, 'db-records-indexed.php');
  $om->AddClass('fcDataRecord_indexed');
$om = new clsModule(__FILE__, 'db-records-keyed.php');
  $om->AddClass('fcRecord_keyed_single_integer');
  $om->AddClass('fcRecord_keyed_single_string');
$om = new clsModule(__FILE__, 'db-records-savable.php');
  $om->AddClass('ftSaveableRecord');
$om = new clsModule(__FILE__, 'db-records-unique.php');
  $om->AddClass('ftUniqueRecords');
$om = new clsModule(__FILE__, 'db-sql-trait.php');
  $om->AddClass('ftQueryableTable');
$om = new clsModule(__FILE__, 'db-table.php');
  $om->AddClass('fcDataTable');
$om = new clsModule(__FILE__, 'db-table-indexed.php');
  $om->AddClass('fcTable_indexed');
$om = new clsModule(__FILE__, 'db-table-keyed.php');
  $om->AddClass('fcTable_keyed_single');
  $om->AddClass('fcTable_keyed_single_standard');
$om = new clsModule(__FILE__, 'db-table-unique.php');
  $om->AddClass('ftUniqueRowsTable');

$om = new clsModule(__FILE__, 'dbt-events.php');
  $om->AddClass('fcrEvent');
  $om->AddClass('ftLoggableRecord');
$om = new clsModule(__FILE__, 'dbt-settings.php');
  $om->AddClass('fcSettingsTable');
  $om->AddClass('fcSettingsTable_standard');

$om = new clsModule(__FILE__, 'object-urls.php');
  $om->AddClass('ftLinkableRecord');
  $om->AddClass('ftLinkableTable');
