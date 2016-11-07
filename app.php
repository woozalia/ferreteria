<?php
/*
  FILE: app.php
  PURPOSE: generic/abstract application framework
  HISTORY:
    2012-05-08 split off from store.php
    2013-10-23 stripped for use with ATC app (renamed as app.php)
    2013-11-11 re-adapted for general library
    2016-10-01 changes to work with db.v2
*/

define('KWP_FERRETERIA_DOC','http://htyp.org/User:Woozle/Ferreteria');
define('KWP_FERRETERIA_DOC_ERRORS',KWP_FERRETERIA_DOC.'/errors');

/*%%%%
  CLASS: clsApp
  PURPOSE: base class -- container for the application
  FUTURE: For now, we're assuming a single database per application.
    This could be changed in the future by having a multiple-db App class and moving some
    of fcApp's methods into a single-db App class, and/or supporting one or more separate DBs for users and sessions.
*/
abstract class fcApp {
    use ftVerbalObject;

    static protected $me;
    static public function Me(fcApp $oApp=NULL) {
	if (!is_null($oApp)) {
	    self::$me = $oApp;
	}
	return self::$me;
    }
    public function __construct() {
	self::Me($this);			// there can be only one
    }
    abstract public function Go();
    abstract public function SetPageObject(clsPage $oPage);
    abstract public function GetPageObject();
    abstract public function GetDatabase();
    abstract public function SessionTable();	// should probably be GetSessionTable()
    abstract public function GetSessionRecord();
    abstract public function GetUserRecord();
}
abstract class fcAppStandard extends fcApp {
    private $oPage;
    private $oSkin;

    // ++ MAIN ++ //

    public function Go() {
	$db = $this->GetDatabase();
	$db->Open();
	if ($db->isOkay()) {
	    $this->GetPageObject()->DoPage();
	    $db->Shut();
	} else {
	    throw new exception('Could not open the database.');
	}
    }

    // -- MAIN -- //
    // ++ PROFILING ++ //
    
    private $fltStart;
    public function SetStartTime() {
	$this->fltStart = microtime(true);
    }
    /*----
      RETURNS: how long since StartTime() was called, in microseconds
    */
    protected function ExecTime() {
	return microtime(true) - $this->fltStart;
    }
    
    // -- PROFILING -- //
    // ++ CLASS NAMES ++ //

    protected function SessionsClass() {
	return KS_CLASS_USER_SESSIONS;
    }
    protected function UsersClass() {
	return 'clsUserAccts';
    }
    protected function EventsClass() {
	return 'fctEvents_standard';
    }

    // -- CLASS NAMES -- //
    // ++ TABLES ++ //

    public function SessionTable() {
	$db = $this->GetDatabase();
	return $db->MakeTableWrapper($this->SessionsClass());
    }
    public function UserTable($id=NULL) {
	$db = $this->GetDatabase();
	return $db->MakeTableWrapper($this->UsersClass(),$id);
    }
    public function EventTable($id=NULL) {
	$db = $this->GetDatabase();
	return $db->MakeTableWrapper($this->EventsClass(),$id);
    }

    // -- TABLES -- //
    // ++ RECORDS ++ //

//    private $rcSess;
    public function GetSessionRecord() {
	return $this->SessionTable()->MakeActiveRecord();
    /* 2016-10-30 old code
	if (empty($this->rcSess)) {
	    $tSess = $this->SessionTable();
	    $this->rcSess = $tSess->GetCurrent();
	} else {
	    $tSess = $this->rcSess->GetTableWrapper();
	}
	if (!$this->rcSess->HasRows()) {

	    $tSess->ClearSession();
	    $this->rcSess = $tSess->GetCurrent();
	}
	return $this->rcSess; */
    }
    private $rcUser;
    public function GetUserRecord() {
	if (empty($this->rcUser)) {
	    $this->rcUser = $this->GetSessionRecord()->UserRecord();
	}
	return $this->rcUser;
    }

