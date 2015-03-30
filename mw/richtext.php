<?php
/*
 LIBRARY: richtext.php - classes for generalizing interaction with formatted text UI
  This is needed so we don't have to write separate functions for handling HTML and wikitext
 HISTORY:
  2009-12-15 (Wzl) created
*/

abstract class clsRichText {
    private $wpBase;

    public static $chSelfLink_Assign = ':';


    public function __construct($iBasePath) {
	$this->wpBase = $iBasePath;
    }

    /*----
      ACTION: outputs text via an appropriate means, depending on format
    */
    abstract public function AddText($iText);
    abstract public function Header($iText,$iLevel=2);
    abstract public function WikiLink($iPage,$iShow=NULL,$iPopup=NULL);	// relative to wiki base
    abstract public function SiteLink($iPath,$iShow=NULL,$iPopup=NULL);	// relative to site base (usually "http://domain/")
    // return self-URL that is appropriate for the current output format
    abstract public function LinkURL(array $iParams);
    // table functions
    abstract public function TableOpen($iAttr=NULL);
    abstract public function TableShut();
    abstract public function TblRowOpen($iAttr=NULL,$iHdr=FALSE);
    abstract public function TblRowShut();
    abstract public function TblCell($iText,$iAttr=NULL);
    /*----
      INPUT:
	iOn turns selection state ON (if TRUE) or OFF (if FALSE)
	iText: show as SELECTED if iOn is TRUE.
      USAGE: Caller is responsible for balancing (each ON is always followed by exactly one OFF).
    */
    abstract public function Selected($iOn,$iText);
    abstract public function Italic($iText);
    public function BasePath() {
	return $this->wpBase;
    }
    public function SelfURL(array $iParams) {
	$strPathSfx = $this->ArrayToPath($iParams);
	$strPathFull = $this->wpBase.$strPathSfx;
//throw new exception('How did we get here?');
	return $strPathFull;
    }
    abstract public function SelfLink(array $iParams,$iShow,$iPopup=NULL);
    public function ArrayToPath(array $iParams) {
	global $chSelfLink_Assign;

	$out = '';
	foreach ($iParams AS $key => $val) {
	    if ($val !== FALSE) {
		$out .= '/';	// only add *between* items
		if ($val === TRUE) {
		    $out .= $key;
		} else {
		    $out .= $key.self::$chSelfLink_Assign.$val;
		}
	    }
	}
	return $out;
    }
}
/*====
 This implementation actually has one MediaWiki-specific method, AddText().
  Later on, this should probably be in a descendant class (clsHTML_MW or similar).

  HISTORY:
    2014-10-25 Renaming from clsHTML to clsRT_HTML to avoid conflict with clsHTML in html.php
*/
class clsRT_HTML extends clsRichText {

