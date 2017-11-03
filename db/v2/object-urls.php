<?php
/*
  PURPOSE: traits and helper class for menu system - manages action URLs
    Provides a set of four functions which are wrappers to a LinkBuilder object:
      * SelfArray(array $arAdd=NULL)
	* returns an array of the currently-defined values to link, based on host object's type and internal state
	* calls LinkBuilder()->LinkArray($arAdd)
      * SelfURL(array $arAdd=NULL)
	* builds a URL from the array returned by SelfArray()
	* calls LinkBuilder()->LinkURL($arAdd)
      * SelfLink($sText=NULL,$sPopup=NULL,array $arAdd=NULL,array $arAttr=NULL)
	* builds a link from SelfURL()'s URL, with text and attributes defined by the additional arguments
	* calls LinkBuilder()->LinkHTML($sText,$sPopup,$arAdd,$arAttr)
      * SelfRedirect(array $arAdd=NULL,$sText=NULL)
	* redirects to the URL returned by SelfURL()
	* calls LinkBuilder()->LinkRedirect($sText,$arAdd)
      In each case, $arAdd is an optional array of additional values to include.
    The host class defines what class the LinkBuilder is. The LinkBuilder-descended class
      implements the code which calculates the basic array of values (SelfArray).
    The host class also decides what text is displayed for a link at call-time.

  HISTORY:
    2013-12-04 adapted from MediaWiki classes for standalone menu system
    2015-05-08 MW data record classes have no App() method, so resorting to static call in _AdminURL() and BuildLink();
    2015-07-12 resolving conflicts with other edited version
    2015-08-11 resolving conflicts with other edited version again
      How did this even happen, since I've only been editing here on cloud2?
    2015-09-05 New class: fcMenuTable_helper
      * TODO: Should probably rename clsMenuData_helper to fcMenuRecord_helper
      * Reworking how this works, so that it makes more sense.
        * renaming classes
        * adding traits
    2015-09-06 renamed from menu-helper.php to object-urls.php; removing old classes
    2015-09-20 moved common functions from ftLinkableTable and ftLinkableRecord to ftLinkableObject
    2016-10-31 moved both versions into db.v* folders; upgrading this one to db.v2
    2017-03-21 modifying slightly to require interfaces rather than descendants of specific class roots
*/

// REQUIRED by ftLinkableObject
interface fiLinkable {
    function SelfURL(array $arAdd=NULL);
    function SelfLink_idArray();
}
// REQUIRED by fcLinkBuilder_table
interface fiLinkableTable extends fiLinkable{
}
// REQUIRED by 
interface fiLinkableRecord extends fiLinkable {
// 2017-03-21 commenting out until proof that it's needed
//    function GetKeyString();	// might represent more than one key field
}

trait ftLinkableObject {
    abstract public function SelfLink_idArray();
    abstract protected function LinkBuilder();
    /*----
      USED BY: VbzAdmin::VbzAdminStkItems::Listing_forItem()
      HISTORY:
	2010-10-06 Disabled, because it wasn't clear if anyone was using it.
	  Thought I checked VbzAdmin, WorkFerret, and AudioFerret
	2010-10-13 VbzAdmin::VbzAdminStkItems::Listing_forItem() calls it
	2013-12-14 Moved from mw/admin.php to vbz-data.php
	2015-09-20 combined 4 Self*() functions from  ftLinkableTable and ftLinkableRecord
    */
    public function SelfArray(array $arAdd=NULL) {
        return $this->LinkBuilder()->LinkArray($arAdd);
    }
    public function SelfURL(array $arAdd=NULL) {
	return $this->LinkBuilder()->LinkURL($arAdd);
    }
    public function SelfLink($sText=NULL,$sPopup=NULL,array $arAdd=NULL,array $arAttr=NULL) {
        return $this->LinkBuilder()->LinkHTML($sText,$sPopup,$arAdd,$arAttr);
    }
    public function SelfRedirect(array $arAdd=NULL,$sText=NULL) {
	return $this->LinkBuilder()->LinkRedirect($sText,$arAdd);
    }
}

trait ftLinkableTable {
    use ftLinkableObject;

    // ++ ABSTRACT ++ //
    
    abstract public function GetActionKey();
    
    // -- ABSTRACT -- //
    // ++ OVERRIDEABLE ++ //

    protected function LinkBuilder() {
        return new fcLinkBuilder_table($this);
    }
    // PUBLIC so Link Builder can use it
    public function SelfLink_idArray() {
        return 	array(
	  'page' =>   $this->GetActionKey()
	  );
    }

    // -- OVERRIDEABLE -- //
}
trait ftLinkableRecord {
    use ftLinkableObject;

    // ++ CONFIGURATION ++ //

    // OVERRIDEABLE
    protected function LinkBuilder() {
        return new fcLinkBuilder_records($this);
    }
    // OVERRIDEABLE
    // PUBLIC so Link Builder can use it
    public function SelfLink_idArray() {
	$arThis = array(
	  'id'	=> $this->GetKeyValue()
	  );
	$tbl = $this->GetTableWrapper();
	if (!method_exists($tbl,'SelfArray')) {
	    $sMsg = 'The table class "'.get_class($tbl).'" needs to use the ftLinkableTable trait'
	      .' so that the recordset class "'.get_class($this).'" can use self-linking features.';
	    throw new exception($sMsg);
	}
	$arAll = $tbl->SelfArray($arThis);
        return $arAll;
    }
    /*----
      PURPOSE: ID value to use for new record ID links
	Okay to override (might be necessary for non-numeric keys).
    */
    public function SelfLink_NewKey() {
	return KS_NEW_REC;
    }

