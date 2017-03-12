<?php
/*
  PURPOSE: extended string functions (whatever that means)
  HISTORY:
    2015-11-09 extracted xtString from strings.php; added xtString_static
    2017-03-01 some changes...
      * renamed xtString to fcStringDynamic (xtString is a deprecated alias until I can remove it)
      * finally removed _DelTail()
      * added SetValue() / GetValue(); protected $Value
      * added SetDoKeep() / GetDoKeep(); protectd $DoKeep
      * added KeepValue() as shorthand for 'if DoKeep then SetValue'
      * no log of what happened to xtString_static; it appears
	to have been consolidated with what is now fcString
	and/or split off into string-block classes
    2017-03-02 moved fcString::FindNeedle() to fcStringDynamic::FindNeedle()
*/

/*::::
  CLASS: fcStringDynamic
  PURPOSE: string functions that operate on an internal value
*/
class fcStringDynamic {

    // ++ SETUP ++ //

    public function __construct($sVal=NULL,$bKeep=TRUE) {
	$this->SetValue($sVal);
	$this->SetDoKeep($bKeep);
    }

    // -- SETUP -- //
    // ++ OPTION CONSTANTS ++ //

    // array index names
    const kBEFORE = 'before';
    const kAFTER = 'after';
    const kINDEX = 'index';
    // search directions
    const kFIRST = 1;
    const kFINAL = -1;
    
    // -- OPTION CONSTANTS -- //
    // ++ OPTIONS ++ //
    
    private $doKeep;	// TRUE = replace value with function results (scalar functions only)
    public function SetDoKeep($b) {
	$this->doKeep = $b;
    }
    protected function GetDoKeep() {
	return $this->doKeep;
    }
    private $isCharList = FALSE;	// FALSE = match string; TRUE = match any character in string
    public function SetIsCharList($b) {
	$this->isCharList = $b;
    }
    public function GetIsCharList() {
	return $this->isCharList;
    }
    // VALUES: KS_BEFORE, KS_AFTER
    private $vPartToReturn;
    public function SetPartToReturn($v) {
	$this->vPartToReturn = $v;
    }
    protected function GetPartToReturn() {
	if (!isset($this->vPartToReturn)) {
	    $this->vPartToReturn = self::kAFTER;	// default
	}
	return $this->vPartToReturn;
    }
    private $vPartForNone;
    public function SetPartForNone($v) {
	$this->vPartForNone = $v;
    }
    protected function GetPartForNone() {
	if (!isset($this->vPartForNone)) {
	    $this->vPartForNone = self::kAFTER;	// default
	}
	return $this->vPartForNone;
    }
    private $vDirection;
    public function SetDirection($v) {
	$this->vDirection = $v;
    }
    protected function GetDirection() {
	if (!isset($this->vDirection)) {
	    $this->vDirection = self::kFIRST;	// default
	}
	return $this->vDirection;
    }

    // -- OPTIONS -- //
    // ++ STATUS ++ //	- other output
    
    private $RepCount;	// replacement count
    protected function SetRepeatCount($n) {
	$this->RepCount = $n;
    }
    public function GetRepeatCount() {
	return $this->RepCount;
    }
    
    // -- STATUS -- //
    // ++ VALUE ACCESS ++ // - fx() which operate directly on $Value
    
    private $Value;
    public function SetValue($s) {
	$this->Value = $s;
    }
    public function GetValue() {
	return $this->Value;
    }
    // ACTION: Set $Value if $DoKeep is set
    protected function KeepValue($s) {
	if ($this->GetDoKeep()) {
	    $this->SetValue($s);
	}
    }
    /*----
      ACTION: Add $iAdd to the current string; if the current string is not empty, add $iSep first.
    */
    public function AddSep($sAdd,$sSep=' ') {
	if (!fcString::IsBlank($this->Value)) {
	    $this->Value .= $sSep;
	}
	$this->Value .= $sAdd;
	return $this->Value;
    }
    
    // -- VALUE ACCESS -- //
    // ++ SEARCH ++ //
    
    /*----
      PURPOSE: Find the first/last match for $sNeedle in $sHay
	* equivalent to strpos(), but respects option constants/settings
      INPUT:
	$sHay: string to search in
	$sNeedle: string or characters to search for
	$nDirection: KI_FIRST or KI_FINAL
      RETURNS: zero-based index of matching character
      RESPECTS GetDirection(), GetIsCharList()
      TODO: 
	* implement KI_FINAL for self:isList(TRUE) by reversing $sHay before searching,
	  then reversing the found string
	* remove $nDirection parameter; use separate option value
      PUBLIC at least temporarily for tests
      HISTORY:
	2017-02-28 made it PUBLIC, and also removed $nDirection parameter (now using option setting)
	2017-03-02 copied and adapted for fcStringDynamic from fcString
    */
    public function FindNeedle($sNeedle) {
	$idx = NULL;
	$sHay = $this->GetValue();
	$vDirection = self::GetDirection();
	if ($this->GetIsCharList()) {
	    switch($vDirection) {
	      case self::kFIRST:
		$sFnd = strpbrk($sHay,$sNeedle);	// returns contents of the string STARTING WITH the matching char
		if ($sFnd !== FALSE) {
		    // get the index of the first found character by subtracting out the rest of the string
		    $idx = strpos($sHay,$sFnd);		// should be same as strlen($sHay) - strlen($sFnd); not sure which is faster
		}
		break;
	      case self::kFINAL:
		throw new exception('Ferreteria code incomplete: kFINAL not yet supported for character list.');
	    }
	} else {
	    switch($vDirection) {
	      case self::kFIRST:
		$idx = strpos($sHay,$sNeedle);
		break;
	      case self::kFINAL:
		$idx = strrpos($sHay,$sNeedle);
		break;
	    }
	    if ($idx === FALSE) {
		$idx = NULL;
	    }
	}
	return $idx;
    }
    
    
    // -- SEARCH -- //
    // ++ DELETION ++ //
    