    public function LinkURL(array $iParams) {
	return $this->SelfURL($iParams);
    }
    public function AddText($iText) {
	global $wgOut;

	$wgOut->AddHTML($iText);
    }
    public function SelfLink(array $iParams,$iShow,$iPopup=NULL) {
	if (is_null($iShow)) {
	    return NULL;
	} else {
	    $strPathFull = $this->SelfURL($iParams);
//echo 'STRPATHFULL=['.$strPathFull.']<br>';
	    $out = $this->SiteLink($strPathFull,$iShow,$iPopup);
	    return $out;
	}
    }
    /*----
      HISTORY:
	2010-10-01 Removed initial '/' from $out because it doesn't work with wikis that are not in the root
    */
/*
    public function SelfURL(array $iParams) {
	return '/'.parent::SelfURL($iParams);
    }
*/
    public function Header($iText,$iLevel=2) {
	return "\n<h$iLevel>$iText</h$iLevel>";
    }
    /*----
      NOTES:
	* When $wgScriptPath is blank, a '/' needs to be in its place
	* When $wgScriptPath is not blank, it apparently needs a '/' after it
      HISTORY:
	2010-11-25 When iPopup is set, creates direct HTML instead of using pareseInLine().
	  Eventually I will figure a non-ugly way to insert attributes into MW-generated links.
	  Or maybe MW will start supporting the title attribute, plzicanhaz?
	2012-03-01 The rule about $wgScriptPath was not being implemented; it was just
	  $wgScriptPath.'/' for all cases, which was causing problems when it wasn't blank
	  (because it included "http://...", which became "/http://...").
	  However, apparently we don't need the '/' even when $wgScriptPath is blank...
	  so totally removing the whole prefix thing for now.
	2012-03-04 ...and using wikicode when there is no Popup is kind of broken.
	  If we want to get "page detection" functionality, it's probably better to go
	  directly to a Title object and ask it for link HTML. LATER.
	2012-05-12 Had to get Title object and ask it for URL so interwiki would work.
	  This doesn't handle page-detection yet. If you find the function for that, then
	  the only problem is how to insert the popup text.
    */
    public function WikiLink($iPage,$iShow=NULL,$iPopup=NULL) {
	global $wgOut;
	$strShow = is_null($iShow)?$iPage:$iShow;

/*
	if (is_null($iPopup)) {
	    $wtOut = '[['.$iPage;
	    if (!is_null($iShow)) {
		$wtOut .= '|'.$iShow;
	    }
	    $wtOut .= ']]';
	    $out = $wgOut->parseInLine($wtOut);
	} else {
*/
/*	    global $wgScriptPath;

	    if (empty($wgScriptPath)) {
		$vgMWLibPfx = '/';
	    } else {
		$vgMWLibPfx = $wgScriptPath;
	    }
	    $out = '<a href="'.$vgMWLibPfx.$iPage.'" title="'.htmlspecialchars($iPopup).'">'.$strShow.'</a>';
*/
	    $mwTitle = Title::newFromText($iPage);
	    $wp = $mwTitle->getFullURL();

//	    $out = '<a href="'.$wp.'" title="'.htmlspecialchars($iPopup).'">'.$strShow.'</a>';
	    $out = $this->SiteLink($wp,$strShow,$iPopup);
//	}
	return $out;
    }
    public function SiteLink($iPath,$iShow=NULL,$iPopup=NULL) {
	$out = '<a href="'.$iPath.'" title="'.htmlspecialchars($iPopup).'">'.$iShow.'</a>';
	return $out;
    }
    // TABLE FUNCTIONS
    public function TableOpen($iAttr=NULL) {
	$htAttr = is_null($iAttr)?'':' '.$iAttr;
	return "\n<table$htAttr>";
    }
    public function TableShut() {
	return "\n</table>";
    }
    public function TblRowOpen($iAttr=NULL,$iHdr=FALSE) {
	$htAttr = is_null($iAttr)?'':' '.$iAttr;
	$this->isHdr = $iHdr;
	return "\n<tr$htAttr>";
    }
    public function TblRowShut() {
	return "\n</tr>";
    }
    public function TblCell($iText,$iAttr=NULL) {
	$htAttr = is_null($iAttr)?'':' '.$iAttr;
	$htTag = ($this->isHdr)?'th':'td';
	return "\n<$htTag$htAttr>$iText</$htTag>";
    }
    /*----
      IMPLEMENTATION: For now, displays selected as bold and non-selected as normal.
	Maybe add a background color later.
    */
    public function Selected($iOn,$iText) {
	if ($iOn) {
	    return '<b>'.$iText.'</b>';
	} else {
	    return $iText;
	}
    }
    public function Italic($iText) {
	return "<i>$iText</i>";
    }
}
/*----
  HISTORY:
    2014-10-25 Renaming from clsWikiText to clsRT_Wiki for consistency with clsRT_HTML
*/
class clsRT_Wiki extends clsRichText {
    /*
      This has to be declared because it's abstract in the base -- but who uses it?
    */
    public function LinkURL(array $iParams) {
	//return $this->
    }

    public function SelfLink(array $iParams,$iShow,$iPopup=NULL) {
	if (is_null($iShow)) {
	    return NULL;
	} else {
	    $strPathFull = $this->SelfURL($iParams);
//echo 'STRPATHFULL=['.$strPathFull.']<br>';
	    $out = $this->WikiLink($strPathFull,$iShow,$iPopup);
	    return $out;
	}
    }

