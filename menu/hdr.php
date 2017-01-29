<?php
/*
  PURPOSE: menu-type stuff intended to be displayed horizontally
    designed for headers (page title, section titles)
  HISTORY:
    2017-01-08 rewriting from menu-action.php
    2017-01-09 retyped from screenshot when Kate crashed
*/
/*::::
  PURPOSE: Renders a menu in a manner appropriate for headers
    Defaults to [link][link] ...
*/
class fcHeaderMenu extends fcMenuFolder {
    // ++ OUTPUT ++ //
    
    /*----
      OVERRIDE
      NOTE: If we want this output to be bracketed with <li></li>:
	* change method name to RenderContent()
	* call GetValue() instead of RenderContent()
    */
    protected function RenderSelf() {
	//$sText = $this->RenderContent();	// for now, the menu itself has no content
	return NULL;
    }
    // OVERRIDE
    protected function RenderNodesBlock() {
	return "\n<span class=menu-text>"
	  .$this->RenderNodes()
	  ."\n</span>"
	  ;
    }
}
/*::::
  PURPOSE: For setting off a group of menu items together, with a label
  TODO:
    This will probably need to be rendered with a colon added to the text, or something.
    Formatting should be done with CSS.
*/
class fcHeaderMenuGroup extends fcMenuFolder {
    protected function RenderNodesBlock() {
	return $this->RenderNodes();
    }
    protected function RenderSelf() {
	return ' &#149; '.$this->RenderContent().':';
    }
}
/*::::
  PURPOSE: menu link that can be one of several mutually-exclusive options
    Designed for headers, but theoretically should work in vertical/hierarchical menus as well.
*/
class fcMenuOptionLink extends fcDynamicLink {

    // ++ SETUP ++ //

    public function __construct($sKeyValue,$sGroupKey=NULL,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL) {
	$this->SetPopup($sPopup);
	$this->SetKeyValue($sKeyValue);
	$this->SetKeyName($sGroupKey);
	$this->SetOffDisplay($sDispOff);
	$this->SetOnDisplay($sDispOn);
	$this->SetupDefaults();			// ALWAYS CALL THIS from constructor
    }

      //++values++//

    // NEW
    protected function GetGroupKey() {
	return $this->sGroup;
    }
    // NEW
    protected function SetOffDisplay($s) {
	$this->SetValue($s);
    }
    // NEW
    protected function GetOffDisplay() {
	$s = $this->GetValue();
	if (is_null($s)) {
	    $s = $this->GetKeyValue();	// default
	}
	return $s;
    }
    private $sDispOn;
    // NEW
    protected function SetOnDisplay($s) {
	$this->sDispOn = $s;
    }
    // NEW
    protected function GetOnDisplay() {
	$s = $this->sDispOn;
	if (is_null($s)) {
	    $s = $this->GetOffDisplay();	// default
	}
	return $s;
    }
    protected function HasOnDisplay() {
	return !is_null($this->sDispOn);
    }
    protected function GetDisplayPrefix() {
	if ($this->HasOnDisplay()) {
	    return '';
	} else {
	    return $this->GetIsSelected()?'-':'+';
	}
    }

      //--values--//
      //++calculations++//

    // NEW
    protected function GetLinkText() {
	if ($this->GetIsSelected()) {
	    $s = $this->GetOnDisplay();
	} else {
	    $s = $this->GetOffDisplay();
	}
	return $this->GetDisplayPrefix().$s;
    }
    
    /*
    // NEW
    // could be called GetInnerContentHTML()
    protected function GetInnerContentHTML() {
	$sCSS = $this->GetIsSelected()?'menu-link-active':'menu-link-inactive';
	$sText = $this->GetLinkText();
	return "<span class=$sCSS>$sText</span>";
    }
    // NEW
    // could be called GetOuterContentHTML()
    protected function FigureLinkHTML($url,$sText,$sPopup) {
	$htPopup = is_null($sPopup)?'':(' title="'.htmlspecialchars($sPopup).'"');
	return "[<a class=ctrl-link-action href='$url'$sPopup>$sText</a>]";
    }
    protected function FigureOuterContentHTML($sContent) {
	return "[$sContent]";
    }
    */
    // OVERRIDE
    protected function RenderSelf() {
	$sCSS = $this->GetIsSelected()?'menu-link-active':'menu-link-inactive';
	$sContent = $this->RenderContent();
	return "\n<span class=$sCSS>[$sContent]</span>";
    }
    protected function GetStyleName() {
	return 'ctrl-link-action';
    }

      //--calculations--//
    // -- SETUP -- //
    // ++ OUTPUT ++ //

    /* this appears to be the wrong level at which to redefine things
    public function Render() {
	$url = $this->GetLinkURL();
	$sText = $this->GetInnerContentHTML();
	$sPopup = $this->GetPopup();
	$sLink = $this->FigureLinkHTML($url,$sText,$sPopup);
	return $this->FigureOuterContentHTML($sLink);
    } */

    // -- OUTPUT -- //

}
class fcSectionHeader extends fcPageElement {
    use ftRenderableTree;
    
    // ++ SETUP ++ //
    
    public function __construct($sTitle,fcHeaderMenu $oMenu=NULL) {
	$this->SetValue($sTitle);
	if (!is_null($oMenu)) {
	    $this->SetNode($oMenu,'menu');
	}
    }

    // STUB CEMENT
    public function DoEvent($nEvent) {}
    
    // NEW
    protected function GetStyleClass() {
	return 'section-header';
    }
    // CEMENT
    public function Render() {
	$sClass = $this->GetStyleClass();
	return "\n<span class='$sClass'>"
	  .$this->RenderTitle()
	  .$this->RenderMenu()
	  ."</span>"
	  ;
    }
    // NEW
    protected function RenderTitle() {
	return 
	  '<span class=section-title>'
	  .$this->GetValue()
	  .'</span>'
	  ;
    }
    // NEW
    protected function RenderMenu() {
	return $this->RenderNodes();
    }
}