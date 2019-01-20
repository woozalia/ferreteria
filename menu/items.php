<?php
/*
  PURPOSE: classes for building navigational display elements (aka menus) on a page
  TERMINOLOGY:
    [self [content]]
      * "content" is what changes from item to item; it may include formatting with variable elements
      * "self" may include additional formatting that surrounds all of "content" and changes only
	with respect to the item's state
  HISTORY:
    2013-10-08 clsNavBar, clsNavItem, clsNavLink
    2013-10-10 clsNavbar_flat, clsNavbar_tree, clsNavItemText
    2013-11-29 renamed from menu.php to navbar.php
      because proper menus look like they'll need another set of classes
    2016-11-29 Adapting for new Page system
    2016-12-05 Adapting some things from page/shop.php
      ftLinkableNode, fcMenuItem_link->fcNavItem_link, fcMenuItem_standard, fcMenuFolder, fcMenuFolder_standard
    2016-12-08 some renaming
    2016-12-09 merging page.navbar.php into here (menu-base.php)
      fcNavItem, fcPageBlock
    2016-12-27..28 reworking class structure
      removed ftLinkableNode
    2017-01-10 extracted kiosk classes to kiosk.php for easier reference; renamed this from menu-base.php -> items.php
*/
trait ftRequiresPermit {

    // ++ EVENTS ++ //

    protected function OnRunCalculations(){
	parent::OnRunCalculations();
	$this->SetIsAuthorized($this->FigureIfAuthorized());
    }

    // -- EVENTS -- //
    // ++ INTERNALS ++ //

    private $isAuth;
    protected function SetIsAuthorized($b) {
	$this->isAuth = $b;
    }
    protected function GetIsAuthorized() {
	return $this->isAuth;
    }

    // -- INTERNALS -- //
}

/*::::
  PURPOSE: NavFolder that may also be aware of an alternate database to use (and maybe other environment stuff later).
*/
class fcMenuFolder extends fcNavFolder {

    private $sDBSpec=NULL;
    public function SetDatabaseSpec($s) {
	$this->sDBSpec = $s;
    }
    protected function GetDatabaseSpec_isSet() {
	return !is_null($this->sDBSpec);
    }
    public function GetDatabase() {
	if ($this->GetDatabaseSpec_isSet()) {
	    $sSpec = $this->sDBSpec;
	    $db = fcDBOFactory::GetConn($sSpec,TRUE);	// TRUE = allow failure
	    // open the database (creates the native object)
	    $db->Open();
	    if (!$db->isOkay()) {
		$sSchema = $db->GetSchemaString();
		throw new exception("Could not open database for schema '$sSchema'.");	// TODO: more graceful failure
	    }
	} else {
	    $db = fcApp::Me()->GetDatabase();
	}
	return $db;
    }
}
/*::::
  PURPOSE: base class for menu-links which are usage-aware
    Base class does not define how usage is determined.
    It is also still PASSIVE - status checks happen when rendering.
  ADDS: usage-awareness
  HISTORY:
    2017-02-08 Can't see any reason for ActionClass methods to be defined here; moving them to Dropin Link class
      (in dropin.php).
    2017-03-26 Figuring authorization at RunCalculations time now, instead of at Render time, because in the latter case
      we don't have the list of missing permits at render time (when we might want to display them).
*/
abstract class fcMenuLink extends fcNavLink {

    // ++ SETUP ++ //

    public function __construct(string $sKeyValue,$sText=NULL,$sPopup=NULL) {
	$this->SetKeyValue($sKeyValue);
	$this->SetLinkText($sText);
	$this->SetPopup($sPopup);
	$this->SetupDefaults();		// ALWAYS CALL THIS from constructor
    }
    protected function SetupDefaults() {
	$this->SetRequiredPrivilege(NULL);	// this class is security-aware, but assumes permission by default
    }

      //++fields++//
    
