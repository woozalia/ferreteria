<?php
/*
  PURPOSE: App framework descendants for MediaWiki
  HISTORY:
    2014-06-17 Created for WorkFerret.
*/

class clsApp_MW extends cAppStandard {
    public function Session() {
	throw new exception('Who is calling this?');
    }
    public function User() {
	throw new exception('Who is calling this?');
    }
    public function Page(clsPage $obj=NULL) {
	if (!is_null($obj)) {
	    $this->oPage = $obj;
	    $obj->App($this);
	    //$obj->Doc($oDoc);
	} elseif (empty($this->oPage)) {
	    $this->oPage = SpecialPageApp::Me();
	}
	return $this->oPage;
    }
}

class cDataRecord_MW extends clsDataRecord_Menu {
    public function CreateEvent(array $arArgs) {
	return NULL;	// stubbed off for now
   }
}