    /*
      ACTION: Remove any characters in $iChars from the beginning of the string; stop when a non-$iChars character is found
      INPUT:
	$iChars = characters to delete; defaults to ltrim()'s default of trimming all whitespace
    */
    public function DelLead($sChars=NULL) {
	if (is_null($sChars)) {
	    $out = ltrim($this->GetValue());
	} else {
	    $out = ltrim($this->GetValue(),$sChars);
	}
	if ($this->GetDoKeep()) {
	    $this->SetValue($out);
	}
	return $out;
    }
    /*
      ACTION: Remove any characters in $iChars from the end of the string; stop when a non-$iChars character is found
    */
    public function DelTail($sChars=NULL) {
	$out = fcString::DelTail($this->GetValue(),$sChars);
	$this->KeepValue($out);
	return $out;
    }
    
    // -- DELETION -- //
    // ++ REPLACEMENT ++ //

    /*----
      INPUT:
	$ar: array of targets and replacement values
	  ar[target] = new value
    */
    public function ReplaceList(array $ar) {
	$nRep = 0;
	$npRep = 0;
	$out = $this->GetValue();
	foreach($ar AS $seek => $repl) {
	    $out = str_replace($seek,$repl,$out,$npRep);
	    $nRep += $npRep;
	}
	$this->SetRepeatCount($nRep);
	$this->KeepValue($out);
	return $out;
    }
    public function ReplaceSequence($iChars, $iRepl, $iMax=0) {
	return fcString::ReplaceSequence($this->GetValue(),$iChars,$iRepl,$iMax);
    }
    /*----
      ACTION: Replace a single instance of $sNeedle with $sInstead
      RESPECTS GetDirection()
    */
    public function ReplaceOnce($sNeedle,$sInstead) {
	$this->SetIsCharList(FALSE);		// we're looking for a string to replace, not a list of chars
	$nFnd = $this->FindNeedle($sNeedle);
	$nLen = strlen($sNeedle);
	$ar = $this->Split_atIndex($nFnd,$nLen);
	$out = $ar[self::kBEFORE].$sInstead.$ar[self::kAFTER];
	$this->KeepValue($out);
	return $out;
    }
    /*----
      ACTION: Keep only characters matching the given regex pattern
      EXAMPLE: KeepOnly('0-9') would remove all non-numeric characters
    */
    public function KeepOnly($iPattern) {
	$out = preg_replace('/[^'.$iPattern.']/','',$this->GetValue());
	$this->KeepValue($out);
	return $out;
    }
    /*----
      ACTION: delete all characters outside of the given range
      NOTE: 2017-03-01 You can tell that this is one of the first PHP fx() I wrote
	because there was a commented-out section (now deleted) of VB var declarations.
      RETURNS: count of deleted characters
	To be consistent, shouldn't that be stored internally and the return value be the resulting string?
    */
    public function DelAscRange($nLow, $nHi) {
	$strIn = $this->GetValue();
	$strOut = '';
	$intLen = strlen($strIn);
	for ($strIdx = 0; $strIdx < $intLen; $strIdx++) {
	    $chIn = substr($strIn, $strIdx, 1);
	    $chInAsc = ord($chIn);
	    If (($chInAsc < $nLow) || ($chInAsc > $nHi)) {
		$strOut .= $chIn;
	    }
	}
	$cntDel = strlen($this->GetValue()) - strlen($strOut);
	$this->KeepValue($strOut);
	return $cntDel;
    }
    
    // -- REPLACEMENT -- //
    // ++ SINGLE-POINT SPLITS ++ //

    /*----
      ACTION: Split the current string into two pieces
      INPUT:
	$nWhere = zero-based index for split point (treated as just before character index)
	$nSkip = number of characters to skip after split point
      RETURNS: array with the two pieces
	array[kBEFORE] = everything before the given index
	array[kAFTER] = everything starting from $nSkip characters after the given index
    */
    protected function Split_atIndex($nWhere,$nSkip=0) {
	$s = $this->GetValue();
	$ar = array(
	  self::kBEFORE	=> substr($s,0,$nWhere),
	  self::kAFTER	=> substr($s,$nWhere+$nSkip)
	  );
	return $ar;
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
    public function SplitFirst($sMatch,array $iOpts=NULL) {
	throw new exception('2017-03-01 Does anything still use this?');
	  // I'm inclined to think fcString fx() should be moved here, then fcString could be rewritten just to call a singleton instance.
	$isFnd = FALSE;
	$isList = fcArray::Nz($iOpts,'isList');
	$ifNone = fcArray::Nz($iOpts,'ifNone','before');
	if ($isList) {
	    $sFnd = strpbrk($this->GetValue(),$sMatch);
	    if ($sFnd === FALSE) {
		$out = NULL;
	    } else {
		$idx = strpos($this->GetValue(),$sFnd);
		$isFnd = TRUE;
	    }
	} else {
	    $idx = strpos($this->GetValue(),$sMatch);
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
	throw new exception('2017-03-01 Does anything still use this?');
	// NOT TESTED!
	$idx = strrpos($this->Value,$iMatch);
	$out['index'] = $idx;
	$out['before'] = substr($this->Value,0,$idx-1);
	$out['after'] = substr($this->Value,$idx+1);
	return $out;
    }

    // -- SINGLE-POINT SPLITS -- //

    public function Xplode() {
	return fcString::Xplode($this->GetValue());
    }
}
class xtString extends fcStringDynamic { }	// temporary alias; will be deprecated later