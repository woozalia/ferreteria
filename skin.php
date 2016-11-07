<?php
/*
  PURPOSE: abstract skin class
  HISTORY:
    2013-10-27 restarted from scratch (implementation in VbzCart was conceptually flawed)
    2013-11-15 added optional iHeight parameter to HLine()
    2013-11-28 major rewrite underway -- skins now do the outputting
  RULES:
    Skin methods should have no business logic; if they need to display calculated values,
      those values should be passed to them. A skin may choose not to display all values,
      so the base method should define arguments for all values that might be used.
    Skin methods do not display anything; they return HTML which the caller can display.
    Skins should be more about layout than content (large wodges of text).
*/

if (!defined('KHT_PAGE_DOCTYPE')) {
    define('KHT_PAGE_DOCTYPE','<!DOCTYPE HTML>');
}

abstract class fcSkin {
    protected $arPieces;	// the pieces
    protected $arPOrder;	// the order in which they appear

    public function __construct() {
	$this->Init();
    }
    /*----
      ACTION: Outputs all the pieces it has been given.
    */
    public function DoPage() {
	foreach ($this->arPOrder as $sName) {
	    $sText = $this->arPieces[$sName];
	    echo $sText;
	}
    }
    public function DumpHTML() {	// for debugging
	foreach ($this->arPOrder as $sName) {
	    $sText = $this->arPieces[$sName];
	    echo "\n<br>===PIECE: [$sName]===<br>\n"
	      .fcString::EncodeForHTML($sText)."\n"
	      ."\n<br>===END $sName===<br>";
	}
    }
    abstract protected function Init();	// name all the pieces here
    abstract public function Build();		// fill up all the pieces
    /*----
      ACTION: add the given text to the piece of the given name
    */
    public function PieceAdd($sName,$sText) {
	if (array_key_exists($sName,$this->arPieces)) {
	    $this->arPieces[$sName] .= $sText;
	} else {
	    throw new exception('There is no piece named "'.$sName.'" in this skin.');
	}
    }

}

abstract class fcSkin_basic extends fcSkin {
    /*----
      PURPOSE: this is where we set up the Pieces
    */
    protected function Init() {
	$this->arPieces = array(
	  'page.hdr'	=> NULL,
	  'page.ftr'	=> NULL,
	  'cont.hdr'	=> NULL,
	  'cont.ftr'	=> NULL,
	  'content'	=> NULL,
	  );
	$this->arPOrder = array('page.hdr','cont.hdr','content','cont.ftr','page.ftr');
    }

    // common/repeated display elements:
    //abstract public function TitleHeader($sTitle);
    abstract public function SectionHeader($sTitle,$htMenu,$sCSSClass);	// called by Page object
    abstract public function HLine($cssClass='hline-section');

    // important status messages (should be eye-catching):
    abstract public function ErrorMessage($sText);
    abstract public function WarningMessage($sText);
    abstract public function SuccessMessage($sText);
}

abstract class fcSkin_standard extends fcSkin_basic {

    // ++ ACTION ++ //

    /*----
      ACTION: Fill in the pieces.
    */
    public function Build() {
	$this->arPieces['page.hdr'] = $this->PageHeader();
	$this->arPieces['page.ftr'] = $this->PageFooter();
    }
    public function Content($sName,$sText) {
	$ht = "\n<!-- BEGIN CONTENT - $sName -->$sText\n<!-- END CONTENT - $sName -->";
	$this->arPieces['content'] = clsArray::Nz($this->arPieces,'content').$ht;
    }

    // -- ACTION -- //
    // ++ FRAGMENT ACCESS METHODS ++ //
    
      //++titles++//
    
    private $htTitle;	// how the page describes itself, displayed within the page - may include HTML
    public function SetPageTitle($htTitle) {
	$this->htTitle = $htTitle;
    }
    /*----
      HISTORY:
	2016-10-23 An undated note says this shouldn't have to be public, so I am optimistically protecting it
	  since I'm already mucking everything else up to hell and gone...
	  Calls to this should probably be replaced by calls to a rendering function (to be written).
    */
    protected function GetPageTitle() {
	return $this->htTitle;
    }
    /*
      RETURNS: title for browser to display if not explicitly set
      NOTES:
      * This implementation returns the page title with HTML tags stripped out.
      * Before overriding it, consider overriding GetBrowserTitle() instead.
    */
    protected function GetBrowserTitle_default() {
	return strip_tags($this->GetPageTitle());
    }
    /*----
      RETURNS: title for browser to display, based on page title
	This lets skin authors have one format for browser title, another for meta-tag, etc.
    */
    private $sBrowserTitle;
    public function GetBrowserTitle() {
	if (empty($this->sBrowserTitle)) {
	    return $this->GetBrowserTitle_default();
	} else {
	    return $this->sBrowserTitle;
	}
    }
    public function SetBrowserTitle($s) {
	$this->sBrowserTitle = $s;
    }
    
      //--titles--//

    /* 2016-09-19 This shouldn't be necessary now that we have separate methods for the browser title.
    // TODO: This should not have to be public.
    public function GetPageTitleText() {
	return strip_tags($this->GetPageTitleHTML());
    } */
    private $sSheet;
    public function Sheet($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sSheet = $sName;
	}
	return $this->sSheet;
    }

    // -- ACCESS METHODS -- //
    // ++ SUBSTANCE ++ //

    protected function PageHeader() {
	$sTitle = $this->GetBrowserTitle();
	$sDocType = KHT_PAGE_DOCTYPE;
	$sCharSet = KS_CHARACTER_ENCODING;
	$out = <<<__END__
$sDocType
<html>
<head>
  <title>$sTitle</title>
  <meta http-equiv="Content-Type" content="text/html; charset=$sCharSet">

__END__;

	if (is_null($this->Sheet())) {
	    throw new exception('Style sheet not set for this page class.');
	}
	$arVars = array('sheet' => $this->Sheet());
	$objStrTplt = new clsStringTemplate_array(NULL,NULL,$arVars);
	$objStrTplt->MarkedValue(KHT_PAGE_STYLE);
	$strContent = KS_SITE_NAME_META.': '.$this->GetPageTitle();
	$out .= "\n".$objStrTplt->Replace()
	  ."\n  <meta name=description content=\"$strContent\" />"
	  ."\n  <meta charset=\"utf-8\" />"
	  ."\n</head>\n".KHT_PAGE_BODY_TAG;

	return $out;
    }
    protected function PageFooter() {
	return "\n<!-- PageFooter in ".__CLASS__." -->\n</body>\n</html>";
    }

    // -- SUBSTANCE -- //
}