<?php
/*
  FILE: time.php -- time and date (but not calendar) functions
  HISTORY:
    2012-02-18 started with Time_DefaultDate() from WorkFerret
    2012-03-03 DateOnly_string()
    2013-01-13 adding classes with static methods
      These should eventually supercede standalone functions.
    2015-09-12 moved xtTime here from strings.php
*/

class fcDate {
    /*----
      INPUT:
	$sDate = date string in any format understood by the DateTime class
	$sNone = value to return if $sDate is not a string
	$sFormat = format to use for output
      RETURNS: either a date string or the default value
    */
    static public function NzDate($sDate,$sNone=NULL,$sFormat='Y-m-d') {
	if (is_string($sDate)) {
	  $objDate = new DateTime($sDate);
	  $out = $objDate->format($sFormat);
	} else {
	  $out = $sNone;
	}
	return $out;
    }
    // TODO: explain what this function does, and maybe come up with a better name
    static public function DefaultYear($iDate,$iYear,$iSmallerPfx='<small>',$iSmallerSfx='</small>') {
	if (empty($iDate)) {
	    return NULL;
	} else {
	    $dtIn = strtotime($iDate);
	    $yrIn = date('Y',$dtIn);
	    $doYr = ($yrIn != $iYear);
	    $out = '';
	    if ($doYr) {
		$out .= $iSmallerPfx;
	    }
	    $ftIn = date('m/d',$dtIn);
	    $out .= $ftIn;
	    if ($doYr) {
		$out .= '<br>'.$yrIn;
		$out .= $iSmallerSfx;
	    }
	    return $out;
	}
    }
}
class clsDate extends fcDate {}		// alias; deprecated

class fcTime {

    // this may not be needed -- try clsDate::NzDate
    static public function ShowStamp_HideTime($iStamp) {

	if (is_string($iStamp)) {
	    $intStamp = strtotime($iStamp);
	} else if (is_int($iStamp)) {
	    $intStamp = $iStamp;
	} else {
	    $intStamp = NULL;
	}
	if (!is_null($intStamp)) {
	    return date('Y-m-d',$intStamp);
	} else {
	    return NULL;
	}
    }

    static public function DefaultDate($iTime,$iDate,$iSmallerPfx='<small>',$iSmallerSfx='</small>',$iDateFmt='n/j') {
	if (empty($iDate)) {
	    return NULL;
	} else {
	    $itTime = strtotime($iTime);	// time to show -- convert to seconds since epoch
	    $itDate = strtotime($iDate);	// base date -- convert to seconds since epoch

	    // convert dates (only) back to string (leave off time)
	    $strTimeDate = date('Ymd',$itTime);
	    $strDateDate = date('Ymd',$itDate);

	    $doDate = ($strTimeDate != $strDateDate);
	    $out = '';
	    if ($doDate) {
		$out .= $iSmallerPfx;
	    }
	    $out .= date('H:i',$itTime);
	    if ($doDate) {
		$out .= '<br>'.date($iDateFmt,$itTime);
		$out .= $iSmallerSfx;
	    }
	    return $out;
	}
    }
}
class clsTime extends fcTime {}		// alias; deprecated


function DateOnly_string($iTime=NULL) {
    $dt = is_null($iTime)?time():$iTime;
    return date('Y-m-d',$dt);
}
/*-----
  PURPOSE: shows the time and date in an abbreviated format where the date can be omitted
    if it matches the given default
  INPUT:
    $iTime; time to show, in text format
    $iDate; base date, in text format
*/
function Time_DefaultDate($iTime,$iDate,$iSmallerPfx='<small>',$iSmallerSfx='</small>',$iDateFmt='n/j') {
    throw new exception('Call fcTime::DefaultDate() instead.');
    if (empty($iDate)) {
	return NULL;
    } else {
	$itTime = strtotime($iTime);	// time to show -- convert to seconds since epoch
	$itDate = strtotime($iDate);	// base date -- convert to seconds since epoch

	// convert dates (only) back to string (leave off time)
	$strTimeDate = date('Ymd',$itTime);
	$strDateDate = date('Ymd',$itDate);

	$doDate = ($strTimeDate != $strDateDate);
	$out = '';
	if ($doDate) {
	    $out .= $iSmallerPfx;
	}
	$out .= date('H:i',$itTime);
	if ($doDate) {
	    $out .= '<br>'.date($iDateFmt,$itTime);
	    $out .= $iSmallerSfx;
	}
	return $out;
    }
}
/*----
  PURPOSE: shows the date and time, but
    - leaves off time if it is 00:00:00
    - leaves off year if it is same as current year
  INPUT:
    $iStamp; date/time to show, in seconds since epoch
*/
function Date_DefaultTime($iStamp) {
    $arStamp = date_parse($iStamp);
    $arNow = getdate();

    $intYrStamp = $arStamp['year'];
    $intMoStamp = $arStamp['month'];
    $intDyStamp = $arStamp['day'];
    $intHrStamp = $arStamp['hour'];
    $intMiStamp = $arStamp['minute'];
    $intSeStamp = $arStamp['second'];

    // (A) format the date

    if ($intYrStamp == $arNow['year']) {
	// same year -- just do month/day, short
	$out = $intMoStamp.'/'.$intDyStamp;
    } else {
	// different year -- use entire date, long format
	//$out = sprintf('%4i-%2i-%2i',$intYrStamp,$intMoStamp,$intDyStamp);
	$out = sprintf('%4d-%02d-%02d',$intYrStamp,$intMoStamp,$intDyStamp);
	//$out = $intYrStamp.'-'.$intMoStamp.'-'.$intDyStamp;
    }

    // (B) format the time, if necessary


    if (($intHrStamp > 0) || ($intMiStamp > 0) || ($intSeStamp > 0)) {
	$out .= sprintf('%2i:%2i',$intHrStamp,$intMiStamp);
	if ($intSeStamp > 0) {
	    $out .= ':'.sprintf('%2i',$intSeStamp);
	}
    }

    return $out;
}
/*----
  USED BY: Special:VbzAdmin
*/
function Date_DefaultYear($iDate,$iYear,$iSmallerPfx='<small>',$iSmallerSfx='</small>') {
    throw new exception('Date_DefaultYear() has been replaced by clsDate::DefaultYear()');
    if (empty($iDate)) {
	return NULL;
    } else {
	$dtIn = strtotime($iDate);
	$yrIn = date('Y',$dtIn);
	$doYr = ($yrIn != $iYear);
	$out = '';
	if ($doYr) {
	    $out .= $iSmallerPfx;
	}
	$ftIn = date('m/d',$dtIn);
	$out .= $ftIn;
	if ($doYr) {
	    $out .= '<br>'.$yrIn;
	    $out .= $iSmallerSfx;
	}
	return $out;
    }
}

