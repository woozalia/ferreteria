<?php
/*
  PURPOSE: Standard classes for apps that support logins
    ...which is probably going to be most of them
  HISTORY:
    2017-01-28 adapting from classes written for VbzCart
*/
class fcContentHeader_login extends fcContentHeader {
    // ++ OUTPUT ++ //
    
      //++overrides++//
      
    // NOTE: It turns out that the outer <ul> element is what does the float-left and border. No need for an enclosing table.
    protected function RenderBefore() {
	return NULL;
    }
    protected function RenderContent() {
	$htTitle = $this->GetTitleString();
	$htWidg = $this->RenderWidgets();
	return
	  "\n<div class=header-block>"
	  ."\n<span class=page-title>$htTitle</span>"
	  ."\n<span class=header-widgets>$htWidg</span>"
	  ."\n</div>"
	  ;
    }
    protected function RenderAfter() {
	return NULL;
    }

      //--overrides--//
      //++new++//
      
    // PURPOSE: render other stuff that goes in the title bar (aside from the title)
    protected function RenderWidgets() {
	$o = fcApp::Me()->GetPageObject()->GetElement_LoginWidget();	// for now; render actual menu too, later
	$htLogin = $o->Render_StatusControl();
	$htMenu = $this->RenderMenu();
	return
	  "\n<span class=header-login>$htLogin</span>"
	  ."\n<span class=header-menu>$htMenu</span>"
	  ;
    }
    protected function RenderMenu() {
	$oMenu = $this->GetElement_Menu();
	if (is_null($oMenu)) {
	    return NULL;	// not all pages have a header menu
	} else {
	    return $oMenu->Render();
	}
    }
      
      //--new--//

    // -- OUTPUT -- //
}
abstract class fcTag_body_login extends fcTag_body_standard {
    use ftLoginContainer_standard;

    /*----
      PUBLIC because page object needs to access login widget's status thingy
      NEW
    */
    public function GetElement_LoginWidget() {
	return $this->MakeElement_LoginWidget();	// in ftLoginContainer_standard
    }
}
// ABSTRACT: n/i = GetStyleFolder, GetElement_PageHeader, GetElement_HeaderMenu, + 2 more
abstract class fcPage_login extends fcPage_standard {

    /*----
      PUBLIC because page header needs to access login widget's status thingy
      NEW
    */
    public function GetElement_LoginWidget() {
	return $this->GetTagNode_body()->GetElement_LoginWidget();
    }
    /*----
      NOTE: These messages *conceivably* could be of use in a non-login app,
	but that seems like a thing to do when the need actually arises.
    */
    public function AddErrorMessage($s) {
	$this->GetElement_PageContent()->AddErrorMessage($s);
    }
    public function AddWarningMessage($s) {
	$this->GetElement_PageContent()->AddWarningMessage($s);
    }
    public function AddSuccessMessage($s) {
	$this->GetElement_PageContent()->AddSuccessMessage($s);
    }
    
}