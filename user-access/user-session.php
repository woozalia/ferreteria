<?php
/*
  PURPOSE: for managing web sessions
  CONSTANTS NEEDED:
    KS_USER_SESSION_KEY : the name of the cookie where the session key will be stored
  INTERNAL RULES:
    * Get the session cookie. (If no cookie, we're not logged in.)
    * Load the session record indicated by the cookie.
    * Check the session record to make sure it matches the current client.
    * If it does, the session's user ID is logged in; otherwise not.
    * A session record is also created for anonymous users.
  HISTORY:
    2013-10-25 stripped Session classes out of VbzCart shop.php for use in ATC project
    2013-11-09 backported improved Session classes back into user-session.php
    2016-04-03 moved RandomString() to fcString::Random().
*/
/*::::
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
    }

    // -- OVERRIDES -- //
    // ++ STATUS ++ //

    /*----
      MEANING: indicates whether a lack of session record is because the user was never logged in (FALSE)
	or because they were logged in but something changed and now the client can't be trusted (TRUE).
    */
    private $isMismatch;
    public function GetStatus_SessionMismatch() {
	return $this->isMismatch;
    }
    protected function SetStatus_SessionMismatch($b) {
	$this->isMismatch = $b;
    }
    
    // ++ STATUS ++ //
    // ++ COOKIE ++ //

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
      TODO: Determine if there's any value in explicitly setting the cookie domain.
    */
    protected function ThrowCookie($sSessKey) {
	$sVal = $sSessKey;
	//$ok = setcookie(KS_USER_SESSION_KEY,$sVal,0,'/',KS_COOKIE_DOMAIN);
	$ok = setcookie(KS_USER_SESSION_KEY,$sVal,0,'/');
	if ($ok) {
	    // store new cookie value (see NOTES above)
	    $this->SetCookieValue($sVal);
	}
	return $ok;
    }

      //--remote--//
      //++local++//

    private $sCookieVal;
    protected function SetCookieValue($sValue) {
	$this->sCookieVal = $sValue;
    }
    /*----
      RULES:
	* If local value is set, return that.
	* Otherwise, get actual cookie, set local value from it, and return that.
	In other words: if local value is set, that bypasses checking for an actual cookie.
	  This assumes that the cookie will never get set later on during a page-load,
	  which seems like a reasonable assumption. (Note: the COOKIE array is effectively read-only.)
    */
    protected function GetCookieValue() {
	if (!isset($this->sCookieVal)) {
	    if (array_key_exists(KS_USER_SESSION_KEY,$_COOKIE)) {
		$this->SetCookieValue($_COOKIE[KS_USER_SESSION_KEY]);
	    } else {
		$this->SetCookieValue(NULL);
	    }
	}
	return $this->sCookieVal;
    }
    
      //--local--//

    // -- COOKIE -- //
    // ++ CURRENT RECORD ++ //
    
    private $rcSess;
    // 2016-11-14 PROTECTING because public access is apparently no longer needed
    protected function ClearSession() {
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
	* if session object has already been loaded, assume it has been validated and return it
	* if not, gets session key and auth from cookie
      ASSUMES: session recordset is either NULL or a valid single record (and will set it accordingly)
	...therefore if there is one loaded already, we can assume it has been validated against the current client.
      HISTORY:
	2012-10-13 Added caching of the Session object to avoid creating multiple copies.
	2015-06-23 Fixed: Was throwing an error if there was no session key; it should just make a new session.
	2016-11-14 
	  * Moved cookie fetching/storage into GetCookieValue().
	  * Replacing screen output with public status methods.
	  * Rewriting to make more logical sense.
    */
    public function MakeActiveRecord() {
	// check to see if we've already loaded a session record
	if (!$this->HasCurrentRecord()) {
	    // no session record yet, so let's get one
	    $sSessKey = $this->GetCookieValue();	// get the session cookie
	    if (is_null($sSessKey)) {
		// browser has no session
		$ok = FALSE;
	    } else {
		list($idRecd,$sToken) = explode('-',$sSessKey);
		if (!is_numeric($idRecd)) {
		    throw new fcSilentException("Ferreteria Input Error: Malformed session cookie received. Hacking attempt? Value: [$sSessKey]");
		}
		
		// try to retrieve requested session record
		$rcRecd = $this->GetRecord_forKey($idRecd);
		$nRows = $rcRecd->RowCount();
		if ($nRows == 0) {
		    // no session found with that ID; create a new one
		    $ok = FALSE;
		} elseif ($nRows == 1) {
		    // session found - verify that it matches browser fingerprint
		    $ok = $rcRecd->IsValidNow($sToken);	// do requested session's creds match browser's creds?
		} else {
		    // This shouldn't ever happen because ID uniqueness is supposed to be enforced by the table design:
		    throw new fcSilentException("Ferreteria Data Integrity Error: $nRows session records found for ID=$idRecd.");
		    $ok = FALSE;
		}
		$this->SetStatus_SessionMismatch(!$ok);	// if found session not valid (or not found), then there was a session mismatch
	    }

	    if ($ok) {
		// requested session is valid for the current client, so we can use it
		$rcSess = $rcRecd;
	    } else {
		// need a new session
		$rcSess = $this->SpawnRecordset();
		$rcSess->InitNew();
		$rcSess->CreateRecord();
		$sSessKey = $rcSess->SessKey();
		$ok = $this->ThrowCookie($sSessKey);
		//$ok = $rcSess->CreateRecord_andThrowCookie();
		if (!$ok) {
		    // if this happens, then some output was already sent, preventing the cookie.
		    throw new exception("Ferreteria System Error: Cookie could not be sent for session key [$sSessKey].");
		}
	    }
	    $this->SetCurrentRecord($rcSess);
	}
	return $this->GetCurrentRecord();
	    
/* 2016-11-14 old code    
	$okSession = FALSE;		// session not valid until we say so
	$sSessKey = GetCookieValue();	// get the session cookie
	$doNew = TRUE;
	if (!is_null($sSessKey)) {
	    // we already have a session key -- see if the session is still valid
	    
	    list($idRecd,$sToken) = explode('-',$sSessKey);
	    if (empty($idRecd)) {
		throw new fcSilentException("Ferreteria Input Error: Received session cookie has no ID. Hacking attempt? Session Key=[$sSessKey]");
	    }

	    // check to see if we've already loaded a session record
	    if ($this->HasCurrentRecord()) {
		$rcSess = $this->GetCurrentRecord();
		$idThis = $rcSess->GetKeyValue();
		if ($idThis == $idRecd) {
		    $doNew = FALSE;
		}
	    }
	    if ($doNew) {
		$rcRecd = $this->GetRecord_forKey($idRecd);
		$this->SetCurrentRecord($rcRecd);
		$rcSess = $rcRecd;
	    }

	    $okSession = $rcSess->IsValidNow($sToken);	// do session's creds match browser's creds?
	}

	if (!$okSession) {
	  // no current/valid session, so make a new one:
	    $rcSess = $this->SpawnRecordset();
	    $rcSess->InitNew();
	    $rcSess->Create();
	    
	    if ($doNew) {
		$this->AppObject()->AddMessage('You have to add items to your cart before you can check out.');
	    } else {
		$this->AppObject()->AddMessage('Your existing session was dropped because your fingerprint changed.');
	    }
	  // add new record for the new session:
	    $this->SetCurrentRecord($rcSess);
	  // generate key from the new session:
	    $sSessKey = $rcSess->SessKey();
	    $ok = $this->ThrowCookie($sSessKey);
	    if (!$ok) {
		throw new exception('Internal Error: Cookie could not be sent for session key "'.$sSessKey.'".');
		// if this happens, then some output was already sent, preventing the cookie.
	    }
	} else {
	    //echo 'SESSION IS FINE, THANKS.<br>';
	}
	return $this->GetCurrentRecord();
*/
    }
    /*----
      ACTION: get the session record we *should* be using, based on current client specs
      HISTORY:
	2016-10-31 Adapted from fcApp::GetSessionRecord() -- couldn't figure out why it needed to be there,
	  and needing to access recordset's GetTableWrapper() was a problem because that's protected now.
    */
    /* 2016-12-18 I'm not sure, but this has to be redundant. In any case, it's a duplicate method name.
    public function MakeActiveRecord() {
	if (empty($this->rcSess)) {
	    $this->rcSess = $this->GetCurrent();
	}
	if (!$this->rcSess->HasRows()) {
	    //throw new exception('Internal error: Loaded Session recordset has no rows.');
/* 2016-03-24 I'm guessing that this happens when there is a partial match between the browser cookie
    and a Session record -- e.g. browser has gone to a new version, so has the cookie but the fingerprint
    doesn't match. In that case, we should just end the Session and start over.
* /
	    $this->ClearSession();
	    $this->rcSess = $this->GetCurrent();
	}
	return $this->rcSess;
    }
*/
    
    // -- CURRENT RECORD -- //

}
/*::::
  PURPOSE: Represents a single user session record
*/
class fcrUserSession extends fcRecord_standard {
    use ftVerbalObject;
    use ftSaveableRecord;

