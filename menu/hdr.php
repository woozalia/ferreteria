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
  TODO: Formatting should be done with CSS.
*/
class fcHeaderMenuGroup extends fcMenuFolder {
    protected function RenderNodesBlock() {
	return $this->RenderNodes();
    }
    protected function RenderSelf() {
	return ' &bull; '.$this->RenderContent().':';
    }
}
/*::::
  PURPOSE: menu link that can be one of several mutually-exclusive options
    Designed for headers, but theoretically should work in vertical/hierarchical menus as well.
*/
class fcMenuOptionLink extends fcToggleLink {

    // ++ SETUP ++ //

    public function __construct($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL) {
	$this->SetPopup($sPopup);
	$this->SetKeyValue($sKeyValue);
	$this->SetKeyName($sGroupKey);
	$this->SetOffDisplay($sDispOff);
	$this->SetOnDisplay($sDispOn);
	$this->SetupDefaults();			// ALWAYS CALL THIS from constructor
	//$this->SetIsAuthorized(TRUE);		// header menus assume access
    }

/* uncomment to see debugging information
    public function Render() {
        $sKeyName = $this->GetKeyName();
        $sKeyValue = $this->GetKeyValue();
        $oPathIn = $this->GetKioskObject()->GetInputObject();

	echo 'RENDERING ['
	  .$sKeyName
	  .']=['
	  .$sKeyValue
	  .'] IS SELECTED=['
	  .$this->GetIsSelected()
	  .'] PATH VALUE=['
	  .$oPathIn->GetBool($sKeyName)
	  .'] SHOULD DISPLAY=['
	  .$this->GetShouldDisplay()
	  //.'] IS AUTH=['
	  //.$this->GetIsAuthorized()
	  .'] REQUIRED PERMIT NULL=['.
	  is_null($this->GetRequiredPrivilege())
	  .']<br>'
	  ;
	return parent::Render();
    }
/**/

      //++values++//

    // NEW
    protected function SetOffDisplay($s) {
	$this->SetValue($s);
    }
    // NEW
    protected function GetOffDisplay() {
	$s = $this->GetValue();
	if (is_null($s)) {
	    $s = $this->GetKeyValue();	// default
	    if ($s === TRUE) {
		// TRUE = special value meaning that the group key should be used without a value, so also display it
		$s = $this->GetKeyName();
	    }
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

}
/*::::
  PURPOSE: Equivalent to a radio-button group
*/
class fcHeaderChoiceGroup extends fcHeaderMenuGroup {

    public function __construct($sKeyName,$sText=NULL,$sPopup=NULL) {
	parent::__construct($sText,$sPopup);
	$this->SetKeyName($sKeyName);
    }
    private $sKeyName;
    protected function SetKeyName($s) {
	$this->sKeyName = $s;
    }
    protected function GetKeyName() {
	return $this->sKeyName;
    }
    public function SetChoice(fcHeaderChoice $oChoice) {
	$sKeyName = $this->GetKeyName();
	$oChoice->SetKeyName($sKeyName);
	$sKeyValue = $oChoice->GetKeyValue();
	$this->SetNode($oChoice,$sKeyValue);
    }
    public function GetChoiceValue() {
	$sKeyName = $this->GetKeyName();
	$oPathIn = fcApp::Me()->GetKioskObject()->GetInputObject();
	$sInValue = $oPathIn->GetString($sKeyName);
	return $sInValue;
    }
    public function GetSelfArray() {
	return array($this->GetKeyName() => $this->GetChoiceValue());
    }
}
/*::::
  PURPOSE: Like a fcMenuOptionLink, but requires a parent fcHeaderChoiceGroup
    This way you can get a single value specifying which choice was clicked.
*/
class fcHeaderChoice extends fcMenuOptionLink {

    // ++ SETUP ++ //

    public function __construct($sKeyValue,$sPopup=NULL,$sDispOff=NULL,$sDispOn=NULL) {
	$this->SetPopup($sPopup);
	$this->SetKeyValue($sKeyValue);
	$this->SetOffDisplay($sDispOff);
	$this->SetOnDisplay($sDispOn);
	$this->SetupDefaults();			// ALWAYS CALL THIS from constructor
    }
    // PUBLICIZE so group object can call it
    public function SetKeyName($s) {
	parent::SetKeyName($s);
    }
    // PUBLICIZE so group object can call it
    public function GetKeyValue() {
	return parent::GetKeyValue();
    }
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
