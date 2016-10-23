<?php
/*
  PURPOSE: extended string functions (whatever that means)
  HISTORY:
    2015-11-09 extracted xtString from strings.php; added xtString_static
  METHOD LIST:
    xtString::
      (dynamic) AddSep($iAdd,$iSep=' ')
      (dynamic) DelLead($iChars=NULL)
      (dynamic) DelTail($iChars=NULL)
      (dynamic) SplitFirst($iMatch,array $iOpts=NULL)
      (dynamic) SplitFinal($iMatch)
      (dynamic) ReplaceList($iList)
      (dynamic) ReplaceSequence($iChars, $iRepl, $iMax=0)
      (dynamic) KeepOnly($iPattern)
      (dynamic) DelAscRange($iLow, $iHi)
      (dynamic) Xplode()
*/

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
	throw new exception('Call xtString->DelTail() or fcString::DelTail() instead of xtString::_DelTail().');
	
	// OLD CODE
	if (is_null($iChars)) {
	    $out = rtrim($s);
	} else {
	    $out = rtrim($s,$iChars);
	}
	return $out;
    }

    // -- STATIC -- //

    // invoked when object is referenced as a string
    /* 2015-11-09 This seems unnecessary.
    public function __toString() {
	return $this->Value;
    } */
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
	$out = fcString::DelTail($this->Value,$iChars);
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
	$isList = fcArray::Nz($iOpts,'isList');
	$ifNone = fcArray::Nz($iOpts,'ifNone','before');
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
	throw new exception('xtString::_ReplaceSequence() has been moved to xtString_static::ReplaceSequence()');
    }
    public function ReplaceSequence($iChars, $iRepl, $iMax=0) {
	return fcString::ReplaceSequence($this->Value,$iChars,$iRepl,$iMax);
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
	return fcString::Xplode($this->Value);
    }
}
