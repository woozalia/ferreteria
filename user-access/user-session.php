<?php
/*
  PURPOSE: for managing web sessions
  CONSTANTS NEEDED:
    KS_USER_SESSION_KEY : the name of the cookie where the session key will be stored
  HISTORY:
    2013-10-25 stripped Session classes out of VbzCart shop.php for use in ATC project
    2013-11-09 backported improved Session classes back into user-session.php
*/
/* ===================
  CLASS: clsUserSessions
  PURPOSE: Collectively handles user sessions
*/
class clsUserSessions extends clsTable {
    protected $SessKey;
    private $sCookieVal;
    private $rcSess;

    const TableName='user_session';

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name(self::TableName);
	  $this->KeyName('ID');
	  $this->ClassSng('clsUserSession');
	$this->rcSess = NULL;
	$this->sCookieVal = NULL;
    }
    private function Create() {
	$rcSess = $this->SpawnItem();
	$rcSess->InitNew();
	$rcSess->Create();
	return $rcSess;
    }
    /*----
      RETURNS: the session's cookie value, if any
    */
    protected function GetCookie() {
	$strSessKey = NULL;
	if (array_key_exists(KS_USER_SESSION_KEY,$_COOKIE)) {
	    $strSessKey = $_COOKIE[KS_USER_SESSION_KEY];
	} elseif(!is_null($this->sCookieVal)) {
	    $strSessKey = $this->sCookieVal;
	}
	return $strSessKey;
    }
    /*----
      ACTION: sets the session's cookie value
      RETURNS: TRUE iff successful
      TODO: should probably unset $_COOKIE[key] if $iSessKey is NULL
	(tidy up after ourselves)
      NOTES:
	* HTTP only sets the cookie when the page is reloaded.
	  Because of this, and because $_COOKIE is read-only,
	  we have to set a local variable when we create a new
	  session so that subsequent requests during the same
	  page-load don't think it hasn't been created yet,
	  and end up creating multiple records for each new session.
	  (It was creating 3 new records and using the last one.)
    */
    protected function SetCookie($iSessKey=NULL) {
	//if (!is_null($iSessKey)) {
	    $this->SessKey = $iSessKey;
	//}
	if (defined('KS_SITE_DOMAIN')) {
	    $sDomain = KS_SITE_DOMAIN;
	} else {
	    $sDomain = $_SERVER['SERVER_NAME'];
	}
	$sVal = $this->SessKey;
	$ok = setcookie(KS_USER_SESSION_KEY,$sVal,0,'/','.'.$sDomain);
	if ($ok) {
	    // store new cookie value (see NOTES above)
	    $this->sCookieVal = $sVal;
	}
	return $ok;
    }
    /*----
      ACTION:
	* gets session key and auth from cookie
	* if session object exists, tries to reuse it
      HISTORY:
	2012-10-13 Added caching of the session object to avoid creating multiple copies.
    */
    public function GetCurrent() {
	$okSession = FALSE;
	$strSessKey = $this->GetCookie();
	$doNew = TRUE;
	if (is_null($strSessKey)) {
	} else {
	    list($ID,$strToken) = explode('-',$strSessKey);
	    if (!is_null($this->rcSess)) {
		if ($this->rcSess->KeyValue() == $ID) {
		    $doNew = FALSE;
		}
	    }
	    if ($doNew) {
		$this->rcSess = $this->GetItem($ID);
	    }

	    $okSession = $this->rcSess->IsValidNow($strToken);	// do session's creds match browser's creds?
	}
	if (!$okSession) {
	  // no current/valid session, so make a new one:
	    // add new record...
	    $this->rcSess = $this->Create();
	    // generate new session key
	    $strSessKey = $this->rcSess->SessKey();
	    $ok = $this->SetCookie($strSessKey);
	    if (!$ok) {
		throw new exception('Internal Error: Cookie could not be sent for session key "'.$strSessKey.'".');
		// if this happens, then some output was already sent, preventing the cookie.
	    }
	}
	return $this->rcSess;
    }
}
/* ===================
  CLASS: clsUserSession
  PURPOSE: Represents a single user session record
*/
class clsUserSession extends clsDataSet {
    private $rcClient;
    protected $rcUser;

    // ++ SETUP ++ //

/*
    public function __construct(clsDatabase $iDB=NULL, $iRes=NULL, array $iRow=NULL) {
	parent::__construct($iDB,$iRes,$iRow);
    }
*/
    protected function InitVars() {
	$this->rcClient = NULL;
	$this->rcUser = NULL;
    }
    public function InitNew() {
	$this->Values(array(
	  'Token'	=> RandomString(31),
	  'ID_Client'	=> NULL,
	  'ID_User'	=> NULL,
	  'WhenCreated'	=> NULL		// hasn't been created until written to db
	  ));
	$this->ClientRecord_needed();	// connect to client record (create if needed)
    }

