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
    2015-09-12 moved xtTime from strings.php to time.php
    2017-02-28 some tweaks:
      Made the defines into class constants, which will definitely cause code errors until usage is updated.
      Added option for PartToReturn, split IsList() into SetIsList()/GetIsList().
      Various smaller tweaks which might break code in some cases.
*/

//define('KI_BEFORE',-1);
//define('KI_AFTER',1);
// array index names
//define('KS_BEFORE','before');
//define('KS_AFTER','after');
//define('KS_INDEX','index');
// search directions
//define('KI_FIRST',1);
//define('KI_FINAL',-1);

/*::::
  CLASS: fcString
  PURPOSE: extended string functions, with emphasis on static methods
*/
class fcString {
    // ++ OPTION CONSTANTS ++ //

    // TODO: Maybe these should be named KENUM_* or just KE_*
    // array index names
    const KS_BEFORE = 'before';
    const KS_AFTER = 'after';
    const KS_INDEX = 'index';
    // search directions
    const KI_FIRST = 1;
    const KI_FINAL = -1;
    
    // -- OPTION CONSTANTS -- //
    // ++ OPTIONS ++ //

    static private $isCharList = FALSE;	// FALSE = match string; TRUE = match any character in string
    static public function SetIsCharList($b) {
	self::$isCharList = $b;
    }
    static public function GetIsCharList() {
	return self::$isCharList;
    }
    static public function IsList($bYes=NULL) {
	throw new exception('IsList() is deprecated; call SetIsCharList() or GetIsCharList().');
    }
    // VALUES: KS_BEFORE, KS_AFTER
    static private $vPartToReturn;
    static public function SetPartToReturn($v) {
	self::$vPartToReturn = $v;
    }
    static protected function GetPartToReturn() {
	if (!isset(self::$vPartToReturn)) {
	    self::$vPartToReturn = self::KS_AFTER;	// default
	}
	return self::$vPartToReturn;
    }
    static private $vPartForNone;
    static public function SetPartForNone($v) {
	self::$vPartForNone = $v;
    }
    static protected function GetPartForNone() {
	if (!isset(self::$vPartForNone)) {
	    self::$vPartForNone = self::KS_AFTER;	// default
	}
	return self::$vPartForNone;
    }
    static private $vDirection;
    static public function SetDirection($v) {
	self::$vDirection = $v;
    }
    static protected function GetDirection() {
	if (!isset(self::$vDirection)) {
	    self::$vDirection = self::KI_FIRST;		// default
	}
	return self::$vDirection;
    }

    // -- OPTIONS -- //
    // ++ STATUS ++ // - other output
    
    static private $arSplit;	// results from last Split operation
    static protected function SetSplitResult(array $ar) {
	self::$arSplit = $ar;
    }
    static public function GetSplitResult($part) {
	return self::$arSplit[$part];
    }
    // PURPOSE: debugging
    static public function RenderSplitResult() {
	return fcArray::Render(self::$arSplit);
    }
    
    // -- STATUS -- //
    // ++ DETECTION ++ //
    
    // NOTE: (2017-02-28) It seems odd to me that there is no native PHP function for this.
    static public function IsBlank($s) {
	return (is_null($s) || ($s == ''));
    }
    
    // -- DETECTION -- //
    // ++ SEARCHING ++ //

    /*----
      PURPOSE: Find the first/last match for $sNeedle in $sHay
	* equivalent to strpos(), but respects fcString option constants/settings
      INPUT:
	$sHay: string to search in
	$sNeedle: string or characters to search for
	$nDirection: KI_FIRST or KI_FINAL
      RETURNS: zero-based index of matching character
      TODO: 
	* implement KI_FINAL for self:isList(TRUE) by reversing $sHay before searching,
	  then reversing the found string
	* remove $nDirection parameter; use separate option value
      PUBLIC at least temporarily for tests
      HISTORY:
	2017-02-28 made it PUBLIC, and also removed $nDirection parameter (now using option setting)
    */
    static public function FindNeedle($sHay,$sNeedle) {
	$oStr = new fcStringDynamic($sHay);
	$oStr->SetDirection(self::GetDirection());
	$oStr->SetIsCharList(self::GetIsCharList());
	return $oStr->FindNeedle($sNeedle);

    /* 2017-03-02 original code
	$idx = NULL;
	$vDirection = self::GetDirection();
	if (self::GetIsCharList()) {
	    switch($vDirection) {
	      case self::KI_FIRST:
		$sFnd = strpbrk($sHay,$sNeedle);	// returns contents of the string STARTING WITH the matching char
		if ($sFnd !== FALSE) {
		    // get the index of the first found character by subtracting out the rest of the string
		    $idx = strpos($sHay,$sFnd);		// should be same as strlen($sHay) - strlen($sFnd); not sure which is faster
		}
		break;
	      case self::KI_FINAL:
		throw new exception('Ferreteria code incomplete: KI_FINAL not yet supported for character list.');
	    }
	} else {
	    switch($vDirection) {
	      case self::KI_FIRST:
		$idx = strpos($sHay,$sNeedle);
		break;
	      case self::KI_FINAL:
		$idx = strrpos($sHay,$sNeedle);
		break;
	    }
	    if ($idx === FALSE) {
		$idx = NULL;
	    }
	}
	return $idx;
	*/
    }

