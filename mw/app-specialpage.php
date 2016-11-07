<?php
/*
  PURPOSE: MediaWiki SpecialPage descendant designed for Ferreteria applications
  HISTORY:
    2015-05-08 split off from menu.php
*/

// TODO: possibly this should be a static class property or something
if (!defined('KS_CHAR_URL_ASSIGN')) {
    define('KS_CHAR_URL_ASSIGN',':');
}
define('KS_CHARACTER_ENCODING','UTF-8');
// ^ this is a kluge; there ought to be some way to detect the MW installation's actual charset

/* ####
  FUTURE: This could eventually be split into a general page-type and one
    which uses page/id for most pages
    Also, the different RichText classes are now dependent on their creator
      (e.g. this class) to pass them the right kind of base URL. Not sure
      how to fix the conceptual model.
*/
class SpecialPageApp extends SpecialPage {
    // DYNAMIC
    protected $args;	// URL arguments as an array
    private $arKeep;	// arguments to preserve (in every menu item) from the current page
    private $arAdd;	// arguments to add to every menu item
    protected $objRT_HTML,$objRT_Wiki;

    // ++ SETUP ++ //

    public function __construct($iName='') {
	//global $wgScriptPath;
	global $vgMWLibPfx;
	global $wgTitle,$wgUser;
	global $vgUserName;

	parent::__construct($iName);
	self::Me($this);			// there can be only one

	$sClass = $this->PageClass();
	$oPage = new $sClass;
	$sClass = $this->AppClass();
	$oApp = new $sClass;
	$oApp->SetPageObject($oPage);		// connects App and Page

	$vgUserName = $wgUser->getName();
	$mwoTitleMe = $this->getTitle();

	$wpBase = $this->BaseURL_rel();
	$wkBase = $mwoTitleMe->getPrefixedText();

	$this->objRT_Wiki = new clsRT_Wiki($wkBase);	// OMGkluge
	$this->objRT_HTML = new clsRT_HTML($wpBase);
	$this->InitArKeep();
    }

    // -- SETUP -- //
    // ++ CLASS NAMES ++ //

    protected function PageClass() {
	return 'fcPage_MW';
    }
    protected function AppClass() {
	return 'clsApp_MW';
    }

    // -- CLASS NAMES -- //
    // ++ APP FRAMEWORK ++ //

    static protected $me;
    static public function Me(SpecialPageApp $oApp=NULL) {
	if (!is_null($oApp)) {
	    self::$me = $oApp;
	}
	return self::$me;
    }
    private $oPage;
    public function Page(clsPage $oPage=NULL) {
	if (is_null($oPage)) {
	    if (empty($this->oPage)) {
		$sClass = $this->PageClass();
		$this->oPage = new $sClass();
	    }
	} else {
	    $this->oPage = $oPage;
	    $oPage->App($this);
	}
	return $this->oPage;
    }
    private $oApp;
    protected function App() {
	if (is_null($this->oApp)) {
	    $sClass = $this->AppClass();
	    $this->oApp = new $sClass();
	}
	return $this->oApp;
    }

    // -- APP FRAMEWORK -- //
    // ++ MW CALLBACK ++ //

    /*----
      PURPOSE: For some reason, parent::execute() resets $vgPage -- so we need to set it after that.
	$vgPage was originally being set in __construct(), but that was getting overwritten.
      FUTURE: Make an application-object to hold $vgPage as a property, instead of having $vgPage as a global.
    */
    public function execute( $subPage ) {
	global $vgPage;

	parent::execute($subPage);
	$vgPage = $this;
    }

    // -- MW CALLBACK -- //
    // ++ CONFIGURATION ++ //

    /*----
      OLD NOTE - not sure if still true:
	USAGE: Must be called before parent::SetHeaders()
    */
    public function SetTitle($iText) {
	//global $wgMessageCache;
        //$wgMessageCache->addMessage('vbzadmin',$iText);

	$out = $this->getOutput();
	$out->setPageTitle( $iText );
    }
    /*----
      RETURNS: URL of basic Special page with no URL arguments
    */
    public function BaseURL() {
	throw new exception('BaseURL() is deprecated; call BaseURL_rel() or BaseURL_abs() (which needs to be written).');
    }
    // should this be moved to fcPage_MW?
    public function BaseURL_rel() {
	$mwoTitle = $this->getTitle();
	$wp = $mwoTitle->getFullURL();
	return $wp;
    }
    protected function InitArKeep() {
	$this->arKeep = array('page','id');
    }
    public function UseHTML() {
	global $vgOut;

	$vgOut = $this->objRT_HTML;
    }
    public function UseWiki() {
	global $vgOut;

	$vgOut = $this->objRT_Wiki;
    }

