<?php
/*
  PURPOSE: Ferreteria database libraries, version 2
  HISTORY:
    2015-03-14 created
*/

$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');
fcCodeLibrary::BasePath($fp.'/');

$oL = new fcCodeLibrary('ferreteria.db.mw','mw/@libs.php');

$om = new fcCodeModule(__FILE__, 'db.php');
  $om->AddClass('fcDBOFactory');
$om = new fcCodeModule(__FILE__, 'db-conn.php');
  $om->AddClass('fcDataConn');
  $om->AddClass('fcDataConn_CliSrv');
$om = new fcCodeModule(__FILE__, 'db-conn-mysqli.php');
  $om->AddClass('fcDataConn_MySQL');
$om = new fcCodeModule(__FILE__, 'db-sql-trait.php');
  $om->AddClass('ftQueryableTable');
$om = new fcCodeModule(__FILE__, 'db-tr-array.php');
  $om->AddClass('fcDataRow_array');
  $om->AddClass('fcDataTable_array');
$om = new fcCodeModule(__FILE__, 'db-tr-multikey.php');
  $om->AddClass('fcTable_keyed_multi');
$om = new fcCodeModule(__FILE__, 'db-ui.php');
  $om->AddClass('ftShowableRecord');
  
$om = new fcCodeModule(__FILE__, 'dbt-events.php');
  $om->AddClass('fctEvents');
  $om->AddClass('fcrEvent');
  $om->AddClass('fiEventTable');
  $om->AddClass('ftLoggableTable');
  $om->AddClass('ftLoggableRecord');
  $om->AddClass('ftLoggedRecord');
$om = new fcCodeModule(__FILE__, 'dbt-settings.php');
  $om->AddClass('fcSettingsTable');
  $om->AddClass('fcSettingsTable_standard');
  
$om = new fcCodeModule(__FILE__, 'form-data.php');
  $om->AddClass('fcForm_DB');
  $om->AddClass('fcForm_blob');
  $om->AddClass('fcBlobField');
  $om->AddClass('fiEditableRecord');

$om = new fcCodeModule(__FILE__, 'object-urls.php');
  $om->AddClass('ftLinkableRecord');
  $om->AddClass('ftLinkableTable');
  
// EVENTS folder
$om = new fcCodeModule(__FILE__, 'events/event-plex.php');
  $om->AddClass('fctEventPlex');
  $om->AddClass('fctEventPlex_standard');
  $om->AddClass('fctSubEvents');
  $om->AddClass('fctSubEvents_done');
  $om->AddClass('fctSubEvents_InTable');

// TABLES folder
$om = new fcCodeModule(__FILE__, 'tables/db-table.php');
  $om->AddClass('fcTable_wSource');
  $om->AddClass('fcTable_wRecords');
  $om->AddClass('fcTable_wSource_wRecords');
  $om->AddClass('fcTable_wName_wSource_wRecords');
 $om = new fcCodeModule(__FILE__, 'tables/db-table-cache.php');
  $om->AddClass('ftCacheableTable');
$om = new fcCodeModule(__FILE__, 'tables/db-table-indexed.php');
  $om->AddClass('fcTable_indexed');
$om = new fcCodeModule(__FILE__, 'tables/db-table-keyed.php');
  $om->AddClass('fcTable_keyed');
  $om->AddClass('fcTable_keyed_single');
  $om->AddClass('fcTable_keyed_single_standard');
$om = new fcCodeModule(__FILE__, 'tables/db-table-unique.php');
  $om->AddClass('ftUniqueRowsTable');

// RECORDS folder
$om = new fcCodeModule(__FILE__, 'records/db-records.php');
  $om->AddClass('fcSourcedDataRow');
  $om->AddClass('fcDataRecord');
  $om->AddClass('fcDataRow');
$om = new fcCodeModule(__FILE__, 'records/db-records-indexed.php');
  $om->AddClass('fcDataRecord_indexed');
$om = new fcCodeModule(__FILE__, 'records/db-records-keyed.php');
  $om->AddClass('fcRecord_keyed');
  $om->AddClass('fcRecord_keyed_single_integer');
  $om->AddClass('fcRecord_keyed_single_string');
  $om->AddClass('fcRecord_standard');
$om = new fcCodeModule(__FILE__, 'records/db-records-savable.php');
  $om->AddClass('ftSaveableRecord');
$om = new fcCodeModule(__FILE__, 'records/db-records-unique.php');
  $om->AddClass('ftUniqueRecords');