    // -- CONFIGURATION -- //
    // ++ INFORMATION ++ //

    // NOTE: This is actually only used by the Form class
    public function GetActionKey() {
	return $this->GetTableWrapper()->GetActionKey();
    }

    // -- INFORMATION -- //
}

abstract class fcLinkBuilder {

    // ++ ABSTRACT METHODS ++ //

    // RETURNS: an array of values that identify the linked object (table or table+record)
    abstract protected function IdentityValues();
    abstract protected function KeyString();

    // -- ABSTRACT METHODS -- //
    // ++ API ++ //

    /*----
      RETURNS: array from which to build the URL
      INPUT:
	$arAdd = optional array of values to add to the list, or to override default values
    */
    public function LinkArray(array $arAdd=NULL) {
	$arBase = $this->IdentityValues();
	$arAll = fcArray::Merge($arBase,$arAdd);	// add/override defaults
	return $arAll;
    }
    /*----
      RETURNS: URL for link
      INPUT:
	$arAdd: same as LinkArray()
      NOTE: (2015-11-30) I've removed the '/' that was being added between $urlBase and $urlAdd
	because it was causing unnecessary duplicattion ('//' in generated URLs) and this does not
	seem to be causing any problems -- but it might in the future. If it does, then there
	needs to be documentation explaining whether BaseURLs should have terminating slashes or not.
	Or something like that.
    */
    public function LinkURL(array $arAdd=NULL) {
	$arLink = $this->LinkArray($arAdd);
	$urlAdd = fcURL::FromArray($arLink);
	$urlBase = fcApp::Me()->GetKioskObject()->GetBasePath();
	return $urlBase.$urlAdd;
    }
    /*----
      RETURNS: complete tag for link
      INPUT:
	$sText: the text that will represent the link
	$sPopup: text to pop up when hovering over the link
	$arAdd: same as LinkArray()
	$arAttr: any HTML attributes to apply to the link
    */
    public function LinkHTML($sText=NULL,$sPopup=NULL,array $arAdd=NULL,array $arAttr=NULL) {
	$url = $this->LinkURL($arAdd);
	$sText = is_null($sText)?($this->KeyString()):$sText;
	$out = fcHTML::BuildLink($url,$sText,$sPopup,$arAttr);
	return $out;
    }
    /*----
      ACTION: redirects to a generated URL
      HISTORY:
	2017-02-09 now includes page contents in stash. Hopefully this won't cause a problem;
	  deal with it when/if it does.
    */
    public function LinkRedirect($sText=NULL,array $arAdd=NULL) {
	$oPage = fcApp::Me()->GetPageObject();
    	$url = $this->LinkURL($arAdd);
    	if (!is_null($sText)) {
	    $oPage->AddContentString($sText);
	}
	$oPage->DoStashedRedirect($url);

	/* 2017-02-09 old code
	// TODO: finish adapting this
	fcHTTP::DisplayOnReturn($sText);
	$url = $this->LinkURL($arAdd);
	echo 'REDIRECTING to '.$url.' and saving the following text:<br>'.$sText;	// for debugging
	fcHTTP::Redirect($url,array(),FALSE,HTTP_REDIRECT_POST);
	die();	// don't do any more work (including accidentally erasing the cookie)
	*/
    }

    // -- API -- //
}

class fcLinkBuilder_table extends fcLinkBuilder {

    // ++ SETUP ++ //

    public function __construct(fiLinkableTable $tbl) {
        $this->SetTableWrapper($tbl);
    }

    // -- SETUP -- //
    // ++ LOCAL FIELD ACCESS ++ //

    private $tbl;
    protected function SetTableWrapper(fiLinkableTable $tbl) {
	$this->tbl = $tbl;
    }
    protected function GetTableWrapper() {
	return $this->tbl;
    }

    // -- LOCAL FIELD ACCESS -- //
    // ++ CEMENTING ++ //

    protected function IdentityValues() {
	return $this->GetTableWrapper()->SelfLink_idArray();
    }
    protected function KeyString() {
	throw new exception('2017-03-21 Does anything actually call this?');
	return $this->GetTableWrapper()->ActionKey();	// not sure if this is the most useful response
    }

    // -- CEMENTING -- //
}

/*::::
  PURPOSE: approximately replaces clsMenuData_helper and offspring
*/
class fcLinkBuilder_records extends fcLinkBuilder {

    // ++ SETUP/CONFIG ++ //

//    public function __construct(fcRecord_keyed $rs) {
    public function __construct(fiLinkableRecord $rs) {
	$this->SetRecordWrapper($rs);
    }
    
    private $rs;
    protected function SetRecordWrapper(fiLinkableRecord $rs) {
	$this->rs = $rs;
    }
    protected function GetRecordWrapper() {
	return $this->rs;
    }

    /*----
      HISTORY:
	2014-02-21 changed $iRecs from single-keyed class to abstract-keyed class
    */
    public function Recs(fiLinkableRecord $iRecs=NULL) {
	throw new exception('2017-03-21 This is deprecated; call SetRecordWrapper() or GetRecordWrapper() instead.');
	if (!is_null($iRecs)) {
	    $this->rs = $iRecs;
	}
	return $this->rs;
    }

    // -- SETUP/CONFIG -- //
    // ++ CEMENTING ++ //

    protected function IdentityValues() {
	return $this->GetRecordWrapper()->SelfLink_idArray();
    }
    // USED BY fcLinkBuilder::LinkHTML()
    protected function KeyString() {
	return $this->GetRecordWrapper()->GetKeyValue();
    }

    // -- CEMENTING -- //
}
