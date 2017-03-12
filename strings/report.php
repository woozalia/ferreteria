<?php
/*
  PURPOSE: 2nd generation templating system that parses into chunks
    Also taking the opportunity to rework data management.
  CLASSES:
    * fcRenderable returns calculated output
    * fcReportBase (a Renderable) - things that Reports and SubReports have in common
      * has text
      * has a dataset
      * has zero or more SubReport objects
    * fcTemplateParser:
      * has a Report
      * has a pointer to the current position in the Report's template text
      * delivers the next Chunk-object on request
    * fcTemplateChunk_static (a Renderable) just returns static text
    * fcTemplateChunk_variable (a Renderable):
      * has a dataset
      * looks up its value in the dataset
    * fcReportMain
      * may have a list of datasets for subreports
  HISTORY:
    2017-02-20 started as base class for Reports
*/

// TRAITS

trait ftHasRecords {
    private $rc;
    public function SetRecords(fcDataRow $rc) {
	$this->rc = $rc;
    }
    public function GetRecords() {
	return $this->rc;
    }
    protected function HasRecords() {
	return isset($this->rc);
    }
}
trait ftHasReport {
    private $oRep = NULL;
    protected function SetReport(fcReportBase $oReport) {
	$this->oRep = $oReport;
    }
    protected function ClearReport() {
	$this->oRep = NULL;
    }
    protected function GetReport() {
	return $this->oRep;
    }
    protected function HasReport() {
	return !is_null($this->oRep);
    }
}
trait ftHasName {

    private $sName;
    protected function SetName($s) {
	$this->sName = $s;
    }
    public function GetName() {
	return $this->sName;
    }
}

// CLASSES

abstract class fcRenderable {
    use ftHasName;
    abstract public function Render();
}
abstract class fcReportBase extends fcRenderable {
    use ftHasRecords,ftHasReport;
    
    public function __construct($sName,fcReportBase $rpParent=NULL) {
	$this->SetName($sName);
	if (!is_null($rpParent)) {
	    $this->SetReport($rpParent);
	}
    }

    // ++ DATA VALUE ++ //
    
    private $sTplt;
    protected function SetTemplateText($s) {
	$this->sTplt = $s;
    }
    public function GetTemplateText() {
	return $this->sTplt;
    }

    // -- DATA VALUE -- //
    // ++ CLASSES ++ //
    
    /*----
      NOTE: This can be more flexible later.
    */
    protected function SubReportClass() {
	return 'fcReportStandard';
    }

    // -- CLASSES -- //
    // ++ OBJECTS ++ //
    
    abstract protected function GetParserObject();
    
    private $arReports;
    public function AddSubReport(fcReportBase $oReport) {
	$sName = $oReport->GetName();
	if ($sName == $this->GetName()) {
	    throw new exception("Ferreteria internal error: adding report [$sName] to itself.");
	}
	$this->arReports[$sName] = $oReport;
    }
    /*----
      PUBLIC so Parser can retrieve a subreport whenever the template names one
      RULES: There are two ways of storing/retrieving a subreport:
	1. The arReports array contains a list of named report objects with datasets
	2. If a named report is not found in arReports, the current dataset is
	  checked to see if there is a field by that name. If there is, and it contains
	  an array, it is assumed to be data which should be loaded into an array rowset
	  and plugged into a standard report object.
	  
	  Note that we can't load the data yet here, because here we're still in the parsing
	  phase and haven't yet started looking through the data.
	  
	  What we do, then, is go ahead and create a subreport without checking for the data.
	  The data is found (or not) during the rendering stage.
    */
    public function GetSubReport($sName) {
	$rpSub = fcArray::Nz($this->arReports,$sName);
	if (is_null($rpSub)) {
	    $sClass = $this->SubReportClass();
	    $rpSub = new $sClass($sName,$this);
	    $this->AddSubReport($rpSub);
	}
	if (is_null($rpSub)) {
	    // 2017-02-24 this probably can't happen now, or at least it's the wrong message
	    throw new exception("Requested report or dataset '$sName' was not found.");
	}
	return $rpSub;
    }
    
    private $arChunks;
    public function AddChunkObject(fcRenderable $oChunk) {
	$this->arChunks[] = $oChunk;
    }
    protected function GetChunkObjectArray() {
	return $this->arChunks;
    }
    /*----
      ACTION: If a recordset is not already assigned, tries to retrieve one from the parent Report
    */
    protected function FindRecords() {
	// FIRST: Make sure we have a records object to work with
	if ($this->HasRecords()) {
	    $rcOut = $this->GetRecords();
	} else {
	    $rcOut = new fcDataRow_array();
	}
	
	// SECOND: See if the field value is an array
	if ($this->HasReport()) {
	    $rc = $this->GetReport()->GetRecords();
	    $sName = $this->GetName();
	    if ($rc->FieldIsSet($sName)) {
		$v = $rc->GetFieldValue($sName);
		if (is_array($v)) {
		    // make a new recordset for the array and return it
		    $rcOut->SetAllRows($v);
		    $this->SetRecords($rcOut);
		} else {
		    echo 'REPORT FIELDS:'.fcArray::Render($rc->GetFieldValues());
		    throw new exception("Requested subreport '$sName' was not found.");
		}
	    }
	}
	return $rcOut;
    }
    
    // -- OBJECTS -- //
    // ++ FORMAT SUPPORT ++ //

    // STUB - becomes important for some file formats
    public function SanitizeInput($s) { return $s; }
    // STUB - becomes important for some file formats
    public function SanitizeOutput($s) { return $s; }

