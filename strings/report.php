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
trait ftHasParentReport {
    private $oRep = NULL;
    protected function SetParent(fcReportBase $oReport) {
	$this->oRep = $oReport;
    }
    protected function ClearParent() {
	$this->oRep = NULL;
    }
    protected function GetParent() {
	return $this->oRep;
    }
    protected function HasParent() {
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
    use ftHasRecords,ftHasParentReport;
    
    public function __construct($sName,fcReportBase $rpParent=NULL) {
	$this->SetName($sName);
	if (!is_null($rpParent)) {
	    $this->SetParent($rpParent);
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
	if ($this->HasParent()) {
            $oRep = $this->GetParent();
	    $rc = $oRep->GetRecords();
	    $sName = $this->GetName();
	    if ($rc->FieldIsSet($sName)) {
                echo "LOOKING UP FIELD [$sName]<br>";
                echo 'FIELDS: '.fcArray::Render($rc->GetFieldValues());
		$v = $rc->GetFieldValue($sName);
		if (is_array($v)) {
		    // make a new recordset for the array and return it
		    echo 'ARRAY V:'.fcArray::Render($v);
		    $rcOut->SetAllRows($v);
		    $this->SetRecords($rcOut);
		    throw new exception('how do we get here?');
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
    use ftHasParentReport;

    public function __construct($sName,fcReportBase $oReport) {
	if (!is_string($sName)) {
	    throw new exception('Chunk has no name.');
	}
	$this->SetName($sName);
	$this->SetParent($oReport);
    }
    protected function GetRecords() {
	return $this->GetParent()->GetRecords();
    }
    public function Render() {
	$rc = $this->GetRecords();
	$sName = $this->GetName();
	if (!is_string($sName)) {
	    throw new exception('Chunk has no name.');
	}
	if ($rc->FieldIsSet($sName)) {
	    $out = $rc->GetFieldValue($sName);
	    $out = $this->GetParent()->SanitizeOutput($out);
	} else {
	    $out = "?$sName?";	// indicate that name is not found in data
	}
	return $out;
    }
}

// implementations


class fcTemplateChunk_advancer extends fcRenderable {
    use ftHasParentReport;
    
    public function __construct(fcReportBase $oReport) {
	$this->SetName($oReport->GetName().'.advancer');
	$this->SetParent($oReport);
    }
    public function Render() {
	$this->GetParent()->GetRecords()->NextRow();
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
