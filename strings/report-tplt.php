<?php
// PURPOSE: finds the chunks; defines what the markup is
abstract class fcTemplateParser {
    use ftHasParentReport;

    public function ParseReport(fcReportBase $oReport) {
	if (is_null($oReport)) {
	    throw new exception('Ferreteria internal error: requesting to parse a NULL report.');
	}
	$this->SetParent($oReport);
	$this->SetText($oReport->GetTemplateText());
    	do {
	    $ok = $this->ParseNextChunkObject();
	} while ($ok);
    }

    /*----
      RETURNS: index where chunk ends, or NULL if no more chunks
	Should call EatToNextToken().
    */
    abstract public function ParseNextChunkObject();
    
    // ++ VALUES ++ //
    
    private $sText;
    protected function SetText($s) {
	$this->sText = $s;
    }
    protected function GetText() {
	return $this->sText;
    }

    // -- VALUES -- //
    // ++ PARSING OPERATIONS ++ //

    private $nLastChunk;
    protected function GetChunk($nLen) {
	return substr($this->GetText(), $this->nLastChunk, $nLen);
    }
    // ASSUMES: $nUntil is greater than $nLastChunk and less than the string length
    protected function EatChunkUntil($nUntil) {
	$nLen = $nUntil-$this->nLastChunk;
	$sChunk = $this->GetChunk($nLen);
	$this->nLastChunk = $nUntil;
	return $sChunk;
    }
    public function EatChunkLength($nLength) {
	$sChunk = $this->GetChunk($nLength);
	$this->nLastChunk += $nLength;
	return $sChunk;
    }
    // RETURNS: NULL if at end of file when called
    public function EatToNextToken($sTok) {
	$s = $this->GetText();
	$nLen = strlen($s);
	if ($nLen == $this->nLastChunk) {
	    return NULL;
	} else {
	    $nPos = strpos($s,$sTok,$this->nLastChunk);
	    if ($nPos === FALSE) {
		// no more tokens, so return rest of string
		$nPos = $nLen;
	    }
	    return $this->EatChunkUntil($nPos);
	}
    }
    
    // -- PARSING OPERATIONS -- //
}
class fcTemplateParser_braces extends fcTemplateParser {
    private $isVarNext;

    private $arReports;
    protected function PushReport(fcReportBase $oRepNew) {
	$oRepOld = $this->GetParent();	// get current Report object
	//echo 'PUSHING ['.$oRepNew->GetName().'] OVER ['.$oRepOld->GetName().']<br>';
	$this->arReports[] = $oRepOld;	// push it onto the stack
	//$oRepNew->SetReport($oRepOld);	// make it the parent of new Report object NOT HERE
	$this->SetParent($oRepNew);	// set new Report object as the current one
    }
    protected function PopReport() {
	if (is_array($this->arReports)) {
	    $oRepOld = array_pop($this->arReports);
	    if (is_null($oRepOld)) {
		$sName = $this->GetParent()->GetName();
		throw new exception("Ferreteria report error: attempting to pop a report over [$sName], but there are none left.");
	    } else {
		//echo 'POPPING ['.$oRepOld->GetName().'] OVER ['.$this->GetReport()->GetName().']<br>';
	    }
	    if (is_null($oRepOld)) {
		$this->ClearParent();
	    } else {
		$this->SetParent($oRepOld);	// restore next-oldest Report from the stack
	    }
	} else {
	    throw new exception('Attempting to pop a report when none have been pushed.');
	}
	return $oRepOld;
    }
    protected function RenderReportStack() {
	if (is_array($this->arReports)) {
	    if (count($this->arReports) > 0) {
		$out = 'Report stack:<ul>';
		foreach ($this->arReports as $oReport) {
		    '<li>('.get_class($oReport).') '.$oReport->GetName().'</li>';
		}
		$out .= '</ul>';
	    } else {
		$out = 'Report stack has been emptied.';
	    }
	    return $out;
	} else {
	    return 'Parser report stack is unused.';
	}
    }
    protected function GetTokenStringToFind($isVarNext) {
	if ($isVarNext) {
	    return '{{';
	} else {
	    return '}}';
	}
    }
    /*----
      PURPOSE: This iterates through the first pass of parsing, where we just create
	a flat array of all the chunks and the objects to handle each one.
	
	Most chunk types are given all the information they need in order to render.
	
	The exception is SubReports. Each of those is added to the array but not given the text
	  it needs to process -- because this has to be figured out on the second pass.
	If there are no SubReports, then no second pass is actually needed.
    */
    public function ParseNextChunkObject() {
	if (!$this->HasParent()) {
	  throw new exception('Ferreteria report error: parsing chunk with no active parent Report object.');
	}
	$isVarNext = empty($this->isVarNext);
	$this->isVarNext = $isVarNext;
	$sTok = $this->GetTokenStringToFind($isVarNext);
	$sChunk = $this->EatToNextToken($sTok);
	$ok = TRUE;
	$doAdd = TRUE;
	$sName = NULL;
	$oReport = $this->GetParent();	// always add stuff to the *current* report
	if ($isVarNext) {
	    if (is_null($sChunk)) {
		$ok = FALSE;
	    } else {
		// $sChunk is the text between vars
		$oChunk = new fcTemplateChunk_static('static',$sChunk);
	    }
	} else {
	    // $sChunk is like "{{name"
	    $this->EatChunkLength(strlen($sTok));	// skip "}}"
	    $sTok = $this->GetTokenStringToFind(TRUE);
	    $sName = substr($sChunk,strlen($sTok));
	    if ($sName === FALSE) {
		$ok = FALSE;	// no more chunks; done
	    } else {
		$sName = $oReport->SanitizeInput($sName);	// remove any format oddities that might mung the name
		$sPrefix = substr($sName,0,1);
		switch ($sPrefix) {
		  case '+':
		    $sName = substr($sName,1);	// remove prefix
		    $oChunk = $oReport->GetSubReport($sName);
		    //echo "FOUND SUBREPORT [$sName]<br>";
		    $this->PushReport($oChunk);
		    break;
		  case '-':
		    $sName = substr($sName,1);	// remove prefix
		    $oChunk = $this->PopReport();
		    if (is_null($oChunk)) {
			echo $this->RenderReportStack();
			throw new exception("Ferreteria report error: tried to pop a report for [$sName], but there aren't any more.");
		    }
		    //echo "RETURNING TO PARENT REPORT [$sName]<br>";
		    $doAdd = FALSE;
		    // TODO: maybe verify that the name matches; for now, it is ignored
		    break;
		  case '@':	// command chunk
		    $sCmd = substr($sName,1);	// remove prefix
		    switch ($sCmd) {
		      case '+':	// advance to next row
			$ok = TRUE;
			$oChunk = new fcTemplateChunk_advancer($oReport);
			break;
		      default:
			throw new exception("Ferreteria Report error: Unrecognized command [$sCmd].");
		    }
		    break;
		  default:	// variable chunk
		    $oChunk = new fcTemplateChunk_variable($sName,$oReport);
		}
	    }
	}
	if ($ok && $doAdd) {
	    $oReport->AddChunkObject($oChunk);
	}
	return $ok;
    }
}
