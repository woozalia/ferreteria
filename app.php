<?php
/*
  FILE: app.php
  PURPOSE: generic/abstract application framework
  HISTORY:
    2012-05-08 split off from store.php
    2013-10-23 stripped for use with ATC app (renamed as app.php)
    2013-11-11 re-adapted for general library
    2016-10-01 changes to work with db.v2
    2017-03-25 moving user-security permission constants here
*/

define('KWP_FERRETERIA_DOC','http://htyp.org/User:Woozle/Ferreteria');
define('KWP_FERRETERIA_DOC_ERRORS',KWP_FERRETERIA_DOC.'/errors');

// ++ EVENTS ++ //

define('KS_EVENT_SUCCESS','fe.OK');
define('KS_EVENT_FAILED','fe.ERR');
define('KS_EVENT_NO_CHANGE','fe.STET');	// data left unaltered
define('KS_EVENT_NEW_RECORD','fe.NEW');
define('KS_EVENT_CHANGE_RECORD','fe.CHG');
define('KS_EVENT_FERRETERIA_SUSPICIOUS_INPUT','fe.suspicious');
define('KS_EVENT_FERRETERIA_EMAIL_SENT','fe.email.sent');
define('KS_EVENT_FERRETERIA_SENDING_ADMIN_EMAIL','fe.email.req');

/*::::
  PURPOSE: application framework base class -- container for the application
  FUTURE: For now, we're assuming a single database per application.
    This could be changed in the future by having a multiple-db App class and moving some
    of fcApp's methods into a single-db App class, and/or supporting one or more separate DBs for users and sessions.
*/
abstract class fcApp {
    use ftSingleton, ftVerbalObject;

    // ++ ACTION ++ //
    
    abstract public function Go();
    abstract public function AddContentString($s);
    
    // -- ACTION -- //
    // ++ CLASSES ++ //
    
    abstract protected function GetSessionsClass();
    abstract protected function GetPageClass();
    abstract protected function GetKioskClass();
    abstract protected function GetDropinManagerClass();

    // -- CLASSES -- //
    // ++ OBJECTS ++ //

    abstract public function GetDatabase();
    public function GetSessionTable() {
	$db = $this->GetDatabase();
	$sClass = $this->GetSessionsClass();
	return $db->MakeTableWrapper($sClass);
    }
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
	} catch(Throwable $e) {
	    $code = $e->getCode();
	    $sNative = ($code==0)?'':"<b>Native error $code</b> caught:";
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
    // ++ CONFIGURATION ++ //
    
    public function GetAppKeyString() {
	return KS_APP_KEY;
    }
    
    // -- CONFIGURATION -- //
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
	//return 'fctUserSessions';
	return KS_CLASS_ADMIN_USER_SESSIONS;
    }
    protected function GetUsersClass() {
	return KS_CLASS_ADMIN_USER_ACCOUNTS;
    }
    private $sClassEvents='fctEventPlex';	// default/base
    protected function GetEventsClass() {
	return $this->sClassEvents;
    }
    public function SetEventsClass($s) {
	$this->sClassEvents = $s;
    }
    protected function GetEventsDoneClass() {
	return 'fctSubEvents_done';
    }
    protected function GetDropinManagerClass() {
	return 'fcDropInManager';
    }

    // -- CLASS NAMES -- //
    // ++ TABLES ++ //

    // TODO 2017-06-11: rename to GetUserTable()
    public function UserTable($id=NULL) {
	$db = $this->GetDatabase();
	return $db->MakeTableWrapper($this->GetUsersClass(),$id);
    }
    // TODO 2017-06-11: rename to GetEventTable()
    public function EventTable($id=NULL) {
	$db = $this->GetDatabase();
	return $db->MakeTableWrapper($this->GetEventsClass(),$id);
    }
    // TODO 2017-06-11: rename to GetEventTable_Done() 
    public function EventTable_Done() {
	$db = $this->GetDatabase();
	return $db->MakeTableWrapper($this->GetEventsDoneClass());
    }

    // -- TABLES -- //
    // ++ STATUS ++ //

    /*----
      RETURNS: TRUE iff the user is logged in
    */
    public function UserIsLoggedIn() {
	return $this->GetSessionRecord()->UserIsLoggedIn();
    }

    // -- STATUS -- //
    // ++ LOGGING ++ //

    public function CreateEvent($sCode,$sText,array $arData=NULL) {
	$t = $this->EventTable();
	$id = $t->CreateBaseEvent($sCode,$sText,$arData);
	return $id;
    }
    public function FinishEvent($idEvent,$sState,$sText=NULL,array $arData=NULL) {
	$t = $this->EventTable_Done();
	$t->CreateRecord($idEvent,$sState,$sText,$arData);
    }

    // -- LOGGING -- //
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
    public function LoginName() {
	if ($this->UserIsLoggedIn()) {
	    return $this->GetUserRecord()->LoginName();
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

	/* 2017-03-18 old
	$idEv = fcApp::Me()->EventTable()->GetLastID();
	$rcEvSub = $this->EventTable_Done();
	$rcEvSub->CreateRecord($idEv,KS_EVENT_FERRETERIA_EMAIL_SENT);
	*/
	$arData = array(
	    'to-addr'	=> $sToAddr,
	    'to-name'	=> $sToName,
	    'subject'	=> $sSubj,
	    'message'	=> $sMsg
	  );
	//$idEv = $this->EventTable()->CreateBaseEvent(KS_EVENT_FERRETERIA_EMAIL_SENT,'admin email sent',$arData);
	$this->CreateEvent(KS_EVENT_FERRETERIA_EMAIL_SENT,'admin email sent',$arData);
	
	return $ok;
    }
    
    // -- EMAIL -- //

}
/*----
  PURPOSE: easy access to objects provided by fcApp descendants
  NOTES:
    * 2017-05-01 This is looking increasingly unnecessary, but for now I'm just commenting out EventTable()
      because it conflicts with another trait. TODO: find out how extensively this is actually used, and
      possibly kill it.
    * 2016-10-23 This doesn't seem very elegant. I want to make it a static class,
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
    /* 2017-05-01 conflicts with ftLoggableObject
    protected function EventTable() {
	return $this->AppObject()->EventTable();
    }
    */
}
