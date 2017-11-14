<?php
/*
PURPOSE: define locations for libraries using modloader.php
FILE SET: common libraries
HISTORY:
  2013-08-29 created
  2015-03-14 extracted data modules from here into separate library
*/

$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');
fcCodeLibrary::BasePath($fp.'/');

// database library indexes
$oL = new fcCodeLibrary('ferreteria.db.1','db/v1/@libs.php');
$oL = new fcCodeLibrary('ferreteria.db.2','db/v2/@libs.php');
$oL = new fcCodeLibrary('ferreteria.mw.core','mw/@libs.php');
$oL = new fcCodeLibrary('ferreteria.mw.1','db/v1/mw/@libs.php');	// DEPRECATED; use ferreteria.db.mw
$oL = new fcCodeLibrary('ferreteria.mw.2','db/v2/mw/@libs.php');	// DEPRECATED; use ferreteria.db.mw
$oL = new fcCodeLibrary('ferreteria.forms.2','forms/@libs.php');
$oL = new fcCodeLibrary('ferreteria.login','user-access/@libs.php');

$om = new fcCodeModule(__FILE__, 'app.php');
  $om->AddClass('fcApp');
  $om->AddClass('fcAppStandard');
/* 2017-01-16 appears to be unnecessary
$om = new fcCodeModule(__FILE__, 'app-user.php');
  $om->AddClass('fcDBOF_UserAuth');
*/
$om = new fcCodeModule(__FILE__, 'crypt.php');
  $om->AddClass('Cipher');
  $om->AddClass('Cipher_pubkey');

$om = new fcCodeModule(__FILE__, 'dropin.php');
  $om->AddClass('fcDropInManager');
$om = new fcCodeModule(__FILE__, 'dtree.php');
  $om->AddClass('fcDTreeAPI');
$om = new fcCodeModule(__FILE__, 'exception.php');
  $om->AddClass('fcSilentException');
$om = new fcCodeModule(__FILE__, 'globals.php');
  $om->AddClass('fcGlobals');
  $om->AddClass('ftSingleton');

$om = new fcCodeModule(__FILE__,'db/sql/db-sql.php');
  $om->AddClass('clsSQL_Query');	// DEPRECATED
  $om->AddClass('QueryableTable');	// trait
  $om->AddClass('fcSQL_Select');
  $om->AddClass('fcSQL_TableSource');
  $om->AddClass('fcSQLt_Filt');

// DEPRECATED
$om = new fcCodeModule(__FILE__, 'deprecated/user-mw.php');
  $om->AddClass('clsUser');
  
$om = new fcCodeModule(__FILE__, 'exception.php');
  $om->AddClass('fcDebugException');

// MENUS
$om = new fcCodeModule(__FILE__, 'menu/dropin.php');
  $om->AddClass('fcDropinLink');
$om = new fcCodeModule(__FILE__, 'menu/hdr.php');
  $om->AddClass('fcHeaderMenu');
  $om->AddClass('fcSectionHeader');
  $om->AddClass('fcMenuOptionLink');
/*
$om = new fcCodeModule(__FILE__, 'menu/menu-action.php');
  $om->AddClass('clsActionLink');
  $om->AddClass('clsActionLink_modeless');
  $om->AddClass('clsActionLink_option');
  $om->AddClass('clsAction_section'); */
$om = new fcCodeModule(__FILE__, 'menu/kiosk.php');
  $om->AddClass('fcMenuKiosk');
  $om->AddClass('fcMenuKiosk_admin');
$om = new fcCodeModule(__FILE__, 'menu/items.php');
  $om->AddClass('fcNavItem');
  $om->AddClass('fcNavLink');
  $om->AddClass('fcMenuFolder');
$om = new fcCodeModule(__FILE__, 'menu/nav.php');
  $om->AddClass('fcNavFolder');
  $om->AddClass('fcNavLinkFixed');

/* 2016-12-31 and now these as well
$om = new fcCodeModule(__FILE__, 'menu/menu-dropin.php');
  $om->AddClass('fcDropinFolder');
/* 2016-12-08 these too
$om = new fcCodeModule(__FILE__, 'menu/menu-item.php');
  $om->AddClass('fcMenuItem');
  $om->AddClass('fcMenuLink');
  $om->AddClass('fcMenuRoot');
  $om->AddClass('fcMenuFolder');
/* 2016-12-05 All of these are being rewritten.
$om = new fcCodeModule(__FILE__, 'menu/menu-manager.php');
  $om->AddClass('fcMenuMgr');
$om = new fcCodeModule(__FILE__, 'menu/menu-mapper.php');
//  $om->AddClass('fcMenuMap_Page');	// 2016-12-05 use trait ftPageMenu instead
$om = new fcCodeModule(__FILE__, 'menu/menu-painter.php');
  $om->AddClass('fcMenuPainter_UL');
*/

