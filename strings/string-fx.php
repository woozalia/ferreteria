<?php
/*
 PURPOSE: Temporary holding-cell for non-class string functions, until I'm sure all the calls have been replaced with class-based calls.
 HISTORY:
  2017-02-28 split off from strings.php
*/
/*###
  SECTION: utility functions
*/

// DEPRECATED
function Xplode($iString) {
    throw new exception('Xplode() is deprecated; call fcString::Xplode() instead.');
    return clsString::Xplode($iString);

/*
    if (!is_string($iString)) {
	throw new exception('Xplode() was given a non-string.');
    }
    $tok = substr ( $iString, 0, 1);	// token for splitting
    if ($tok) {
	$tks = substr ( $iString, 1 );	// tokenized string
	$list = explode ( $tok, $tks );	// split the string
	return $list;
    } else {
	return NULL;
    }
    */
}
/*
function nz(&$iVar,$iDefault=NULL) {
    if (isset($iVar)) {
	if (!is_null($iVar)) {
	    return $iVar;
	}
    }
    return $iDefault;
}
*/
// these variants may be unnecessary
function nzInt(&$iVar,$iDefault=0) {
    if (isset($iVar)) {
	if (!is_null($iVar)) {
	    return $iVar;
	}
    }
    return $iDefault;
}
function nzStr(&$iVar,$iDefault='') {
    if (isset($iVar)) {
	if (!is_null($iVar)) {
	    return $iVar;
	}
    }
    return $iDefault;
}
/*----
  ACTION: Concatenates two strings. If both are non-empty, separate them with iSep.
*/
function StrCat($iLeft,$iRight,$iSep) {
    throw new exception('Call fcString::StrCat() instead.');
    if (empty($iLeft) || empty($iRight)) {
	return $iLeft.$iRight;
    } else {
	return $iLeft.$iSep.$iRight;
    }
}
/*
  ACTION: Parses a block of text lines into an array, where the
    key for each element is the text up to the first space
    and the value is the rest of the line.
  DEPRECATED; use xtString::ParseTextLines()
*/
function ParseTextLines($iText,$iComment='!;',$iBlanks=" \t") {
    throw new exception('ParseTextLines() standalone is deprecated; use xtString_static::ParseTextLines().');
    $arLines = preg_split("/\n/",$iText);
    if (is_array($arLines)) {
	$xts = new xtString();
	$arOut = NULL;
	foreach ($arLines as $idx => $line) {
	    $xts->Value = $line;
	    $arSplit = $xts->SplitFirst($iComment);	// split at first comment character
	    $xts->Value = $arSplit[KS_BEFORE];
	    $xts->ReplaceSequence($iBlanks, ' ');		// replace all blanks with single space
	    $xts->DelLead(' ');					// remove any leading spaces
	    if ($xts->Value != '') {
		// if there's anything left, split it into key/value pair and add to output array
		$arSplit = $xts->SplitFirst(' ');			// split at first space
		$strKey = $arSplit[KS_BEFORE];
		$sqlFilt = '';
		$strVal = $arSplit[KS_AFTER];
		$arOut[$strKey] = $strVal;
	    }
	}
	return $arOut;
    } else {
	return NULL;
    }
}