    // -- FORMAT SUPPORT -- //
    // ++ OUTPUT ++ //
    
    // ACTION: parse template into chunk object tree
    public function Parse() {
	$this->GetParserObject()->ParseReport($this);
    }
    public function Render() {
	// iterate through chunks, rendering each one
	$rc = $this->FindRecords();
	if ($rc->HasRow()) {
	    // if row is loaded, assume only one row and render it:
	    $out = $this->RenderRow();
	} else {
	    // if row not loaded, assume we need to cycle through rows:
	    $out = NULL;
	    while ($rc->NextRow()) {
		$out .= $this->RenderRow();
	    }
	}
	return $out;
    }
    protected function RenderRow() {
	$out = NULL;
	$arChunks = $this->GetChunkObjectArray();
	if (is_array($arChunks)) {
	    foreach ($arChunks as $oChunk) {
		$sName = $oChunk->GetName();
		$sLine = $oChunk->Render();
		$out .= $sLine;
	    }
	} else {
	    // LATER: Maybe this shouldn't be an error. Could have an option to define "no contents" string.
	    throw new exception('Subreport '.$this->GetName().' has no chunks.');
	}
	return $out;
    }

    // -- OUTPUT -- //
}
// PURPOSE: finds the chunks; defines what the markup is
abstract class fcTemplateParser {
    use ftHasReport;

    public function ParseReport(fcReportBase $oReport) {
	if (is_null($oReport)) {
	    throw new exception('Ferreteria internal error: requesting to parse a NULL report.');
	}
	$this->SetReport($oReport);
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
class fcTemplateChunk_static extends fcRenderable {

    public function __construct($sName,$sValue) {
	$this->SetName($sName);
	$this->SetValue($sValue);
    }

    private $sValue;
    protected function SetValue($s) {
	$this->sValue = $s;
    }
    protected function GetValue() {
	return $this->sValue;
    }
    
    public function Render() {
	return $this->GetValue();
    }
}
class fcTemplateChunk_variable extends fcRenderable {
    use ftHasReport;

    public function __construct($sName,fcReportBase $oReport) {
	if (!is_string($sName)) {
	    throw new exception('Chunk has no name.');
	}
	$this->SetName($sName);
	$this->SetReport($oReport);
    }
    protected function GetRecords() {
	return $this->GetReport()->GetRecords();
    }
    public function Render() {
	$rc = $this->GetRecords();
	$sName = $this->GetName();
	if (!is_string($sName)) {
	    throw new exception('Chunk has no name.');
	}
	if ($rc->FieldIsSet($sName)) {
	    $out = $rc->GetFieldValue($sName);
	    $out = $this->GetReport()->SanitizeOutput($out);
	} else {
	    $out = "?$sName?";	// indicate that name is not found in data
	}
	return $out;
    }
}

// implementations

class fcTemplateParser_braces extends fcTemplateParser {
    private $isVarNext;

    private $arReports;
    protected function PushReport(fcReportBase $oRepNew) {
	$oRepOld = $this->GetReport();	// get current Report object
	//echo 'PUSHING ['.$oRepNew->GetName().'] OVER ['.$oRepOld->GetName().']<br>';
	$this->arReports[] = $oRepOld;	// push it onto the stack
	//$oRepNew->SetReport($oRepOld);	// make it the parent of new Report object NOT HERE
	$this->SetReport($oRepNew);	// set new Report object as the current one
    }
    protected function PopReport() {
	if (is_array($this->arReports)) {
	    $oRepOld = array_pop($this->arReports);
	    if (is_null($oRepOld)) {
		$sName = $this->GetReport()->GetName();
		throw new exception("Ferreteria report error: attempting to pop a report over [$sName], but there are none left.");
	    } else {
		//echo 'POPPING ['.$oRepOld->GetName().'] OVER ['.$this->GetReport()->GetName().']<br>';
	    }
	    if (is_null($oRepOld)) {
		$this->ClearReport();
	    } else {
		$this->SetReport($oRepOld);	// restore next-oldest Report from the stack
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
	if (!$this->HasReport()) {
	  throw new exception('Ferreteria report error: parsing chunk with no active Report object.');
	}
	$isVarNext = empty($this->isVarNext);
	$this->isVarNext = $isVarNext;
	$sTok = $this->GetTokenStringToFind($isVarNext);
	$sChunk = $this->EatToNextToken($sTok);
	$ok = TRUE;
	$doAdd = TRUE;
	$sName = NULL;
	$oReport = $this->GetReport();	// always add stuff to the *current* report
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
class fcTemplateChunk_advancer extends fcRenderable {
    use ftHasReport;
    
    public function __construct(fcReportBase $oReport) {
	$this->SetName($oReport->GetName().'.advancer');
	$this->SetReport($oReport);
    }
    public function Render() {
	$this->GetReport()->GetRecords()->NextRow();
	return '';
    }
}

// PURPOSE: a standard report (sensible defaults for various things)
class fcReportStandard extends fcReportBase {
    protected function GetParserObject() {
	return new fcTemplateParser_braces();
    }
    
    // THESE TWO METHODS ARE A BIG OL' KLUGE - we need a separate class for handling the master document format
    
    /*----
      ACTION: Remove any tags which might have gotten inserted in template variable names
      OVERRIDE
    */
    public function SanitizeInput($s) {
	return strip_tags($s);
    }
    /*----
      ACTION: Convert syntactically-significant characters to HTML entities
      OVERRIDE
    */
    public function SanitizeOutput($s) {
	return htmlspecialchars($s);
    }
    
}
