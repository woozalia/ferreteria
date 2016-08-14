<?php
/*
  PURPOSE: library for extended string classes
  HISTORY:
    2009-10-23 Added constructor for xtString
    2009-11-22 xtString.KeepOnly()
    2010-11-04
      xtString.SplitFirst(): added isList parameter
      Moved ParseTextLines() here from SpecialVbzAdmin.php
    2010-11-07 StrCat()
    2011-09-09 Tentatively commenting out libmgr.php requirement; it doesn't seem to be used
    2012-03-04 non-class function Xplode(); moved functions above classes
    2015-09-12 moved xtTime from here to strings.php
  METHOD LIST:
    fcString::
      (static) FindNeedle($sHay,$sNeedle,$nDirection)
      (static) SplitFirst($s,$sMatch,$sIfNone=KS_AFTER)
      (static) SplitFinal($s,$sMatch,$sIfNone=KS_AFTER)
      (static) Xplode($iString)
      (static) DelTail($s,$sChars=NULL)
      (static) GetBefore($s,$sTarget)
      (static) GetAfter($s,$sTarget)
      (static) ReplaceSequence($iVal, $iChars, $iRepl, $iMax=0)
      (static) Pluralize($iQty,$iSingular='',$iPlural='s')
*/

//define('KI_BEFORE',-1);
//define('KI_AFTER',1);
// array index names
define('KS_BEFORE','before');
define('KS_AFTER','after');
define('KS_INDEX','index');
// search directions
define('KI_FIRST',1);
define('KI_FINAL',-1);

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

/*###
  SECTION: classes
*/

/*%%%%%
  CLASS: fcString
  PURPOSE: extended string functions, with emphasis on static methods
*/
class fcString {

    // ++ OPTIONS ++ //

    static private $isList = FALSE;	// FALSE = match string; TRUE = match any character in string
    static public function IsList($bYes=NULL) {
	if (!is_null($bYes)) {
	    self::$isList = $bYes;
	}
	return self::$isList;
    }

    // -- OPTIONS -- //
    // ++ DETECTION ++ //
    
    static public function IsBlank($s) {
	return (is_null($s) || ($s == ''));
    }
    
    // -- DETECTION -- //
    // ++ GENERAL SPLITTING ++ //

    /*----
      INPUT:
	$nDirection: KI_FIRST or KI_FINAL
      TODO: implement KI_FINAL for self:isList(TRUE) by reversing $sHay before searching,
	then reversing the found string
    */
    static protected function FindNeedle($sHay,$sNeedle,$nDirection) {
	$idx = NULL;
	if (self::isList()) {
	    $sFnd = strpbrk($sHay,$sNeedle);
	    if ($sFnd !== FALSE) {
		// get the index of the first found character
		$idx = strpos($sHay,$sFnd);
	    }
	} else {
	    switch($nDirection) {
	      case KI_FIRST:
		$idx = strpos($sHay,$sNeedle);
		break;
	      case KI_FINAL:
		$idx = strrpos($sHay,$sNeedle);
		break;
	    }
	    if ($idx === FALSE) {
		$idx = NULL;
	    }
	}
	return $idx;
    }
    /*-----
      ACTION: Splits the string at the first match
      RETURNS: Array containing the part before in array['before'] and the part after in array['after']
	...or NULL if no match found.
      INPUT:
	iMatch = string to look for
	iOpts = array of options
	  [isList]:
	    TRUE = iMatch is a list of characters - finding any one of them is a match
	    FALSE = iMatch is a string; the entire sequence must be found, in the same order
	  [ifNone]: where to put the string if no match was found
      HISTORY:
	2012-01-31 changed 2nd param to be option array
	2014-07-10 adapting from xtString to clsString (static method)
	2014-08-10 simplifying input parameters - use self::IsList() for 'isList' option
    */
    static public function SplitFirst($s,$sMatch,$sIfNone=KS_AFTER) {

	$idx = self::FindNeedle($s,$sMatch,KI_FIRST);
	if (is_null($idx)) {
	    $out[KS_BEFORE] = $out[KS_AFTER] = '';
	    $out[$sIfNone] = $s;
	} else {
	    $out[KS_BEFORE] = substr($s,0,$idx);
	    $out[KS_AFTER] = substr($s,$idx+1);
	}
	$out[KS_INDEX] = $idx;
	return $out;
    }
    /*----
      HISTORY:
	2014-07-10 adapting from xtString to clsString (static method)
	2014-08-10 added nIfNone parameter
    */
    static public function SplitFinal($s,$sMatch,$sIfNone=KS_AFTER) {
	$idx = self::FindNeedle($s,$sMatch,KI_FINAL);
	if (is_null($idx)) {
	    $out[KS_BEFORE] = $out[KS_AFTER] = '';
	    $out[$sIfNone] = $s;
	} else {
	    $out[KS_BEFORE] = substr($s,0,$idx);
	    $out[KS_AFTER] = substr($s,$idx+strlen($sMatch));
	}
	$out[KS_INDEX] = $idx;
	return $out;
    }
    static public function Xplode($iString) {
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
    }

    // -- GENERAL SPLITTING -- //
    // ++ SPECIFIC SPLITS ++ //

