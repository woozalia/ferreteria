<?php
/*
  PURPOSE: clsPage descendant(s) for use with MediaWiki
  NOTE: (2015-07-23) Perhaps somewhat confusingly, Ferreteria considers SpecialPage descendents to be taking the role of the App class,
    not the Page class. Perhaps in the future we should have a clsApp descendant which takes a pointer to the SpecialPage
    class, rather than descending from it... or maybe Page and App should both have pointers to the SpecialPage, so that they can more easily provide wrapper methods for their respective functionalities.
  HISTORY:
    2015-07-23 started
    2017-01-16 need to see just how badly this needs to be rewritten - making it a base class
    2018-01-24 Renaming fcPage_MW to fcPageData_MW and making it the base class for fcPageData_SMW.
      Prior to this, very little seemed to be using it and its functionality was pretty minimal anyway.
*/
class fcPageData_MW {

    public function __construct(Title $mwo=NULL) {
	$this->SetTitleObject($mwo);
    }

    // ++ TITLE ++ //
    
    private $mwoTitle;	// throw an error if we try to access this without setting it
    /*----
      PUBLIC for use in re-casting Title object for additional functionality
      TODO: Rename this something like GetNativeTitleObject() or GetMWTitleObject()
    */
    public function GetTitleObject() {
	return $this->mwoTitle;
    }
    // API
    public function SetTitleObject(Title $mwo=NULL) {
	$this->mwoTitle = $mwo;
    }
    // API
    public function Use_GlobalTitle() {
	global $wgTitle;
	$this->SetTitleObject($wgTitle);
    }
    public function Use_Title_Named($iName) {
	$mwoTitle = Title::newFromDBkey($iName);
	$this->SetTitleObject($mwoTitle);
    }
    public function Use_Title_Keyed($iName,$iSpace=NS_MAIN) {
	$mwoTitle = Title::newFromText($iName,$iSpace);
	$this->SetTitleObject($mwoTitle);
    }

    // -- TITLE -- //
    // ++ OBJECTS ++ //
    
    public function GetProperties() {
	global $wgParser;	// 2018-02-10 Is there a better way to get this?
    
	$oProp = new fcMWProperties_page($wgParser,$this->GetTitleObject());
	return $oProp;
    }
}
