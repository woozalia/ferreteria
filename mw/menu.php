<?php
/*
  LIBRARY: menu.php - a few classes for managing menus
  REQUIRES: richtext.php
  HISTORY:
    2009-08-09 Extracted from SpecialVbzAdmin.php
    2009-11-04 clsArgs
    2010-02-20 clsWikiSection::SectionAdd(), ::ToggleAdd()
    2010-04-06
      * General reconciliation with edited non-dev version
      * Done earlier but not logged here:
	clsWikiSection::ArgsToAdd() (ported from other copy of menu.php)
	clsAdminTable, clsAdminData, clsAdminData_Logged, clsAdminEvents, clsAdminEvent
    2010-06-17 StartEvent() / FinishEvent() now keep the event ID in $this->idEvent
    2010-10-06
      Reconciling with dev:
      * Minor improvements; split() is deprecated; small bugfixes
    2010-10-07 reconstructed clsDataSet::_AdminLink(), which apparently got lost during reconciliation
    2010-10-17 Expanded SpecialPageApp::Args() to allow returning a limited set of arguments
    2013-01-04 clsDataSet isn't in this file, so why is the note about clsDataSet::_AdminLink() here?
    2013-06-02 fixed Values_forLink() - toggles on as well as off now
    2013-06-06 rework of menu link classes complete -- working now
  BUGS:
    2010-09-13 When there are multiple toggles, they don't preserve each other's states
      http://wiki.vbz.net/Special:VbzAdmin/page:order/id:4263/receipt -- "email" cancels out "receipt"
    2012-05-27 lots of tweaks and fixes and some new classes to replace old ones; see code comments
    2012-07-08 explicit prepping of mw.menus module
*/

if (!defined('KS_CHAR_URL_ASSIGN')) {
    define('KS_CHAR_URL_ASSIGN',':');
}

class clsMenu {
    public $Page;
    private $SubNodes;
    private $AllNodes;
    protected $Action;
    protected $Selected;
    protected $isSubActive;

    public function __construct($iWikiPage) {
	$this->Page = $iWikiPage;
	$this->isSubActive = FALSE;
    }
    public function Render($iAction) {
	$this->Action = $iAction;
	if (isset($this->AllNodes[$iAction])) {
	    $this->AllNodes[$iAction]->Activate();
	    $this->isSubActive = TRUE;
	}
	$out = $this->Render_SubMenu($iAction);
	return $out;
    }
    public function Render_SubMenu($iAction) {
	$out = NULL;
	foreach ($this->SubNodes as $objNode) {
	    if (!is_null($out)) {
		$out .= ' | ';
	    }
	    $out .= $objNode->Render($iAction);
	}
	return $out;
    }
    public function Add(clsMenuNode $iNode) {
	$this->SubNodes[$iNode->Name] = $iNode;
	$this->Root()->AllNodes[$iNode->Name] = $iNode;
	$iNode->Parent = $this;
    }
    protected function Root() {
	return $this;
    }
    protected function Activate() {    }	// do nothing
    public function Execute() {
	if (isset($this->Selected)) {
	    $out = $this->Selected->DoAction();
	} else {
	    $out = NULL;
	}
	return $out;
    }
    /*----
      RETURNS: TRUE if the current input has activated a sub-menu
    */
    public function IsSubMenuActive() {
	return 	$this->isSubActive;
    }
}

abstract class clsMenuNode extends clsMenu {
    public $Name;
    protected $Text;
    protected $DoSpec;
    public $Parent;
    protected $IsActive;

    public function __construct($iText, $iDo) {
	$this->Name = $iDo;
	$this->Text = $iText;
	$this->DoSpec = $iDo;
    }
    public function Root() {
	return $this->Parent->Root();
    }
    abstract public function DoAction();
    protected function Activate() {
	$this->IsActive = TRUE;
	$this->Parent->Activate();
    }
    public function Render($iAction) {
	$wtSelf = $this->Root()->Page;
	//$wtItem = "[[$wtSelf/page".KS_CHAR_URL_ASSIGN."{$this->DoSpec}|{$this->Text}]]";
	$url = $wtSelf.'/page'.KS_CHAR_URL_ASSIGN.$this->DoSpec;
	$sText = $this->Text;
	$ftItem = "<a href='$url'>$sText</a>";
//	if ($iAction == $this->DoSpec) {
	if ($this->IsActive) {
	    $out = "<b>$ftItem</b>";
	    $this->Root()->Selected = $this;
	} else {
	    $out = $ftItem;
	}
	return $out;
    }
}

class clsMenuRow extends clsMenuNode {
    public function DoAction() {
        $sText = $this->Text;
	$out = "<br><b>$sText</b>: ";
	$out .= $this->Render_SubMenu($this->Root()->Action);
	return $out;
    }
}

