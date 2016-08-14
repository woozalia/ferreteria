<?php
/*
  PURPOSE: string functions for blocks of column-formatted text
  HISTORY:
    2015-11-09 created fcStringBlock from methods in xtString
*/

// DEPRECATED -- use fcsStringBlock
class fcStringBlock_static {
    static public function ParseTextLines($iText,$iComment='!;',$iBlanks=" \t") {
	$xts = new fcStringBlock($iText);
	$arOpts = array('comment'=>$iComment,'blanks'=>$iBlanks);
	return $xts->ParseTextLines($arOpts);
    }
}

class fcsStringBlock {
    static public function ParseTextLines($iText,array $arOpts=NULL) {
	$xts = new fcStringBlock($iText);
	return $xts->ParseTextLines($arOpts);
    }
}

// TODO: rename this to fcdStringBlock
class fcStringBlock extends xtString {
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
	    (default): line contents are returned as a single string, with first column used as key
	    'arr': entire line contents are returned as a subarray; lines are indexed numerically
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
	2016-01-19 Fixed bug in keyed-array return, then fixed bug this caused in numeric-array return.
	  You have to use a different type of merge depending on whether we're in line=arr[x] mode or not.
    */
    public function ParseTextLines(array $iOpts=NULL) {
	$this->chsComment	= clsArray::Nz($iOpts,'comment','!;');
	$this->chsBlanks	= clsArray::Nz($iOpts,'blanks'," \t");
	$this->strSep		= clsArray::Nz($iOpts,'sep',' ');
	$this->strDefVal	= clsArray::Nz($iOpts,'def val');
	$sLineOpt		= clsArray::Nz($iOpts,'line');
	$this->doArrX = ($sLineOpt == 'arrx');
	$this->doArr = $this->doArrX || ($sLineOpt == 'arr');
	//$this->doKeys = ($sLineOpt == 'keys');

	$strLineSep = "\n";	// make this an option later

	// check for single line
	if (strpos($this->Value,$strLineSep) === FALSE) {
	    return $this->SplitLine($this->Value);
	} else {
	    $arLines = preg_split("/$strLineSep/",$this->Value);
//echo 'LINES:<pre>'.print_r($arLines,TRUE).'</pre>';
	    if (is_array($arLines)) {
		$this->InitLineArray();
		foreach ($arLines as $idx => $line) {
//echo "\n<br>IDX=$idx LINE: [$line]<br>";
		    $arThis = $this->SplitLine($line);
		    if (is_null($arThis)) {
			// nothing to add to arOut
		    } else {
			$this->MergeLineArray($arThis);
		    }
		}
		return $this->GetLineArray();
	    } else {
		return NULL;
	    }
	} die(__FILE__.' line '.__LINE__);
    }
    private $arOut;
    protected function InitLineArray() {
	$this->arOut = NULL;
    }
    protected function MergeLineArray(array $arLine) {
	if (is_array($this->arOut)) {
	    if ($this->doArr) {
		// merge numbered array
		$this->arOut = array_merge($this->arOut,$arLine);
	    } else {
		// merge keyed array
		$this->arOut = clsArray::Merge($this->arOut,$arLine);
	    }
	} else {
	    $this->arOut = $arLine;
	}
    }
    protected function GetLineArray() {
	return $this->arOut;
    }
    /*----
      HISTORY:
	2015-11-09 a few fixes to make this work after rearranging the classes (which I did
	  because this wasn't working)
	2016-02-05 Fixed bug in the condition where there's only one column. (Looks like there
	  might have been an earlier rewrite in progress, but not completed.)
      TODO: This could probably be rewritten a bit more succinctly if we store $sFnd in $this->Value
	and maybe use some options in common (e.g. for SplitFirst()).
    */
    protected function SplitLine($sLine) {
	$arOut = NULL;

	// 1. HANDLE COMMENTS

	$sFnd = strpbrk($sLine,$this->chsComment);
	if ($sFnd === FALSE) {
	    // no comment found; use entire line
	    $sContent = $sLine;
	} else {
	
	    $idx = strpos($sLine,$sFnd);
	    $isFnd = TRUE;

	    $sContent = substr($sLine,0,$idx);	// before mark = contents
	    $sComment = substr($sLine,$idx+1);	// after mark = comments
	}
	// remove any leading or trailing blanks/CR (input from multiline forms often ends in \r)
	$sFnd = trim($sContent,$this->chsBlanks."\r");
	// replace all blank sequences with separator string
	$sFnd = fcString::ReplaceSequence($sFnd,$this->chsBlanks,$this->strSep);
	// $sFnd is now unwrinkled line content (i.e. entire line with comments removed and some massaging)

	// 2. BREAK LINE CONTENT INTO COMPONENTS

	if ($sFnd != '') {	// if there's anything left after removing comments...
	    if ($this->doArr) {
		// split it into columns and add that to the output array
		if ($this->doArrX) {
		    $arSplit = fcString::Xplode($sFnd);
		} else {
		    $arSplit = explode($this->strSep,$sFnd);
		}
		$arOut[] = $arSplit;
	    } else {
		// split it into key/value pair and add to output array

		$arSplit = fcString::SplitFirst($sFnd,$this->strSep,KS_BEFORE);		// split at first separator
		$sBefore = $arSplit[KS_BEFORE];
		$sAfter = $arSplit[KS_AFTER];
		$sKey = $sBefore;
		if (empty($sAfter)) {
		    $sVal = $this->strDefVal;
		} else {
		    $sVal = $sAfter;
		}
		$arOut[$sKey] = $sVal;
	    }
	}
	return $arOut;
    }
}