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
    $arLines = preg_split("/\n/",$iText);
    if (is_array($arLines)) {
	$xts = new xtString();
	$arOut = NULL;
	//$doDeptOnly = $this->AffectsCatNum();
	foreach ($arLines as $idx => $line) {
	    $xts->Value = $line;
	    $arSplit = $xts->SplitFirst($iComment,TRUE);	// split at first comment character
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
  CLASS: clsString
  PURPOSE: extended string functions, with emphasis on static methods
*/
class clsString {

    // ++ OPTIONS ++ //

    static private $isList = FALSE;	// FALSE = match string; TRUE = match any character in string
    static public function IsList($bYes=NULL) {
	if (!is_null($bYes)) {
	    self::$isList = $bYes;
	}
	return self::$isList;
    }

    // -- OPTIONS -- //
    // ++ NULL HANDLING ++ //

    /*-----
      FUNCTION: nzAppend -- NZ Append
      PURPOSE: Like nzAdd(), but appends strings instead of adding numbers
      DEPRECATED: this really isn't necessary. Just set $ioVal to NULL, then append.
    */ /*
    static public function nzAppend(&$ioVal,$iTxt=NULL) {
	if (empty($ioVal)) {
	    $ioVal = $iTxt;
	} else {
	    $ioVal .= $iTxt;
	}
	return $ioVal;
    } */

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
    /* 2014-08-10 OLD VERSION
    static public function SplitFirst($s,$iMatch,array $iOpts=NULL) {
	$isFnd = FALSE;
	$isList = NzArray($iOpts,'isList');
	$ifNone = NzArray($iOpts,'ifNone',KS_BEFORE);
	if ($isList) {
	    $strFnd = strpbrk($s,$iMatch);
	    if ($strFnd === FALSE) {
		$out = NULL;
	    } else {
		$idx = strpos($s,$strFnd);
		$isFnd = TRUE;
	    }
	} else {
	    $idx = strpos($s,$iMatch);
	    if ($idx === FALSE) {
		$out = NULL;
	    } else {
		$isFnd = TRUE;
	    }
	}
	if ($isFnd) {
	    $out['index'] = $idx;
	    $out['before'] = substr($s,0,$idx);
	    $out['after'] = substr($s,$idx+1);
	} else {
	    $out[$ifNone] = $s;
	}
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
    // ++ NATURAL LANGUAGE ++ //

    static function Pluralize($iQty,$iSingular='',$iPlural='s') {
	if ($iQty == 1) {
	    return $iSingular;
	} else {
	    return $iPlural;
	}
    }

    // -- NATURAL LANGUAGE -- //
}

/*%%%%%
  CLASS: xtString
  PURPOSE: extended string functions
*/
class xtString {
    public $Value;
    public $RepCount;	// replacement count
    public $DoKeep;	// TRUE = replace value with function results (scalar functions only)

    // ++ SETUP ++ //

    public function __construct($iVal=NULL,$iKeep=TRUE) {
	$this->Value = $iVal;
	$this->DoKeep = $iKeep;
    }

    // -- SETUP -- //
    // ++ STATIC ++ //

    /*
      ACTION: Remove any characters in $iChars from the end of the string; stop when a non-$iChars character is found
    */
    static public function _DelTail($s,$iChars=NULL) {
	if (is_null($iChars)) {
	    $out = rtrim($s);
	} else {
	    $out = rtrim($s,$iChars);
	}
	return $out;
    }

    // -- STATIC -- //

    // invoked when object is referenced as a string
    public function __toString() {
	return $this->Value;
    }
    /*
      ACTION: Add $iAdd to the current string; if the current string is not empty, add $iSep first.
    */
    public function AddSep($iAdd,$iSep=' ') {
	if (!empty($this->Value)) {
	    $this->Value .= $iSep;
	}
	$this->Value .= $iAdd;
	return $this->Value;
    }
    /*
      ACTION: Remove any characters in $iChars from the beginning of the string; stop when a non-$iChars character is found
      INPUT:
	$iChars = characters to delete; defaults to ltrim()'s default of trimming all whitespace
    */
    public function DelLead($iChars=NULL) {
	if (is_null($iChars)) {
	    $out = ltrim($this->Value);
	} else {
	    $out = ltrim($this->Value,$iChars);
	}
	if ($this->DoKeep) {
	    $this->Value = $out;
	}
	return $out;
    }
    /*
      ACTION: Remove any characters in $iChars from the end of the string; stop when a non-$iChars character is found
    */
    public function DelTail($iChars=NULL) {
/*
      if (is_null($iChars)) {
	    $out = rtrim($this->Value);
	} else {
	    $out = rtrim($this->Value,$iChars);
	}
	if ($this->DoKeep) {
	    $this->Value = $out;
	}*/
	$out = self::_DelTail($this->Value,$iChars);
	return $out;
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
    */
    public function SplitFirst($iMatch,array $iOpts=NULL) {
	$isFnd = FALSE;
	$isList = NzArray($iOpts,'isList');
	$ifNone = NzArray($iOpts,'ifNone','before');
	if ($isList) {
	    $strFnd = strpbrk($this->Value,$iMatch);
	    if ($strFnd === FALSE) {
		$out = NULL;
	    } else {
		$idx = strpos($this->Value,$strFnd);
		$isFnd = TRUE;
	    }
	} else {
	    $idx = strpos($this->Value,$iMatch);
	    if ($idx === FALSE) {
		$out = NULL;
	    } else {
		$isFnd = TRUE;
	    }
	}
	if ($isFnd) {
	    $out['index'] = $idx;
	    $out['before'] = substr($this->Value,0,$idx);
	    $out['after'] = substr($this->Value,$idx+1);
	} else {
	    $out[$ifNone] = $this->Value;
	}
	return $out;
    }
    public function SplitFinal($iMatch) {
	// NOT TESTED!
	$idx = strrpos($this->Value,$iMatch);
	$out['index'] = $idx;
	$out['before'] = substr($this->Value,0,$idx-1);
	$out['after'] = substr($this->Value,$idx+1);
	return $out;
    }
    /*----
      INPUT:
	$iList: array of targets and replacement values
	  iList[target] = new value
    */
    public function ReplaceList($iList) {
	$this->RepCount = 0;
	$intRep = 0;
	$out = $this->Value;
	foreach($iList AS $seek => $repl) {
	    $out = str_replace($seek,$repl,$out,$intRep);
	    $this->RepCount += $intRep;
	}
	if ($this->DoKeep) {
	    $this->Value = $out;
	}
	return $out;
    }
    /*=====
      ACTION: Replace any
	{{character sequences where all characters are found in $iChars}
	that are longer than $iMax}
	with the string $iRepl
    */
    static public function _ReplaceSequence($iVal, $iChars, $iRepl, $iMax=0) {
	$in = $iVal;
	$out = '';
	$lenIn = strlen($in);
	$rpos = 0;

	while ($rpos < $lenIn) {
	    $fnd = strspn($in,$iChars,$rpos);
	    if ($fnd > 0) {
	    // found a sequence; does it exceed $iMin?
		if ($fnd > $iMax) {
		    // yes - replace it
		    $out .= $iRepl;
		} else {
		    // no - keep it
		    $out .= substr($in,$rpos,$fnd);
		}
		$rpos += $fnd;	// advance the read pointer
	    } else {
		// matching sequence not found at current position, so gobble up non-matching chars until the next one
		$fnd = strcspn($in,$iChars,$rpos);
		$add = substr($in,$rpos,$fnd);
		$out .= $add;
		$rpos += $fnd;	// advance the read pointer
	    }
	}
	return $out;
    }
    public function ReplaceSequence($iChars, $iRepl, $iMax=0) {
	return self::_ReplaceSequence($this->Value,$iChars,$iRepl,$iMax);
/*
	$in = $this->Value;
	$out = '';
	$lenIn = strlen($in);
	$rpos = 0;

	while ($rpos < $lenIn) {
	    $fnd = strspn($in,$iChars,$rpos);
	    if ($fnd > 0) {
	    // found a sequence; does it exceed $iMin?
		if ($fnd > $iMax) {
		    // yes - replace it
		    $out .= $iRepl;
		} else {
		    // no - keep it
		    $out .= substr($in,$rpos,$fnd);
		}
		$rpos += $fnd;	// advance the read pointer
	    } else {
		// matching sequence not found at current position, so gobble up non-matching chars until the next one
		$fnd = strcspn($in,$iChars,$rpos);
		$add = substr($in,$rpos,$fnd);
		$out .= $add;
		$rpos += $fnd;	// advance the read pointer
	    }
	}
	if ($this->DoKeep) {
	    $this->Value = $out;
	}
	return $out;
*/
    }
    /*=====
      ACTION: Keep only characters matching the given regex pattern
      EXAMPLE: KeepOnly('0-9') would remove all non-numeric characters
    */
    public function KeepOnly($iPattern) {
	$out = preg_replace('/[^'.$iPattern.']/','',$this->Value);
	if ($this->DoKeep) {
	    $this->Value = $out;
	}
	return $out;
    }
    public function DelAscRange($iLow, $iHi) {
// ACTION: delete all characters outside of the given range
/* VB var defs
    Dim strIn As String
    Dim strOut As String
    Dim strIdx As Long
    Dim chIn As Byte
    Dim cntDel As Long
*/
	$strIn = $this->Value;
	$strOut = '';
	$intLen = strlen($strIn);
	for ($strIdx = 0; $strIdx < $intLen; $strIdx++) {
	    $chIn = substr($strIn, $strIdx, 1);
	    $chInAsc = ord($chIn);
	    If (($chInAsc < $iLow) || ($chInAsc > $iHi)) {
		$strOut .= $chIn;
	    }
	}
	$cntDel = strlen($this->Value) - strlen($strOut);
	if ($this->DoKeep) {
	    $this->Value = $strOut;
	}
	return $cntDel;
    }

    public function Xplode() {
	return clsString::Xplode($this->Value);
    }
    /*----
      ACTION: Parses a block of text lines into an array, where the
	key for each element is the text up to the first space
	and the value is the rest of the line.
      INPUT:
	(arg) iOpts: array of possible options
	  [comment] = list of characters which begin a comment.
	    When one of these characters is found, the rest of the line is ignored.
	    Defaults to "!;"
	  [blanks] = list of characters considered to be "blanks".
	    Runs of one or more of these are consolidated to a single space before parsing.
	    Spaces then are considered separators between pieces of the line.
	    Defaults to space + tab
	  [sep] = string to use for column separator (must not appear within a column)
	    Defaults to space.
	  [line]: format in which each line is returned
	    'str': line contents are returned as a single string (default), first column is index
	    'arr': line contents are returned as an array; lines are indexed numerically
	    'arrx': same as arr, but line is Xploded instead of using a specified separator
	  [def val]: default value -- value to use when there is only one column
      OUTPUT: array containing one line of text per element
      TODO: make $strLineSep an overrideable option
      HISTORY:
	2010-11-27 Added line=arrx option.
	2011-02-06 Doesn't seem to handle arrx where the separator is TAB -
	  TABs get stripped from start of line.
	  Made a small code change to fix this, but it didn't change the output.
	  Fix later.
	2012-01-31 Added handling for single line; split off most of the code
	  into SplitLine() to make this easier.
	2012-03-05 array_merge was DOON IT RONG; using ArrayJoin() instead
	2012-03-13 ArrayJoin() doesn't work with sequenced arrays; array_merge() does.
	  If it seems to be a problem again, document the data it is receiving and the circumstances.
	  Right now, data coming from SplitLine() is unkeyed (sequenced) because the only possible key
	    (catalog #) will not always be unique for each line.
	  Rewrote to reduce usage of state-dependent dynamic internal functions -- easier to debug.
	    Wasn't working properly in inventory entry.
    */
    public function ParseTextLines(array $iOpts=NULL) {
	$this->chsComment = nz($iOpts['comment'],'!;');
	$this->chsBlanks = nz($iOpts['blanks']," \t");
	$this->strSep = nz($iOpts['sep'],' ');
	$this->strDefVal = NzArray($iOpts,'def val');
	$this->doArrX = (nz($iOpts['line']) == 'arrx');
	$this->doArr = $this->doArrX || (nz($iOpts['line']) == 'arr');

	$strLineSep = "\n";	// make this an option later

	// check for single line
	if (strpos($this->Value,$strLineSep) === FALSE) {
	    return $this->SplitLine_debug($this->Value);
	} else {
	    $arLines = preg_split("/$strLineSep/",$this->Value);
//echo 'LINES:<pre>'.print_r($arLines,TRUE).'</pre>';
	    if (is_array($arLines)) {
		$arOut = NULL;
		foreach ($arLines as $idx => $line) {
//echo "\n<br>IDX=$idx LINE: [$line]<br>";
		    $arThis = $this->SplitLine($line);
		    if (is_null($arThis)) {
			// nothing to add to arOut
		    } else {
			if (is_array($arOut)) {
			    $arOut = array_merge($arOut,$arThis);
//echo '<br>THIS:<pre>'.print_r($arThis,TRUE).'</pre>';
			    // ArrayJoin() can't be used because $arThis is not a keyed array
			    //$arOut = ArrayJoin($arOut,$arThis,FALSE,TRUE);	// overwrite:no; append:yes
//echo '<br>OUT:<pre>'.print_r($arOut,TRUE).'</pre>';
			} else {
			    $arOut = $arThis;
			}
		    }
		}
		return $arOut;
	    } else {
		return NULL;
	    }
	} die();
    }
    protected function SplitLine($iLine) {
	$arOut = NULL;

	// 1. HANDLE COMMENTS

	$strFnd = strpbrk($iLine,$this->chsComment);
	if ($strFnd === FALSE) {
	    // no comment found; use entire line
	    $strFnd = $iLine;
	} else {
	    $idx = strpos($iLine,$strFnd);
	    $isFnd = TRUE;

	    $strBefore = substr($iLine,0,$idx);
	    $strAfter = substr($iLine,$idx+1);

	    $strFnd = trim($strBefore,$this->chsBlanks."\r");	// remove any leading or trailing blanks/CR
	    $strFnd = self::_ReplaceSequence($strFnd,$this->chsBlanks,$this->strSep);		// replace all blank sequences with separator string
	}
	// $strFnd is now main contents of line (without comments)

	// 2. BREAK LINE INTO COMPONENTS (cat #, qty (optional))

//echo '<br>[1] STRFND=['.$strFnd.']';
	if ($strFnd != '') {	// if there's anything left...
	    if ($this->doArr) {
		// split it into columns and add that to the output array
		if ($this->doArrX) {
		    $arSplit = Xplode($strFnd);
//echo '<br>[2] STRFND=['.$strFnd.'] ARSPLIT:<pre>'.print_r($arSplit,TRUE).'</pre>';
		} else {
		    $arSplit = explode($this->strSep,$strFnd);
//echo '<br>[3] STRFND=['.$strFnd.'] ARSPLIT:<pre>'.print_r($arSplit,TRUE).'</pre>';
		}
		$arOut[] = $arSplit;
	    } else {
		// split it into key/value pair and add to output array


		$arSplit = $xts->SplitFirst();		// split at first separator

		$idx = strpos($strFnd,$this->strSep);
		if ($idx === FALSE) {
		    $out = NULL;
		} else {
		    $isFnd = TRUE;
		}
		$strBefore = substr($strFnd,0,$idx);
		$strAfter = substr($strFnd,$idx+1);

		$strKey = $strBefore;
		if (empty($strAfter)) {
		    $strVal = $this->strDefVal;
		} else {
		    $strVal = $strAfter;
		}
		$arOut[$strKey] = $strVal;
	    }
	}

	return $arOut;
    }
    /*----
      RETURNS: indexed/unkeyed array
	array(col1, col2)
	...because multiple lines may have the same catalog #
    */
    protected function SplitLine_bad($iLine) {
	$arOut = NULL;

	$xts = new xtString($iLine);

	$arSplit = $xts->SplitFirst($this->chsComment,array('isList'=>TRUE));		// split at first comment character
	if (is_null($arSplit)) {
	    // no comment found; use entire line
	} else {
	    // use only portion of line before comment
	    $xts->Value = $arSplit['before'];
	}
//		$xts->DelLead($chsBlanks);				// remove any leading spaces
	if (!empty($this->chsBlanks)) {
	    $xts->Value = trim($xts->Value,$this->chsBlanks."\r");	// remove any leading or trailing blanks/CR
	    $xts->ReplaceSequence($this->chsBlanks,$this->strSep);		// replace all blank sequences with separator string
	}
	if ($xts->Value != '') {	// if there's anything left...
	    if ($this->doArr) {
		// split it into columns and add that to the output array
		if ($this->doArrX) {
		    $arSplit = $xts->Xplode();
		} else {
		    $arSplit = explode($this->strSep,$xts->Value);
		}
		$arOut[] = $arSplit;
	    } else {
		// split it into key/value pair and add to output array

		$arSplit = $xts->SplitFirst($this->strSep);		// split at first separator
		$strKey = NzArray($arSplit,'before');
		$strVal = NzArray($arSplit,'after',$this->strDefVal);
		$arOut[$strKey] = $strVal;
	    }
	}

	return $arOut;
    }
}