class clsMenuItem extends clsMenuNode {
    public function DoAction() {
// later: actually implement menu item action
    }
}

// A way of passing a variable number of options to a function
// Possibly this should be in a separate library
// 2010-02-20 ok, this is kind of stupid; just the usual array syntax is simple enough. Is any code using this?
//	Currently ruling out SpecialVbzAdmin...
/*
class clsArgs {
    private $arArgs;

    public function __construct(array $iPairs=NULL) {
	if (!is_null($iPairs)) {
	    foreach ($iPairs as $key=>$val) {
		$this->Add($key,$val);
	    }
	}
    }

    public function Add($iName, $iValue=NULL) {
	$this->arArgs[$iName] = $iValue;
    }
    public function Value($iName, $iValue=NULL) {
	if (!is_null($iValue)) {
	    $this->arArgs[$iName] = $iValue;
	}
	if (isset($this->arArgs[$iName])) {
	    return $this->arArgs[$iName];
	} else {
	    return NULL;
	}
    }
}
*/
// link-data functions
/*----
  RETURNS: the part of the path *after* the specialpage name
  USED BY: SelfURL(), which is used by clsWikiSection:ActionAdd()
*/
function SelfLink_Path(array $iData) {
    $fp = '';
    foreach ($iData AS $key => $val) {
	if ($val !== FALSE) {
	    if ($val === TRUE) {
		$fp .= '/'.$key;
	    } else {
		$fp .= '/'.$key.KS_CHAR_URL_ASSIGN.$val;
	    }
	}
    }
    $out = $fp;
    return $out;
}
function SelfLink_WT(array $iData,$iShow) {
    if (is_null($iShow)) {
	return NULL;
    } else {
	$strPath = SelfLink_Path($iData);
	return '[[{{FULLPAGENAME}}'.$strPath.'|'.$iShow.']]';
    }
}
/* 2010-10-01 NEED TO KNOW who uses this, so I can fix it
function SelfLink_HTML(array $iData,$iShow,$iPopup=NULL) {
    if (is_null($iShow)) {
	return NULL;
    } else {
	$strPath = SelfLink_Path($iData);
	$htPopup = is_null($iPopup)?'':(' title="'.$iPopup.'"');
	return '<a href="'.$strPath.'"'.$htPopup.'>'.$iShow.'</a>';
    }
}
*/
//}
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

    static protected $me;
    static public function Me(SpecialPageApp $oApp=NULL) {
	if (!is_null($oApp)) {
	    self::$me = $oApp;
	}
	return self::$me;
    }

    public function __construct($iName='') {
	//global $wgScriptPath;
	global $vgMWLibPfx;
	global $wgTitle,$wgUser;
	global $vgUserName;

	parent::__construct($iName);
	self::Me($this);			// there can be only one

//	$vgPage = $this;
	$vgUserName = $wgUser->getName();
	$mwoTitleMe = $this->getTitle();

	$wpBase = $this->BaseURL_abs();
	$wkBase = $mwoTitleMe->getPrefixedText();

	$this->objRT_Wiki = new clsRT_Wiki($wkBase);	// OMGkluge
	$this->objRT_HTML = new clsRT_HTML($wpBase);
	$this->InitArKeep();
    }
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
    /* 2015-06-25 DEPRECATED; use BaseURL_rel() or BaseURL_abs() instead.
    public function BaseURL() {
	$mwoTitle = $this->getTitle();
	$wp = $mwoTitle->getFullURL();
	return $wp;
    }*/
    /*----
      RETURNS: Absolute URL for the base folder of the current application (Special Page)
	Not sure whethere there is supposed to be a trailing slash.
    */
    public function BaseURL_abs() {
	$mwoTitle = $this->getTitle();
	$wp = $mwoTitle->getFullURL();
	return $wp;
    }
    /*----
      RETURNS: Domain-relative URL for the base folder of the current application (Special Page)
	Not sure whethere there is supposed to be a trailing slash.
    */
    public function BaseURL_rel() {
	global $wgScriptPath;

	$mwoTitle = $this->getTitle();
	$wp = $wgScriptPath.'/'.$mwoTitle->getBaseTitle();
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
/*
    public function RichTextObj() {
	return $vgOut;
    }
*/
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

    /*====
      SECTION: link generation
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
	return SelfLink_Path($arData);
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
		$strPath = SelfLink_Path($iAdd);
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
	return $wpPrefix.$strPath;
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
    /*----
      INPUT:
	iarClassNames: array of names of classes to load
    */
    public function RegObjs(array $iarClassNames) {
	foreach ($iarClassNames as $strCls) {
	    $tbl = $this->DB()->Make($strCls);
	    $strAct = $tbl->ActionKey();
	    $arTbls[$strAct] = $tbl;
	}
	$this->arTbls = $arTbls;
    }
    /*----
      ACTION: handles arguments embedded in the URL
      REQUIRES: classes must be registered in $this->arTbls (use $this->RegObjs())
      HISTORY:
	2011-10-15 moved here from SpecialVbzAdmin.php and renamed from doAdminLookup() to HandlePageArgs()
    */
    protected function HandlePageArgs() {
	if (isset($this->args['page'])) {
	    $page = $this->args['page'];
	} else {
	    $page = NULL;
	}

	$idObj = NULL;
	$doObj = FALSE;
	$doNew = FALSE;
	if (!is_null($page)) {
	    if (isset($this->args['id'])) {
		$doObj = TRUE;
		$idObj = $this->args['id'];
		if ($idObj == 'new') {
// an ID of NULL tells GetItem() to create a new object rather than loading an existing record
		    $idObj = NULL;
		    $doNew = TRUE;
		}
	    }
	}
	$this->page = $page;
	$this->doObj = $doObj;
	$this->idObj = $idObj;

	$tbl = NULL;
	$obj = NULL;
	if (array_key_exists($page,$this->arTbls)) {
	    $tbl = $this->arTbls[$page];
	    if ($doObj) {
		$obj = $tbl->GetItem($idObj);
		if (!$doNew && $obj->IsNew()) {
		    $out = '<b>Internal Error</b>: Record was expected, but none found. Check the URL.';
		    $out .= '<ul>';
		    $out .= '<li><b>requested ID</b>: '.$idObj;
		    global $sql;
		    $out .= '<li><b>SQL used</b>: '.$sql;
		    $out .= '<li><b>Table class</b>: '.get_class($tbl);
		    $out .= '<li><b>Record class</b>: '.get_class($obj);
		    $out .= '</ul>';

		    echo $out; $out=NULL;
		    throw new exception('Expected record was not found.');
		}

		if (method_exists($obj,'PageTitle')) {
		    $this->SetTitle($obj->PageTitle());
		}
	    } else {
		if (method_exists($tbl,'PageTitle')) {
		    $this->SetTitle($tbl->PageTitle());
		}
	    }
	}
	$this->tbl = $tbl;
	$this->obj = $obj;
    }
}