    /*----
      ACTION: Remove any characters in $iChars from the end of the string; stop when a non-$iChars character is found
    */
    static public function DelTail($s,$sChars=NULL) {
	if (is_null($sChars)) {
	    $out = rtrim($s);
	} else {
	    $out = rtrim($s,$sChars);
	}
	return $out;
    }
    /*----
      ACTION: Return only the portion of $s prior to the final occurance of $sTarget
    */
    static public function GetBefore($s,$sTarget) {
	$ar = self::SplitFirst($s,$sTarget,KS_BEFORE);
	return $ar[KS_BEFORE];
    }
    /*----
      ACTION: Return only the portion of $s after the final occurrence of $sTarget
    */
    static public function GetAfter($s,$sTarget) {
	$ar = self::SplitFinal($s,$sTarget,KS_AFTER);
	return $ar[KS_AFTER];
    }

    // -- SPECIFIC SPLITS -- //
    // ++ CONCATENATION ++ //
  
    /*----
      ACTION: Concatenates two strings. If both are non-empty, separate them with iSep.
    */
    static public function StrCat($iLeft,$iRight,$iSep) {
	if (empty($iLeft) || empty($iRight)) {
	    return $iLeft.$iRight;
	} else {
	    return $iLeft.$iSep.$iRight;
	}
    }
    /*----
      NOTES:
	* Elements that are blank ("") are treated the same as NULL -- no separator is added.
	* Apparently we don't have PHP 5.6 yet? Otherwise we could use a variable number of parameters:
	  static public function Concat($sSep,...$sStrings)
    */
    static public function ConcatArray($sSep,array $sStrings) {
	$out = NULL;
	foreach ($sStrings as $sString) {
	    if (!is_null($out)) {
		if (!is_null($sString)) {
		    $out .= $sSep;
		}
	    }
	    if (!empty($sString)) {
		if (!is_string($sString)) {
		    throw new exception('Array element is not string.');
		}
		$out .= $sString;
	    }
	}
	return $out;
    }
    /*----
      ACTION: if $sTest is not NULL, returns $sToUse; otherwise returns $sIfNull.
    */
    static public function IfPresent($sTest,$sToUse,$sIfNull=NULL) {
	if (is_null($sTest)) {
	    return $sIfNull;
	} else {
	    return $sToUse;
	}
    }
    /*----
      ACTION: if $sValue is not NULL, returns $sPfx.$sValue.$sSfx; otherwise returns NULL.
	I ended up not actually using this, but I'm pretty sure there are some situations
	where it will come in handy -- so keeping it.
    */
    static public function MarkIfPresent($sPfx,$sValue,$sSfx=NULL) {
	if (is_null($sValue)) {
	    return NULL;
	} else {
	    return $sPfx.$sValue.$sSfx;
	}
    }
    
    // -- CONCATENATION -- //
    // ++ REPLACEMENT ++ //

    // NOTE: A good default for KS_CHARACTER_ENCODING seems to be 'ISO-8859-15'.
    static public function EncodeForHTML($sVal) {
	return htmlspecialchars($sVal,ENT_QUOTES,KS_CHARACTER_ENCODING);
    }
    /*----
      ACTION: Replace any
	{{character sequences where all characters are found in $iChars}
	that are longer than $iMax}
	with the string $iRepl
      HISTORY:
	2016-01-19 This must have been broken until just now, because the code consistently expected
	  $sVal to be named $in.
    */
    static public function ReplaceSequence($sVal, $sChars, $sRepl, $nMax=0) {
	$out = '';
	$lenIn = strlen($sVal);
	$rpos = 0;

	while ($rpos < $lenIn) {
	    $fnd = strspn($sVal,$sChars,$rpos);
	    if ($fnd > 0) {
	    // found a sequence; does it exceed $iMin?
		if ($fnd > $nMax) {
		    // yes - replace it
		    $out .= $sRepl;
		} else {
		    // no - keep it
		    $out .= substr($sVal,$rpos,$fnd);
		}
		$rpos += $fnd;	// advance the read pointer
	    } else {
		// matching sequence not found at current position, so gobble up non-matching chars until the next one
		$fnd = strcspn($sVal,$sChars,$rpos);
		$add = substr($sVal,$rpos,$fnd);
		$out .= $add;
		$rpos += $fnd;	// advance the read pointer
	    }
	}
	return $out;
    }
    
    // -- REPLACEMENT -- //
    // ++ NATURAL LANGUAGE ++ //

    static public function Pluralize($iQty,$iSingular='',$iPlural='s') {
	if ($iQty == 1) {
	    return $iSingular;
	} else {
	    return $iPlural;
	}
    }
    static public function NoYes($iBool,$iNo='no',$iYes='yes') {
	if ($iBool) {
	    return $iYes;
	} else {
	    return $iNo;
	}
    }

    // -- NATURAL LANGUAGE -- //
    // ++ ENCODING ++ //
    
    static public function Random($iLen) {
    $out = '';
    for ($i = 0; $i<$iLen; $i++) {
	$n = mt_rand(0,61);
	$out .= self::CharHash($n);
    }
	return $out;
    }
    static public function CharHash($iIndex) {
	if ($iIndex<10) {
	    return $iIndex;
	} elseif ($iIndex<36) {
	    return chr($iIndex-10+ord('A'));
	} else {
	    return chr($iIndex-36+ord('a'));
	}
    }
}


