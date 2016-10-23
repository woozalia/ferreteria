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
*/
abstract class clsApp {
    use ftVerbalObject;

    static protected $me;
    static public function Me(clsApp $oApp=NULL) {
	if (!is_null($oApp)) {
	    self::$me = $oApp;
	}
	return self::$me;
    }
    public function __construct() {
	self::Me($this);			// there can be only one
    }
    abstract public function Go();
    abstract public function Session();
    abstract public function Skin();
    abstract public function Page(clsPage $iObj=NULL);
    //abstract public function Data(clsDatabase_abstract $iObj=NULL);
    abstract public function GetMainDB();
    abstract public function User();
    //abstract public function BaseURL_abs();
    //abstract public function BaseURL_rel();
}
abstract class fcAppStandard extends clsApp {
    private $oPage;
    private $oSkin;

    // ++ MAIN ++ //

    public function Go() {
	$db = $this->GetMainDB();
	$db->Open();
	if ($db->isOk()) {
	    $this->Page()->DoPage();
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
	return 'clsSysEvents';
    }

    // -- CLASS NAMES -- //
    // ++ TABLES ++ //

    protected function SessionTable() {
	return $this->GetDataFactory()->Make($this->SessionsClass());
    }
    public function Users($id=NULL) {
	$o = $this->GetDataFactory()->Make($this->UsersClass(),$id);
	return $o;
    }

    // -- TABLES -- //
    // ++ RECORDS/OBJECTS ++ //

    public function Page(clsPage $obj=NULL) {
	if (!is_null($obj)) {
	    $this->oPage = $obj;
	    $obj->App($this);
	    //$obj->Doc($oDoc);
	}
	return $this->oPage;
    }
    public function Skin(clsSkin $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->oSkin = $iObj;
	}
	return $this->oSkin;
    }
    /*
    private $oData;
    public function Data(clsDatabase_abstract $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->oData = $iObj;
	}
	return $this->oData;
    }*/
    private $rcSess;
    public function Session() {
	if (empty($this->rcSess)) {
	    $tSess = $this->SessionTable();
	    $this->rcSess = $tSess->GetCurrent();
	} else {
	    $tSess = $this->rcSess->Table();
	}
	if (!$this->rcSess->HasRows()) {
	    //throw new exception('Internal error: Loaded Session recordset has no rows.');
/* 2016-03-24 I'm guessing that this happens when there is a partial match between the browser cookie
    and a Session record -- e.g. browser has gone to a new version, so has the cookie but the fingerprint
    doesn't match. In that case, we should just end the Session and start over.
*/
	    $tSess->ClearSession();
	    $this->rcSess = $tSess->GetCurrent();
	}
	return $this->rcSess;
    }
    public function UncacheSession() {
	$this->rcSess = NULL;
    }
    private $rcUser;
    public function User() {
	if (empty($this->rcUser)) {
	    $this->rcUser = $this->Session()->UserRecord();
	}
	return $this->rcUser;
    }
    // 2016-05-22 It seems like a good idea to have this, to pass to record-creation methods that ask for it. (using it in cart.logic)
    public function UserID() {
	if ($this->UserKnown()) {
	    return $this->Session()->UserID();
	} else {
	    return NULL;
	}
    }
    /*----
      RETURNS: TRUE iff the user is logged in
    */
    public function UserKnown() {
	// 2016-05-22 why doesn't Session have a UserKnown method?
	return (!is_null($this->Session()->UserRecord()));
    }
    /*----
      RETURNS: User login string, or NULL if user not logged in
      HISTORY:
	2014-07-27 Written because this seems to be where it belongs.
	  May duplicate functionality in Page object. Why is that there?
    */
    public function UserName() {
	if ($this->UserKnown()) {
	    return $this->User()->UserName();
	} else {
	    return NULL;
	}
    }
    public function Events($id=NULL) {
	return $this->GetDataFactory()->Make($this->EventsClass(),$id);
    }

    // -- RECORDS/OBJECTS -- //
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