/*==========
| FORM CLASSES
| Trying not to be too ambitious at first, but eventually could be separate library file
*/
class clsWikiFormatter {
    private $mwoPage;

    public function __construct(SpecialPageApp $iPage) {
	$this->mwoPage = $iPage;
    }
    public function Page() {
	return $this->mwoPage;
    }
}
/*%%%%
  PURPOSE: revised clsWikiSection, taking into account usability lessons learned
    and implementing a more flexible link design.
*/
class clsWikiSection2 {
    private $objFmt;	// page-formatter object
    private $arMenu;	// array of menu objects
    private $arPage;	// page keys array
    private $strTitle;	// title of section - for display
    private $intLevel;	// indentation level of section

    public function __construct(clsWikiFormatter $iFmt,$iTitle,$iLevel=2) {
	$this->objFmt = $iFmt;
	$this->strTitle = $iTitle;
	$this->intLevel = $iLevel;
	$this->arMenu = NULL;
	$this->arPage = NULL;
    }
    /*----
      ACTION: set the list of keys that represent the current page spec.
	Preserving these keys will keep the user on the current page even if other data changes.
    */
    public function PageKeys(array $arKeys=NULL) {
	if (!is_null($arKeys)) {
	    $this->arPage = $arKeys;
	}
	return $this->arPage;
    }
    /*----
      RETURNS: array representing page-defining data (but not all URL values)
    */
    public function PageData($iKey=NULL) {
	$arArgs = $this->PageVals();

	if (is_null($iKey)) {
	    // return array of values for all the keys that identify the page
	    foreach ($this->arPage as $idx => $key) {
		if (array_key_exists($key,$arArgs)) {
		    // 2012-05-27 this seems to happen when a key is defined but not used in some circumstance
		    $arOut[$key] = $arArgs[$key];
		}
	    }
	    return $arOut;
	} else {
	    return NzArray($arArgs,$iKey);
	}
    }
    public function PageVals() {
	$objPage = $this->objFmt->Page();
	return $objPage->Args();
    }
    public function AddLink(clsWikiSectionWidget $iLink) {
	$this->arMenu[] = $iLink;	// add Link to list of Links
	$iLink->Section($this);		// pass Link a pointer back to $this
	return $iLink;
    }
    /*----
      PURPOSE: add a link to do something on the current page -- preserve page-significant data
    */
    public function AddLink_local(clsWikiSectionLink_base $iLink) {
	$this->AddPageDefaults_toLink($iLink);
	$this->AddLink($iLink);
	return $iLink;
    }
    /*----
      PURPOSE: add a link that preserves all other URL data (preserves the state of the page)
    */
    public function AddLink_state(clsWikiSectionLink $iLink) {
	$this->AddPageValues_toLink($iLink);
	$this->AddLink($iLink);
	return $iLink;
    }
    public function AddPageDefaults_toLink(clsWikiSectionLink_base $iLink) {
	$iLink->Values_default($this->PageData());
    }
    public function AddPageValues_toLink(clsWikiSectionLink $iLink) {
	$iLink->Values_default($this->PageVals());
    }
    public function Render() {
	$tag = 'h'.$this->intLevel;
	$title = $this->strTitle;
/* This worked for an older version of MW
	$out = "\n<div class=\"mw-content-ltr\"><$tag>\n";
	if (is_array($this->arMenu)) {
	    $arMenu = array_reverse($this->arMenu);	// right-alignment reverses things
	    foreach ($arMenu as $objLink) {
		$out .= $objLink->Render();
	    }
	}
	$out .= "\n$title</$tag></div>";
*/

	// this is being tested against MW 1.22.6
	$out = "\n<$tag><span class=\"mw-headline\">$title</span>\n"
	  .'<span class="mw-editsection">';
	if (is_array($this->arMenu)) {
	    foreach ($this->arMenu as $objLink) {
		$out .= $objLink->Render();
	    }
	}
	$out .= "\n</span></$tag>";

	return $out;
    }
    public function FormOpen() {
	$oForm = new clsWikiSectionForm($this->PageData());
	return $oForm->Render();
    }
}
class clsWikiSection_std_page extends clsWikiSection2 {
    public function __construct(clsWikiFormatter $iFmt,$iTitle,$iLevel=2) {
	parent::__construct($iFmt,$iTitle,$iLevel);
	$this->PageKeys(array('page','id'));
    }
}
/*%%%%
  PURPOSE: abstract handler for stuff that might go in the action/link area of a section header
*/
abstract class clsWikiSectionWidget {
    protected $objSect;	// section object
    private $strPopup;	// popup text (if any)