    protected $rcUser;

    // ++ SETUP ++ //

    protected function InitVars() {
	$this->ClientRecord_clear();
	$this->rcUser = NULL;
    }
    public function InitNew() {
	$this->ClearFields();
	$this->SetFieldValues(array(
	  'Token'	=> fcString::Random(31),
	  'ID_Client'	=> NULL,
	  'ID_Acct'	=> NULL,
	  'WhenCreated'	=> NULL		// hasn't been created until written to db
	  ));
	$this->ClientRecord_needed();	// connect to client record (create if needed)
    }

    // -- SETUP -- //
    // ++ CLASSES ++ //

    protected function ClientsClass() {
	return 'clsUserClients';
    }
    protected function UsersClass() {
	return 'fctUserAccts';
    }

    // -- CLASSES -- //
    // ++ TABLES ++ //

    protected function ClientTable($id=NULL) {
	return $this->GetConnection()->MakeTableWrapper($this->ClientsClass(),$id);
    }
    protected function UserTable($id=NULL) {
	return $this->GetConnection()->MakeTableWrapper($this->UsersClass(),$id);
    }

    // -- TABLES -- //
    // ++ RECORDS ++ //

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
	2016-12-18 This now checks to make sure the session recordset actually has a row.
    */
    protected function ClientRecord_asSet() {
	if (is_null($this->rcClient)) {
	    // if there's no client record, see if there's a client ID:
	    if ($this->HasRow()) {
		$idCli = $this->GetClientID();
		if (!is_null($idCli)) {
		    // there's a client ID, so get the client record from that:
		    $this->rcClient = $this->ClientTable($idCli);
		}
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
		$idUser = $this->GetUserID();
		$rc = $tUser->GetRecord_forKey($idUser);
		if ($this->RowCount() == 0) {
		    throw new exception("Ferreteria data error: Session expected user ID [$idUser], but this pulled up zero records.");
		}
		$this->rcUser = $rc;
	    } else {
		$this->rcUser = NULL;
	    }
	}
	return $this->rcUser;
    }
    
