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

    // ++ APP FRAMEWORK ++ //

    // NOTE: Not sure if this is the right name for this. Is a SpecialPage a type of Page, or a Title, or Article, or what?
    private $oMW;
    public function MWPageObject(SpecialPageApp $oMW=NULL) {
	throw new exception('MWPageObject() is deprecated; call MWSpecialPage() instead (but read-only).');
	if (!is_null($oMW)) {
	    $this->oMW = $oMW;
	}
	return $this->oMW;
    }
    public function MWSpecialPage() {
	return SpecialPageApp::Me();
    }

    // -- APP FRAMEWORK -- //
    // ++ CEMENTING ++ //

    private $oPage;
    public function Page(clsPage $obj=NULL) {
	if (!is_null($obj)) {
	    if (!is_object($obj)) {
		throw new exception('Passed a non-object to Page().');
	    }
	    $this->oPage = $obj;
	    $obj->App($this);
	    //$obj->Doc($oDoc);
	} elseif (empty($this->oPage)) {
	    $this->oPage = $this->MWSpecialPage();
	}
	return $this->oPage;
    }
    // Should this even be here, or should we throw an exception and tell callers to go directly to Page()->BaseURL()?
    public function BaseURL_rel() {
	return $this->MWSpecialPage()->BaseURL_rel();
    }

    // -- CEMENTING -- //
}

