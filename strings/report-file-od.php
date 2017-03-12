<?php
/*
  PURPOSE: ReportFile subclass for OpenDocument (.od?) files
    Automatically unpacks .od? format, reads content XML file as template,
      copies file structure, overwrites copied content XML,
      and recompresses back to a valid .od? file.
  HISTORY:
    2017-02-18 started (based on Simple Reports, which is all there was at the time)
      The result of this work was later
    2017-02-21 working for Simple Reports (single-layer templates)
    2017-02-23 got multilayer Reports (fcReport) working (for one sublayer, at least).
      Renamed Simple OD Reports class to fcReportSimple_OpenDoc
      Adapting fcReportSimple_OpenDoc for multilayer reports (fcReport_OpenDoc).
*/

class fcReport_OpenDoc extends fcReportFile {

    // ++ FILESPECS ++ //
    
    private $fpUnzip;
    protected function GetUnzipFolderSpec() {
	if (empty($this->fpUnzip)) {
	    $fpTemp = fcFileSystem::CreateTemporaryFolder('reports');
	    $this->fpUnzip = $fpTemp;
	}
	return $this->fpUnzip;
    }
    // WHAT: filespec for generated content file (.xml)
    protected function GetContentSpec() {
	return $this->GetUnzipFolderSpec().'/content.xml';
    }
    
    // -- FILESPECS -- //
    // ++ AUXILIARY OUTPUT ++ //
 
    // MEANING: XML output for the ODT file, but with line-breaks for easier error tracking
    private $xmlOut;
    protected function SetOutput_XML($s) {
	$this->xmlOut = $s;
    }
    public function GetOutput_XML() {
	return $this->xmlOut;
    }
    // MEANING: XML output formatted for HTML display
    private $htmlOut;
    protected function SetOutput_HTML($ht) {
	$this->htmlOut = $ht;
    }
    public function GetOutput_HTML() {
	return $this->htmlOut;
    }
    
    // -- AUXILIARY OUTPUT -- //
    // ++ FILE I/O ++ //
    