    /*----
      ACTION: render the link
    */
    abstract public function Render();

    public function Popup($iPopup=NULL) {
	if (!is_null($iPopup)) {
	    $this->strPopup = $iPopup;
	}
	return $this->strPopup;
    }
    public function Section(clsWikiSection2 $iSection=NULL) {
	if (!is_null($iSection)) {
	    $this->objSect = $iSection;
	}
	return $this->objSect;
    }

}
abstract class clsWikiSectionLink_base extends clsWikiSectionWidget {
    protected $arData;	// key:value pairs to always include in link

    public function __construct(array $iarData) {
	$this->arData = $iarData;
    }
    /*----
      RETURNS: URL for this link
    */
    public function SelfURL() {
	global $vgOut;

	$ar = $this->Values_forLink();
	return $vgOut->SelfURL($ar,FALSE);
    }

    abstract protected function Values_forLink();
    /*----
      ACTION: sets a bunch of values to be used as defaults
	if there is not already a value for each key.
    */
    public function Values_default(array $iarValues) {
	foreach ($iarValues as $key => $val) {
	    $this->arData[$key] = NzArray($this->arData,$key,$val);
	}
    }
    public function Values(array $iValues=NULL) {
	if (!is_null($iValues)) {
	    $this->arData = $iValues;
	}
	return $this->arData;
    }
    public function Value($iKey,$iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->arData[$iKey] = $iVal;
	}
	return $this->arData[$iKey];
    }
    /*----
      ACTION: render the link
    */
    public function Render() {

	$ftURL = $this->SelfURL();

	$htPopup = htmlspecialchars($this->Popup());
	$htDispl = htmlspecialchars($this->DisplayText());

	$isActive = $this->Selected();
	$htFmtOpen = $isActive?'<b>':'';
	$htFmtShut = $isActive?'</b>':'';

//	$out = '<div class="mw-content-ltr"><span class="editsection">';
/* This was for an older version of MW
	$out = '<span class="editsection">';
	$out .= $htFmtOpen.'[';
	$out .= '<a href="'.$ftURL.'" title="'.$htPopup.'">'.$htDispl.'</a>';
	$out .= ']'.$htFmtShut;
//	$out .= '</span></div>';
	$out .= '</span>';
*/

	// This is being tested against MW 1.22.6
	$out = '<span class="mw-editsection-bracket">'
	  .$htFmtOpen
	  .'[</span>'
	  .'<a href="'.$ftURL.'" title="'.$htPopup.'">'.$htDispl.'</a>'
	  .'<span class="mw-editsection-bracket">]</span>'
	  .$htFmtShut;

	return $out;
    }
}
/*%%%%
  PURPOSE: handles a single action-link (e.g. [edit]) on a section header
  TODO: This should be renamed; it's a particular type of link, possibly obsolete.
*/
abstract class clsWikiSectionLink extends clsWikiSectionLink_base {
    protected $strDisp;	// text to display