// PAGE CLASSES
$om = new fcCodeModule(__FILE__, 'page/page.php');
  $om->AddClass('fcPageElement');
  $om->AddClass('fcpeSimple');
  $om->AddClass('fcTag_html');
  $om->AddClass('fcTag_header');
  $om->AddClass('fiEventAware');
$om = new fcCodeModule(__FILE__, 'page/page.login.php');
  $om->AddClass('ftLoginContainer_standard');
/* 2016-12-05 This is being rewritten.
$om = new fcCodeModule(__FILE__, 'page/page.menu.php');
  $om->AddClass('ftPageMenu');
*/
$om = new fcCodeModule(__FILE__, 'page/page.standard.php');
  $om->AddClass('fcPage_standard');
  $om->AddClass('fcTag_html_standard');
  $om->AddClass('fcTag_body_standard');
  $om->AddClass('fcContentHeader');
$om = new fcCodeModule(__FILE__, 'page/page.standard.login.php');
  $om->AddClass('fcPage_login');
  $om->AddClass('fcTag_body_login');
  $om->AddClass('fcContentHeader_login');

// RICH TEXT HANDLERS
$om = new fcCodeModule(__FILE__, 'rtext/rtext.php');
  $om->AddClass('clsRTDoc');
$om = new fcCodeModule(__FILE__, 'rtext/rtext-html.php');
  $om->AddClass('clsRTDoc_HTML');
  $om->AddClass('clsNavList');

// STRING CLASSES
$om = new fcCodeModule(__FILE__, 'strings/strings.php');
  $om->AddClass('fcString');
$om = new fcCodeModule(__FILE__, 'strings/string-block.php');
  $om->AddClass('fcStringBlock_static');
  $om->AddClass('fcsStringBlock');
  $om->AddClass('fcStringBlock');
$om = new fcCodeModule(__FILE__, 'strings/report.php');
  $om->AddClass('fcReport');
  $om->AddClass('fcReportStandard');
$om = new fcCodeModule(__FILE__, 'strings/report-file.php');
  $om->AddClass('fcReportFile');
$om = new fcCodeModule(__FILE__, 'strings/report-file-od.php');
  $om->AddClass('fcReport_OpenDoc');
$om = new fcCodeModule(__FILE__, 'strings/string-report.php');
  $om->AddClass('fcReportSimple');
$om = new fcCodeModule(__FILE__, 'strings/string-tplt.php');
  $om->AddClass('fcTemplate_array');
  $om->AddClass('clsStringTemplate');
  $om->AddClass('clsStringTemplate_array');
$om = new fcCodeModule(__FILE__, 'strings/string-xt.php');
  $om->AddClass('fcStringDynamic');
  $om->AddClass('xtString');	// deprecated

// UTILITY LIBRARIES
$om = new fcCodeModule(__FILE__, 'util/args.php');
  $om->AddClass('fcInputData_array');
  $om->AddClass('fcInputData_array_local');
$om = new fcCodeModule(__FILE__, 'util/array.php');
  $om->AddClass('fcArray');
$om = new fcCodeModule(__FILE__, 'util/cache.php');
  $om->AddClass('fcThingCache');
$om = new fcCodeModule(__FILE__, 'util/cache-file.php');
  $om->AddClass('fcCacheFile');
$om = new fcCodeModule(__FILE__, 'util/debug.php');
  $om->AddClass('fcStackTrace');
  $om->AddClass('ftInstanceCounter');
$om = new fcCodeModule(__FILE__, 'util/html.php');
  $om->AddClass('fcHTML');
  $om->AddClass('fcHTML_Parser');
$om = new fcCodeModule(__FILE__, 'util/http.php');
  $om->AddClass('fcHTTP');
$om = new fcCodeModule(__FILE__, 'util/file.php');
  $om->AddClass('fcFileSystem');
$om = new fcCodeModule(__FILE__, 'util/money.php');
  $om->AddClass('fcMoney');
//  $om->AddFunc('Xplode');
$om = new fcCodeModule(__FILE__, 'util/time.php');
  $om->AddClass('fcDate');
  $om->AddClass('fcTime');
  $om->AddClass('xtTime');
//  $om->AddFunc('Time_DefaultDate');
  $om->AddFunc('Date_DefaultYear');
$om = new fcCodeModule(__FILE__, 'util/tree.php');
  $om->AddClass('fcTreeNode');
$om = new fcCodeModule(__FILE__, 'util/url.php');
  $om->AddClass('fcURL');

// WIDGETS
$om = new fcCodeModule(__FILE__, 'widgets/js.php');
  $om->AddClass('fcJavaScript');
$om = new fcCodeModule(__FILE__, 'widgets/object-msgs.php');
  $om->AddClass('ftVerbalObject');
/* 2016-12-01 replacing all these with page/page.navbar.php
$om = new fcCodeModule(__FILE__, 'widgets/navbar.php');
  $om->AddClass('fcNavbar_flat');
  $om->AddClass('fcNavbar_tree');
  $om->AddClass('fcNav_LabeledLink');
*/
