<?php
/*
  PURPOSE: classes for dealing with money
    At this point, this is mainly for formatting purposes.
  HISTORY:
    2013-12-17 Created for DataCurr()
    2016-01-18 Same() method added
    2016-02-01 Renaming clsMoney -> fcMoney, with clsMoney as a deprecated alias
    2016-10-23 Format_withSymbol_asHTML()
*/

class fcMoney {
    static private $nDigits = 2;	// TODO: make this an overrideable option

    // ++ COMPARISON ++ //
    
    static public function Same($nAmt1, $nAmt2) {
	// until PHP 5.6 is more ubiquitous, we have to fake powers of ten:
	//$fRez = 10 ** (self::$nDigits);
	$fRez = '1' . str_repeat('0',self::$nDigits);

	$ni1 = (int)($nAmt1 * $fRez);
	$ni2 = (int)($nAmt2 * $fRez);

	return $ni1 == $ni2;
    }

    // -- COMPARISON -- //
    // ++ MATH ++ //

    /*
      HISTORY:
	2011-08-03 added round() function to prevent round-down error
	2016-09-11 converted from standalone function in VbzCart:shop.php to fcMoney method
    */
    static public function Add($iMoney1,$iMoney2) {
	$intMoney1 = (int)round($iMoney1 * 100);
	$intMoney2 = (int)round($iMoney2 * 100);
	$intSum = $intMoney1 + $intMoney2;
	return $intSum/100;
    }
    /*
      HISTORY:
	2011-08-03 added round() function to prevent round-down error
    */
    static public function Sum(&$iMoney,$iMoneyAdd) {
	$intBase = (int)round(($iMoney * 100));
	$intAdd = (int)round(($iMoneyAdd * 100));
	$intSum = $intBase + $intAdd;
	$iMoney = $intSum/100;
    }
    
    // -- MATH -- //
    // ++ FORMATTING ++ //
    
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
    // TODO: positive and negative formatting should be more flexible
    static public function Format_withSymbol_asHTML($nAmt,$sSymbol='$',$sPlus='') {
	if (is_null($nAmt)) {
	    return NULL;
	} else {
	    // format numerically
	    $sNumFmt = sprintf("%01.2f",abs($nAmt));
	    // prefix with sign
	    if ($nAmt < 0) {
		// native formatting adds minus sign automatically, so just add markup for CSS
		$htNumFmt = "<span class=money-amount><span class=money-negative><span class=number-sign>-</span>$sNumFmt</span></span>";
	    } else {
		$htNumFmt = "<span class=money-amount><span class=money-positive><span class=number-sign>$sPlus</span>$sNumFmt</span></span>";
	    }
	    return "<span class=money><span class=money-symbol>$sSymbol</span>$htNumFmt</span>";
	}
    }
    // -- FORMATTING -- //
}
class clsMoney extends fcMoney {}	// deprecated