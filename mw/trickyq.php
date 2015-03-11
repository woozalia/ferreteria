<?php
/*
  FILE: trickyq.php -- Tricky Questions to stop bots
    Intended to work with the "QuestyCaptcha" mode of the ConfirmEdit extension
  HISTORY:
    2012-07-04 (Wzl) Started
    2013-10-22 (Wzl) More or less working -- 100-999
*/
$karSingles = array('one','two','three','four','five','six','seven','eight','nine',
  'ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen');
$karTens = array('twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety');

class BotTricks {
    static public $sAnswer;

    /*----
      INPUT: An integer between 1 and 99 inclusive
      RETURNS: That number, spelled out
      ASSUMES: iNum is an integer between 1 and 99 inclusive (no validity-checking)
    */
    static public function SpellNumber_99($iNum) {
	global $karSingles,$karTens;

	if ($iNum < 20) {
	    if ($iNum == 0) {
		$out = 'zero';
	    } else {
		$out = $karSingles[$iNum-1];
	    }
	} else {
	    $dec = (int)$iNum/10;
	    $out = $karTens[$dec-2];
	    $digit = $iNum % 10;	// mod 10 = last digit
	    if ($digit > 0) {
		$out .= '-'.$karSingles[$digit-1];
	    }
	}
	return $out;
    }

    static public function SpellNumber($iNum) {
	$n100 = floor($iNum/100);
	$n99 = $iNum-($n100*100);
	$s99 = self::SpellNumber_99($n99);

	$sOut = '';
	if ($n100 > 0) {
	    $s100 = self::SpellNumber_99($n100);
	    $sOut .= $s100.' hundred and ';
	}
	$sOut .= $s99;
	return $sOut;
    }
    static public function Answer($sText=NULL) {
	if (!is_null($sText)) {
	    self::$sAnswer = $sText;
	}
	return self::$sAnswer;
    }
    /*----
      ACTION: Generates textual number between 100 and 999
    */
    static public function Generate_3digits_text() {
	$int = rand(100,999);
	$out = self::SpellNumber($int);
	self::Answer($int);
	return $out;
    }
    static public function Generate_3digits_single_question() {
	$sQ = 'Enter the number "'.self::Generate_3digits_text().'" using numerals instead of words:';
	return $sQ;
    }
    static public function Generate_3digits_twice_question() {
	$sOne = self::Generate_3digits_text();
	$nOne = self::Answer();
	$sTwo = self::Generate_3digits_text();
	$nTwo = self::Answer();
	
	if ($nOne > $nTwo) {
	    $nBigger = $nOne;
	    $nSmaller = $nTwo;
	} else {
	    $nBigger = $nTwo;
	    $nSmaller = $nOne;
	}
	
	$bFlag = rand(1,2);
	if ($bFlag == 1) {
	    $sA = $nBigger;
	    $sDesc = 'greater';
	} else {
	    $sA = $nSmaller;
	    $sDesc = 'lesser';
	}
	self::Answer($sA);
	$sQ = "Enter the $sDesc of these numbers using numerals instead of words: <ul><li>$sOne</li><li>$sTwo</li></ul>";
	return $sQ;
    }
}
