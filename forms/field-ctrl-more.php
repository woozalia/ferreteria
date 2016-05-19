<?php
/*
  PURPOSE: Additional controls that aren't used as much
  HISTORY:
    2016-04-07 created for VbzCart input-mode selector buttons
*/

// PURPOSE: helper-component class for fcFormControl_HTML_InstaMode
class fcInstaModeButton {

    // ++ SETUP ++ //

    public function __construct($sName,$sText) {
	$this->Name($sName);
	$this->Text($sText);
    }
    private $sName;
    public function Name($s=NULL) {
	if (!is_null($s)) {
	    $this->sName = $s;
	}
	return $this->sName;
    }
    private $sText;
    public function Text($s=NULL) {
	if (!is_null($s)) {
	    $this->sText = $s;
	}
	return $this->sText;
    }
    
    // -- SETUP -- //
    // ++ OUTPUT ++ //
    
    public function Render() {
	$sName = $this->Name();
	$sText = $this->Text();
	return "<input type=submit name=$sName value='$sText'>";
    }
}

/*----
  PURPOSE: Buttons which instantly change some kind of mode (page is reloaded using new mode)
  DETAILS: HTML submit buttons are stupid. The "value" is also what the button displays;
    there's no way to set them separately -- so we have to use it as an index if we want
    to know which button was pushed without giving them different names (which would require
    going through the whole $_POST checking for any of the possible names).
    
    So when the button array is defined, we go through it and create an index of the texts.
*/
class fcFormControl_HTML_InstaMode extends fcFormControl {

    // ++ SETUP ++ //

    /*----
      INPUT:
	arModes = array of mode definitions
	  array[value] = button object
    */
    public function __construct(fcFormField $oField, array $arButtons) {
	parent::__construct($oField);
	$this->ButtonArray($arButtons);
    }
    private $arBtns;
    protected function ButtonArray(array $arButtons=NULL) {
	if (!is_null($arButtons)) {
	    $this->arBtns = $arButtons;
	    foreach ($arButtons as $key => $obj) {
		$sBtnPost = $obj->Text();	// get the button text which is also the POST key
		$this->SetPostLookup($sBtnPost,$key);	// index the POST key
	    }
	}
	return $this->arBtns;
    }
    private $arPosts;
    protected function SetPostLookup($sPost,$sVal) {
	$this->arPosts[$sPost] = $sVal;
    }
    protected function GetValue_fromPost($sPost) {
	return $this->arPosts[$sPost];
    }
    
    // -- SETUP -- //
    // ++ OVERRIDES ++ //
    
    protected function Setup() {
	// config defaults
	$this->Editable(TRUE);	// irrelevant, actually
	$this->Required(FALSE);
    }
    
    // -- OVERRIDES -- //
    // ++ CEMENTING ++ //
    
    /*----
      ACTION: receive this control's data from form submission
    */
    public function ReceiveForm(array $arData) {
	$sName = $this->NameString();
	$sMsg = NULL;
	if (array_key_exists($sName,$arData)) {
	    $sPost = $arData[$sName];
	    $sVal = $this->GetValue_fromPost($sPost);
	    $this->NativeObject()->SetValue($sVal);
	    $arOut['absent'] = FALSE;
	    $arOut['blank'] = FALSE;
	} else {
	    $arOut['absent'] = NULL;
	    $arOut['blank'] = NULL;
	}
    }
    /*----
      ACTION: render code to display the control
      RULE: value must always be set
      NOTE: This is tricky. The field's value needs to represent the *current* mode,
	but it needs to render the button(s) that will switch to the *desired* mode,
	i.e. all buttons *except* the one for the current mode. Since the current
	usage case (as of 2016-04-10) only has two buttons, we don't yet have to worry
	about how to format multiple buttons (e.g. do we want "or" between them? do we
	want a template?) so for now we'll just render the one other button that isn't
	the current mode. (Codewise, we iterate through the list and render the first one
	that isn't.)
    */
    public function Render($doEdit) {
	$ar = $this->ButtonArray();
	$sMode = $this->GetValueNative();
	foreach ($ar as $key => $btn) {
	    if ($key != $sMode) {
		return $btn->Render();
	    }
	}
	return NULL;	// in case no buttons are defined, I guess
    }

}