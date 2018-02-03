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

    // ++ INFORMATION ++ //

    public function BaseURL_rel() {
    throw new exception('2018-01-24 Who calls this?');
	return $this->App()->BaseURL_rel();
    }
    public function BaseURL_abs() {
    throw new exception('Who calls this? (affirmed 2018-01-24)');
	return $this->App()->BaseURL_abs();
    }
//    protected function App_BaseURL_rel() {
  //  }

    // -- INFORMATION -- //
    // ++ TITLE ++ //
    
    private $mwoTitle = NULL;
    // PUBLIC for use in re-casting Title object for additional functionality
    public function GetTitleObject() {
	global $wgTitle;
	
	if (is_null($this->mwoTitle)) {
	    throw new exception('2018-02-02 This probably should not be the default. Call Use_GlobalTitle() first.');
	    $this->mwoTitle = $wgTitle;
	}
	return $this->mwoTitle;
    }
    // PUBLIC for reasons (2018-01-27)
    public function SetTitleObject(Title $mwo) {
	$this->mwoTitle = $mwo;
    }
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
}
