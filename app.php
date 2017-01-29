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

/*::::
  PURPOSE: application framework base class -- container for the application
  FUTURE: For now, we're assuming a single database per application.
    This could be changed in the future by having a multiple-db App class and moving some
    of fcApp's methods into a single-db App class, and/or supporting one or more separate DBs for users and sessions.
*/
abstract class fcApp {
    use ftVerbalObject;

    // ++ SETUP ++ //
    
    static protected $me;
    static public function Me(fcApp $oApp=NULL) {
	if (!is_null($oApp)) {
	    self::$me = $oApp;
	}
	if (!isset(self::$me)) {
	    throw new exception('Ferreteria usage error: attempting to access App object before it has been set.');
	}
	return self::$me;
    }
    public function __construct() {
	self::Me($this);	// there can be only one
    }
    
    // -- SETUP -- //
    // ++ ACTION ++ //
    
    abstract public function Go();
    abstract public function AddContentString($s);
    
    // -- ACTION -- //
    // ++ OBJECTS ++ //

    abstract public function GetDatabase();
    public function GetSessionTable() {
	$db = $this->GetDatabase();
	$sClass = $this->GetSessionsClass();
	return $db->MakeTableWrapper($sClass);
    }
    abstract public function GetSessionRecord();
    abstract public function GetUserRecord();
    private $oPage;
    public function GetPageObject() {
	if (empty($this->oPage)) {
	    $sClass = $this->GetPageClass();
	    $this->oPage = new $sClass();
	}
	return $this->oPage;
    }
    private $oKiosk;
    public function GetKioskObject() {
	if (empty($this->oKiosk)) {
	    $sClass = $this->GetKioskClass();
	    $this->oKiosk = new $sClass();
	}
	return $this->oKiosk;
    }
    private $oHdrMenu;
    public function GetHeaderMenu() {
	if (empty($this->oHdrMenu)) {
	    $this->oHdrMenu = $this->GetPageObject()->GetElement_HeaderMenu();
	}
	return $this->oHdrMenu;
    }
    private $oDropinMgr;
    public function GetDropinManager() {
	if (!isset($this->oDropinMgr)) {
	    $sClass = $this->GetDropinManagerClass();
	    // not a sourced table
	    //$db = $this->GetDatabase();
	    //return $db->MakeTableWrapper($sClass);
	    $this->oDropinMgr = new $sClass;
	}
	return $this->oDropinMgr;
    }
    
    // -- OBJECTS -- //
    // ++ CLASSES ++ //
    
    abstract protected function GetSessionsClass();
    abstract protected function GetPageClass();
    abstract protected function GetKioskClass();
    abstract protected function GetDropinManagerClass();

    // -- CLASSES -- //
    // ++ INFORMATION ++ //
    
    abstract public function UserIsLoggedIn();

    // -- INFORMATION -- //
}
/*::::
  ABSTRACT: n/i - GetDatabase(), GetPageClass(), GetKioskClass()
*/
abstract class fcAppStandard extends fcApp {

    // ++ MAIN ++ //