    // -- CONFIGURATION -- //
    // ++ URL GENERATION ++ //
    /*++++
      HISTORY:
	2011-03-30 adapted from clsWikiSection to SpecialPageApp
    */

    public function ArgsToKeep(array $iKeep) {
	$this->arKeep = $iKeep;
    }
    public function ArgsToAdd(array $iAdd) {
	$this->arAdd = $iAdd;
    }
    public function ArrayCheck(array $iarCheck) {
	if (is_array($this->arAdd)) {
	    $arOut = array_unique(array_merge($iarCheck,$this->arAdd));
	} else {
	    $arOut = $iarCheck;
	}
	return $arOut;
    }
    /*----
      RETURNS: $this->Arg() elements found in $this->arKeep, plus $this->arAdd (via ArrayCheck())
    */
    public function SelfArray() {
	$arLink = array();
	if (is_array($this->arKeep)) {
	    foreach ($this->arKeep as $key) {
		if ($this->HasArg($key)) {
		    $arLink[$key] = $this->Arg($key);
		}
	    }
	}
	return $this->ArrayCheck($arLink);
    }
    /*----
      RETURNS: Just the path after the page name, not the whole wiki title
	based on $this->args plus $iAdd plus $this->arAdd
      USED BY: $this->SelfURL()
      HISTORY:
	2013-08-26 added ArrayCheck()
    */
    private function SelfLink_Path(array $iAdd) {
	$arData = $this->args;
	foreach ($iAdd AS $key => $val) {
	    $arData[$key] = $val;	// override any matching keys
	}
	$arData = $this->ArrayCheck($arData);	// include anything from $this->arAdd
	//return SelfLink_Path($arData);
	return clsURL::FromArray($arData);
    }
    /*----
      USED BY: clsWikiSection::ActionAdd
      HISTORY:
	2013-08-26 set $arLink to NULL if iClear is TRUE
	  otherwise "rebuild tree" on http://vbz.net/corp/wiki/Special:VbzAdmin/page:topic fails
	2015-05-03 renamed this to SelfURL_extend() to avoid conflict with clsPageLogin::SelfURL(),
	  whose second parameter means somewhat the opposite of what $iClear does.
      NOTE: Not entirely clear what this is actually supposed to do.
    */
    public function SelfURL_what(array $iAdd=NULL,$iClear=TRUE) {
	$arAddSave = $this->arAdd;
	$this->arAdd = $iAdd;
	if ($iClear) {
	    $arLink = $this->arAdd;
	} else {
	    $arLink = $this->SelfArray();
	}
	$wpPath = $this->SelfURL_calc($arLink,$iClear);
	$this->arAdd = $arAddSave;
	return $wpPath;
    }
    /*----
      RETURNS: URL calculated from identity keys
    */
    public function SelfURL_basic() {
	$arLink = $this->SelfArray();
	$wpPath = $this->SelfURL_calc($arLink,TRUE);	// TRUE = don't include anything except ID keys
	return $wpPath;
    }
    /*----
      RETURNS: base URL (SelfURL_basic) extended by the items in arAdd
    */
    public function SelfURL_extend(array $arAdd) {
	$arAddSave = $this->arAdd;
	$this->arAdd = $arAdd;
	$arLink = $this->SelfArray();
	$wpPath = $this->SelfURL_calc($arLink,TRUE);
	$this->arAdd = $arAddSave;
	return $wpPath;
   }
    public function SelfURL() {
	throw new exception('SelfURL() is deprecated; call SelfURL_extend() or SelfURL_basic().');
    }
    /*----
      NOTE: This has only been tested with SpecialPages; in theory it could be used on regular pages
    */
    static public function URL_fromArray(array $iArgs) {
	global $wgTitle;
	global $wgScriptPath;

	$strPath = SelfLink_Path($iArgs);	// the part after the page name
	return $wgScriptPath.'/'.$wgTitle->getPrefixedText().$strPath;
    }
    /*----
      RETURNS: Relative path, ready for use in an href
      USED BY: clsWikiSection:ActionAdd()
      INPUT:
	iAdd: settings to add
	iClear: if TRUE, don't include local "keep" settings
      HISTORY:
	2011-03-30 renamed from SelfURL() to SelfURL_calc()
	2012-02-24 dropped use of $wgScriptPath, because it doesn't work with MW1.18; rewrote path calculation.
    */
    protected function SelfURL_calc(array $iAdd=NULL,$iClear=FALSE) {
	global $wgTitle;

	if (is_array($iAdd)) {
	    if ($iClear) {
		//$strPath = SelfLink_Path($iAdd);
		$strPath = clsURL::FromArray($iAdd);
	    } else {
		$strPath = $this->SelfLink_Path($iAdd);
	    }
	} else {
	    $strPath = '';
	}
	//$wpPrefix = $wgTitle->getPrefixedText();
	//$wpPrefix = $this->getName();
	$mwoTitle = $this->getTitle();
	$wpPrefix = $mwoTitle->getFullURL();
	return "$wpPrefix/$strPath";
    }
    // DEPRECATED
    public function SelfLink_WT(array $iAdd, $iShow) {
	$arData = $this->args;
	foreach ($iAdd AS $key => $val) {
	    $arData[$key] = $val;	// override any matching keys
	}
	$out = SelfLink_WT($arData,$iShow);
	return $out;
    }
/* 2010-10-01 NEED TO KNOW who uses this, so I can fix it
    public function SelfLink_HTML(array $iAdd, $iShow, $iClear=FALSE, $iPopup=NULL) {
	$urlPath = $this->SelfURL($iAdd,$iClear);
	$htPopup = is_null($iPopup)?'':(' title="'.$iPopup.'"');
	$out = '<a href="'.$urlPath.'"'.$htPopup.'>'.$iShow.'</a>';
	return $out;
    }
*/
    /*----
      USED BY: VbzAdminOrder.DoSetup()
    */
    public function SelfLink(array $iAdd, $iShow, $iPopup=NULL) {
	global $vgOut;

	$arData = $this->args;
	foreach ($iAdd AS $key => $val) {
	    $arData[$key] = $val;	// override any matching keys
	}
	$out = $vgOut->SelfLink($arData,$iShow,$iPopup);
	return $out;
    }
    /*----
      ACTION: Displays a mini-menu from a list of possible values
      INPUT:
	$iArgName: name of argument whose value changes with each choice
	$iValues: array containing prefix-delimited list of data for each choice:
	  iValue[URL value] = "\display value\popup text"
    */
    public function SelfLinkMenu($iArgName,array $iValues,$iCur=NULL) {
	global $vgOut;

	$strCur = (is_null($iCur))?$this->Arg($iArgName):$iCur;

	$objStr = new xtString;
	$strArg = $iArgName;
	$out = '';
	foreach ($iValues as $strURL => $xtData) {
	    $objStr->Value = $xtData;
	    $arVal = $objStr->Xplode();
	    $strDisp = $arVal[0];
	    $strPop = $arVal[1];
	    $arAdd[$strArg] = $strURL;
	    $isSel = ($strURL == $strCur);
	    $out .= $vgOut->Selected($isSel,'[ '.$this->SelfLink($arAdd,$strDisp,$strPop).' ]');
	}
	return $out;
    }

