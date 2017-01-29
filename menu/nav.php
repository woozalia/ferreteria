<?php
/*
  PURPOSE: simple navigation items
*/
/*::::
  PURPOSE: base class for links and folders
  ADDS event-passing and consumption to PageElements
  ABSTRACT: n/i = Render(), new = RenderContent()
*/
abstract class fcNavBase extends fcPageElement {
    use ftExecutableTree;
    
    // ++ SETUP ++ //
    
    public function __construct($sText=NULL,$sPopup=NULL) {
	$this->SetLinkText($sText);
	$this->SetPopup($sPopup);
	$this->SetupDefaults();		// ALWAYS CALL THIS from constructor
    }

    // -- SETUP -- //
    // ++ EVENTS ++ //

    protected function SetupDefaults() {
	$this->SetVisible(TRUE);	// default
    }
    // DEFAULT: Do nothing; items are added from outside.
    protected function OnCreateElements(){}
    // DEFAULT: Nothing to do.
    protected function OnRunCalculations(){}

    // -- EVENTS -- //
    // ++ OUTPUT ++ //
    
    public function Render(){
	if ($this->GetShouldDisplay()) {
	    return $this->RenderFull();
	} else {
	    return NULL;
	}
    }
    /*----
      CEMENT
      PURPOSE: Renders everything that should show if the user has permission to use this node
    */
    protected function RenderFull() {
	return $this->RenderSelf();
    }
    protected function RenderSelf() {
	$sTag = get_class($this).'-'.$this->GetName();
	return "\n<li id='$sTag'>".$this->RenderContent()."</li>";
    }
    abstract protected function RenderContent();

    // -- OUTPUT -- //
    // ++ PROPERTY VALUES ++ //
    
    protected function SetLinkText($s) {
	$this->SetValue($s);
    }
    protected function GetLinkText() {
	return $this->GetValue();
    }
    private $isVisible;
    protected function SetVisible($b) {
	$this->isVisible = $b;
    }
    protected function GetVisible() {
	return $this->isVisible;
    }
    private $sPopup;
    protected function SetPopup($s) {
	$this->sPopup = $s;
    }
    protected function GetPopup() {
	return $this->sPopup;
    }
    
    // -- PROPERTY VALUES -- //
    // ++ PROPERTY CALCULATIONS ++ //
    
    protected function GetShouldDisplay() {
	return $this->GetVisible();
    }

    // -- PROPERTY CALCULATIONS -- //
}

/*::::
  PURPOSE: a navigation link
    * permission-aware (from base)
    * defaults to having permission (from base)
    * does not define URL
  NOTE: It's tempting to make this a fcHypertextTag because that adds executableTree and renderableTree,
    but it also assumes an opening and closing tag.
*/
abstract class fcNavLink extends fcNavBase {

    // ++ PROPERTY VALUES ++ //

    abstract protected function GetLinkURL();
    protected function GetStyleName() {
	return 'menu-link';
    }

    // -- SETUP -- //
    // ++ OUTPUT ++ //
    
    protected function RenderContent() {
	return $this->RenderLink();
    }
    /*----
      PURPOSE: renders just the link, no before/after decoration
      TAGS: NEW
    */
    protected function RenderLink() {
	$sLinkURL = $this->GetLinkURL();
	$htLinkText = htmlspecialchars($this->GetLinkText(),ENT_QUOTES);
	$htLinkPopup = htmlspecialchars($this->GetPopup(),ENT_QUOTES);
	$htStyleName = $this->GetStyleName();
	return "<a href='$sLinkURL' title='$htLinkPopup' class='$htStyleName'>$htLinkText</a>";
    }

    // -- OUTPUT -- //
}
// ADDS: fixed URL given at construct time
class fcNavLinkFixed extends fcNavLink {

    // ++ SETUP ++ //

    public function __construct($urlLink, $sText=NULL,$sPopup=NULL) {
	parent::__construct($sText,$sPopup);
	$this->SetLinkURL($urlLink);
    }

    // -- SETUP -- //
    // ++ PROPERTY VALUES ++ //
    
    private $sURL;
    // NEW
    protected function SetLinkURL($url) {
	$this->sURL = $url;
    }
    // CEMENT
    protected function GetLinkURL() {
	return $this->sURL;
    }
    
    // -- PROPERTY VALUES -- //

}
// ADDS: rendering of sub-nodes
class fcNavFolder extends fcNavBase {

    // ++ CALCULATIONS ++ //
    
    /*----
      NOTE: This assumes that unauthorized subnodes will delete themselves.
	At some point, we'll probably need to get more sophisticated and, instead,
	cycle through all subnodes counting how many are authorized -- otherwise, folders
	will still display if they have hidden items (MenuActions) that are authorized.
    */
    protected function GetShouldDisplay() {
	$sName = $this->GetValue();
	return $this->HasNodes();
    }
    
    // ++ OUTPUT ++ //

    // OVERRIDE
    protected function RenderFull() {
	return $this->RenderSelf()
	  .$this->RenderUnder();
    }
    // NEW
    protected function RenderUnder() {
	if ($this->HasNodes()) {
	    return $this->RenderNodesBlock();
	} else {
	    return NULL;
	}
    }
    // NEW
    protected function RenderNodesBlock() {
	return "\n<ul>"
	  .$this->RenderNodes()
	  ."\n</ul>"
	  ;
    }
    // NEW
    protected function RenderNodes() {
	$out = NULL;
	foreach ($this->GetNodes() as $oNode) {
	    $out .= $oNode->Render();
	}
	return $out;
    }
    // CEMENT
    protected function RenderContent() {
	return $this->GetValue();
    }

    // -- OUTPUT -- //

}
