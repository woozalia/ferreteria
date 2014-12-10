<?php
/*
  FILE: time.php -- time and date (but not calendar) functions
  HISTORY:
    2012-02-18 started with Time_DefaultDate() from WorkFerret
    2012-03-03 DateOnly_string()
    2013-01-13 adding classes with static methods
      These should eventually supercede standalone functions.
*/

class clsDate {
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
}

class clsTime {

    // this may not be needed -- try clsData::NzDate
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
}


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