    // -- SEARCHING -- //
    // ++ SINGLE SPLIT ++ //

    /*----
      ACTION: Splits the string at the first/final match
	first/final depends on PartToReturn option setting
	type of match depends on IsCharList option setting
      RETURNS: Array containing the part before in array['before'] and the part after in array['after']
	If no match is found, entire string is returned in one of those depending on PartForNone setting
      HISTORY:
	2017-03-01 adapted from SplitFirst()/SplitFinal()
    */
    static public function Split_atMatch($s,$sMatch) {
	$idx = self::FindNeedle($s,$sMatch);
	if (is_null($idx)) {
	    // no match found - behavior depends on PartForNone
	    $vIfNone = self::GetPartForNone();
	    $out[self::KS_BEFORE] = $out[self::KS_AFTER] = '';
	    $out[$vIfNone] = $s;
	} else {
	    if (self::GetIsCharList()) {
		$nMatchLen = 1;
	    } else {
		$nMatchLen = strlen($sMatch);
	    }
	    $out[self::KS_BEFORE] = substr($s,0,$idx);
	    $out[self::KS_AFTER] = substr($s,$idx+$nMatchLen);
	}
	$out[self::KS_INDEX] = $idx;
	self::SetSplitResult($out);
	return $out;
    }
    /*----
      ACTION: Does a Split_atMatch() and then returns the portion specified by GetPartToReturn()
    */
    static public function Get_fromSplit($s,$sMatch) {
	$ar = self::Split_atMatch($s,$sMatch);
	return $ar[self::GetPartToReturn()];
    }
    /*-----
      ACTION: Splits the string at the first match
      RETURNS: Array containing the part before in array['before'] and the part after in array['after']
      INPUT:
	iMatch = string to look for
	iOpts = array of options
	  [isCharList]:
	    TRUE = iMatch is a list of characters - finding any one of them is a match
	    FALSE = iMatch is a string; the entire sequence must be found, in the same order
	  [PartForNone]: where to put the string if no match was found
      TODO: DEPRECATE in favor of Split_atMatch()
      HISTORY:
	2012-01-31 changed 2nd param to be option array
	2014-07-10 adapting from xtString to clsString (static method)
	2014-08-10 simplifying input parameters - use self::IsList() for 'isList' option
	2017-02-28 (Now Set/GetIsCharList())
    */
    static public function SplitFirst($s,$sMatch,$sIfNone=self::KS_AFTER) {
	self::SetDirection(self::KI_FIRST);
	$idx = self::FindNeedle($s,$sMatch);
	if (is_null($idx)) {
	    $out[self::KS_BEFORE] = $out[self::KS_AFTER] = '';
	    $out[$sIfNone] = $s;
	} else {
	$ht = htmlspecialchars($s);
	//echo "IDX=[$idx] S=[$ht]<br>";
	    $out[self::KS_BEFORE] = substr($s,0,$idx);
	    $out[self::KS_AFTER] = substr($s,$idx+1);
	//echo "AR (inside):".fcArray::Render($out);
	}
	$out[self::KS_INDEX] = $idx;
	return $out;
    }
    /*----
      TODO: DEPRECATE in favor of Split_atMatch()
      HISTORY:
	2014-07-10 adapting from xtString to clsString (static method)
	2014-08-10 added nIfNone parameter
    */
    static public function SplitFinal($s,$sMatch,$sIfNone=self::KS_AFTER) {
	self::SetDirection(self::KI_FINAL);
	$idx = self::FindNeedle($s,$sMatch);
	if (is_null($idx)) {
	    $out[self::KS_BEFORE] = $out[self::KS_AFTER] = '';
	    $out[$sIfNone] = $s;
	} else {
	    $out[self::KS_BEFORE] = substr($s,0,$idx);
	    $out[self::KS_AFTER] = substr($s,$idx+strlen($sMatch));
	}
	$out[self::KS_INDEX] = $idx;
	return $out;
    }
    /*----
      ACTION: Return only the portion of $sHay prior to the first occurance of $sNeedle
    */
    static public function GetBeforeFirst($sHay,$sNeedle) {
	self::SetDirection(self::KI_FIRST);
	self::SetPartForNone(self::KS_BEFORE);
	$ar = self::Split_atMatch($sHay,$sNeedle);
	return $ar[self::KS_BEFORE];
    }
    static public function GetBefore($s,$sTarget) {
	throw new exception('GetBefore() is deprecated; call GetBeforeFirst().');
    }
    /*----
      ACTION: Return only the portion of $sHay after the first occurance of $sNeedle
    */
    static public function GetAfterFirst($sHay,$sNeedle) {
	self::SetDirection(self::KI_FIRST);
	self::SetPartForNone(self::KS_AFTER);
	$ar = self::Split_atMatch($sHay,$sNeedle);
	return $ar[self::KS_AFTER];
    }
    /*----
      ACTION: Return only the portion of $sHay after the final occurrence of $sNeedle
    */
    static public function GetAfterFinal($sHay,$sNeedle) {
	self::SetDirection(self::KI_FINAL);
	self::SetPartForNone(self::KS_AFTER);
	$ar = self::Split_atMatch($sHay,$sNeedle);
	return $ar[self::KS_AFTER];
    }
    static public function GetAfter($sHay,$sNeedle) {
	throw new exception('GetAfter() is deprecated; call GetAfterFinal().');
    }
    // NOTE: There could also be a GetBeforeFinal(), but it hasn't been needed yet.

