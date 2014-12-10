<?php
/*
  PURPOSE: classes for dealing with money
    At this point, this is mainly for formatting purposes.
  HISTORY:
    2013-12-17 Created for DataCurr()
*/

class clsMoney {
/*
    function FormatMoney($iAmount,$iPrefix='',$iPlus='') {
	if ($iAmount < 0) {
	    $str = '-'.$iPrefix.sprintf( '%0.2f',-$iAmount);
	} else {
	    $str = $iPlus.$iPrefix.sprintf( '%0.2f',$iAmount);
	}
	return $str;
    }*/
    static public function BasicFormat($nAmt,$sPfx='$',$sPlus='') {
	throw new exception('BasicFormat() is deprecated; call Format_withSymbol() or Format_number().');
	if (is_null($nAmt)) {
	    return NULL;
	} else {
	    $sCore = $sPfx.sprintf("%01.2f",$nAmt);
	    if ($nAmt < 0) {
		$out = '-'.$sCore;
	    } else {
		$out = $sPlus.$sCore;
	    }
	    return $out;
	}
    }
    static public function Format_number($nAmt,$sPlus='') {
	return static::Format_withSymbol($nAmt,NULL,$sPlus);
    }
    /*----
      RETURNS: money amount formatted with currency symbol
      INPUT:
	nAmt: the money amount to format
	sSymbol: currency symbol to use
	sPlus: plus symbol to use when amount is positive
	nWidth: number of spaces to fit result in (left-align symbol, right-align number)
    */
    static public function Format_withSymbol($nAmt,$sSymbol='$',$sPlus='',$nWidth=NULL) {
	if (is_null($nAmt)) {
	    return NULL;
	} else {
	    // format numerically
	    $sNumFmt = sprintf("%01.2f",$nAmt);

	    // prefix with sign if needed
	    if ($nAmt < 0) {
		//$sNumFmt = '-'.$sNum;
	    } else {
		$sNumFmt = $sPlus.$sNumFmt;
	    }

	    // pad number before adding currency symbol
	    $nWidthNum = $nWidth-strlen($sSymbol);
	    $sNumFmt = str_pad($sNumFmt,$nWidthNum,' ',STR_PAD_LEFT);
	    return $sSymbol.$sNumFmt;
	}
    }

}