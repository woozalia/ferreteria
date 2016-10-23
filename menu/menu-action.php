<?php
/*
  PURPOSE: for handling action-links on section bars
  NOTE: I tried going the route of descending these from menu-item classes, but they really don't
    quite work the same way. These select themselves (based on looking at the Page's internal state),
    while menu-item classes must be selected by an external object (this being more efficient when
    only one item may be selected at a time).
    
    There's probably some way to do a Grand Unified Smart-Link System, but it apparently requires more
	brain cycles than I currently have available.
  HISTORY:
    2013-12-06 adapting from mw/lib/menu.php
*/

/*%%%%
  PURPOSE: abstract handler for stuff that might go in the action/link area of a section header
*/
abstract class clsActionWidget {
    protected $objSect;	// section object
    private $strPopup;	// popup text (if any)

    // ++ SETUP ++ //

    public function __construct() {
	$this->oPage = NULL;
    }

    // -- SETUP -- //
    // ++ APP FRAMEWORK ++ //

    private $oPage;	// Page object
    public function Page(clsPageLogin $oPage=NULL) {
	if (!is_null($oPage)) {
	    $this->oPage = $oPage;
	} else if (is_null($this->oPage)) {
	    throw new exception('Attempting to access Page object before setting it.');
	}
	return $this->oPage;
    }

    // -- APP FRAMEWORK -- //

    /*----
      ACTION: render the link
    */
    abstract public function Render();

    /*----
      TODO: rename this to Descr()
	-- purpose-descriptive rather than appearance-descriptive
    */
    public function Popup($iPopup=NULL) {
	if (!is_null($iPopup)) {
	    $this->strPopup = $iPopup;
	}
	return $this->strPopup;
    }
/*
    public function Section(clsWikiSection2 $iSection=NULL) {
	if (!is_null($iSection)) {
	    $this->objSect = $iSection;
	}
	return $this->objSect;
    }
*/

}
abstract class clsActionLink_base extends clsActionWidget {
    protected $arData;	// key:value pairs to always include in link
    private $sDesc;	// descriptive text
    private $doAbs;	// TRUE = link's URL is added to Page's

    // ++ SETUP ++ //

    public function __construct(array $iarData) {
	$this->arData = $iarData;
	$this->UseAbsoluteURL(self::UseAbsoluteURL_default());
	parent::__construct();
    }

    // -- SETUP -- //
    // ++ STATUS/OPTIONS ++ //

    /*----
      RETURNS: TRUE if the current URL causes this link to be selected
	May also return a value rather than TRUE/FALSE.
    */
    abstract public function Selected();
    /*----
      RETURNS: the string to use in the URL
    */
    abstract protected function LinkKey();
    abstract protected function HasGroup();
    /*----
      ACTION: Sets the default UseAbsoluteURL value for new objects
    */
    static private $doAbsDefault = FALSE;
    static public function UseAbsoluteURL_default($doAbs=NULL) {
	if (!is_null($doAbs)) {
	    self::$doAbsDefault = $doAbs;
	}
	return self::$doAbsDefault;
    }
    public function UseAbsoluteURL($doAbs=NULL) {
	if (!is_null($doAbs)) {
	    $this->doAbs = $doAbs;
	}
	return $this->doAbs;
    }
    protected function Description($sText=NULL) {
	if (!is_null($sText)) {
	    $this->sDesc = $sText;
	}
	return $this->sDesc;
    }
    /*----
      RETURNS: URL for this link
      TODO: figure out *which* parts of the URL it needs to be
	responsible for
    */
    public function SelfURL() {
	$ar = $this->Values_forLink();
	return $this->Page()->SelfURL($ar,!$this->UseAbsoluteURL());
    }

    // -- STATUS/OPTIONS -- //
    // ++ VALUES ++ //

