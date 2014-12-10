<?php
/*
  PURPOSE: HTML-specific functions
    partly just so they can be in a class, which can be autoloaded
  NOTE: functions for managing URLs are in clsHTTP
  HISTORY:
    2013-12-12 started - ArrayToAttrs(), BuildLink()
*/
class clsHTML {

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
    static public function BuildLink($urlBase,$sText,$sDescr=NULL,$arAttr=NULL) {
	$htURL = '"'.$urlBase.'"';
	if (is_null($sDescr)) {
	    $htDescr = '';
	} else {
	    $htDescr = ' title="'.htmlspecialchars($sDescr).'"';
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