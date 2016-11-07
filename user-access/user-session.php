<?php
/*
  PURPOSE: for managing web sessions
  CONSTANTS NEEDED:
    KS_USER_SESSION_KEY : the name of the cookie where the session key will be stored
  HISTORY:
    2013-10-25 stripped Session classes out of VbzCart shop.php for use in ATC project
    2013-11-09 backported improved Session classes back into user-session.php
    2016-04-03 moved RandomString() to fcString::Random().
*/
/*%%%%
  PURPOSE: Handles the table of user sessions
*/
class fctUserSessions extends fcTable_keyed_single_standard {
    use ftFrameworkAccess;

    // ++ CEMENTING ++ //
    
    protected function TableName() {
	return 'user_session';
    }
    protected function SingularName() {
	return KS_CLASS_USER_SESSION;
    }
    
    // -- CEMENTING -- //
    // ++ OVERRIDES ++ //
    
    protected function InitVars() {
	$this->ClearSession();
	$this->ClearCookieValue();
    }

    
    private function Create() {
	$rcSess = $this->SpawnRecordset();
	$rcSess->InitNew();
	$rcSess->Create();
	return $rcSess;
    }

    // ++ COOKIE VALUE ++ //

      //++remote++//
    
    /*----
      ACTION: tosses the session cookie to the browser
      RETURNS: TRUE iff successful
      NOTES:
	* HTTP only sets the cookie when the page is reloaded.
	  Because of this, and because $_COOKIE is read-only,
	  we have to set a local variable when we create a new
	  session so that subsequent requests during the same
	  page-load don't think it hasn't been created yet,
	  and end up creating multiple records for each new session.
	  (It was creating 3 new records and using the last one.)
    */
    protected function ThrowCookie($sSessKey) {
	$sVal = $sSessKey;
	$sDomain = KS_COOKIE_DOMAIN;
	//$ok = setcookie(KS_USER_SESSION_KEY,$sVal,0,'/',$sDomain);
	$ok = setcookie(KS_USER_SESSION_KEY,$sVal,0,'/');
	if ($ok) {
	    // store new cookie value (see NOTES above)
	    $this->CookieValue($sVal);
	}
	return $ok;
    }

      //--remote--//
      //++local++//

    private $sCookieVal;
    protected function ClearCookieValue() {
	$this->sCookieVal = NULL;
    }
    protected function CookieValue($sValue=NULL) {
	if (!is_null($sValue)) {
	    $this->sCookieVal = $sValue;
	}
	return $this->sCookieVal;
    }
    
      //--local--//

    // -- COOKIE VALUE -- //
    // ++ CURRENT RECORD ++ //
    