    public function AddText($iText) {
	global $wgOut;

	$wgOut->addWikiText($iText,FALSE);
    }
    public function Header($iText,$iLevel=2) {
	return "\n<h$iLevel>$iText</h$iLevel>";	// this works in wikitext too, so why not.
    }
    /*----
      NOTE: wikitext can't do popups, so we ignore iPopup
    */
    public function WikiLink($iPage,$iShow=NULL,$iPopup=NULL) {
	if (is_null($iShow)) {
	    return '[['.$iPage.']]';
	} else {
	    return '[['.$iPage.'|'.$iShow.']]';
	}
    }
    public function SiteLink($iPath,$iShow=NULL,$iPopup=NULL) {
	echo 'IPATH=['.$iPath.'] ISHOW=['.$iShow.']<br>'."\n";
	throw new exception('How did we get here, again?');
    }
    // TABLE FUNCTIONS
    public function TableOpen($iAttr=NULL) {
	$htAttr = is_null($iAttr)?'':' '.$iAttr;
	return "\n{|$htAttr";
    }
    public function TableShut() {
	return "\n|}";
    }
    public function TblRowOpen($iAttr=NULL,$iHdr=FALSE) {
	$htAttr = is_null($iAttr)?'':' '.$iAttr;
	$this->isHdr = $iHdr;
	return "\n|-$htAttr";
    }
    public function TblRowShut() {
	return '';
    }
    public function TblCell($iText,$iAttr=NULL) {
	$htAttr = is_null($iAttr)?'':' '.$iAttr;
	$htTag = ($this->isHdr)?'!':'|';
	return "\n$htTag$htAttr | $iText";
    }
    /*----
      IMPLEMENTATION: For now, displays selected as bold and non-selected as normal.
	Maybe add a background color later.
    */
    public function Selected($iOn,$iText) {
	if ($iOn) {
	    return "'''$iText'''";
	} else {
	    return $iText;
	}
    }
    public function Italic($iText) {
	return "''$iText''";
    }
}
/********
 FORM HANDLING
  needs to be a separate library eventually
*/
class clsUpdateRow {
    public $arUpd;	// array for values to update
    private $arCurr;	// current row values
    private $arFields;	// array of field objects

    /*-----
      INPUT: Row array from a clsDataSet
    */
    public function __construct(array $iRow=NULL) {
	$this->arCurr = $iRow;
    }
    public function AddField(clsUpdateField $iField) {
	$strName = $iField->Name();
	assert('!empty($strName)');
	$this->arFields[$strName] = $iField;
    }
    public function CurrVal($iName) {
	return nz($this->arCurr[$iName]);
    }
    public function Change($iName,$iVal) {
	$this->arUpd[$iName] = $iVal;
    }
    /*-----
      RETURNS: Array of modified values as set by clsUpdateField::NewVal()
    */
    public function ChangedValues() {
	return $this->arUpd;
    }
    public function ChangedValuesSQL() {
	if (is_array($this->arUpd)) {
	    foreach ($this->arUpd as $name=>$val) {
		$arOut[$name] = SQLValue($val);
	    }
	    return $arOut;
	} else {
	    return NULL;
	}
    }
    /*-----
      RETURNS: Array of current values -- new value if changed, old value if not
    */
    public function CurrentValues() {
	$arOut = NULL;
	foreach ($this->arFields as $name=>$field) {
	    if (isset($this->arUpd[$name])) {
		$val = $this->arUpd[$name];
	    } else {
		// nz() is needed in case we're dealing with a new record
		$val = nz($this->arCurr[$name]);
	    }
	    $arOut[$name] = $val;
	}
	return $arOut;
    }
    /*-----
      RETURNS: Array of all values - old value if not updated by NewVal(),
	otherwise the new value.
    */
    public function AllValues() {
	foreach ($this->arCurr as $name=>$val) {
	    if (isset($this->arUpd[$name])) {
		$arOut[$name] = $this->arUpd[$name];
	    } else {
		$arOut[$name] = $val;
	    }
	}
	return $arOut;
    }
    public function OldValues() {
	return $this->arCurr;
    }
}
class clsUpdateField {
    private $objRow;
    private $strName;

    public function __construct(clsUpdateRow $iRow,$iName) {
	assert('!empty($iName)');
	$this->objRow = $iRow;
	$this->strName = $iName;
	$iRow->AddField($this);
    }
    public function Name() {
	return $this->strName;
    }
    protected function OldVal() {
	return $this->objRow->CurrVal($this->strName);
    }
    protected function Change($iNew) {
	$this->objRow->Change($this->strName,$iNew);
    }
    public function NewVal($iNew) {
	if ($this->OldVal() != $iNew) {
	    $this->Change($iNew);
	}
    }
}
class clsUpdateField_Time extends clsUpdateField {
    public function NewVal($iNew) {
	if ($this->OldVal() != $iNew) {
	    if (is_string($iNew)) {
		$utNew = strtotime($iNew);
		$utOld = strtotime($this->OldVal());
		if ($utNew != $utOld) {
		    $strNew = date('c',$utNew);
		    $this->Change($strNew);
		}
	    } else {
		if ($this->OldVal() != '') {
		    $this->Change(NULL);
		}
	    }
	}
    }
}
class clsUpdateField_Numeric extends clsUpdateField {
    public function NewVal($iNew) {
	if (is_numeric($iNew)) {
	    $vNew = $iNew;
	} else {
	    $vNew = NULL;
	}
	parent::NewVal($vNew);
    }
}