    /*----
      ACTION: sets a bunch of values to be used as defaults
	if there is not already a value for each key.
    */
    public function Values_default(array $arValues) {
	foreach ($arValues as $key => $val) {
	    $this->arData[$key] = clsArray::Nz($this->arData,$key,$val);
	}
    }
    // SET/RETURNS array of additional values to be included in the generated link URL
    private $arExtra;
    public function Values_extra(array $arValues=NULL) {
	if (!is_null($arValues)) {
	    $this->arExtra = $arValues;
	}
	if (empty($this->arExtra)) {
	    $this->arExtra = NULL;
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

    // -- VALUES -- //
    // ++ DISPLAY ++ //

    /*----
      ACTION: render the link
    */
    public function Render() {

	$ftURL = $this->SelfURL();
	$htPopup = fcString::EncodeForHTML($this->Description());
	$htDispl = fcString::EncodeForHTML($this->DisplayText());

	$isActive = $this->Selected();
	$cssClass = $isActive?'menu-link-active':'menu-link-inactive';

	$out = '<span class="ctrl-link-action">';
	$out .= '[<a class="'.$cssClass.'" href="'.$ftURL.'" title="'.$htPopup.'">'.$htDispl.'</a>]';
	$out .= '</span>';

	return $out;
    }

    // -- DISPLAY -- //
    // ++ CALCULATIONS ++ //
    
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

	return clsArray::Merge($ar,$this->Values_extra());
    }

    // -- CALCULATIONS -- //
}
/*%%%%
  PURPOSE: handles a single action-link (e.g. [edit]) on a section header
  TODO: This should be renamed; it's a particular type of link, possibly obsolete.
*/
class clsActionLink_modeless extends clsActionLink_base {
    private $sKey;	// string for URL
    private $sDisp;	// text to display

    /*----
      INPUT:
	iarData = list of URL values to always preserve
	iDisp = text to display
    */
    public function __construct(array $iarData,$sKey,$sDisp,$sDescr=NULL) {
	parent::__construct($iarData);
	$this->sKey = $sKey;
	$this->sDisp = $sDisp;
	$this->Description($sDescr);
	$this->objSect = NULL;
	$this->isActive = FALSE;
    }
    protected function DisplayText() {
	return $this->sDisp;
    }
    protected function LinkKey() {
	return $this->sKey;
    }
    protected function HasGroup() {
	return FALSE;
    }
    
    // ++ CEMENT ++ //
    
    /*----
      RETURNS: TRUE if the current URL causes this link to be selected
	May also return a value rather than TRUE/FALSE.
    */
    public function Selected() {
	return FALSE;
    }

    // -- CEMENT -- //
    
    /*----
      RETURNS: array of link's values, adjusted as needed for link behavior
	i.e. if link should toggle OFF, then the appropriate array value
	is set FALSE.
      VERSION: removes key from link if it is currently active
	This originally just returned $this->Values()
	It may be that this class should do that, and we need
	  a clsWikiSectionLink_toggle to do what this one does now.
    */
    /* 2015-05-08 this doesn't work anymore
    protected function Values_forLink() {
	$ar = $this->Values();

	$key = $this->Key();
	$ar[$key] = !$this->Selected();

	return $ar;
    }
    */
}
/*%%%%
  PURPOSE: handles a group of links where only one link in the group can be activated at one time
    If no group key is specified, each link acts like a toggle.
  USAGE: added by caller
*/
class clsActionLink_option extends clsActionLink_base {
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
    public function __construct(array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL,$iDescr=NULL) {
	parent::__construct($iarData);
	$this->sGKey = $iGroupKey;
	$this->sLKey = $iLinkKey;
	$this->sDi0 = is_null($iDispOff)?$iLinkKey:$iDispOff;
	$this->sDi1 = is_null($iDispOn)?$this->sDi0:$iDispOn;
	$this->Description($iDescr);
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
	return $this->Page()->PathArg($gk);	// return group's current value
    }
    public function Selected() {
	$lk = $this->LinkKey();			// key for this link
	if ($this->HasGroup()) {
	    $gv = $this->GroupVal();		// current group value
	    return ($gv==$lk);			// does group value match link key?
	} else {
	    $is = $this->Page()->PathArg($lk);
	    return $is;	// return TRUE iff link key exists in URL
	}
    }
    protected function DisplayText() {
	if ($this->Selected()) {
	    return $this->sDi1;
	} else {
	    return $this->sDi0;
	}
    }
}
/*%%%%
  PURPOSE: handles link-area subsections (static text)
*/
class clsAction_section extends clsActionWidget {
    protected $strDisp;

    public function __construct($iDisp) {
	$this->strDisp = $iDisp;
    }
    public function Render() {
	$out = '<span class="ctrl-link-action">';
	$out .= '| <b>'.$this->strDisp.'</b>:';
	$out .= '</span>';
	return $out;
    }
}
