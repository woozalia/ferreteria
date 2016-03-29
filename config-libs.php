<?php
/*
PURPOSE: define locations for libraries using modloader.php
FILE SET: common libraries
HISTORY:
  2013-08-29 created
  2015-03-14 extracted data modules from here into separate library
*/

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');
clsLibrary::BasePath($fp.'/');

// database library indexes
$oL = new clsLibrary('ferreteria.db.1','db/v1/config-libs.php');
$oL = new clsLibrary('ferreteria.db.2','db/v2/config-libs.php');
$oL = new clsLibrary('ferreteria.mw.core','mw/config-libs.php');
$oL = new clsLibrary('ferreteria.mw.1','db/v1/mw/config-libs.php');	// DEPRECATED; use ferreteria.db.mw
$oL = new clsLibrary('ferreteria.mw.2','db/v2/mw/config-libs.php');	// DEPRECATED; use ferreteria.db.mw
$oL = new clsLibrary('ferreteria.forms.2','forms/config-libs.php');
$oL = new clsLibrary('ferreteria.login','user-access/config-libs.php');

$om = new clsModule(__FILE__, 'app.php');
  $om->AddClass('clsApp');
  $om->AddClass('cAppStandard');
$om = new clsModule(__FILE__, 'app-user.php');
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
$om = new clsModule(__FILE__, 'page.php');
  $om->AddClass('clsPage');
  $om->AddClass('clsPageLogin');
  $om->AddClass('clsPageRestricted');
$om = new clsModule(__FILE__, 'page.menu.php');
  $om->AddClass('ftPageMenu');
$om = new clsModule(__FILE__, 'page-data.php');
  $om->AddClass('fcDataRecs');
$om = new clsModule(__FILE__, 'skin.php');
  $om->AddClass('clsSkin');
  $om->AddClass('clsSkin_standard');
$om = new clsModule(__FILE__, 'skin-login.php');
  $om->AddClass('clsSkin_login');

$om = new clsModule(__FILE__,'db/sql/db-sql.php');
  $om->AddClass('clsSQL_Query');	// DEPRECATED
  $om->AddClass('QueryableTable');	// trait
  $om->AddClass('fcSQL_Select');
  $om->AddClass('fcSQL_TableSource');
  $om->AddClass('fcSQLt_Filt');

// DEPRECATED
$om = new clsModule(__FILE__, 'deprecated/user-mw.php');
  $om->AddClass('clsUser');
  
// MENUS
$om = new clsModule(__FILE__, 'menu/menu-action.php');
  $om->AddClass('clsActionLink');
  $om->AddClass('clsActionLink_modeless');
  $om->AddClass('clsActionLink_option');
  $om->AddClass('clsAction_section');
$om = new clsModule(__FILE__, 'menu/menu-item.php');
  $om->AddClass('clsMenuItem');
  $om->AddClass('clsMenuLink');
  $om->AddClass('clsMenuRoot');
$om = new clsModule(__FILE__, 'menu/menu-manager.php');
  $om->AddClass('fcMenuMgr');
$om = new clsModule(__FILE__, 'menu/menu-mapper.php');
  $om->AddClass('fcMenuMap_Page');
$om = new clsModule(__FILE__, 'menu/menu-painter.php');
  $om->AddClass('clsMenuPainter_UL');

// RICH TEXT HANDLERS
$om = new clsModule(__FILE__, 'rtext/rtext.php');
  $om->AddClass('clsRTDoc');
$om = new clsModule(__FILE__, 'rtext/rtext-html.php');
  $om->AddClass('clsRTDoc_HTML');
  $om->AddClass('clsNavList');

// UTILITY LIBRARIES
$om = new clsModule(__FILE__, 'util/array.php');
  $om->AddClass('fcArray');
  $om->AddClass('clsArray');
$om = new clsModule(__FILE__, 'util/cache.php');
  $om->AddClass('clsCacheFile');
/* 2015-11-23 These classes are now deprecated.
$om = new clsModule(__FILE__, 'util/forms.php');
  $om->AddClass('clsCtrls');
  $om->AddClass('clsFieldNum');
  $om->AddClass('clsWidget_ShortList');
*/
$om = new clsModule(__FILE__, 'util/html.php');
  $om->AddClass('clsHTML');
$om = new clsModule(__FILE__, 'util/http.php');
  $om->AddClass('fcHTTP');
  $om->AddClass('clsHTTP');	// TODO: deprecate
  $om->AddClass('fcInputData_array');
  $om->AddClass('fcInputData_array_local');
$om = new clsModule(__FILE__, 'util/money.php');
  $om->AddClass('fcMoney');
  $om->AddClass('clsMoney');
$om = new clsModule(__FILE__, 'util/strings.php');
  $om->AddClass('fcString');
$om = new clsModule(__FILE__, 'util/string-block.php');
  $om->AddClass('fcStringBlock_static');
  $om->AddClass('fcsStringBlock');
  $om->AddClass('fcStringBlock');
$om = new clsModule(__FILE__, 'util/string-tplt.php');
  $om->AddClass('fcTemplate_array');
  $om->AddClass('clsStringTemplate');
  $om->AddClass('clsStringTemplate_array');
$om = new clsModule(__FILE__, 'util/string-xt.php');
  $om->AddClass('xtString_static');
  $om->AddClass('xtString');
//  $om->AddFunc('Xplode');
$om = new clsModule(__FILE__, 'util/time.php');
  $om->AddClass('clsDate');
  $om->AddClass('xtTime');
//  $om->AddFunc('Time_DefaultDate');
  $om->AddFunc('Date_DefaultYear');
$om = new clsModule(__FILE__, 'util/tree.php');
  $om->AddClass('clsTreeNode');
$om = new clsModule(__FILE__, 'util/url.php');
  $om->AddClass('clsURL');

// WIDGETS
$om = new clsModule(__FILE__, 'widgets/data-cache.php');
  $om->AddClass('clsTableCache');
$om = new clsModule(__FILE__, 'widgets/js.php');
  $om->AddClass('fcJavaScript');
$om = new clsModule(__FILE__, 'widgets/object-urls.php');
  $om->AddClass('ftLinkableRecord');
  $om->AddClass('ftLinkableTable');
$om = new clsModule(__FILE__, 'widgets/navbar.php');
  $om->AddClass('clsNavbar_flat');
  $om->AddClass('clsNavbar_tree');