    // -- RECORDS -- //
    // ++ FRAMEWORK OBJECTS ++ //

    public function SetPageObject(clsPage $oPage) {
	$this->oPage = $oPage;
	//$oPage->App($this);
    }
    public function GetPageObject() {
	return $this->oPage;
    }
    /* 2016-10-23 Still trying to figure out if these are needed....
    protected function GetDataFactory() {
	return new fcDatabase($this->DataConnectionSpec());
    }
    abstract protected function DataConnectionSpec();
    */
    /*
    private $oData;
    public function Data(clsDatabase_abstract $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->oData = $iObj;
	}
	return $this->oData;
    }*/
    
    // -- FRAMEWORK OBJECTS -- //
    // ++ FIELD CALCULATIONS ++ //

    /*----
      NOTES:
	2016-05-22 It seems like a good idea to have this, to pass to record-creation methods that ask for it.
      PUBLIC so cart.logic can use it
    */
    public function GetUserID() {
	if ($this->UserIsLoggedIn()) {
	    return $this->GetSessionRecord()->GetUserID();
	} else {
	    return NULL;
	}
    }
    /*----
      RETURNS: TRUE iff the user is logged in
    */
    public function UserIsLoggedIn() {
	return $this->GetSessionRecord()->UserIsLoggedIn();
    }
    /*----
      RETURNS: User login string, or NULL if user not logged in
      HISTORY:
	2014-07-27 Written because this seems to be where it belongs.
	  May duplicate functionality in Page object. Why is that there?
    */
    public function UserName() {
	if ($this->UserIsLoggedIn()) {
	    return $this->GetUserRecord()->UserName();
	} else {
	    return NULL;
	}
    }

    // -- FIELD CALCULATIONS -- //
    // ++ EMAIL ++ //

    protected function EmailAddr_FROM($sTag) {
	$ar = array('tag'=>$sTag);
	$tplt = new clsStringTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT,$ar);

	return $tplt->Replace(KS_TPLT_EMAIL_ADDR_ADMIN);
    }
    /*----
      ACTION: send an automatic email (i.e. not a message from an actual person)
      USAGE: called from clsEmailAuth::SendPassReset_forAddr()
    */
    public function DoEmail_Auto($sToAddr,$sToName,$sSubj,$sMsg) {
	if (empty($sToName)) {
	    $sAddrToFull = $sToAddr;
	} else {
	    $sAddrToFull = $sToName.' <'.$sToAddr.'>';
	}

	$sHdr = 'From: '.$this->EmailAddr_FROM(date('Y'));
	$ok = mail($sAddrToFull,$sSubj,$sMsg,$sHdr);
	return $ok;
    }
    
    // -- EMAIL -- //

}
/*----
  PURPOSE: easy access to objects provided by fcApp descendants
  NOTE: 2016-10-23 This doesn't seem very elegant. I want to make it a static class,
    but then I have to wonder why I don't just call fcApp static methods. And why does
    fcApp* ever need to be instantiated, anyway? Shouldn't it be all-static?
  TODO: Assuming we keep this methodology, maybe all method names should be prefixed with "App", e.g. AppPageObject(),
    to make it clear where they come from.
*/
trait ftFrameworkAccess {
    protected function AppObject() {
	return vcApp::Me();
    }
    protected function PageObject() {
	return $this->AppObject()->GetPageObject();
    }
    protected function SkinObject() {
	return $this->AppObject()->GetPageObject()->GetSkinObject();
    }
    protected function DatabaseObject() {
	return $this->AppObject()->GetDatabase();
    }
    protected function UserRecord() {
	return $this->AppObject()->GetUserRecord();
    }
    protected function SessionRecord() {
	return $this->AppObject()->GetSessionRecord();
    }
}

// WORKS WITH: fcAppStandard
trait ftFrameworkAccess_standard {
    use ftFrameworkAccess;

    protected function EventTable() {
	return $this->AppObject()->EventTable();
    }
}