    private $sPageTitle;
    public function SetPageTitle($s) {
	$this->sPageTitle = $s;
    }
    protected function HasPageTitle() {
	return !empty($this->sPageTitle);
    }
    protected function GetPageTitle() {
	return $this->sPageTitle;
    }
    private $sReqPriv;
    public function SetRequiredPrivilege($sPerm) {
	$this->sReqPriv = $sPerm;
    }
    protected function GetRequiredPrivilege() {
	return $this->sReqPriv;
    }
    private $sBasePath;
    public function SetBasePath($s) {
	$this->sBasePath = $s;
    }
    protected function GetBasePath() {
	return $this->sBasePath;
    }
    protected function HasBasePath() {
	return isset($this->sBasePath);
    }
    protected function GetBasePath_toUse() {
	if ($this->HasBasePath()) {
	    return $this->GetBasePath();
	} else {
	    return fcApp::Me()->GetKioskObject()->GetPagePath();
	}
    }
    protected function MakeURL_fromPath($sPath) {
	return $this->GetBasePath_toUse().$sPath;
    }

      //--fields--//

    // -- SETUP -- //
    // ++ EVENTS ++ //
/*
    protected function OnRunCalculations(){
	parent::OnRunCalculations();
	$this->SetIsAuthorized($this->FigureIfAuthorized());
    } */
    /*----
      NOTE: This has to intercept Render() rather than RenderContent() because fcNavBase::RenderContent()
	is only called if GetShouldDisplay() is true, and we might want to render the Page content to which
	this menu item is attached even if the menu item itself should not be rendered.
    */ /* moving to dropin.php
    public function Render() {
	if ($this->GetIsSelected() && $this->GetIsAuthorized()) {
	    //$this->DoSelect();
	    $this->PageRender();
	}
	return parent::Render();
    }
    protected function OnEventAfter($nEvent){
	$this->PageEvent($nEvent);
    }
*/
    // -- EVENTS -- //
    // ++ STATUS ++ //
    
      //++stored values++//

    // NEW
    private $sKeyValue;
    protected function SetKeyValue($s) {
	$this->sKeyValue = $s;
    }
    protected function GetKeyValue() {
	return $this->sKeyValue;
    }
    // NEW
    private $sKeyName;
    protected function SetKeyName($s) {
	$this->sKeyName = $s;
    }
    protected function GetKeyName() {
	return $this->sKeyName;
    }
    /*
    private $isAuth;
    protected function SetIsAuthorized($b) {
	$this->isAuth = $b;
    }
    protected function GetIsAuthorized() {
	return $this->isAuth;
    }*/
    
    // -- STATUS -- //
    // ++ CALCULATIONS ++ //

    /*----
      NEW
      NOTES:
	2017-01-01 Deleting unauthorized nodes ensures that nothing further happens if not authorized.
		More practically, it also means that the folder doesn't need to do a count of "permitted" nodes
		in order to decide whether or not to display itself; it can assume any remaining nodes are permitted.
		If we later decide NOT to delete unauthorized nodes, then we need
			(a) some way to prevent them from being selected
			(b) some reasonably efficient way for the parent-folder to count how many are permitted
	2017-02-10 Came really close to needing to hide the "users" node from unauthorized users while still allowing
	  them to access their own profile through it, but decided that "my profile" should be a "do:" command instead.
	  If we (later) want admins to be able to modify other users' profiles without logging in as them, we can add
	  an extra user ID parameter. No changes made here.
    */
    protected function FigureIfAuthorized() {
	$sPerm = $this->GetRequiredPrivilege();
	$ok = FALSE;
	if (is_null($sPerm)) {
	    // in this context, NULL permission means everyone is authorized
	    $ok = TRUE;
	} elseif (fcApp::Me()->UserIsLoggedIn()) {
	    if (fcApp::Me()->GetUserRecord()->CanDo($sPerm)) {
		// this item has been authorized for access
		$ok = TRUE;	// node has been authorized
	    }
	}
	if (!$ok) {
	    // We used to make sure the node has a parent before deleting it, but menu entries should never be the node-tree root.
	    // And actually, root nodes should respond sensibly to this request anyway.
	    $this->DeleteMe();
	}
	return $ok;
    }
    
    // -- CALCULATIONS -- //
    // ++ INPUT-TO-OUTPUT STATES ++ //
    
    private $isActive;
    protected function SetIsActive($b) {
	$this->isActive = $b;
    }
    protected function GetIsActive() {
	return $this->isActive;
    }
    /*----
      PURPOSE: This is essentially a stub routine for descendents to override
	if they want to impose conditions.
    */
    protected function GetShouldDisplay() {
	return TRUE;
    }

