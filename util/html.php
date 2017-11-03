<?php
/*
  PURPOSE: HTML-specific functions
    partly just so they can be in a class, which can be autoloaded
  NOTE: functions for managing URLs are in clsHTTP
  HISTORY:
    2013-12-12 started - ArrayToAttrs(), BuildLink()
*/
class fcHTML {

    // ++ COMPONENTS ++ //

    static public function CheckBox($sName,$bSelected,$id=NULL) {
	$htSel = $bSelected?' checked':'';
	$htName = is_null($id)?$sName:($sName.'['.$id.']');
	$out = "<input name=$htName type=checkbox$htSel>";
	return $out;
    }

    // -- COMPONENTS -- //
    // ++ ARRAYS ++ //

    static public function ArrayToAttrs(array $arAttr=NULL) {
	$htAttr = '';
	if (is_array($arAttr)) {
	    foreach ($arAttr as $key => $val) {
		if (is_scalar($val)) {
		    $htAttr .= ' '.$key.'="'.$val.'"';
		} else {
		    echo 'arAttr:'.fcArray::Render($arAttr);
		    throw new fcDebugException("Ferreteria Usage Error: Array value for [$key] is not a scalar. Type=".gettype($val));
		}
	    }
	}
	return $htAttr;
    }
    /*----
      HISTORY:
	2016-02-24 I was filtering the popup with htmlspecialchars(), but that prevents HTML entities. Removing.
    */
    static public function BuildLink($urlBase,$sText,$sDescr=NULL,$arAttr=NULL) {
	if (is_array($sText)) {
	    echo "STEXT: ".fcArray::Render($sText);
	    throw new exception('Internal Error: Received an array for $sText instead of a string.');
	}
	$htURL = '"'.$urlBase.'"';
	if (is_null($sDescr)) {
	    $htDescr = '';
	} else {
	    $htDescr = ' title="'.$sDescr.'"';
	}
	if (is_null($arAttr)) {
	    $htAttr = NULL;
	} else {
	    $htAttr = ' '.static::ArrayToAttrs($arAttr);
	}
	$out = "<a href=$htURL$htDescr$htAttr>$sText</a>";
	return $out;
    }

    // -- ARRAYS -- //
    // ++ WIDGETS ++ //

    /*----
      TODO: There's probably a way to set the actual display-character via CSS, too.
	This should be done.
    */
    static public function fromBool($iVal) {
	if ($iVal) {
	    $out = '<span class="true" title="yes">&radic;</span>';
	} else {
	    $out = '<span class="false" title="no">&ndash;</span>';
	}
	return $out;
    }
    /*----
      ACTION: Apply CSS style depending on whether flag is set
    */
    static public function FlagToFormat($sShow,$bFlag,$cssOn='state-on',$cssOff='state-off') {
	$cssSpan = $bFlag?$cssOn:$cssOff;
	$out = "<span class=$cssSpan>$sShow</span>";
	return $out;
    }
    /*----
      INPUT:
	$htName - name for the input element
	$arRows - array of rows: array(ID => display)
	$idDefault - default value to choose
	$sChoose - optional string for first row, telling user to choose something
	  If NULL, no such row is displayed.
	  If displayed, and user leaves it there, returned value is NULL/blank.
    */
    static public function DropDown_arr($htName,array $arRows,$idDefault,$sChoose=NULL) {
	$out = "\n<select name='$htName'>";
	if (!is_null($sChoose)) {
	    $htSelect = is_null($idDefault)?' selected':NULL;
	    $out .= "\n  <option$htSelect value=''>$sChoose</option>";
	}
	foreach ($arRows as $id => $sDisplay) {
	    $htSelect = ($id==$idDefault)?' selected':NULL;
	    $out .= "\n  <option$htSelect value='$id'>$sDisplay</option>";
	}
	$out .= "\n</select>";
	return $out;
    }
    
    // -- WIDGETS -- //
    // ++ FORMATS ++ //
    
    static public function RespectLineBreaks($s) {
	return str_replace("\n",'<br />',$s);
	// NOTE: could probably use nl2br() instead. Test later.
    }
    // NOTE: This is actually specifically for use in attribute values, and should probably be renamed to reflect that.
    static public function FormatString_forTag($s) {
	return htmlspecialchars($s,ENT_QUOTES);
    }
    // RETURNS: string with brackets converted to entities so they are visible
    static public function FormatString_SafeToOutput($s) {
	return htmlspecialchars($s);
    }
    
    // -- FORMATS -- //

