<?php
/*
  PURPOSE: App framework descendants for MediaWiki
  HISTORY:
    2014-06-17 Created for WorkFerret.
    2015-07-12 resolving conflicts with other edited version
*/

class clsApp_MW extends cAppStandard {
    public function Session() {
	throw new exception('Who is calling this?');
    }
    public function User() {
	throw new exception('Who is calling this?');
    }

    // ++ CEMENTING ++ //

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
    // Should this even be here, or should we throw an exception and tell callers to go directly to Page()->BaseURL()?
    public function BaseURL() {
	return $this->Page()->BaseURL();
    }

    // -- CEMENTING -- //
}

class cDataRecord_MW extends clsDataRecord_Menu {

    // ++ ACTION ++ //

    public function CreateEvent(array $arArgs) {
	return NULL;	// stubbed off for now
    }

    // -- ACTION -- //
}