    public function SetUserRecord(fcrUserAcct $rcUser) {
	$doChg = FALSE;
	$idNew = $rcUser->GetKeyValue();
	if (is_null($this->rcUser)) {
	    $doChg = TRUE;
	} else {
	    $idOld = $this->GetUserID();
	    if ($idOld != $idNew) {
		$doChg = TRUE;
	    } elseif (get_class($this->rcUser) != get_class($rcUser)) {
		$doChg = TRUE;
	    }
	}
	if ($doChg) {
	    $this->rcUser = $rcUser;
	    // UPDATE local & saved ID_Acct
	    $this->SaveUserID($idNew);
	}
    }
    /* 2017-01-16 old version
    // TODO: is this ever *supposed* to be called with oUser=NULL? If so, why? Document.
    public function SetUserRecord(fcrUserAcct $oUser=NULL) {
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
	    $idOld = $this->GetUserID();
	    if ($idOld != $idNew) {
		$doChg = TRUE;
	    } elseif (get_class($this->rcUser) != get_class($oUser)) {
		$doChg = TRUE;
	    }
	}
	if ($doChg) {
	    $this->rcUser = $oUser;
	    // UPDATE local & saved ID_User
	    $this->SaveUserID($idNew);
	}
    } */
    /*----
      RETURNS: User record object, if the session has a user; NULL otherwise
      ASSUMES:
	If $this->objUser is set, it matches ID_User
    */ /* 2016-12-18 It appears that nothing is using this anymore.
    public function UserObj(fcrUserAcct $oUser=NULL) {
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
    } */

    // -- RECORDS -- //
    // ++ FIELD VALUES ++ //

    protected function SetClientID($id) {
	return $this->SetFieldValue('ID_Client',$id);
    }
    protected function GetClientID() {
	return $this->GetFieldValue('ID_Client');
    }
    public function GetUserID() {
	return $this->GetFieldValue('ID_Acct');
    }
    protected function GetToken() {
	return $this->GetFieldValue('Token');
    }

      //++stash++//

