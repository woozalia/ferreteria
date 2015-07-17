<?php
/*
  PURPOSE: classes for rendering page-sections with optional menus in MediaWiki
    This is an attempt to replace page-section.php with a simpler, more intuitive API.
  RULES:
    * The Section will keep track of the base URL; Widgets just render what gets added to that.
  HISTORY:
    2015-05-08 started for FinanceFerret
*/

class fcSectionHeader_MW {
    static public function Render(array $arMenu, $urlPage, $sTitle, $nLevel=2) {
	$ftMenu = NULL;
	foreach ($arMenu as $oLink) {
	    $oLink->BaseURL($urlPage);
	    $ftMenu .= $oLink->Render();
	}
	
	$out = "\n<h$nLevel><span class='mw-headline'>$sTitle</span>"
	  ."<span class='mw-editsection'>$ftMenu</span>"
	  ."</h$nLevel>";
	return $out;
    }
}

/*%%%%
  PURPOSE: abstract handler for stuff that might go in the action/link area of a section header
*/
abstract class fcSectionWidget {
    private $urlBase;	// base URL to use as prefix for all links
    private $sKey;	// text for URL
    private $sShow;	// text to show
    private $sLong;	// longer text (if any)
    
    // ++ SETUP ++ //

    public function __construct($sKey,$sShow,$sLong=NULL) {
	$this->KeyText($sKey);
	$this->ShowText($sShow);
	$this->LongText($sLong);
    }

    // -- SETUP -- //
    // ++ CONFIGURATION ++ //

    public function BaseURL($url=NULL) {
	if (!is_null($url)) {
	  $this->urlBase = $url;
	}
	return $this->urlBase;
    }
    protected function KeyText($sText=NULL) {
	if (!is_null($sText)) {
	    $this->sKey = $sText;
	}
	return $this->sKey;
    }
    protected function ShowText($sText=NULL) {
	if (!is_null($sText)) {
	    $this->sShow = $sText;
	}
	return $this->sShow;
    }
    /*----
      ACTION: Sets or returns longer text to show where appropriate (e.g. in hover-popup).
    */
    protected function LongText($sText=NULL) {
	if (!is_null($sText)) {
	    $this->sLong = $sText;
	}
	return $this->sLong;
    }
    
    // -- CONFIGURATION -- //
    // ++ ABSTRACT ++ //
    
    /*----
      ACTION: render the widget
    */
    abstract public function Render();

    // -- ABSTRACT -- //
}

class fcSectionMenuItem extends fcSectionWidget {

    // ++ CEMENT ++ //

    /*----
      ACTION: render the link
    */
    public function Render() {
	$htTitle = is_null($this->LongText())?'':(' title="'.htmlspecialchars($this->LongText()).'"');
	$sShow = $this->ShowText();
	$urlBase = $this->BaseURL();
	$urlAdd = $this->KeyText();
	$url = "$urlBase$urlAdd";
	$out = 
	  '<span class="mw-editsection">'
	  .'<span class="mw-editsection-bracket">[</span>'
	  ."<a href='$url'$htTitle>$sShow</a>"
	  .'<span class="mw-editsection-bracket">]</span>'
	  .'</span>';
	return $out;
    }

    // -- CEMENT -- //
}