    // -- SINGLE SPLIT -- //
    // ++ MULTI-SPLIT ++ //

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

    // -- MULTI-SPLIT -- //
    // ++ CLEANUP ++ //

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

    // -- CLEANUP -- //
    // ++ CONCATENATION ++ //
  
    /*----
      ACTION: Concatenates two strings. If both are non-empty, separate them with iSep.
      TODO: allow variable number of strings (more than 2) when running under PHP > 5.6
    */
    static public function Concat($sSep,$sLeft,$sRight) {
	if (self::IsBlank($sLeft)) {
	    return $sRight;
	} elseif (self::IsBlank($sRight)) {
	    return $sLeft;
	} else {
	    return $sLeft.$sSep.$sRight;
	}
    }
    /*----
      ACTION: Concatenates two strings. If both are non-empty, separate them with sSep.
      TODO: DEPRECATE in favor of Concat()
    */
    static public function StrCat($sLeft,$sRight,$sSep) {
	if (self::IsBlank($sLeft)) {
	    return $sRight;
	} elseif (self::IsBlank($sRight)) {
	    return $sLeft;
	} else {
	    return $sLeft.$sSep.$sRight;
	}
    }
    /*----
      NOTES:
	* Elements that are blank ("") are treated the same as NULL -- no separator is added.
	* Apparently we don't have PHP 5.6 yet? Otherwise we could use a variable number of parameters:
	  static public function Concat($sSep,...$sStrings)
    */
    static public function ConcatArray($sSep,array $arStrings) {
	$out = NULL;
	foreach ($arStrings as $sString) {
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
    // -- CONCATENATION -- //
    // ++ CONDITIONAL ++ //
    
    /*----
      ACTION: if $sTest is not NULL, returns $sToUse; otherwise returns $sIfNull.
      NOTE: I can't think of any reason why this is better than the trinary operator (?:). Deprecate?
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
    
    // -- CONDITIONAL -- //
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
    // ++ PARSING ++ //

    /*----
      ACTION: Finds the next *paired* occurrence of $sRight. That is, each occurrence of $sLeft must be matched with
	an $sRight, and we're looking for the one that is paired with the first $sLeft.
      EXAMPLE: FindPair('<abcd<efghi>jkl>mnop','<','>') would return 15, the position of the second '>'.
      RETURNS: zero-based index of target, or NULL if not found.
      HISTORY:
	2016-09-20 written for w3tpl
    */
    static public function FindPair($s,$sLeft,$sRight) {
	$isLeftFnd = FALSE;
	$isRightFnd = FALSE;
	$idxLeft = strpos($s,$sLeft);
	if ($idxLeft === FALSE) {
	    return NULL;
	} else {
	    $nDepth = 1;
	    while ($nDepth > 0) {
		$idxLeft = strpos($s,$sLeft);
		$idxRight = strpos($s,$sRight);
		if ($idxLeft === FALSE) {
		    $nDepth--;
		} elseif ($idxRight === FALSE) {
		    // there is no match
		    return FALSE;
		} elseif ($idxLeft < $idxRight) {
		    // opener was next item found
		    $nDepth++;
		} else {
		    // closer was next item found
		    $nDepth--;
		}
	    }
	    return $idxRight;
	}
    }

    // -- PARSING -- //
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
    static public function CharHash($nIndex) {
	if ($nIndex<10) {
	    return $nIndex;
	} elseif ($nIndex<36) {
	    return chr($nIndex-10+ord('A'));
	} else {
	    return chr($nIndex-36+ord('a'));
	}
    }

    // -- ENCODING -- //
}