    /*----
      INPUT:
	iarData = list of URL values to always preserve
	iDisp = text to display
    */
    public function __construct(array $iarData,$iDisp) {
	parent::__construct($iarData);
	$this->strDisp = $iDisp;
	$this->objSect = NULL;
	$this->strPopup = NULL;
	$this->isActive = FALSE;
    }
    protected function DisplayText() {
	return $this->strDisp;
    }
    /*----
      RETURNS: TRUE if the current URL causes this link to be selected
	May also return a value rather than TRUE/FALSE.
    */
    abstract public function Selected();
    /*----
      RETURNS: array of link's values, adjusted as needed for link behavior
	i.e. if link should toggle OFF, then the appropriate array value
	is set FALSE.
      VERSION: removes key from link if it is currently active
	This originally just returned $this->Values()
	It may be that this class should do that, and we need
	  a clsWikiSectionLink_toggle to do what this one does now.
    */
    protected function Values_forLink() {
	$ar = $this->Values();

	$key = $this->Key();
	$ar[$key] = !$this->Selected();

	return $ar;
    }
}
/*%%%%
  PURPOSE: handles links that have a "key", i.e. a particular identifying piece of data.
    Default behavior is to treat this like a toggle.
  DEPRECATED - use clsWikiSectionLink_option
*/
class clsWikiSectionLink_keyed extends clsWikiSectionLink {
    protected $strKey;

    /*----
      INPUT:
	iarData: not sure... other stuff to always appear in URL, probably
	iDisp: text to display for link
	iKey: key to look up in URL to see if link is activated
    */
    public function __construct(array $iarData,$iDisp,$iKey=NULL) {
	parent::__construct($iarData,$iDisp);
	$this->strKey = is_null($iKey)?$iDisp:$iKey;
	$this->isSelected = NULL;
    }
    public function Key($iKey=NULL) {
	if (!is_null($iKey)) {
	    $this->strKey = $iKey;
	}
	return $this->strKey;
    }
    public function Selected() {
	$key = $this->Key();
	$val = $this->objSect->PageData($key);
	return $val;
    }
}
/*%%%%
  PURPOSE: handles a form within a section.
    Right now, it actually just handles the opening form tag.
  USAGE: created internally by section object; not added to list
*/
class clsWikiSectionForm extends clsWikiSectionLink_base {
    /*----
      ACTION: render the form
    */
    public function Render() {
	$out = '<form method=post action="'.$this->SelfURL().'">'."\n";
	return $out."\n";
    }
    protected function Values_forLink() {
	return $this->Values();
    }
}
/*%%%%
  PURPOSE: handles a group of links where only one link in the group can be activated at one time
    If no group key is specified, each link acts like a toggle.
  USAGE: added by caller
*/
class clsWikiSectionLink_option extends clsWikiSectionLink_base {
    protected $sLKey;	// link key
    protected $sGKey;	// link group key
    protected $sDi0;	// display when not activated
    protected $sDi1;	// display when activated
    /*----
      INPUT:
	iarData: other stuff to always appear in URL, regardless of section's menu state
	iLinkKey: value that the group should be set to when this link is activated
	iGroupKey: group's identifier string in URL (.../iGroupKey:iLinkKey/...)
	  if NULL, then there is no group key, and presence of link key (.../iLinkKey/...) is a flag
	iDispOff: text to display when link is not activated - defaults to iLinkKey
	iDispOn: text to display when link is activated - defaults to iDispOff
    */
    public function __construct(array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL) {
	parent::__construct($iarData);
	$this->sGKey = $iGroupKey;
	$this->sLKey = $iLinkKey;
	$this->sDi0 = is_null($iDispOff)?$iLinkKey:$iDispOff;
	$this->sDi1 = is_null($iDispOn)?$this->sDi0:$iDispOn;
    }
    protected function LinkKey() {
	return $this->sLKey;
    }
    protected function GroupKey() {
	return $this->sGKey;
    }
    protected function HasGroup() {
	return !is_null($this->GroupKey());
    }
    protected function GroupVal() {
	$gk = $this->GroupKey();		// key for link's group
	return $this->objSect->PageData($gk);	// return group's current value
    }
    public function Selected() {
	$lk = $this->LinkKey();			// key for this link
	if ($this->HasGroup()) {
	    $gv = $this->GroupVal();		// current group value
	    return ($gv==$lk);			// does group value match link key?
	} else {
	    return $this->objSect->PageData($lk);	// return TRUE iff link key exists in URL
	}
    }
    protected function DisplayText() {
	if ($this->Selected()) {
	    return $this->sDi1;
	} else {
	    return $this->sDi0;
	}
    }
    /*----
      RETURNS: array of link's values, adjusted as needed for link behavior
	i.e. if link should toggle OFF, then the appropriate array value
	is set FALSE.
      VERSION:
	if there is a group key:
	  if selected, sets group key FALSE to remove it from URL
	  if NOT selected, sets group key to link key
	if there is NO group key:
	  if selected, sets link key FALSE to remove it from URL
	  if NOT selected, sets link key TRUE to put it in the URL
    */
    protected function Values_forLink() {
	$ar = $this->Values();

	$lk = $this->LinkKey();
	if ($this->HasGroup()) {
	    $gk = $this->GroupKey();
	    if ($this->Selected()) {
		$ar[$gk] = FALSE;
	    } else {
		$ar[$gk] = $lk;
	    }
	} else {
	    $ar[$lk] = !$this->Selected();
	}

	return $ar;
    }
    /*----
      ACTION: render the link
    */
/* 2014-06-17 This duplicates functionality now in the base class
    public function Render() {

	$ftURL = $this->SelfURL();

	$htPopup = htmlspecialchars($this->Popup());
	$htDispl = htmlspecialchars($this->DisplayText());

	$isActive = $this->Selected();
	$htFmtOpen = $isActive?'<b>':'';
	$htFmtShut = $isActive?'</b>':'';

//	$out = '<div class="mw-content-ltr"><span class="editsection">';
	$out = '<span class="editsection">';
	$out .= $htFmtOpen.'[';
	$out .= '<a href="'.$ftURL.'" title="'.$htPopup.'">'.$htDispl.'</a>';
	$out .= ']'.$htFmtShut;
//	$out .= '</span></div>';
	$out .= '</span>';

	return $out;
    }
*/
}
/*%%%%
  PURPOSE: handles links that express possible values of a single key
  DEPRECATED: Does anything use this successfully?
    clsWikiSectionLink_option is a rewrite to provide the same functionality.
*/
class clsWikiSectionLink_choice extends clsWikiSectionLink_keyed {
    public function Selected() {
	$key = $this->Key();
	$valPage = parent::Selected();
	$valLink = $this->Value($key);
	return $valPage == $valLink;
    }
}
/*%%%%
  PURPOSE: handles link-area subsections (static text)
*/
class clsWikiSection_section extends clsWikiSectionWidget {
    protected $strDisp;

