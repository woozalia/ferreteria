<?php
abstract class clsStringTemplate {
// Abstract version
    public $Value;
    protected $strStMark;
    protected $strFiMark;

    function __construct($iStartMark, $iFinishMark) {
	    $this->strStMark = $iStartMark;
	    $this->strFiMark = $iFinishMark;
    }
    abstract protected function GetValue($iName);
    function Replace($iValue=NULL) {
	global $wxgDebug;

	if (is_null($iValue)) {
	    $out = $this->Value;
	} else {
	    $out = $iValue;
	}

// do variable swapout:
	$intStarts = 0;
	do {
	    $isFound = false;
	    $posSt = strpos ( $out, $this->strStMark );
	    if ($posSt !== FALSE) {
		$intStarts++;
		$posFiVar = strpos ( $out, $this->strFiMark, $posSt );
		if ($posFiVar !== FALSE) {
		    $isFound = true;
		    $posStVar = ($posSt+strlen($this->strStMark));
		    $varLen = $posFiVar - $posStVar;
		    $varName = substr($out, $posStVar, $varLen);
		    $posFi = ($posFiVar+strlen($this->strFiMark));
		    $varVal = $this->GetValue($varName);	// virtual method to retrieve variable's value
		    $wxgDebug .= '<br>KEY=['.$varName.'] VAL=['.$varVal.']';
		    $out =
			substr($out, 0, $posSt )
			.$varVal
			.substr($out, $posFi );
		}
	    }
	} while ($isFound);
	$wxgDebug .= "\n* STARTS [".$this->strStMark."]: $intStarts";
	return $out;
    }
}
class clsStringTemplate_array extends clsStringTemplate {
// This version can be used if the values are in an associative array
	public $List;

	public function __construct($iStartMark, $iFinishMark, array $iValues) {
	    parent::__construct($iStartMark,$iFinishMark);
	    $this->List = $iValues;
	}
	/*
	  ACTION: Sets ->Value, ->strStMark, and ->strFiMark from iText
	  INPUT:
	    iText = prefix-delimited string where:
	      [0] is the start mark
	      [1] is the finish mark
	      [2] is the template to process
	*/
	public function MarkedValue($iText) {
	    $xts = new xtString;
	    $xts->Value = $iText;
	    $arIn = $xts->Xplode();
	    $this->strStMark = $arIn[0];
	    $this->strFiMark = $arIn[1];
	    $this->Value = $arIn[2];
	}
	protected function GetValue($iName) {
	    return $this->List[$iName];
	}
}
/*
class xtString {
    public $Value;
    public function GetArray() {
	$tok = substr ( $this->Value, 0, 1);	// token for splitting
	if ($tok) {
		$tks = substr ( $this->Value, 1 );	// tokenized string
		$list = explode ( $tok, $tks );	// split the string
		return $list;
	} else {
		return NULL;
	}
    }
}
*/