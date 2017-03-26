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
  HISTORY:
    2017-02-08 If a DropinLink is created without defining its Action Class, that creates problems
      which were difficult to trace -- so let's just make that part of the constructor, shall we?
      Also, moving Set/Get Action Class methods here from parent. All the other Action stuff seems
      to be added here.
*/
class fcDropinLink extends fcDynamicLink {

    // ++ SETUP ++ //
    
    // OVERRIDE
    public function __construct(string $sKeyValue,string $sActionClass,string $sText=NULL,string $sPopup=NULL) {
	parent::__construct($sKeyValue,$sText,$sPopup);
	$this->SetActionClass($sActionClass);
    }
    // NEW
    private $sActionClass;
    public function SetActionClass($sClass) {
	$this->sActionClass = $sClass;
    }
    protected function GetActionClass() {
	return $this->sActionClass;
    }

    // ++ EVENTS ++ //

    // OVERRIDE
    protected function SetupDefaults() {
	$this->SetRequiredPrivilege('*');
	// if '*' ever means anything, that will be "root".
	// For now, it implies "nobody has permission", because there is no permission named '*'
	// ...although the site admin could just define one.
	$this->SetKeyName(KS_PATHARG_NAME_TABLE);
	$this->SetIndexName(KS_PATHARG_NAME_INDEX);	// same
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
	parent::OnRunCalculations();
	if ($this->GetIsSelected() && $this->GetIsAuthorized()) {
	    $this->DoSelect();
	}
    }

    // -- EVENTS -- //
    // ++ OBJECTS ++ //

    // NOTE: This could presumably be overridden to invoke things other than tables.
    protected function MakeActionObject() {
	$oKiosk = $this->GetKioskObject();
	$sClass = $this->GetActionClass();
	if (empty($sClass)) {
	    throw new exception('Ferreteria Dropin definition error: action class not defined!');
	}

	$id = $oKiosk->GetInputObject()->GetString($this->GetIndexName());
	//$id = $oKiosk->GetInputRecordKey();	// TODO: this belongs in the dropin link, not the kiosk
	return fcApp::Me()->GetDatabase()->MakeTableWrapper($sClass,$id);
    }

    // -- OBJECTS -- //
    // ++ CALCULATIONS ++ //
    
    // OVERRIDE
    protected function GetBasePath_toUse() {
	return fcApp::Me()->GetKioskObject()->GetBasePath();
    }

    // -- CALCULATIONS -- //
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
    /*----
      NOTE: This could presumably be overridden to invoke a different method.
      TODO: Require an interface for $o
    */
    protected function DoObjectAction($o) {
	$oApp = fcApp::Me();
	if (method_exists($o,'SelfURL')) {
	    $oApp->GetKioskObject()->SetPagePath($o->SelfURL());	// let's just assume $o is a LinkableObject
	    $oApp->AddContentString($o->MenuExec());
	} else {
	    throw new exception('Ferreteria usage error: class "'.get_class($o).'" needs to use ftLinkableTable.');
	}
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

    public function __construct(string $sKeyValue,string $sActionClass) {
	parent::__construct($sKeyValue,$sActionClass);
    }
    protected function GetShouldDisplay() {
	return FALSE;
    }

}