    private $rcSess;
    public function ClearSession() {
	$this->rcSess = NULL;
    }
    // 2016-10-31 PROTECTING until need for public access is known
    protected function SetCurrentRecord(fcrUserSession $rcSess) {
	$this->rcSess = $rcSess;
    }
    // 2016-10-31 PROTECTING until need for public access is known
    protected function GetCurrentRecord() {
	return $this->rcSess;
    }
    public function HasCurrentRecord() {
	return !is_null($this->GetCurrentRecord());
    }
    /*----
      ACTION: returns a Session object for the current connection, whether or not one already exists
	* gets session key and auth from cookie
	* if session object exists, tries to reuse it
      HISTORY:
	2012-10-13 Added caching of the Session object to avoid creating multiple copies.
	2015-06-23 Fixed: Was throwing an error if there was no session key; it should just make a new session.
    */
    public function GetCurrent() {
	$okSession = FALSE;

	$sSessKey = NULL;
	if (array_key_exists(KS_USER_SESSION_KEY,$_COOKIE)) {
	    $sSessKey = $_COOKIE[KS_USER_SESSION_KEY];
	} elseif(!is_null($this->CookieValue())) {
	    $sSessKey = $this->CookieValue();
	}

	$doNew = TRUE;
	if (!is_null($sSessKey)) {
	    // if we already have a session key, see if the session is still valid
	    list($idRecd,$sToken) = explode('-',$sSessKey);
	    if (empty($idRecd)) {
		// TODO: this should just silently log a possible hacking attempt
		throw new exception("Input Error: Received session cookie has no ID. Session Key=[$sSessKey]");
	    }
	    // TODO: look up the ID and token in the session table

	    if ($this->HasCurrentRecord()) {
		$rcThis = $this->GetCurrentRecord();
		$idThis = $rcThis->GetKeyValue();
		if ($idThis == $idRecd) {
		    $doNew = FALSE;
		}
	    }
	    if ($doNew) {
		$rcRecd = $this->GetRecord_forKey($idRecd);
		$this->SetCurrentRecord($rcRecd);
		$rcThis = $rcRecd;
	    }

	    $okSession = $rcThis->IsValidNow($sToken);	// do session's creds match browser's creds?
	}

	if (!$okSession) {
	  // no current/valid session, so make a new one:
	    $rcThis = $this->Create();
	    if ($doNew) {
		$this->AppObject()->AddMessage('You have to add items to your cart before you can check out.');
	    } else {
		$this->AppObject()->AddMessage('Your existing session was dropped because your fingerprint changed.');
	    }
	  // add new record for the new session:
	    $this->SetCurrentRecord($rcThis);
	  // generate key from the new session:
	    $sSessKey = $rcThis->SessKey();
	    $ok = $this->ThrowCookie($sSessKey);
	    if (!$ok) {
		throw new exception('Internal Error: Cookie could not be sent for session key "'.$sSessKey.'".');
		// if this happens, then some output was already sent, preventing the cookie.
	    }
	} else {
	    //echo 'SESSION IS FINE, THANKS.<br>';
	}
	return $this->GetCurrentRecord();
    }
    /*----
      ACTION: get the session record we *should* be using, based on current client specs
      HISTORY:
	2016-10-31 Adapted from fcApp::GetSessionRecord() -- couldn't figure out why it needed to be there,
	  and needing to access recordset's GetTableWrapper() was a problem because that's protected now.
    */
    public function MakeActiveRecord() {
	if (empty($this->rcSess)) {
	    $this->rcSess = $this->GetCurrent();
	}
	if (!$this->rcSess->HasRows()) {
	    //throw new exception('Internal error: Loaded Session recordset has no rows.');
/* 2016-03-24 I'm guessing that this happens when there is a partial match between the browser cookie
    and a Session record -- e.g. browser has gone to a new version, so has the cookie but the fingerprint
    doesn't match. In that case, we should just end the Session and start over.
*/
	    $this->ClearSession();
	    $this->rcSess = $this->GetCurrent();
	}
	return $this->rcSess;
    }

    
    // -- CURRENT RECORD -- //

}
/*::::
  PURPOSE: Represents a single user session record
*/
class fcrUserSession extends fcRecord_standard {
    use ftVerbalObject;

    protected $rcUser;