    // -- SETUP -- //
    // ++ CLASS NAMES ++ //

    protected function ClientsClass() {
	return 'clsUserClients';
    }
    protected function UsersClass() {
	return 'clsUserAccts';
    }

    // -- CLASS NAMES -- //
    // ++ DATA TABLES ++ //

    protected function ClientTable($id=NULL) {
	return $this->Engine()->Make($this->ClientsClass(),$id);
    }
    protected function UserTable($id=NULL) {
	return $this->Engine()->Make($this->UsersClass(),$id);
    }

    // -- DATA TABLES -- //
    // ++ ACTIONS ++ //

    /*----
      ACTION: Create a new session record from the current memory data.
    */
    public function Create() {
	$ar = array(
	  'ID_Client'	=> SQLValue($this->Value('ID_Client')),
	  'ID_User'	=> SQLValue($this->Value('ID_User')),
	  'Token'	=> SQLValue($this->Token),
	  'WhenCreated'	=> 'NOW()'
	  );
	$this->ID = $this->Table()->Insert($ar);
	$rcClient = $this->ClientRecord_needed();
	if (!$rcClient->isNew()) {
	    $rcClient->Stamp();
	}
    }
    /*----
      ACTION: Attempts to log the user in with the given credentials.
      RETURNS: user object if successful, NULL otherwise.
    */
    public function UserLogin($iUser,$iPass) {
	//$tUsers = $this->Engine()->Users();
	//$tUsers = $this->Engine()->App()->Users();
	$tUsers = $this->UserTable();
	$oUser = $tUsers->Login($iUser,$iPass);
	$this->SetUserRecord($oUser);		// set user for this session
    }
    /*----
      ACTION: Logs the current user out. (Clears ID_User in session record.)
    */
    public function UserLogout() {
	$this->ClearValue('ID_User');
	$arUpd = array('ID_User'=>'NULL');
	$this->Update($arUpd);
    }
    public function SaveUserID($idUser) {
	$ar = array('ID_User'=>$idUser);
	$this->Update($ar);			// save user ID to database
	$this->Value('ID_User',$idUser);	// update it in RAM as well
    }

    // -- ACTIONS -- //
    // ++ STATUS/FIELD ACCESS ++ //

    protected function ClientID($id=NULL) {
	return $this->Value('ID_Client',$id);
    }
    public function UserID() {
	return $this->Value('ID_User');
    }
    protected function Token() {
	return $this->Value('Token');
    }
    /*----
      NOTE: I can't think of any circumstances under which $this->HasValue('ID_User') would be false.
    */
    public function HasUser() {
	return !is_null($this->UserID());
    }
    /*-----
      RETURNS: TRUE if the stored session credentials match current reality (browser's credentials)
    */
    public function IsValidNow($iKey) {
	$ok = ($this->Token == $iKey);
	if ($ok) {
	    $rcClient = $this->ClientRecord_asSet();
	    $ok = $rcClient->IsValidNow();
	    /* This doesn't make sense. We need to ask the client if it matches the browser fingerprint.
	    $idClientWas = $this->ClientID();
	    $idClientNow = $this->ClientID();
	    if ($idClientWas != $idClientNow) {
		// not an error, but could indicate a hacking attempt -- so log it, flagged as severe:
		$this->Engine()->LogEvent(
		  'session.valid',
		  'KEY='.$iKey,' OLD-CLIENT='.$idClientWas.' NEW-CLIENT='.$idClientNow,
		  'stored session client mismatch','XCRED',FALSE,TRUE);
		$ok = FALSE;
	    } */
	}
	return $ok;
    }
    public function SessKey() {
	return $this->KeyValue().'-'.$this->Token();
    }

    // -- STATUS/FIELD ACCESS -- //
    // ++ CALCULATIONS ++ //

    /*----
      RETURNS: User's login name, or NULL if user not logged in
    */
    public function UserString() {
	if ($this->HasUser()) {
	    return $this->UserRecord()->UserName();
	} else {
	    return NULL;
	}
    }
    /*----
      RETURNS: User's email address, or NULL if user not logged in
    */
    public function UserEmailAddress() {
	if ($this->HasUser()) {
	    return $this->UserRecord()->EmailAddress();
	} else {
	    return NULL;
	}
    }

