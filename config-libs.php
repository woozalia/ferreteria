<?php
/*
PURPOSE: define locations for libraries using modloader.php
FILE SET: common libraries
HISTORY:
  2013-08-29 created
*/
//require_once('../modloader.php');

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

$om = new clsModule(__FILE__, 'app.php');
  $om->AddClass('clsApp');
  $om->AddClass('cAppStandard');
  $om->AddClass('clsDatabase_UserAuth');
$om = new clsModule(__FILE__, 'crypt.php');
  $om->AddClass('Cipher');
  $om->AddClass('Cipher_pubkey');


$om = new clsModule(__FILE__, 'dropin.php');
  $om->AddClass('clsDropInManager');
$om = new clsModule(__FILE__, 'dtree.php');
  $om->AddClass('clsDTreeAPI');
$om = new clsModule(__FILE__, 'events.php');
  $om->AddClass('clsSysEvents');
  $om->AddClass('clsSysEvents_abstract');
  $om->AddClass('clsLogger_DataSet');
$om = new clsModule(__FILE__, 'form-data.php');
  $om->AddClass('clsForm_recs');
  $om->AddClass('clsForm_recs_indexed');
$om = new clsModule(__FILE__, 'menu.php');
  $om->AddClass('clsMenuRegistry');
  $om->AddClass('clsMenuItem');
  $om->AddClass('clsMenuLink');
$om = new clsModule(__FILE__, 'menu-data.php');
  $om->AddClass('clsDataTable_Menu');
  $om->AddClass('clsDataRecord_Menu');
$om = new clsModule(__FILE__, 'page.php');
  $om->AddClass('clsPageLogin');
  $om->AddClass('clsPageRestricted');
$om = new clsModule(__FILE__, 'skin.php');
  $om->AddClass('clsSkin');
  $om->AddClass('clsSkin_standard');
$om = new clsModule(__FILE__, 'skin-login.php');
  $om->AddClass('clsSkin_login');

// DATABASE LIBRARIES
$om = new clsModule(__FILE__, 'db/data.php');
  $om->AddClass('clsDatabase_abstract');
  $om->AddClass('clsDatabase');
  $om->AddFunc('SQLValue');
  $om->AddFunc('NzArray');
$om = new clsModule(__FILE__, 'db/data-chained.php');
  $om->AddClass('clsTable_chained');
$om = new clsModule(__FILE__, 'db/data-indexed.php');
  $om->AddClass('clsTable_indexed');
$om = new clsModule(__FILE__, 'db/data-indexer.php');
  $om->AddClass('clsIndexer_Table');
  $om->AddClass('clsIndexer_Table_single_key');
  $om->AddClass('clsIndexer_Table_multi_key');
$om = new clsModule(__FILE__, 'db/data-records.php');
  $om->AddClass('clsDataSet');
  $om->AddClass('clsRecs_keyed_abstract');
  $om->AddClass('clsRecs_key_single');
$om = new clsModule(__FILE__, 'db/data-table.php');
  $om->AddClass('clsTable_abstract');
  $om->AddClass('clsTable');
  $om->AddClass('clsTable_keyed_abstract');
  $om->AddClass('clsTable_key_single');

// DEPRECATED
$om = new clsModule(__FILE__, 'deprecated/user-mw.php');
  $om->AddClass('clsUser');

// RICH TEXT HANDLERS
$om = new clsModule(__FILE__, 'rtext/rtext.php');
  $om->AddClass('clsRTDoc');
$om = new clsModule(__FILE__, 'rtext/rtext-html.php');
  $om->AddClass('clsRTDoc_HTML');
  $om->AddClass('clsNavList');

// UTILITY LIBRARIES
$om = new clsModule(__FILE__, 'util/array.php');
  $om->AddClass('clsArray');
$om = new clsModule(__FILE__, 'util/cache.php');
  $om->AddClass('clsCacheFile');
$om = new clsModule(__FILE__, 'util/forms.php');
  $om->AddClass('clsCtrls');
  $om->AddClass('clsFieldNum');
  $om->AddClass('clsWidget_ShortList');
$om = new clsModule(__FILE__, 'util/html.php');
  $om->AddClass('clsHTML');
$om = new clsModule(__FILE__, 'util/http.php');
  $om->AddClass('clsHTTP');
  $om->AddClass('clsHTTPInput');
$om = new clsModule(__FILE__, 'util/money.php');
  $om->AddClass('clsMoney');
$om = new clsModule(__FILE__, 'util/strings.php');
  $om->AddClass('clsString');
  $om->AddClass('xtString');
  $om->AddFunc('Xplode');
$om = new clsModule(__FILE__, 'util/StringTemplate.php');
  $om->AddClass('clsStringTemplate');
  $om->AddClass('clsStringTemplate_array');
$om = new clsModule(__FILE__, 'util/time.php');
  $om->AddClass('clsDate');
  $om->AddFunc('Time_DefaultDate');
  $om->AddFunc('Date_DefaultYear');
$om = new clsModule(__FILE__, 'util/tree.php');
  $om->AddClass('clsTreeNode');
$om = new clsModule(__FILE__, 'util/url.php');
  $om->AddClass('clsURL');

// WIDGETS
$om = new clsModule(__FILE__, 'widgets/data-cache.php');
  $om->AddClass('clsTableCache');
$om = new clsModule(__FILE__, 'widgets/menu-action.php');
  $om->AddClass('clsActionLink_option');
$om = new clsModule(__FILE__, 'widgets/menu-helper.php');
  $om->AddClass('clsMenuData_helper');
$om = new clsModule(__FILE__, 'widgets/navbar.php');
  $om->AddClass('clsNavbar_flat');
  $om->AddClass('clsNavbar_tree');

if (defined('KF_WANT_LIB_USER_ACCESS')) {
    require_once('user-access/config-libs.php');
}