<?php
/*
  HISTORY:
    2016-08-08 fcTemplate can now be constructed with no parameters, so we can set marks and template with the same string
      using MarkedValue().
*/

abstract class fcTemplate {
    protected $sMarkSt;
    protected $sMarkFi;
    private $sTplt;

    public function __construct($sStartMark=NULL, $sFinishMark=NULL,$sTemplate=NULL) {
	$this->SetStartMark($sStartMark);
	$this->SetFinishMark($sFinishMark);
	$this->Template($sTemplate);
    }
    /*----
      PUBLIC so callers can override individual values
    */
    abstract public function VariableValue($sName);

    // ++ CONFIGURATION ++ //

    public function Template($s=NULL) {
	if (!is_null($s)) {
	    $this->sTplt = $s;
	}
	return $this->sTplt;
    }
    protected function SetStartMark($s) {
	$this->sMarkSt = $s;
    }
    protected function StartMark($s=NULL) {
	if (!is_null($s)) {
	    $this->sMarkSt = $s;
	}
	return $this->sMarkSt;
    }
    protected function SetFinishMark($s) {
	$this->sMarkFi = $s;
    }
    protected function FinishMark($s=NULL) {
	if (!is_null($s)) {
	    $this->sMarkFi = $s;
	}
	return $this->sMarkFi;
    }
    /*
      ACTION: Sets ->Value, ->strStMark, and ->strFiMark from iText
      INPUT:
	iText = prefix-delimited string where:
	  First segment is the start mark
	  Second segment is the finish mark
	  Third Segment is the template to process
    */
    public function MarkedValue($sValue) {
	$arIn = fcString::Xplode($sValue);
	$this->StartMark($arIn[0]);
	$this->FinishMark($arIn[1]);
	$this->Template($arIn[2]);
    }

    // -- CONFIGURATION -- //
    // ++ MAIN PROCESS ++ //

    public function Render() {
	$out = $this->Template();
	$smSt = $this->StartMark();
	$smFi = $this->FinishMark();

	$nStarts = 0;
	do {
	    $isFound = false;
	    $posSt = strpos ( $out, $smSt );
	    if ($posSt !== FALSE) {
		$nStarts++;
		$posFiVar = strpos ( $out, $smFi, $posSt );
		if ($posFiVar !== FALSE) {
		    $isFound = true;
		    $posStVar = ($posSt+strlen($smSt));
		    $varLen = $posFiVar - $posStVar;
		    $varName = substr($out, $posStVar, $varLen);
		    $posFi = ($posFiVar+strlen($smFi));
		    $varVal = $this->VariableValue($varName);	// virtual method to retrieve variable's value
		    $out =
			substr($out, 0, $posSt )
			.$varVal
			.substr($out, $posFi );
		}
	    }
	} while ($isFound);
	return $out;
    }
    /*----
      PURPOSE: Same as Render(), but handles recursive replacement -- i.e. a variable's value
	may contain a reference to another variable.
      HISTORY:
	2015-09-03 Defined $smSt. Code could not possibly have worked before this.
    */
    public function RenderRecursive() {
	$out = $this->Render();
	$done = FALSE;
	$smSt = $this->StartMark();
	do {
	    $posSt = strpos ( $out, $smSt );	// does output contain more vars?
	    if ($posSt === FALSE) {
		$done = TRUE;
	    } else {
		// spawn another object to handle inner vars
		$sClass =  __CLASS__;
		$tpInner = new $sClass($smSt,$this->FinishMark(),$out);
		$out = $tpInner->RenderRecursive();
	    }
	} while (!$done);
	return $out;
    }

    // -- MAIN PROCESS -- //
}

class fcTemplate_array extends fcTemplate {
    private $arVals;

    public function VariableValues(array $arVals=NULL) {
	if (!is_null($arVals)) {
	    $this->arVals = $arVals;
	}
	return $this->arVals;
    }
    public function VariableValue($sName,$sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->arVals[$sName] = $sVal;
	}
	if (array_key_exists($sName,$this->arVals)) {
	    return $this->arVals[$sName];
	} else {
	    echo 'Variables defined:<br>'.clsArray::Render($this->arVals).'<br>';
	    throw new exception("Attempting to access undefined template variable [$sName].");
	}
    }

    public function Render($arVals=NULL) {
	$this->VariableValues($arVals);
	return parent::Render();
    }
}

// OLD CLASSES -- deprecate soon, remove later

abstract class clsStringTemplate {
// Abstract version
    public $Value;
    protected $strStMark;
    protected $strFiMark;

    // ++ SETUP ++ //
    
    public function __construct($iStartMark, $iFinishMark) {
	    $this->strStMark = $iStartMark;
	    $this->strFiMark = $iFinishMark;
    }
    public function StartMark($sMark=NULL) {
	if (!is_null($sMark)) {
	    $this->strStMark = $sMark;
	}
	return $this->strStMark;
    }
    public function FinishMark($sMark=NULL) {
	if (!is_null($sMark)) {
	    $this->strFiMark = $sMark;
	}
	return $this->strFiMark;
    }
    
    // -- SETUP -- //
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
	    if (empty($this->strStMark)) {
		throw new exception('Template error: starting mark not defined.');
	    }
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
	protected function GetValue($sName) {
	    if (array_key_exists($sName,$this->List)) {
		return $this->List[$sName];
	    } else {
		throw new exception("Template requires [$sName], but it is not defined in the array.");
	    }
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