    // -- URL GENERATION -- //
    // ++ URL PARSING ++ //

    /*----
      ACTION: Parses variable arguments from the URL
	The URL is formatted as a series of arguments /arg:val/arg:val/..., so that we can always refer directly
	  to any particular item as a wiki page title while also not worrying about hierarchy/order.
	  An arg can also appear without a val (/arg/...), in which case it is treated as a flag set to TRUE.
      TODO: This can probably be replaced by clsURL::ParsePath($par);
    */
    protected function GetArgs($par) {
	$args_raw = preg_split('/\//',$par);
	foreach($args_raw as $arg_raw) {
	    if (strpos($arg_raw,KS_CHAR_URL_ASSIGN) !== FALSE) {
		list($key,$val) = preg_split('/'.KS_CHAR_URL_ASSIGN.'/',$arg_raw);
		$this->args[$key] = $val;
	    } else {
		$this->args[$arg_raw] = TRUE;
	    }
	}
    }
    /*----
      RETURNS: array of all arguments found in URL,
	or just the arguments listed in $iList
    */
    public function Args(array $iList=NULL) {
	if (is_array($iList)) {
	    foreach ($iList as $name) {
		$arOut[$name] = $this->Arg($name);
	    }
	    return $arOut;
	} else {
	    return $this->args;
	}
    }
    /*----
      RETURNS: value of single specified argument from URL
      HISTORY:
	2012-02-26 changed default return from FALSE to NULL
	  NULL is easier to test for.
    */
    public function Arg($iName) {
	if (isset($this->args[$iName])) {
	    return $this->args[$iName];
	} else {
	    return NULL;
	}
    }
    // Alias for compatibility with other Ferreteria Page classes
    public function PathArg($sName) {
	return $this->Arg($sName);
    }
    /*----
      HISTORY:
	2013-08-26 added so we could avoid adding nonexistent data to the URL
    */
    public function HasArg($iName) {
	return isset($this->args[$iName]);
    }