    public function Go() {
	try {
	    $this->Main();
	} catch(fcExceptionBase $e) {
	    $e->React();
	} catch(exception $e) {
	    $code = $e->getCode();
	    $sNative = ($code==0)?'':"<b>Native exception $code</b> caught:";
	    echo '<html><head><title>'
	      .KS_SITE_SHORT
	      .' error</title></head>'
	      .$sNative
	      .'<ul>'
	      .'<li><b>Error message</b>: '.$e->getMessage().'</li>'
	      .'<li><b>Thrown in</b> '.$e->getFile().' <b>line</b> '.$e->getLine()
	      .'<li><b>Stack trace</b>:'.nl2br($e->getTraceAsString()).'</li>'
	      .'</ul>'
	      .'</html>'
	      ;
	    // TO DO: generate an email as well
	}
    }
    public function ReportSimpleError($s) {
	$this->DoEmail_fromAdmin_Auto(
	  KS_TEXT_EMAIL_ADDR_ERROR,
	  KS_TEXT_EMAIL_NAME_ERROR,
	  'Silent Internal Error',$s);
	// TODO: log it also?
    }
    protected function Main() {
	$db = $this->GetDatabase();
	$db->Open();
	if ($db->isOkay()) {
	    $oPage = $this->GetPageObject();
	    $oPage->DoBuilding();
	    $oPage->DoFiguring();
	    $oPage->DoOutput();
	    $db->Shut();
	} else {
	    throw new fcDebugException('Ferreteria Config Error: Could not open the database.');
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

    protected function GetSessionsClass() {
	return 'fctUserSessions';
    }
    protected function GetUsersClass() {
	return 'fctUserAccts_admin';
    }
    protected function GetEventsClass() {
	return 'fctEvents_standard';
    }
    protected function GetDropinManagerClass() {
	return 'fcDropInManager';
    }

    // -- CLASS NAMES -- //
    // ++ TABLES ++ //

    public function UserTable($id=NULL) {
	$db = $this->GetDatabase();
	return $db->MakeTableWrapper($this->GetUsersClass(),$id);
    }
    public function EventTable($id=NULL) {
	$db = $this->GetDatabase();
	return $db->MakeTableWrapper($this->GetEventsClass(),$id);
    }

    // -- TABLES -- //
    // ++ RECORDS ++ //

//    private $rcSess;
    public function GetSessionRecord() {
	return $this->GetSessionTable()->MakeActiveRecord();
    }
    private $rcUser;
    public function GetUserRecord() {
	if (empty($this->rcUser)) {
	    $this->rcUser = $this->GetSessionRecord()->UserRecord();
	}
	return $this->rcUser;
    }

    // -- RECORDS -- //
    // ++ STATUS ++ //

    /*----
      RETURNS: TRUE iff the user is logged in
    */
    public function UserIsLoggedIn() {
	return $this->GetSessionRecord()->UserIsLoggedIn();
    }

    // -- STATUS -- //
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
    // ++ ACTION ++ //
    
    // CEMENT
    public function AddContentString($s) {
	$oPage = $this->GetPageObject();
	$oPage->GetElement_PageContent()->AddText($s);
    }

    // -- ACTION -- //
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
    public function DoEmail_fromAdminAuto_OLD($sToAddr,$sToName,$sSubj,$sMsg) {
	if (empty($sToName)) {
	    $sAddrToFull = $sToAddr;
	} else {
	    $sAddrToFull = $sToName.' <'.$sToAddr.'>';
	}

	$sHdr = 'From: '.$this->EmailAddr_FROM(date('Y'));
	$ok = mail($sAddrToFull,$sSubj,$sMsg,$sHdr);
	return $ok;
    }
    /*----
      ACTION: send an automatic administrative email
      USAGE: called from vcPageAdmin::SendEmail_forLoginSuccess()
    */
    public function DoEmail_fromAdmin_Auto($sToAddr,$sToName,$sSubj,$sMsg) {
	if ($this->UserIsLoggedIn()) {
	    $rcUser = $this->GetUserRecord();
	    $sTag = 'user-'.$rcUser->GetKeyValue();
	} else {
	    $sTag = date('Y');
	}

	$oTplt = new clsStringTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT,array('tag'=>$sTag));

	$sAddrFrom = $oTplt->Replace(KS_TPLT_EMAIL_ADDR_ADMIN);
	if (empty($sToName)) {
	    $sAddrToFull = $sToAddr;
	} else {
	    $sAddrToFull = $sToName.' <'.$sToAddr.'>';
	}

	$sHdr = 'From: '.$sAddrFrom;
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
	return fcApp::Me();
    }
    protected function PageObject() {
	return $this->AppObject()->GetPageObject();
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