    protected function FetchStash() {
	if ($this->FieldIsSet('Stash')) {
	    $sStash = $this->GetFieldValue('Stash');
	    if (is_null($sStash)) {
		$arStash = array();
	    } else {
		$arStash = unserialize($sStash);
	    }
	} else {
	    $arStash = array();
	}
	return $arStash;
    }
    protected function StoreStash(array $ar) {
	if (count($ar) > 0) {
	    $sStash = serialize($ar);
	    $this->SetFieldValue('Stash',$sStash);
	} else {
	    $this->DeleteField('Stash');
	}
    }
    public function SetStashValue($sApp,$sName,$sValue) {
	$arStash = $this->FetchStash();
	$arStash[$sApp][$sName] = $sValue;
	$this->StoreStash($arStash);
    }
    public function GetStashValue($sApp,$sName) {
	$arStash = $this->FetchStash();
	$arAppStash = fcArray::Nz($arStash,$sApp);
	$sValue = fcArray::Nz($arAppStash,$sName);
	return $sValue;
    }
    // ACTION: retrieve the value from the stash and remove it
    public function PullStashValue($sApp,$sName) {
	$sValue = $this->GetStashValue($sApp,$sName);
	$this->ClearStashValue($sApp,$sName);
	return $sValue;
    }
    // ACTION: delete the given value from the stash
    protected function ClearStashValue($sApp,$sName) {
	$arStash = $this->FetchStash();
	unset($arStash[$sApp][$sName]);
	$this->StoreStash($arStash);
    }

      //--stash--//

    // -- FIELD VALUES -- //
    // ++ FIELD CALCULATIONS ++ //

    // TODO: rename to GetAcctID_SQL()
    protected function GetUserID_SQL() {
	if ($this->FieldIsSet('ID_Acct')) {
	    $idAcct = $this->GetFieldValue('ID_Acct');
	    if (is_null($idAcct)) {
		return 'NULL';
	    } else {
		return (int)$idAcct;
	    }
	} else {
	    return 'NULL';
	}
    }
    // NOTE: The token will never have punctuation in it, so we can just quote without sanitizing.
    protected function GetToken_SQL() {
	return '"'.$this->GetToken().'"';
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
	Right now, this means everything has to match (cookie token, IP address, browser string)
	but in the future we might allow users to reduce their individual security level
	by turning off the IP address check and/or the browser check. (This may require
	table modifications.)
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
	    $ok = ($this->GetToken() == $iKey);
	    if ($ok) {
		// client record currently includes both browser string and IP address
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
	return $this->GetKeyValue().'-'.$this->GetToken();
    }
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

    // -- FIELD CALCULATIONS -- //
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
      HISTORY:
	2016-11-14 Made PROTECTED, and renamed Create() -> CreateRecord().
	2016-12-18 Needs to be PUBLIC so that the Table class can call it.
	  Also, no sanitization done here anymore. What isn't now handled elsewhere
	  is unnecessary.
      PUBLIC so Table class can call it.
    */
    public function CreateRecord() {
	$ar = array(
	  'ID_Client'	=> $this->Make_ClientID(),
	  'ID_Acct'	=> $this->GetUserID_SQL(),
	  'Token'	=> $this->GetToken_SQL(),
	  'WhenCreated'	=> 'NOW()'
	  );
	$tbl = $this->GetTableWrapper();
	$idNew = $tbl->Insert($ar);
	if ($idNew === FALSE) {
	    echo '<b>SQL</b>: '.$tbl->sql.'<br>';
	    throw new exception('Could not create new Session record.');
	}
	$this->SetKeyValue($idNew);
	$rcClient = $this->ClientRecord_needed();
	if (!$rcClient->isNew()) {
	    $rcClient->Stamp();
	}
    }
    /*
    public function CreateRecord_andThrowCookie() {
	$this->CreateRecord();
	$sSessKey = $this->SessKey();
	$ok = $this->ThrowCookie($sSessKey);
	return $ok;
    }*/
    /*----
      ACTION: Attempts to log the user in with the given credentials.
      RETURNS: nothing
	Call UserIsLoggedIn() to find out if successful.
    */
    public function UserLogin($sUser,$sPass) {
	$tUsers = $this->UserTable();
	$rcUser = $tUsers->Login($sUser,$sPass);
	$this->SetUserRecord($rcUser);		// set user for this session
    }
    /*----
      ACTION: Logs the current user out. (Clears ID_Acct in session record.)
    */
    public function UserLogout() {
	$this->ClearValue('ID_Acct');
	$arUpd = array('ID_Acct'=>'NULL');
	$this->Update($arUpd);
    }
    /*----
      TODO: convert this to use UpdateArray() and Save().
      HISTORY:
	2016-12-21 No longer needs to be public, so making it protected.
    */
    protected function SaveUserID($idUser) {
	$ar = array('ID_Acct'=>$idUser);
	$this->Update($ar);				// save account ID to database
	$this->SetFieldValue('ID_Acct',$idUser);	// update it in RAM as well
    }

    // -- ACTIONS -- //
    // ++ CALCULATIONS ++ //


    // -- CALCULATIONS -- //

}