// TODO: determine which of these functions are *not* duplicates of native PHP functions, and document.
class xtTime {
    public function __construct($iValue=NULL) {
	if (is_string($iValue)) {
	    $this->Parse($iValue);
	} else {
	    $this->Parts();	// set all to NULL;
	}
    }
    /*----
      HISTORY:
	2016-01-12 This used to only set fields when the input wasn't NULL, but I decided to always
	  set them because (duh) you can test for NULL if you want to know whether a field was NULL.
    */
    public function Parts($iYear=NULL,$iMonth=NULL,$iDay=NULL,$iHour=NULL,$iMin=NULL,$iSec=NULL) {
	$this->intYr = $iYear;
	$this->intMo = $iMonth;
	$this->intDy = $iDay;
	$this->intHr = $iHour;
	$this->intMi = $iMin;
	$this->intSe = $iSec;
    }
    public function PartsArray($iArray=NULL) {
	if (!is_null($iArray)) {
	    $intYr = nz($iArray['year']);
	    $intMo = nz($iArray['month']);
	    $intDy = nz($iArray['day']);
	    $intHr = nz($iArray['hour']);
	    $intMi = nz($iArray['minute']);
	    $intSe = nz($iArray['second']);
	    $this->Parts($intYr,$intMo,$intDy,$intHr,$intMi,$intSe);
	}
	$arOut = array(
	  'year'	=> nz($this->intYr),
	  'month'	=> nz($this->intMo),
	  'day'		=> nz($this->intDy),
	  'hour'	=> nz($this->intHr),
	  'minute'	=> nz($this->intMi),
	  'second'	=> nz($this->intSe));
	return $arOut;
    }
    public function Year($iYear=NULL) {
	if (!is_null($iYear)) {
	    $this->intYr = $iYear;
	}
	if (isset($this->intYr) || is_null($this->intYr)) {
	    return $this->intYr;
	} else {
	    throw new exception('Trying to retrieve intYr before it has been set.');
	}
    }
    public function Parse($iString) {		// date and/or time
	$this->DateParse($iString);
    }
    public function DateParse($iString) {
	$arDate = date_parse($iString);
	$this->PartsArray($arDate);
    }
    public function HasTime() {
	return !empty($this->intHr);
    }
    public function FormatSortable($iSep='-') {
	$out = $this->intYr.$iSep.sprintf('%02u',$this->intMo).$iSep.sprintf('%02u',$this->intDy);
	return $out;
    }
    public function FormatSQL() {
	$out = $this->intYr.'/'.$this->intMo.'/'.$this->intDy;
	if ($this->HasTime()) {
	    $out .= ' '.$this->intHr.':'.$this->intMi.':'.$this->intSe;
	}
	return $out;
    }
/*
    public function DateTimeObj() {
	$dtOut = new DateTime($this->Format('Y-);
    }
*/
    public function AssumeYear($iMonthsAhead) {
	if (empty($this->intYr)) {
	    $intYrCur = date('Y');
	    $this->intYr = $intYrCur;
	    if (isset($this->intMo)) {
		$intMoCur = date('n');
		if (($this->intMo - $intMoCur) > $iMonthsAhead) {
		    $this->intYr--;
		}
	    }
	}
    }
}
