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
    
    public function Use_TitleObject(Title $iTitle) {
	$this->mwoTitle = $iTitle;
    }
    public function MW_Object() {
	return $this->mwoTitle;
    }
    public function Use_GlobalTitle() {
	global $wgTitle;
	$this->Use_TitleObject($wgTitle);
    }
    public function Use_Title_Named($iName) {
	$mwoTitle = Title::newFromDBkey($iName);
	$this->Use_TitleObject($mwoTitle);
    }
    public function Use_Title_Keyed($iName,$iSpace=NS_MAIN) {
	$mwoTitle = Title::newFromText($iName,$iSpace);
	$this->Use_TitleObject($mwoTitle);
    }

    // -- TITLE -- //
}