    public function __construct($iDisp) {
	$this->strDisp = $iDisp;
    }
    public function Render() {
	$out = '<span class="editsection">';
	$out .= '| <b>'.$this->strDisp.'</b>:';
	$out .= '</span>';
	return $out;
    }
}

/*
class clsWikiSectionLink_bool {
    public function __construct(array $iarData,$iDisp=NULL,$iPopup=NULL,$iKey=NULL) {
    public function Selected($iOn=NULL) {
	if (!is_null($iOn)) {
	    $isActive = !empty($arLink['active']) || $arLink['isdef'];
	}
	return NzArray($this->arData,'active');
    }
}
*/

class clsWikiSection {
    // STATIC
    public static $arKeepDefault = array('page','id');

    // DYNAMIC
    private $strName;	// short name for header text
    private $strDescr;	// longer description for popups
    private $intLevel;	// level of header; defaults to 2
    private $objFmt;	// page-formatter object
    private $arMenu;	// menu entries
    private $arKeep;	// arguments to preserve (in every menu item) from the current page
    private $arAdd;	// arguments to add to every menu item

    public function __construct(clsWikiFormatter $iFmt,$iName,$iDescr=NULL,$iLevel=2,array $iMenu=NULL) {
	$this->objFmt = $iFmt;
	$this->strName = $iName;
	$this->strDescr = is_null($iDescr)?$iName:$iDescr;
	$this->intLevel = $iLevel;
	$this->arMenu = $iMenu;
	$this->arKeep = self::$arKeepDefault;
    }
    /*-----
      PURPOSE: Add an action link to the menu
      USED BY: any admin page that adds an action link to a section (e.g. "edit")
      INPUT;
	iDisp = text to display (usually short, preferably one word)
	iActive = TRUE shows the item as selected, FALSE shows it normally; default is to attempt to detect the state
	iKey = key to use in URL. If this is an array, then it is passed directly to SelfURL(), overriding any existing values.
      HISTORY:
	2010-11-07 added array option for iKey
    */
    public function ActionAdd($iDisp,$iPopup=NULL,$iActive=NULL,$iKey=NULL,$iIsDefault=FALSE) {
	$strPopup = is_null($iPopup)?($iDisp.' '.$this->strDescr):$iPopup;

	$objPage = $this->objFmt->Page();
	$arLink = $objPage->ArrayCheck($objPage->SelfArray());

	if (is_array($iKey)) {
	    $wpPath = $objPage->SelfURL($iKey,TRUE);
	    $isActive = is_null($iActive)?FALSE:$iActive;
	} else {
	    $strKey = is_null($iKey)?$iDisp:$iKey;
	    $arLink['do'] = $strKey;
	    $arLink = $objPage->ArrayCheck($arLink);
	    $wpPath = $objPage->SelfURL($arLink,TRUE);
	    $isActive = is_null($iActive)?($objPage->Arg('do')==$strKey):$iActive;
	}

	$arEntry = array(
	  'type'	=> 'action',
	  'path'	=> $wpPath,
	  'popup'	=> $strPopup,
	  'displ'	=> $iDisp,
	  'active'	=> $isActive,
	  'isdef'	=> $iIsDefault
	  );
	$this->arMenu[] = $arEntry;
    }
    /*----
      ACTION: adds a static-text separator to the section menu
      FUTURE: Rename this to SepAdd or DividerAdd
    */
    public function SectionAdd($iDisp) {
	$arEntry = array(
	  'type'	=> 'section',
	  'displ'	=> $iDisp
	  );
	$this->arMenu[] = $arEntry;
    }
    public function ToggleAdd($iDisp,$iPopup=NULL,$iKey=NULL) {
	$strPopup = is_null($iPopup)?($iDisp.' '.$this->strDescr):$iPopup;
	$strKey = is_null($iKey)?$iDisp:$iKey;

	$objPage = $this->objFmt->Page();

	$arLink = $objPage->SelfArray();
	$isActive = $objPage->Arg($strKey);	// active state is indicated by presence of key
	$arLink[$strKey] = !$isActive;

	$arLink = $objPage->ArrayCheck($arLink);
	$wpPath = $objPage->SelfURL($arLink,TRUE);

	$arEntry = array(
	  'type'	=> 'toggle',
	  'path'	=> $wpPath,
	  'popup'	=> $strPopup,
	  'displ'	=> $iDisp,
	  'active'	=> $isActive
	  );
	$this->arMenu[] = $arEntry;
    }
    /*----
      PURPOSE: More flexible section link type
      INPUT:
	$iDisp = text to display
	$iArgs = arguments for link
	$iPopup = text for tooltip (optional)
      HISTORY:
	2011-01-02 Created to allow section commands to link to new-record-creation pages
    */
    public function CommandAdd($iDisp,array $iArgs,$iPopup=NULL) {
	$objPage = $this->objFmt->Page();
	$wpPath = $objPage->SelfURL($iArgs);
	$arEntry = array(
	  'type'	=> 'action',
	  'path'	=> $wpPath,
	  'popup'	=> $iPopup,
	  'displ'	=> $iDisp,
	  'isdef'	=> FALSE	// I've forgotten what this does, but there's a warning if it's not set
	  );
	$this->arMenu[] = $arEntry;
    }
    public function Generate() {
	$out = '<h'.$this->intLevel.'>';
	if (is_array($this->arMenu)) {
	    $arMenu = array_reverse($this->arMenu);	// right-alignment reverses things
	    foreach ($arMenu as $arLink) {
		$strType = $arLink['type'];
		switch ($strType) {
		  case 'action':
		    $htPath = $arLink['path'];
		    $htPopup = $arLink['popup'];
		    $htDispl = $arLink['displ'];
		    $isActive = !empty($arLink['active']) || $arLink['isdef'];
		    $htFmtOpen = $isActive?'<b>':'';
		    $htFmtShut = $isActive?'</b>':'';
		    $out .= '<span class="editsection">';
		    $out .= $htFmtOpen.'[';
		    $out .= '<a href="'.$htPath.'" title="'.$htPopup.'">'.$htDispl.'</a>';
		    $out .= ']'.$htFmtShut;
		    $out .= '</span>';
		    break;
		  case 'toggle':
		    $htPath = $arLink['path'];
		    $htPopup = $arLink['popup'];
		    $htDispl = $arLink['displ'];
		    $isActive = !empty($arLink['active']);
		    $htFmtOpen = $isActive?'<b>':'';
		    $htFmtShut = $isActive?'</b>':'';
		    $out .= '<span class="editsection">';
		    $out .= $htFmtOpen.'[';
		    $out .= '<a href="'.$htPath.'" title="'.$htPopup.'">'.$htDispl.'</a>';
		    $out .= ']'.$htFmtShut;
		    $out .= '</span>';
		    break;
		  case 'section':
		    $htDispl = $arLink['displ'];
		    $out .= '<span class="editsection">';
		    $out .= '| <b>'.$htDispl.'</b>:';
		    $out .= '</span>';
		    break;
		}
	    }
	}
	$out .= '<span class="mw-headline">'.$this->strName.'</span></h'.$this->intLevel.'>'."\n";
	return $out;
    }
    public function FormOpen() {
	$objPage = $this->objFmt->Page();
	$url = $objPage->SelfURL();
	$out = '<form method=post action="'.$url.'">'."\n";
	return $out;
    }
}
class clsWikiAdminSection { // DEPRECATED; use clsWikiFormatter / clsWikiSection
    private $strTitle;
    private $strDescr;
    private $doEdit;
    private $arKeep;
    //private $htPath;
    public static $arKeepDefault = array('page','id');

