<?php
/*
  HISTORY:
    2018-04-18 moving some non-data-dependent base classes here from db/v2/object-urls.php
*/

// REQUIRED by ftLinkableObject
interface fiLinkable {
    function SelfURL(array $arAdd=NULL);
    function SelfLink_idArray();
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
