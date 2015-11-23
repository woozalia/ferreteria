<?php
/*
  PURPOSE: string functions for blocks of column-formatted text
  HISTORY:
    2015-11-09 created fcStringBlock from methods in xtString
*/

class fcStringBlock_static {
    static public function ParseTextLines($iText,$iComment='!;',$iBlanks=" \t") {
	$xts = new fcStringBlock($iText);
	$arOpts = array('comment'=>$iComment,'blanks'=>$iBlanks);
	return $xts->ParseTextLines($arOpts);
    }
}

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
	    return $this->SplitLine($this->Value);
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
    /*----
      HISTORY:
	2015-11-09 a few fixes to make this work after rearranging the classes (which I did
	  because this wasn't working)
      TODO: This could probably be rewritten a bit more succinctly if we store $sFnd in $this->Value
	and maybe use some options in common (e.g. for SplitFirst()).
    */
    protected function SplitLine($sLine) {
	$arOut = NULL;

	// 1. HANDLE COMMENTS

	$sFnd = strpbrk($sLine,$this->chsComment);
	if ($sFnd === FALSE) {
	    // no comment found; use entire line
	    $sFnd = $sLine;
	} else {
	    $idx = strpos($sLine,$sFnd);
	    $isFnd = TRUE;

	    $sBefore = substr($sLine,0,$idx);
	    $sAfter = substr($sLine,$idx+1);

	    // remove any leading or trailing blanks/CR
	    $sFnd = trim($sBefore,$this->chsBlanks."\r");
	    // replace all blank sequences with separator string
	    $sFnd = fcString::ReplaceSequence($sFnd,$this->chsBlanks,$this->strSep);
	}
	// $sFnd is now main contents of line (without comments)

	// 2. BREAK LINE INTO COMPONENTS (cat #, qty (optional))

//echo '<br>[1] STRFND=['.$strFnd.']';
	if ($sFnd != '') {	// if there's anything left...
	    if ($this->doArr) {
		// split it into columns and add that to the output array
		if ($this->doArrX) {
		    $arSplit = fcString::Xplode($sFnd);
//echo '<br>[2] STRFND=['.$strFnd.'] ARSPLIT:<pre>'.print_r($arSplit,TRUE).'</pre>';
		} else {
		    $arSplit = explode($this->strSep,$sFnd);
//echo '<br>[3] STRFND=['.$strFnd.'] ARSPLIT:<pre>'.print_r($arSplit,TRUE).'</pre>';
		}
		$arOut[] = $arSplit;
	    } else {
		// split it into key/value pair and add to output array

		$arSplit = fcString::SplitFirst($sFnd,$this->strSep);		// split at first separator

		$idx = strpos($sFnd,$this->strSep);
		if ($idx === FALSE) {
		    $out = NULL;
		} else {
		    $isFnd = TRUE;
		}
		$sBefore = substr($sFnd,0,$idx);
		$sAfter = substr($sFnd,$idx+1);

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
    /*----
      RETURNS: indexed/unkeyed array
	array(col1, col2)
	...because multiple lines may have the same catalog #
    */
    /*
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
    } */
}