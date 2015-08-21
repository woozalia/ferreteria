<?php
/*
  PURPOSE: clsPage descendant(s) for use with MediaWiki
  NOTE: (2015-07-23) Perhaps somewhat confusingly, Ferreteria considers SpecialPage descendents to be taking the role of the App class,
    not the Page class. Perhaps in the future we should have a clsApp descendant which takes a pointer to the SpecialPage
    class, rather than descending from it... or maybe Page and App should both have pointers to the SpecialPage, so that they can more easily provide wrapper methods for their respective functionalities.
  HISTORY:
    2015-07-23 started
*/

class fcPage_MW extends clsPage {

    // ++ INFORMATION ++ //

    public function BaseURL_rel() {
	return $this->App()->BaseURL_rel();
    }
    public function BaseURL_abs() {
    throw new exception('Who calls this?');
	return $this->App()->BaseURL_abs();
    }
//    protected function App_BaseURL_rel() {
  //  }

    // -- INFORMATION -- //

}