    // -- INPUT-TO-OUTPUT STATES -- //
    // ++ FRAMEWORK ++ //
    
    protected function GetKioskObject() {
	return fcApp::Me()->GetKioskObject();
    }
}
class fcLink_fromArray extends fcMenuLink {
    /*----
      CEMENT
    */
    protected function GetLinkURL() {
	$arPath = $this->GetLinkArray();
	$fpArgs = fcURL::FromArray($arPath);
	return $this->MakeURL_fromPath($fpArgs);
    }
    /*----
      NEW
      PURPOSE: returns the array to use for calculating link URL
    */
    protected function GetLinkArray() {
	return array_merge(
	  $this->GetLinkArray_figured(),
	  $this->GetLinkArray_added()
	  );
    }
    private $arContext;
    public function AddLinkArray(array $ar) {
	$this->arContext = array_merge($this->GetLinkArray_added(),$ar);
    }
    protected function GetLinkArray_added() {
	return isset($this->arContext) ? $this->arContext : array();
    }
    /*----
      NEW
      PURPOSE: calculates self-identifying array
    */
    // PUBLIC for testing
    public function GetLinkArray_self() {
	$sValue = $this->GetKeyValue();
	$sName = $this->GetKeyName();
	return array($sName=>$sValue);
    }
    /*----
      NEW
      PURPOSE: gets the part of the link array which might change
      NOTE: There originally was code to handle value-only paths --
	    $arPath = array($sValue=>(!$isSel));
	-- but it's not clear that this is actually necessary.
	Wait for a usage case, and then test.
    */
    protected function GetLinkArray_figured() {
	return $this->GetLinkArray_self();
    }
}
/*::::
  PURPOSE: links which are basically utilities -- like "login" -- so the URL has to follow
    a particular convention
    For links where the URL is calculated elsewhere, use fcNavLinkFixed.
*/
class fcUtilityLink extends fcLink_fromArray {
    public function __construct($sKeyName,$sKeyValue,$sText=NULL,$sPopup=NULL) {
	parent::__construct($sKeyValue,$sText,$sPopup);
	$this->SetKeyName($sKeyName);
    }
    // OVERRIDE - we don't want any list-formatting (also, don't prefix with newline)
    protected function RenderSelf() {
	$sClass = $this->GetKeyName().'-'.$this->GetKeyValue();
	return "<span class='$sClass'>".$this->RenderContent()."</span>";
    }
    /*
    public function Render() {
	$sLinkURL = $this->GetLinkURL();
	$htLinkText = htmlspecialchars($this->GetLinkText(),ENT_QUOTES);
	return $this->RenderLink();
    }*/
}
/*::::
  PURPOSE: selectable link that isn't dropin-specific
  ABSTRACT: n/i = GetLinkURL()
*/
abstract class fcDynamicLink extends fcLink_fromArray {

    // ++ CALCULATIONS ++ //
    
    private $isSel;
    // PUBLIC so other objects can see whether menu options are activated
    public function GetIsSelected() {
	if (empty($this->isSel)) {
	    $sKeyValue = $this->GetKeyValue(); // value to activate (TRUE = just needs to be present)
	    $sKeyName = $this->GetKeyName();
	    $oPathIn = $this->GetKioskObject()->GetInputObject();
	    if ($sKeyValue === TRUE || is_null($sKeyValue)) {
		// if key name is TRUE or NULL, we use the presence of the value as a flag
		$this->isSel = $oPathIn->GetBool($sKeyName);
	    } else {
		$sInValue = $oPathIn->GetString($sKeyName);
		$this->isSel = ($sInValue == $sKeyValue);
	    }
	}
	return $this->isSel;
    }
    
    // -- CALCULATIONS -- //

}
class fcToggleLink extends fcDynamicLink {
    /*----
      OVERRIDE - removes self from the URL when selected, so that clicking again un-toggles state
    */
    protected function GetLinkArray_figured() {
	$isSel = $this->GetIsSelected();
	if ($isSel) {
	    $arPath = array();
	} else {
	    $arPath = $this->GetLinkArray_self();
	}
	return $arPath;
    }
}
