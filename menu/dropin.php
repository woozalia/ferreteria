<?php
/*
  PURPOSE: menu-item types for use in dropins
  HISTORY:
    2017-01-21 started
*/

/*::::
  NOTE: Could also be called fcTableLink
  RULES:
    * When a link determines that it is being "used", DoSelect() is called (abstract here).
    * DoSelect() instantiates the ActionClass as a table and a predefined method is called.
    * This method is hard-coded here as MenuExec(), but that can be overridden by descendant classes.
*/
class fcDropinLink extends fcDynamicLink {

    // ++ EVENTS ++ //

    // OVERRIDE
    protected function SetupDefaults() {
	$this->SetRequiredPrivilege('*');
	// if '*' ever means anything, that will be "root".
	// For now, it implies "nobody has permission", because there is no permission named '*'.
	$this->SetKeyName('page');	// this could be a site option
	$this->SetIndexName('id');	// same
    }

    // -- EVENTS -- //
    // ++ SETUP FIELDS ++ //
      //++internal++//

    /*----
      NEW
      TODO or at least to consider: maybe value and index should be part of the same value
	e.g. page:<tablename>:<id>
	This will require some work on the URL parsing methods, so LATER.
    */
    private $sIndexName;
    protected function SetIndexName($s) {
	$this->sIndexName = $s;
    }
    protected function GetIndexName() {
	return $this->sIndexName;
    }
    /* 2017-01-23 Actually, parent version should work fine now
    protected function GetIsSelected() {
	$sKeyValue = $this->GetKeyValue();
	$oKiosk = $this->GetKioskObject();
	$isSel = $oKiosk->GetInputTableKey() == $sKeyValue;
	return $isSel;
    } */
    
      //--internal--//
    // -- SETUP FIELDS -- //
    // ++ EVENTS ++ //

    protected function OnRunCalculations() {
	if ($this->GetIsSelected() && $this->GetIsAuthorized()) {
	    $this->DoSelect();
	}
    }

    // -- EVENTS -- //
    // ++ CALCULATIONS ++ //
    
    /*----
      OVERRIDE
      ADDS: Does not modify URL when entry is selected
    */
    protected function GetLinkArray_dynamic() {
	return $this->GetLinkArray_base();
    }

    // -- CALCULATIONS -- //
    // ++ OBJECTS ++ //

    // NOTE: This could presumably be overridden to invoke things other than tables.
    protected function MakeActionObject() {
	$oKiosk = $this->GetKioskObject();
	$sClass = $this->GetActionClass();

	$id = $oKiosk->GetInputObject()->GetString($this->GetIndexName());
	//$id = $oKiosk->GetInputRecordKey();	// TODO: this belongs in the dropin link, not the kiosk
	return fcApp::Me()->GetDatabase()->MakeTableWrapper($sClass,$id);
    }

    // -- OBJECTS -- //
    // ++ ACTIONS ++ //
    
    /*----
      ACTION: Do whatever needs to be done when this menu entry is selected
      OVERRIDE
    */
    protected function DoSelect() {
	$oPage = fcApp::Me()->GetPageObject();
	if ($this->HasPageTitle()) {
	    $oPage->SetPageTitle($this->GetPageTitle());
	}
	$o = $this->MakeActionObject();
	$this->DoObjectAction($o);
    }
    // NOTE: This could presumably be overridden to invoke a different method.
    protected function DoObjectAction($o) {
	// We probably just need to have an App method for adding content.
	fcApp::Me()->AddContentString($o->MenuExec());
    }

    // -- ACTIONS -- //
    // ++ OUTPUT ++ //
    
    // OVERRIDE
    protected function RenderSelf() {
	$sCSS = $this->GetIsSelected()?'state-on':'state-normal';
	$sContent = $this->RenderContent();
	return "\n<li class='$sCSS'>$sContent</li>";
    }

    // -- OUTPUT -- //
}
/*::::
  ADDS: node is invisible; *only* responds to usage
    Keeps text and popup features from parent, but does not use them.
*/
class fcDropinAction extends fcDropinLink {

    protected function GetShouldDisplay() {
	return FALSE;
    }

}
