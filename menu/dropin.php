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
    use ftRequiresPermit;

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
	$this->SetRequiredPrivilege(KS_PERM_FE_ROOT);
	$this->SetKeyName(KS_PATHARG_NAME_TABLE);
	$this->SetIndexName(KS_PATHARG_NAME_INDEX);	// same
    }
    /*----
      NOTE: This has to intercept Render() rather than RenderContent() because fcNavBase::RenderContent()
	is only called if GetShouldDisplay() is true, and we might want to render the Page content to which
	this menu item is attached even if the menu item itself should not be rendered.
    */
    public function Render() {
	if ($this->GetIsSelected() && $this->GetIsAuthorized()) {
	    $this->PageRender();
	}
	return parent::Render();
    }
    protected function OnEventAfter($nEvent){
	if ($this->GetIsSelected() && $this->GetIsAuthorized()) {
	    $this->PageEvent($nEvent);
	}
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
    
      //--internal--//
    // -- SETUP FIELDS -- //
    // ++ OBJECTS ++ //

    /*----
      NOTE: (2017-03-27) Having this returned in two stages is a kluge for requiring both fiLinkable and fiEventAware.
	There's got to be a better way to do this, short of creating an fiLinkableEventConsumer interface and maintaining
	it manually. Maybe the two interfaces don't actually need to be separate? (04-13) Or maybe the _raw one can
	be more general, somehow.
      HISTORY:
	2017-04-13 Added caching, because otherwise we end up doing rendering on a different object than
	  the one we passed the build and figure events. Aside from being inefficient, this means that
	  any calculations or building are actually lost.
    */
    private $arObj=array();
    protected function MakeActionObject_raw() : fiLinkable {
	$oKiosk = $this->GetKioskObject();
	$sClass = $this->GetActionClass();
	if (empty($sClass)) {
	    throw new exception('Ferreteria Dropin definition error: action class not defined!');
	}

	$id = $oKiosk->GetInputObject()->GetString($this->GetIndexName());
	//$id = $oKiosk->GetInputRecordKey();	// TODO: this belongs in the dropin link, not the kiosk
	
	$sCache = $sClass.'.'.$id;
	if (array_key_exists($sCache,$this->arObj)) {
	    $o = $this->arObj[$sCache];
	} else {
	    $o = fcApp::Me()->GetDatabase()->MakeTableWrapper($sClass,$id);
	    $this->arObj[$sCache] = $o;
	}
	return $o;
    }
    protected function MakeActionObject() : fiEventAware {
	$o = $this->MakeActionObject_raw();
	fcApp::Me()->GetKioskObject()->SetPagePath($o->SelfURL());
	return $o;
    }

    // -- OBJECTS -- //
    // ++ CALCULATIONS ++ //
    
    // OVERRIDE
    protected function GetBasePath_toUse() {
	return fcApp::Me()->GetKioskObject()->GetBasePath();
    }
    // OVERRIDE
    protected function GetShouldDisplay() {
	return $this->GetIsAuthorized();
    }

    // -- CALCULATIONS -- //
    // ++ EVENTS ++ //
    
    protected function PageEvent($nEvent) {
	$o = $this->MakeActionObject();
	fcApp::Me()->GetKioskObject()->SetPagePath($o->SelfURL());
	$o->DoEvent($nEvent);
    }
    protected function PageRender() {
	$oPage = fcApp::Me()->GetPageObject();
	if ($this->HasPageTitle()) {
	    $oPage->SetPageTitle($this->GetPageTitle());
	}
	$o = $this->MakeActionObject();
	fcApp::Me()->AddContentString($o->Render());
    }
    
    // ++ ACTIONS ++ //
    
    /*----
      ACTION: Do whatever needs to be done when this menu entry is selected
      OVERRIDE
    */
    /* 2017-03-26 replacing this with Event and Render calls
    protected function DoSelect() {
	$oPage = fcApp::Me()->GetPageObject();
	if ($this->HasPageTitle()) {
	    $oPage->SetPageTitle($this->GetPageTitle());
	}
	$o = $this->MakeActionObject();
	$this->DoObjectAction($o);
    } */
    /*----
      NOTE: This could presumably be overridden to invoke a different method.
      TODO: Require an interface for $o
    */
    /* 2017-03-26 replacing this with Event and Render calls
    protected function DoObjectAction($o) {
	$oApp = fcApp::Me();
	if (method_exists($o,'SelfURL')) {
	    $oApp->GetKioskObject()->SetPagePath($o->SelfURL());	// let's just assume $o is a LinkableObject
	    $oApp->AddContentString($o->MenuExec());
	} else {
	    throw new exception('Ferreteria usage error: class "'.get_class($o).'" needs to use ftLinkableTable.');
	}
    } */

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