    /*----
      ACTION:
	* does any massaging necessary to access the template text
	* loads file data into the object
      REQUIRES: TemplateSpec must be set
      OVERRIDE
    */
    public function LoadTemplateFile() {
	$fs = $this->GetTemplateSpec();
	$this->DoUnzip($fs);	// unzip it (in a temp location)
	$fsContent = $this->GetContentSpec();
	$this->LoadTemplateRawFile($fsContent);
    }
    /*----
      ACTION:
	* writes out the rendering to the given filename
	* does any post-write massaging necessary to compose the final file
	* overwrites any existing content
      ASSUMES: source template file has already been unzipped to a temporary location
      OVERRIDE
    */
    public function RenderToFile($fs) {	
	
	// XML DEBUGGING
	$sRaw = $this->Render();
	$this->FormatContent($sRaw);
	$sOut = $this->GetOutput_XML();
	$this->WriteContent($sOut);
	$this->DoRezip($fs);			// re-zip entire package to $fs
    }
    // ACTION: write XML to content file
    protected function WriteContent($s) {
	$fsContent = $this->GetContentSpec();	// get path to unzipped content
	$n = file_put_contents($fsContent,$s);	// replace unzipped template content with output content
	if ($n === FALSE) {
	    $this->AddError("Error writing content to [$fsContent].");
	} else {
	    $this->SetSuccessMessage("Wrote $n bytes to [$fsContent].");
	}
    }
    // ACTION: save modified content back to packed template file
    public function SaveTemplateContent($s) {
	$this->WriteContent($s);		// write content to unpacked content file
	$fs = $this->GetTemplateSpec();		// get the template filespec
	$this->DoRezip($fs);			// rezip the unpacked files back up to the template
    }
    /*----
      OUTPUT:
	1. GetOutput_XML() - largely unaltered XML
	  ...but puts a line break after each tag, so it's easier to find the location of an error
	2. GetOutput_HTML() - HTML showing tree structure, with line numbers
	3. GetErrors() - array of text messages describing any errors found
    */
    public function FormatContent($s) {
	// set up indentation markup
	// - HTML (for debug output)
	$htIBlk = "\n<ul>";	// string to start a block indent
	$htLine = "\n<li>";	// line prefix
	$htOBlk = "\n</ul>";	// string to end a block indent (outdent)
	// - plaintext (for document generation)
	$sInd = "\t";		// string to indent a line
	$sLine = "\n";		// line suffix

	$sRaw = $s;
	$nLenAll = strlen($sRaw);
	$nPos = 0;
	$nLine = 0;
	$nInd = 0;
	$htOut = $htIBlk;
	$sOut = NULL;
	$arErrors = NULL;
	$ok = TRUE;
	while (($nPos < $nLenAll) && $ok) {
	    $nNxt = $nPos+1;
	    $nFnd = strpos($sRaw,'<',$nNxt);	// find next tag
	    if ($nFnd === FALSE) {
		// no more found
		$nFnd = $nLenAll;	// point just past end of source text
	    }
	    $sPiece = substr($sRaw,$nPos,$nFnd-$nPos);
	    $sPiece = trim($sPiece);		// remove any blanks from beginning/end
	    // at this point, $sPiece could be "<tag value/> some text here"
	    $nLenThis = strlen($sPiece);	// should be same as nFnd-nPos
	    $htPiece = htmlspecialchars($sPiece);

	    $sDent = str_repeat($sInd,$nInd);	// current text indentation
	    
	    // detect hierarchy
	    $ch = substr($sPiece,1,1);	// second character
	    if ($ch == '/') {
		$oTag = new fcHTML_Parser($sPiece);
		$oTag->ParseTag();
		$sTag = $oTag->GetTagName();
		
		// OUTDENT the tag
		$htDent = $htOBlk;
		$nInd--;
		if ($nInd < 0) {
		    $this->AddError("<b>line $nNext</b>: Outdent exceeds indent.");
		    $ok = FALSE;	// exit the loop
		    $css = 'color: red;';
		    $sPopup = 'Outdent exceeds indent';
		} else {
		    $sTagWas = $arTags[$nInd];
		    if ($sTagWas == $sTag) {
			$css = 'color: green;';
			$sPopup = '';
		    } else {
			$css = 'color: red;';
			$sPopup = "tag name should be [$sTagWas], found [$sTag]";
			$nNext = $nLine+1;
			$this->AddError("<b>line $nNext</b>: $sPopup");
		    }
		}
		$htPiece = htmlspecialchars($sPiece);
		$htTag = htmlspecialchars($sTag);
		$oTagHT = new fcStringDynamic($htPiece);
		$htPiece = $oTagHT->ReplaceOnce($htTag,"<span style='$css' title='$sPopup'>$htTag</span>");
	    } else {
		$oTag = new fcHTML_Parser($sPiece);
		$oTag->ParseTag();
		if ($oTag->GetClosesSelf()) {
		    $htDent = '';	// no change
		} else {
		    // INDENT the tag
		    $htDent = $htIBlk;
		    
		    $sTag = $oTag->GetTagName();
		    		
		    $htPiece = htmlspecialchars($sPiece);
		    $htTag = htmlspecialchars($sTag);
		    $oTagHT = new fcStringDynamic($htPiece);
		    $htPiece = $oTagHT->ReplaceOnce($htTag,"<span style='color: green;'>$htTag</span>");
		    
		    $arTags[$nInd] = $sTag;
		    $nInd++;
		}
	    }
	    
	    $nPos = $nFnd;

	    $nLine++;
	    $htOut .= $htLine.$nLine.'. '.$htPiece.$htDent;
	    $sOut .= $sDent.$sPiece.$sLine;
	}
	$htOut .= $htOBlk;
	
	// save outputs for caller
	$this->SetOutput_XML($sOut);
	$this->SetOutput_HTML($htOut);
	//$this->SetErrors($arErrors);
    }
    // NEW
    protected function DoUnzip($fs) {
	$fpUnzip = $this->GetUnzipFolderSpec();	// get folder for unzipped version
	$ok = chdir($fpUnzip);
	if (!$ok) {
	    throw new exception("Ferreteria configuration error: Could not chdir to folder [$fpUnzip].");
	}
	$sCmd = 'unzip "'.$fs.'"';
	exec($sCmd,$arCmdOut);
	$this->AddCommandResult(array('source file'=>$fs));
	$this->AddCommandResult(array('target folder'=>$fpUnzip));
	$this->AddCommandResult(array('unzip'=>$arCmdOut));
    }
    protected function DoRezip($fs) {
	if (file_exists($fs)) {
	    fcFileSystem::MakeBackup($fs,FALSE);	// move file to backup name
	}
	
	// zip content.xml with copy of the rest of the file
	$sCmd = "zip -0 -X '$fs' mimetype";
	exec($sCmd,$arCmdOut);
	$this->AddCommandResult(array('create'=>$arCmdOut));
	$sCmd = "zip -r '$fs' * -x mimetype";
	exec($sCmd,$arCmdOut);
	$this->AddCommandResult(array('re-zip'=>$arCmdOut));
    }

    // -- FILE I/O -- //
    // ++ FORMAT SUPPORT ++ //
    
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

    // -- FORMAT SUPPORT -- //
}