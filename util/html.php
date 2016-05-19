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

    static public function ArrayToAttrs(array $iarAttr=NULL) {
	$htAttr = '';
	if (is_array($iarAttr)) {
	    foreach ($iarAttr as $key => $val) {
		$htAttr .= ' '.$key.'="'.$val.'"';
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
	    echo "STEXT: ".clsArray::Render($sText);
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

    // ++ URLs ++ //
    // TODO: move these functions to clsHTTP?

    static public function RemoveBaseURI($fpBaseURL,$sPath=NULL) {
	throw new exception('Use clsHTTP for this function.');
    }
    static public function ParsePath($sPath,$sPathSep=KS_CHAR_PATH_SEP,$sArgSep=KS_CHAR_URL_ASSIGN) {
	throw new exception('Use clsHTTP for this function.');
    }

    // -- URLs -- //
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
class clsHTML extends fcHTML {}	// DEPRECATED alias
