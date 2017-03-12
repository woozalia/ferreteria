<?php
/*
  PURPOSE: Reports that read a file for the template and produce files for output
  HISTORY:
    2017-02-20 Created for testing report.php
*/

/*::::
  PURPOSE: reports based on files that don't need any massaging before or after
  TODO: This should be descended from fcReport, or maybe fcReportStandard should be set up so that
    its defaults can be altered by setting various constants. Worry about this for later. For now,
    it will use sensible defaults.
  NOTES: I'm not happy with the "LoadTemplate" / "RenderTo" naming dichotomy, but nothing else
    really seems accurate.
*/
class fcReportFile extends fcReportStandard {

    public function __construct($sName,$fsTemplate,$fsRendered=NULL) {
	parent::__construct($sName);
	$this->SetTemplateSpec($fsTemplate);
	$this->SetRenderedSpec($fsRendered);
    }

    // ++ ACTION ++ //
    
    public function DoReport($fnDown=NULL) {
	$this->LoadTemplateFile();
	$this->Parse();
	$fsRendered = $this->GetRenderedSpec();
	$this->RenderToFile($fsRendered);
	if (!is_null($fnDown)) {
	    $this->DeliverReport($fsRendered,$fnDown);
	}
    }
    
    // -- ACTION -- //
    // ++ SETTINGS ++ //
    
    private $fsTplt;
    protected function SetTemplateSpec($fs) {
	$this->fsTplt = $fs;
    }
    protected function GetTemplateSpec() {
	return $this->fsTplt;
    }
    private $fsRend;
    protected function SetRenderedSpec($fs) {
	$this->fsRend = $fs;
    }
    protected function GetRenderedSpec() {
	return $this->fsRend;
    }

    // -- SETTINGS -- //
    // ++ FILE I/O ++ //
    
    /*----
      ACTION:
	* does any massaging necessary to access the template text
	* loads file data into the object
    */
    public function LoadTemplateFile() {
	$fs = $this->GetTemplateSpec();
	$this->LoadTemplateRawFile($fs);
    }
    /*----
      ACTION: save modified content back to source template file
      TODO: test this.
      HISTORY:
	2017-03-05 Written just for consistency; not tested
    */
    public function SaveTemplateContent($s) {
	$fs = $this->GetTemplateSpec();
	file_put_contents($fs,$s);
    }
    /*----
      ACTION:
	* writes out the rendering to the given filename
	* does any post-write massaging necessary to compose the final file
	* overwrites any existing content
    */
    public function RenderToFile($fs) {
	$this->RenderToRawFile($fs);
    }
    
    /*----
      ACTION: loads file data directly into the object, to be used as the template
    */
    protected function LoadTemplateRawFile($fs) {
	$s = file_get_contents($fs);
	$this->SetTemplateText($s);
    }
    /*----
      ACTION: writes out the rendered text directly to the given filename
	Overwrites any existing content.
    */
    protected function RenderToRawFile($fs) {
	$sOut = $this->Render();
	$nBytes = file_put_contents($fs,$sOut);
	
	$arStatus = array('output stage',
	  array(
	    "WRITING TO [$fs]",
	    "WROTE $nBytes byte".fcString::Pluralize($nBytes),
	    )
	  );
	$this->AddCommandResult($arStatus);
	
    }
    public function DeliverReport($fs,$fnDown) {
	$sMIME = mime_content_type($fs);
	fcHTTP::SendDownload($sMIME,$fs,$fnDown);
    }

    // -- FILE I/O -- //
    // ++ STATUS MESSAGES ++ // - currently only used in descendant, but really these belong here
    
    private $arErr;
    /* 2017-03-05 This seems like a bad idea, because it's too easy to overwrite already-added errors.
    protected function SetErrors($ar) {
	$this->arErr = $ar;
    }*/
    protected function GetErrors() {
	return $this->arErr;
    }
    protected function AddError($sErr) {
	$this->arErr[] = $sErr;
    }
    public function HasErrors() {
	if (is_null($this->GetErrors())) {
	    return FALSE;
	} else {
	    return count($this->GetErrors());
	}
    }
    public function RenderErrors() {
	$arErrors = $this->GetErrors();
	if (is_null($arErrors)) {
	    $out = NULL;
	} else {
	    $nErr = count($arErrors);
	    $sPlur = fcString::Pluralize($nErr,'One error found',$nErr.' errors found');
	    $htErrors = NULL;
	    foreach ($arErrors as $htLine) {
		$htErrors .= "\n<li>$htLine</li>";
	    }
	    $out = "\n<b>$sPlur in output:</b>\n<ul>$htErrors\n</ul>";
	}
	return $out;
    }
    private $arCmds;
    protected function AddCommandResult(array $arCmds) {
	$nRows = count($this->arCmds);
	foreach ($arCmds as $sCmd => $sRes) {
	    $nRows++;
	    $this->arCmds[$nRows.'. '.$sCmd] = $sRes;
	}
    }
    protected function GetCommandResults() {
	return $this->arCmds;
    }
    public function RenderCommandResults() {
	$nRows = count($this->arCmds);
	if ($nRows > 0) {
	    $out = "\nCommand results:\n<ul>";
	    foreach ($this->arCmds as $sCmd => $sRes) {
		$out .= "\n<li><b>$sCmd</b>";
		if (is_array($sRes)) {
		    $out .= fcArray::Render($sRes);
		} else {
		    $out .= ' '.$sRes;
		}
		$out .= '</li>';
	    }
	    $out .= "\n</ul>";
	} else {
	    $out = NULL;
	}
	return $out;
    }
    private $sSuccess;
    protected function SetSuccessMessage($s) {
	$this->sSuccess = $s;
    }
    public function GetSuccessMessage() {
	return $this->sSuccess;
    }
    
    // -- STATUS MESSAGES -- //
    
}