    /*----
      ACTION: handles arguments embedded in the URL
      REQUIRES: classes must be registered in $this->arTbls (use $this->RegObjs())
      HISTORY:
	2011-10-15 moved here from SpecialVbzAdmin.php and renamed from doAdminLookup() to HandlePageArgs()
    */
    private $sPage, $tbl, $rec, $idRec, $doRec;
    protected function HandlePageArgs() {
	if (isset($this->args['page'])) {
	    $sPage = $this->args['page'];
	} else {
	    $sPage = NULL;
	}

	$idRec = NULL;
	$doRec = FALSE;
	$doNew = FALSE;
	if (!is_null($sPage)) {
	    if (isset($this->args['id'])) {
		$doRec = TRUE;
		$idRec = $this->args['id'];
		if ($idRec == KS_NEW_REC) {
// an ID of NULL tells GetItem() to create a new object rather than loading an existing record
		    $idRec = NULL;
		    $doNew = TRUE;
		}
	    }
	}
	$this->sPage = $sPage;
	$this->doRec = $doRec;
	$this->idRec = $idRec;

	$tbl = NULL;
	$rc = NULL;
	$arTbls = $this->TableArray();
	if (array_key_exists($sPage,$arTbls)) {
	    $tbl = $arTbls[$sPage];
	    if ($doRec) {
		$rc = $tbl->GetItem($idRec);
		if (!$doNew && $rc->IsNew()) {
		    $out = '<b>Internal Error</b>: Record was expected, but none found. Check the URL.';
		    $out .= '<ul>';
		    $out .= '<li><b>requested ID</b>: '.$idRec;
		    global $sql;
		    $out .= '<li><b>SQL used</b>: '.$sql;
		    $out .= '<li><b>Table class</b>: '.get_class($tbl);
		    $out .= '<li><b>Record class</b>: '.get_class($rc);
		    $out .= '</ul>';

		    echo $out; $out=NULL;
		    throw new exception('Expected record was not found.');
		}

		if (method_exists($rc,'PageTitle')) {
		    $this->SetTitle($rc->PageTitle());
		}
	    } else {
		if (method_exists($tbl,'PageTitle')) {
		    $this->SetTitle($tbl->PageTitle());
		}
	    }
	}
	$this->tbl = $tbl;
	$this->rec = $rc;
    }
    protected function PageArg_Table() {
	return $this->tbl;
    }
    protected function PageArg_Record() {
	return $this->rec;
    }
    protected function PageArg_PageName() {
	return $this->sPage;
    }

    // -- URL PARSING -- //
}

/*%%%%
  PURPOSE: SpecialPage using data-driven menu (URL-parsing)
*/
abstract class SpecialPage_DataMenu extends SpecialPageApp {

    // ++ ABSTRACT ++ //

    abstract protected function DB();	// app-related DB (for URL parsing)

    // -- ABSTRACT -- //
    // ++ SETUP ++ //

    public function TableArray(array $arTbls=NULL) {
	if (!is_null($this->arClassNames)) {
	    $oDB = $this->DB();
	    foreach ($this->arClassNames as $strCls) {
		$tbl = $oDB->Make($strCls);
		$strAct = $tbl->ActionKey();
		$arTbls[$strAct] = $tbl;
	    }
	    $this->arTbls = $arTbls;
	}
	return $this->arTbls;
    }
     /*----
      INPUT:
	iarClassNames: array of names of classes to load
    */
/* 2015-07-16 old version
    public function RegObjs(array $iarClassNames) {
	foreach ($iarClassNames as $strCls) {
	    $tbl = $this->DB()->Make($strCls);
	    $strAct = $tbl->ActionKey();
	    $arTbls[$strAct] = $tbl;
	}
	$this->arTbls = $arTbls;
    } */
    private $arClassNames;
    public function RegObjs(array $arClassNames) {
	$this->arClassNames = $arClassNames;
    }

    // -- SETUP -- //

}