    // -- CALCULATIONS -- //
    // ++ DATA RECORD ACCESS ++ //

    /*----
      HISTORY:
	2014-09-18 Creating multiple ClientRecord() methods for different circumstances:
	  ClientRecord_asSet() - the client record last used for the session
	  ClientRecord_current() - the current client record; NULL if it does not match browser fingerprint
	  ClientRecord_needed() - a client record that can be used; creates new one if current record
	    does not match browser fingerprint
    */
    protected function ClientRecord_asSet() {
	if (is_null($this->rcClient)) {
	    $idCli = $this->ClientID();
	    if (!is_null($idCli)) {
		$this->rcClient = $this->ClientTable($idCli);
	    }
	}
	return $this->rcClient;
    }
    protected function ClientRecord_current() {
	$rcCli = $this->ClientRecord_asSet();
	if (!is_null($rcCli)) {
	    if (!$rcCli->IsValidNow()) {
		$this->rcClient = NULL;	// doesn't match current client; need a new one
	    }
	}
	return $this->rcClient;
    }
    protected function ClientRecord_needed() {
// if the session's client record matches, then load the client record; otherwise create a new one:
	$rcCli = $this->ClientRecord_current();
	if (is_null($rcCli)) {
	    $tClients = $this->ClientTable();
	    $this->rcClient = $tClients->SpawnItem();
	    $this->rcClient->InitNew();
	    $this->rcClient->Build();
	    $this->ClientID($this->rcClient->KeyValue());
	}
	return $this->rcClient;
    }
    /*----
      RETURNS: User record object, if the session has a user; NULL otherwise
    */
    public function UserRecord() {
	if (is_null($this->rcUser)) {
	    $tUser = $this->UserTable();
	    if ($this->HasUser()) {
		$this->rcUser = $tUser->GetItem($this->Value('ID_User'));
	    } else {
		$this->rcUser = NULL;
	    }
	}
	return $this->rcUser;
    }
    public function SetUserRecord(clsUserAcct $oUser=NULL) {
	$doChg = FALSE;
	$idNew = $oUser->KeyValue();
	if (is_null($this->rcUser)) {
	    $doChg = TRUE;
	} else {
	    $idOld = $this->Value('ID_User');
	    if ($idOld != $idNew) {
		$doChg = TRUE;
	    } elseif (get_class($this->rcUser) != get_class($oUser)) {
		$doChg = TRUE;
	    }
	}
	if ($doChg) {
	    $this->rcUser = $oUser;
	    // UPDTE local & saved ID_User
	    $this->SaveUserID($idNew);
	}
    }
    /*----
      RETURNS: User record object, if the session has a user; NULL otherwise
      ASSUMES:
	If $this->objUser is set, it matches ID_User
    */
    public function UserObj(clsUserAcct $oUser=NULL) {
	throw new exception('UserObj() is deprecated; call UserRecord or SetUserRecord()');
	if (!is_null($oUser)) {

	    // we are SETTING the user
	    $doChg = FALSE;
	    $idNew = $oUser->KeyValue();
	    if (is_null($this->objUser)) {
		$doChg = TRUE;
	    } else {
		$idOld = $this->Value('ID_User');
		if ($idOld != $idNew) {
		    $doChg = TRUE;
		} elseif (get_class($this->objUser) != get_class($oUser)) {
		    $doChg = TRUE;
		}
	    }
	    if ($doChg) {
		$this->objUser = $oUser;
		// UPDTE local & saved ID_User
		$this->SaveUserID($idNew);
	    }
	} else {
	    // we are trying to RETRIEVE the user
	    if (empty($this->objUser)) {
		$tUser = $this->UserTable();
		echo 'USER TABLE CLASS: ['.get_class($tUser).']<br>';
		if ($this->HasUser()) {
		    $this->objUser = $tUser->GetItem($this->Value('ID_User'));
		} else {
		    $this->objUser = NULL;
		}
	    }
	}
	return $this->objUser;
    }

    // -- DATA RECORD ACCESS -- //

}

/* ===============
 UTILITY FUNCTIONS
*/
function RandomString($iLen) {
    $out = '';
    for ($i = 0; $i<$iLen; $i++) {
	$n = mt_rand(0,61);
	$out .= CharHash($n);
    }
    return $out;
}
function CharHash($iIndex) {
    if ($iIndex<10) {
	return $iIndex;
    } elseif ($iIndex<36) {
	return chr($iIndex-10+ord('A'));
    } else {
	return chr($iIndex-36+ord('a'));
    }
}