    /*----
      INPUT: arArgs = array of unparsed URL path arguments
      OUTPUT: this->arArgs = array of parsed URL path arguments
	[name] = value
      LATER: we might want to make the path-segment parsing into
	a separate method, so descendants could implement it
	differently. Right now, anything after a 2nd ':' is discarded.
      2013-12-12
	renamed from ParseArgs to ParsePathArray
	moved from clsMenuItem to clsHTML (static)
    */
/*
    static public function ParsePathArray(array $arArgs=NULL,$sSep=':') {
	$arOut = NULL;
	if (is_array($arArgs)) {
	    foreach ($arArgs as $part) {
		// parse the path segment
		$arPieces = explode($sSep,$part);
		if (count($arPieces) == 1) {
		    $arOut[$part] = TRUE;
		} else {
		    $arOut[$arPieces[0]] = $arPieces[1];
		}
	    }
	}
	return $arOut;
    }
*/
}
class fcHTML_Parser extends fcStringDynamic {
    
    // ++ STATUS ++ // - other output

    private $sTagName;
    protected function SetTagName($s) {
	$this->sTagName = $s;
    }
    public function GetTagName() {
	return $this->sTagName;
    }
    private $sTagValue;	// all attributes, not parsed
    protected function SetTagValue($s) {
	$this->sTagValue = $s;
    }
    public function GetTagValue() {
	return $this->sTagValue;
    }
    private $bClosesBlock;
    protected function SetClosesBlock($b) {
	$this->bClosesBlock = $b;
    }
    public function GetClosesBlock() {
	return $this->bClosesBlock;
    }
    private $bClosesSelf;
    protected function SetClosesSelf($b) {
	$this->bClosesSelf = $b;
    }
    public function GetClosesSelf() {
	return $this->bClosesSelf;
    }

    // -- STATUS -- //
    // ++ CALCULATIONS ++ //
    
    /*----
      ASSUMES: GetValue() a single HTML tag, optionally followed by some inter-tag text.
	* First character should be "<".
	* Must be exactly one "<" and exactly one ">".
    */
    public function ParseTag() {
	$sPiece = $this->GetValue();
	
	$htPiece = htmlspecialchars($sPiece);
	
	// $sTag = "<name value/>intertag"
	fcString::SetIsCharList(TRUE);	// set splitting option: look for any char in divider string
	$sTag = fcString::GetAfterFirst($sPiece,'<');		// = "name value/>"

	fcString::SetPartToReturn(fcString::KS_BEFORE);
	$sName = fcString::Get_fromSplit($sTag," >\t");		// $sName = "name" or "name/"
	$sRem = fcString::GetSplitResult(fcString::KS_AFTER);	// $sRem = "value/>inter-tag text" or just "inter-tag text"
	if (fcString::IsBlank($sRem)) {
	    // tag has no value portion
	    $sVal = NULL;
	    if (substr($sName,-1,1) == '/') {
		$this->SetClosesSelf(TRUE);
		$sName = substr($sName,0,-1);	// remove ending '/'
	    } else {
		$this->SetClosesSelf(FALSE);
	    }
	    
	} else {
	    // $sRem = "value/>inter-tag text"
	    $sRem = fcString::Get_fromSplit($sRem,'>');		// $sRem = "value/"
	    $sInter = fcString::GetSplitResult(fcString::KS_AFTER);	// $sInter = "inter-tag text"
	    
	    //fcString::SetPartToReturn(fcString::KS_BEFORE);
	    //$sRem = fcString::GetSplitResult(fcString::KS_AFTER);	// $sRem = "value/"
	    
	    // NOTE 2017-03-06: for now, $sInter is discarded. Maybe we should save it.
	    if (substr($sRem,-1,1) == '/') {
		$this->SetClosesSelf(TRUE);
		$sVal = trim(substr($sRem,0,strlen($sRem)-1));
	    } else {
		$this->SetClosesSelf(FALSE);
		$sVal = trim($sRem);
	    }
	}
	$sInter = fcString::Get_fromSplit($sRem,'>');		// $sInter = "inter-tag text"
	
	if (substr($sName,0,1) == '/') {
	    $this->SetClosesBlock(TRUE);
	    $sName = substr($sName,1);
	} else {
	    $this->SetClosesBlock(FALSE);
	}
	$this->SetTagName($sName);
	$this->SetTagValue($sVal);
    }
    public function HighlightName($sTag) {
	fcString::SetIsCharList(TRUE);	// set splitting option: look for any char in divider string
	$sTag = fcString::GetAfterFirst($sPiece,'<');
	$sTag = fcString::GetBeforeFirst($sTag," >\t");
    }
    
    // -- CALCULATIONS -- //
}