<?php
/*
  PURPOSE: MediaWiki interface classes -- version-independent files
  HISTORY:
    2015-05-08 created to remove duplication in config-libs-v1 and config-libs-v2
*/

$om = new clsModule(__FILE__, 'app-specialpage.php');
  $om->AddClass('SpecialPageApp');
$om = new clsModule(__FILE__, 'mw-page.php');
  $om->AddClass('fcPage_MW');
$om = new clsModule(__FILE__, 'page-section.php');
  $om->AddClass('clsWikiFormatter');
$om = new clsModule(__FILE__, 'page-section-v3.php');
  $om->AddClass('fcSectionHeader_MW');
  $om->AddClass('fcSectionMenuItem');
$om = new clsModule(__FILE__, 'richtext.php');
  $om->AddClass('clsRT_HTML');
  $om->AddClass('clsRT_Wiki');
