<?php
/*
  PURPOSE: classes for rendering page-sections with optional menus in MediaWiki
    This needs some serious cleaning up and reorganizing. Any way to make it
      work better with the similar functionality available elsewhere in Ferreteria?
  HISTORY:
    2015-05-08 split off from menu.php
*/

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

    public function __construct(clsWikiFormatter $oFmt,$sTitle,$nLevel=2) {
	$this->objFmt = $oFmt;
	$this->strTitle = $sTitle;
	$this->intLevel = $nLevel;
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
	    $arOut = NULL;
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
    throw new exception('How do we get here?');
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
/*
2011-03-30 who is using these?
2011-10-17 ArgsToKeep() and ArgsToAdd() were being used by SpecialWorkFerret clsWFSession.SectionHdr(); checking to see if actually needed...
  no, they aren't -- DEPRECATED -- use $vgPage-> functions instead.
    public function ArgsToKeep(array $iKeep) {
	$this->arKeep = $iKeep;
    }
    public function ArgsToAdd(array $iAdd) {
	$this->arAdd = $iAdd;
    }
    protected function ArrayCheck(array $iarCheck) {
	if (is_array($this->arAdd)) {
	    $arOut = array_unique(array_merge($iarCheck,$this->arAdd));
	} else {
	    $arOut = $iarCheck;
	}
	return $arOut;
    }
    public function SelfURL() {
	$arLink = $this->SelfArray();
	$objPage = $this->objFmt->Page();
	$wpPath = $objPage->SelfURL($arLink,TRUE);
	return $wpPath;
    }
    public function SelfArray() {
	$objPage = $this->objFmt->Page();
	if (is_array($this->arKeep)) {
	    foreach ($this->arKeep as $key) {
		$arLink[$key] = $objPage->Arg($key);
	    }
	}
	return $this->ArrayCheck($arLink);
    }
*/
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
