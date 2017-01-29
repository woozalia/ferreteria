<?php
/*
  PURPOSE: App framework descendants for MediaWiki
  HISTORY:
    2014-06-17 Created for WorkFerret.
    2015-07-12 resolving conflicts with other edited version
*/

class clsApp_MW extends fcAppStandard {

    // ++ DEPRECATED ++ //

    public function Session() {
	throw new exception('Who is calling this?');
    }
    public function User() {
	throw new exception('Who is calling this?');
    }
    
    // -- DEPRECATED -- //
    // ++ CLASSES ++ //
    
    protected function GetPageClass() {
	return 'fcPage_MW';	// 2017-01-16 This may not work.
    }
    protected function GetKioskClass() {
	throw new exception('Kiosk class needs to be written for MediaWiki.');
    }

    // -- CLASSES -- //
    // ++ ACTION ++ //
    
    public function AddContentString($s) {
	throw new exception('AddContentString() needs to be written for MediaWiki.');
    }

    // -- ACTION -- //
    
    /* 2016-10-03 this should be obsolete now
    public function Data(clsDatabase_abstract $iObj=NULL) {
	if (is_null($iObj)) {
	    $db = parent::Data();
	    if (is_null($db)) {
		parent::Data(new clsMWData(wfGetDB(DB_SLAVE)));
	    }
	}
	return parent::Data($iObj);
    }
    */
    public function GetDatabase() {
	return new fcDataConn_MW(wfGetDB(DB_SLAVE));
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

    /* 2017-01-16 This isn't how we do Pages anymore.
    private $oPage;
    public function SetPageObject(clsPage $obj) {
	
	if (!is_object($obj)) {
	    throw new exception('Passed a non-object to Page().');
	}
	$this->oPage = $obj;
	//$obj->App($this); 2016-11-05 no longer needed
	//$obj->Doc($oDoc);
    }
    public function GetPageObject() {
	if (empty($this->oPage)) {
	    $this->oPage = $this->MWSpecialPage();
	}
	return $this->oPage;
    } */
    /*----
      2016-03-29 A note said:
	"Should this even be here, or should we throw an exception and tell callers to go directly to Page()->BaseURL()?"
	...but Page()->BaseURL_rel() calls this, so I'm not sure if that would work.
	Either way, adding a terminating slash because otherwise the links aren't built right.
    */
    /* 2017-01-16 Use the Kiosk object.
    public function BaseURL_rel() {
	return $this->MWSpecialPage()->BaseURL_rel().'/';
    } */

    // -- CEMENTING -- //
}

