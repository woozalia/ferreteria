<?php
/*
  PURPOSE: MediaWiki interface classes -- version-independent files
  HISTORY:
    2015-05-08 created to remove duplication in config-libs-v1 and config-libs-v2
    2015-09-24 renamed config-libs-both.php -> config-libs.php after moving db-dependent mw libs into separate folders
    2018-03-12 obsoleted menu.php, page-section.php, page-section-v3.php
*/
$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');

$om = new fcCodeModule(__FILE__, 'app-mw.php');
  $om->AddClass('ferreteria\mw\fcApp_MW');
/*
$om = new fcCodeModule(__FILE__, 'menu.php');
  $om->AddClass('clsMenu');
*/
$om = new fcCodeModule(__FILE__, 'data-page.php');
  $om->AddClass('fcPageData_MW');
$om = new fcCodeModule(__FILE__, 'kiosk.php');
  $om->AddClass('ferreteria\mw\fcMenuKiosk_MW');
/*
$om = new fcCodeModule(__FILE__, 'page-section.php');
  $om->AddClass('clsWikiFormatter');
$om = new fcCodeModule(__FILE__, 'page-section-v3.php');
  $om->AddClass('fcSectionHeader_MW');
  $om->AddClass('fcSectionMenuItem');
  */
$om = new fcCodeModule(__FILE__, 'specialpage.php');
  $om->AddClass('ferreteria\mw\tSpecialPage');
$om = new fcCodeModule(__FILE__, 'richtext.php');
  $om->AddClass('clsRT_HTML');
  $om->AddClass('clsRT_Wiki');

// SMW - we'll use v3 by default; create override option only if needed

$om = new fcCodeModule(__FILE__, 'smw/v3/data-db.php');
  $om->AddClass('fcDataConn_SMW');
$om = new fcCodeModule(__FILE__, 'smw/v3/data-page.php');
  $om->AddClass('fcPageData_SMW');
$om = new fcCodeModule(__FILE__, 'smw/v3/data-prop.php');
  $om->AddClass('fcPropertyData_SMW');
$om = new fcCodeModule(__FILE__, 'smw/v3/data-tbl-di-blob.php');
  $om->AddClass('fctqSMW_Blob');
$om = new fcCodeModule(__FILE__, 'smw/v3/data-tbl-di-wikipage.php');
  $om->AddClass('fctqSMW_WikiPage');
$om = new fcCodeModule(__FILE__, 'smw/v3/data-tbl-di-time.php');
  $om->AddClass('fctqSMW_Time');