    // ++ SETUP ++ //

/*
    public function __construct(clsDatabase $iDB=NULL, $iRes=NULL, array $iRow=NULL) {
	parent::__construct($iDB,$iRes,$iRow);
    }
*/
    protected function InitVars() {
	$this->ClientRecord_clear();
	$this->rcUser = NULL;
    }
    public function InitNew() {
	$this->ClearFields();
	$this->SetFieldValues(array(
	  'Token'	=> fcString::Random(31),
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
	return $this->GetConnection()->MakeTableWrapper($this->ClientsClass(),$id);
    }
    protected function UserTable($id=NULL) {
	return $this->GetConnection()->MakeTableWrapper($this->UsersClass(),$id);
    }

    // -- DATA TABLES -- //
    // ++ STATUS/FIELD ACCESS ++ //

    protected function SetClientID($id) {
	return $this->SetFieldValue('ID_Client',$id);
    }
    protected function GetClientID() {
	return $this->GetFieldValue('ID_Client');
    }
    public function GetUserID() {
	return $this->GetFieldValue('ID_User');
    }
    protected function Token() {
	return $this->GetFieldValue('Token');
    }
    public function UserIsLoggedIn() {
	return $this->GetUserID() > 0;	// can be 0 or NULL if no user
    }
    /*---
      NOTE: As of 2016-11-03, this will return the same result as UserIsLoggedIn() because
	we use UserID > 0 as a way of detecting whether the user is logged in -- but that
	might change. This function will always return a boolean which answers the question
	"do we know the user's ID?". That might conceivably different if, say, we want to
	access some non-sensitive information about the user such as layout preferences.
	Some sites will recognize users in that sort of way even when they are logged out.
	I'm not sure if this is good security practice, but it's a possibility which
	should be allowed for in the API even if Ferreteria doesn't currently support it.
    */
    public function UserIsKnown() {
	return $this->GetUserID() > 0;	// for now, user ID is cleared from session when user is logged out
    }
    /*-----
      RETURNS: TRUE if the stored session credentials match current reality (browser's credentials)
      PUBLIC so fctUserSessions can call it
      HISTORY:
	2015-04-26 This sometimes comes up with no record -- I'm guessing that happens when a matching
	  Session isn't found. (Not sure why this isn't detected elsewhere.)
	2016-04-03 Removed commented-out section.
    */
    public function IsValidNow($iKey) {
	if ($this->IsNew()) {
	    $ok = FALSE;
	} else {
	    $ok = ($this->Token() == $iKey);
	    if ($ok) {
		$rcClient = $this->ClientRecord_asSet();
		$ok = $rcClient->IsValidNow();
	    }
	}
	return $ok;
    }
    public function SessKey() {
	if ($this->IsNew()) {
	    throw new exception('Trying to generate a session key when session record has no ID.');
	}
	return $this->GetKeyValue().'-'.$this->Token();
    }

    // -- STATUS/FIELD ACCESS -- //
    // ++ ACTIONS ++ //

    /*----
      ACTION: Make sure the Client ID is set correctly for the current browser client
	If not set or doesn't match, get a new one.
    */
    protected function Make_ClientID() {
	$rcCli = $this->ClientRecord_needed();
	$this->SetClientID($rcCli->GetKeyValue());
	return $this->GetClientID();
    }
    /*----
      ACTION: Create a new session record from the current memory data.
    */
    public function Create() {
	$db = $this->GetConnection();
	$ar = array(
	  'ID_Client'	=> $db->Sanitize_andQuote($this->Make_ClientID()),
	  'ID_User'	=> $db->Sanitize_andQuote($this->GetUserID()),
	  'Token'	=> $db->Sanitize_andQuote($this->Token()),
	  'WhenCreated'	=> 'NOW()'
	  );
	$idNew = $this->GetTableWrapper()->Insert($ar);
	if ($idNew === FALSE) {
	    throw new exception('Could not create new Session record.');
	}
	$this->SetKeyValue($idNew);
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
    // TODO: convert this to use UpdateArray() and Save().
    public function SaveUserID($idUser) {
	$ar = array('ID_User'=>$idUser);
	$this->Update($ar);			// save user ID to database
	$this->SetFieldValue('ID_User',$idUser);	// update it in RAM as well
    }

    // -- ACTIONS -- //
    // ++ CALCULATIONS ++ //

    /*----
      RETURNS: User's login name, or NULL if user not logged in
    */
    public function UserString() {
	if ($this->UserIsLoggedIn()) {
	    return $this->UserRecord()->UserName();
	} else {
	    return NULL;
	}
    }
    /*----
      RETURNS: User's email address, or NULL if user not logged in
    */
    public function UserEmailAddress() {
	if ($this->UserIsLoggedIn()) {
	    return $this->UserRecord()->EmailAddress();
	} else {
	    return NULL;
	}
    }

    // -- CALCULATIONS -- //
    // ++ DATA RECORD ACCESS ++ //

    private $rcClient;
    protected function ClientRecord_clear() {
	$this->rcClient = NULL;
    }
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
	    // if there's no client record, see if there's a client ID:
	    $idCli = $this->GetClientID();
	    if (!is_null($idCli)) {
		// there's a client ID, so get the client record from that:
		$this->rcClient = $this->ClientTable($idCli);
	    }
	}
	// If nothing worked, then rcClient is NULL and we just return that.
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
	$rc = $this->ClientRecord_current();
	if (is_null($rc)) {
	    $rc = $this->ClientTable()->MakeRecord_forCRC();
	    /* 2016-10-27 old code
	    $tClients = $this->ClientTable();
	    $this->rcClient = $tClients->SpawnRecordset();
	    $this->rcClient->InitNew();
	    $this->rcClient->Build();
	    */
	    $this->rcClient = $rc;
	    $this->SetClientID($rc->GetKeyValue());
	}
	return $this->rcClient;
    }
    /*----
      RETURNS: User record object, if the session has a user; NULL otherwise
    */
    public function UserRecord() {
	if (is_null($this->rcUser)) {
	    $tUser = $this->UserTable();
	    if ($this->UserIsLoggedIn()) {
		$this->rcUser = $tUser->GetRecord_forKey($this->GetUserID());
	    } else {
		$this->rcUser = NULL;
	    }
	}
	return $this->rcUser;
    }
    // TODO: is this ever *supposed* to be called with oUser=NULL? If so, why? Document.
    public function SetUserRecord(clsUserAcct $oUser=NULL) {
	$doChg = FALSE;
	if (is_null($oUser)) {
	    $idNew = NULL;
	} else {
	    $idNew = $oUser->GetKeyValue();
	}
	if (is_null($this->rcUser)) {
	    $doChg = TRUE;
	} else {
	    if (is_null($oUser)) {
		throw new exception('This should not happen.');
	    }
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
	    $idNew = $oUser->GetKeyValue();
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
		if ($this->UserIsLoggedIn()) {
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
 UTILITY FUNCTIONS - moved to fcString::.
*/
/*
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
}//*/
