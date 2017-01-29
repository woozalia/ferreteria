<?php
/*
  PURPOSE: MediaWiki interface classes -- version-independent files
  HISTORY:
    2015-05-08 created to remove duplication in config-libs-v1 and config-libs-v2
    2015-09-24 renamed config-libs-both.php -> config-libs.php after moving db-dependent mw libs into separate folders
*/

$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');

$om = new fcCodeModule(__FILE__, 'app-mw.php');
  $om->AddClass('clsApp_MW');
$om = new fcCodeModule(__FILE__, 'app-specialpage.php');
  $om->AddClass('SpecialPageApp');
  $om->AddClass('SpecialPage_DataMenu');
$om = new fcCodeModule(__FILE__, 'menu.php');
  $om->AddClass('clsMenu');
$om = new fcCodeModule(__FILE__, 'mw-page.php');
  $om->AddClass('fcPage_MW');
$om = new fcCodeModule(__FILE__, 'page-section.php');
  $om->AddClass('clsWikiFormatter');
$om = new fcCodeModule(__FILE__, 'page-section-v3.php');
  $om->AddClass('fcSectionHeader_MW');
  $om->AddClass('fcSectionMenuItem');
$om = new fcCodeModule(__FILE__, 'richtext.php');
  $om->AddClass('clsRT_HTML');
  $om->AddClass('clsRT_Wiki');
