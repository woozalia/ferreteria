<?php
/*
  PURPOSE: App framework descendants for MediaWiki
  HISTORY:
    2014-06-17 Created for WorkFerret.
    2015-07-12 resolving conflicts with other edited version
    2017-11-05 renamed clsApp_MW -> fcApp_MW
    2018-01-24 ftMediaWiki_Database
    2018-01-25 moved ftMediaWiki_Database into fcApp_MW

*/

class fcApp_MW extends fcAppStandard {

    // ++ CLASSES ++ //
    
    private $sDatabaseClass = 'fcDataConn_MW';	// default
    protected function GetDatabaseClass() {
	return $this->sDatabaseClass;
    }
    // USAGE: call this *before* first instantiating a database object
    public function SetDatabaseClass($sClass) {
	$this->sDatabaseClass = $sClass;
    }
    protected function GetPageClass() {
	return 'fcPageData_MW';	// 2017-01-16 This may not work.
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

    // ++ FRAMEWORK ++ //

    // NEW
    protected function GetDatabase_MW() {
	return wfGetDB( DB_SLAVE );
    }
    // CEMENT
    static private $fcDB = NULL;
    public function GetDatabase() {
	if (is_null(self::$fcDB)) {
	    $sClass = $this->GetDatabaseClass();
	    $dbmw = $this->GetDatabase_MW();
	    $dbf = new $sClass($dbmw);
	    self::$fcDB = $dbf;
	}
	return self::$fcDB;
    }
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

    // -- FRAMEWORK -- //
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