    public function __construct($iTitle, $iDescr=NULL, array $iKeep=NULL) {
	global $vgPage;

	$this->strTitle = $iTitle;
	$this->strDescr = is_null($iDescr)?$iTitle:$iDescr;
	$this->arKeep = is_null($iKeep)?(self::$arKeepDefault):$iKeep;
	$this->doEdit = $vgPage->Arg('edit');
	//$this->htPath = $vgPage->SelfURL(array('edit'=>!$this->doEdit));
	$this->htPath = $vgPage->SelfURL();
    }
    public function HeaderHtml(array $iMenu=NULL) {
	$out = '<h2>';
	if (is_array($iMenu)) {
	    foreach ($iMenu as $arLink) {
		$htPath = $arLink['path'];
		$htPopup = $arLink['popup'];
		$htDispl = $arLink['displ'];
		$isActive = !empty($arLink['active']);
		$out .= '<span class="editsection">';
		if ($isActive) {   $out .= '<b>'; }
		$out .= '[';
		if ($isActive) {   $out .= '</b>'; }
		$out .= '<a href="'.$htPath.'" title="'.$htPopup.'">'.$htDispl.'</a>';
		if ($isActive) {   $out .= '<b>'; }
		$out .= ']';
		if ($isActive) {   $out .= '</b>'; }
		$out .= '</span> ';
	    }
	} else {
	    $out = '';
	}
	$out .= '<span class="mw-headline">'.$this->strTitle.'</span></h2>';
	return $out;
    }
    public function HeaderHtml_OLD() {
	$out = '<h2>';
	if (!$this->doEdit) {
	  $out .= '<span class="editsection">[<a href="'.$this->htPath.'" title="Edit '.$this->strDescr.'">edit</a>]</span> ';
	}
	$out .= '<span class="mw-headline">'.$this->strTitle.'</span></h2>';
	return $out;
    }
    public function HeaderHtml_Edit(array $iMenu=NULL) {
	global $vgPage;

	if (is_array($this->arKeep)) {
	    foreach ($this->arKeep as $key) {
		$arLink[$key] = $vgPage->Arg($key);
	    }
	}

	$arLink['edit'] = TRUE;
	$wpPathEd = $vgPage->SelfURL($arLink,TRUE);

	$arLink['edit'] = FALSE;
	$wpPathVw = $vgPage->SelfURL($arLink,TRUE);

	// generate standard part of menu:
	$arMenu = array(
	  array(
	    'path'	=> $wpPathEd,
	    'popup'	=> 'edit '.$this->strDescr,
	    'displ'	=> 'edit',
	    'active'	=> $this->doEdit),
	  array(
	    'path'	=> $wpPathVw,
	    'popup'	=> 'view '.$this->strDescr,
	    'displ'	=> 'view',
	    'active'	=> !$this->doEdit));

	if (is_array($iMenu)) {
	    $arMenu = array_merge($arMenu,$iMenu);	// append passed menu
	}

	return $this->HeaderHtml($arMenu);
    }
    public function FormOpen() {
	if ($this->doEdit) {
	    $out = '<form method=post action="'.$this->htPath.'">'."\n";
	} else {
	    $out = NULL;
	}
	return $out;
    }
}
// TABBED CONTENTS
/*
  NOTES: There's probably a better way of doing this (e.g. using the same code used by the User Preferences page), but for now
    this uses the Tabber extension.
*/
function TabsOpen() {
    global $wgScriptPath,$wgOut;

    $path = $wgScriptPath . '/extensions/tabber/';
    $htmlHeader = '<script type="text/javascript" src="'.$path.'tabber.js"></script>'
	    . '<link rel="stylesheet" href="'.$path.'tabber.css" TYPE="text/css" MEDIA="screen">'
	    . '<div class="tabber">';
    $wgOut->addHTML($htmlHeader);
}
function TabsShut() {
    global $wgOut;

    $wgOut->addHTML('</div>');
}
function TabOpen($iName) {
    global $wgOut;

    $wgOut->addHTML('<div class="tabbertab" title="'.$iName.'"><p>');
}
function TabShut() {
    global $wgOut;

    $wgOut->addHTML('</p></div>');
}