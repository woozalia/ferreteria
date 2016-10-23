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
class fctUserSessions extends clsTable {
    protected $SessKey;
    private $sCookieVal;
    private $rcSess;

    const TableName='user_session';

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name(self::TableName);
	  $this->KeyName('ID');
	  $this->ClassSng(KS_CLASS_USER_SESSION);
	$this->rcSess = NULL;
	$this->sCookieVal = NULL;
    }
    private function Create() {
	$rcSess = $this->SpawnItem();
	$rcSess->InitNew();
	$rcSess->Create();
	return $rcSess;
    }
    public function ClearSession() {
	$this->rcSess = NULL;
    }
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
    protected function ThrowCookie($iSessKey) {
	$this->SessKey = $iSessKey;
	$sDomain = KS_COOKIE_DOMAIN;
	$sVal = $this->SessKey;
	//$ok = setcookie(KS_USER_SESSION_KEY,$sVal,0,'/',$sDomain);
	$ok = setcookie(KS_USER_SESSION_KEY,$sVal,0,'/');
	if ($ok) {
	    // store new cookie value (see NOTES above)
	    $this->CookieValue($sVal);
	}
	return $ok;
    }
    protected function CookieValue($sValue=NULL) {
	if (!is_null($sValue)) {
	    $this->sCookieVal = $sValue;
	}
	return $this->sCookieVal;
    }
    public function CurrentRecord(fcrUserSession $rcSess=NULL) {
	if (!is_null($rcSess)) {
	    $this->rcSess = $rcSess;
	}
	return $this->rcSess;
    }
    public function HasCurrentRecord() {
	return !is_null($this->rcSess);
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
		$rcThis = $this->CurrentRecord();
		$idThis = $rcThis->KeyValue();
		if ($idThis == $idRecd) {
		    $doNew = FALSE;
		}
	    }
	    if ($doNew) {
		$rcRecd = $this->GetItem($idRecd);
		$this->CurrentRecord($rcRecd);
		$rcThis = $rcRecd;
	    }

	    $okSession = $rcThis->IsValidNow($sToken);	// do session's creds match browser's creds?
	}

	if (!$okSession) {
	  // no current/valid session, so make a new one:
	    $rcThis = $this->Create();
	    echo "DONEW: [$doNew]<br>";
	    if ($doNew) {
		clsApp::Me()->AddMessage('You have to add items to your cart before you can check out.');
	    } else {
		clsApp::Me()->AddMessage('Your existing session was dropped because your fingerprint changed.');
	    }
	  // add new record for the new session:
	    $this->CurrentRecord($rcThis);
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
	//echo 'RCTHIS SESSION MESSAGES: '.$rcThis->MessagesString().'<br>';
	//die('CURRENT SESSION MESSAGES: '.$this->CurrentRecord()->MessagesString());
	return $this->CurrentRecord();
    }
}
/*::::
  PURPOSE: Represents a single user session record
*/
class fcrUserSession extends clsDataSet {
    use ftVerbalObject;

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
	return $this->Engine()->Make($this->ClientsClass(),$id);
    }
    protected function UserTable($id=NULL) {
	return $this->Engine()->Make($this->UsersClass(),$id);
    }

    // -- DATA TABLES -- //
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
	$idUser = $this->UserID();
	return !empty($idUser);	// can be 0 or NULL if no user
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
	return $this->KeyValue().'-'.$this->Token();
    }

    // -- STATUS/FIELD ACCESS -- //
    // ++ ACTIONS ++ //

    /*----
      ACTION: Make sure the Client ID is set correctly for the current browser client
	If not set or doesn't match, get a new one.
    */
    protected function Make_ClientID() {
	$rcCli = $this->ClientRecord_needed();
	$this->ClientID($rcCli->KeyValue());
	return $this->ClientID();
    }
    /*----
      ACTION: Create a new session record from the current memory data.
    */
    public function Create() {
	$db = $this->Table()->Engine();
	$ar = array(
	  'ID_Client'	=> $db->SanitizeAndQuote($this->Make_ClientID()),
	  'ID_User'	=> $db->SanitizeAndQuote($this->UserID()),
	  'Token'	=> $db->SanitizeAndQuote($this->Token()),
	  'WhenCreated'	=> 'NOW()'
	  );
	$idNew = $this->Table()->Insert($ar);
	if ($idNew === FALSE) {
	    throw new exception('Could not create new Session record.');
	}
	$this->KeyValue($idNew);
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
    public function SaveUserID($idUser) {
	$ar = array('ID_User'=>$idUser);
	$this->Update($ar);			// save user ID to database
	$this->Value('ID_User',$idUser);	// update it in RAM as well
    }

    // -- ACTIONS -- //
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
    // TODO: is this ever *supposed* to be called with oUser=NULL? If so, why? Document.
    public function SetUserRecord(clsUserAcct $oUser=NULL) {
	$doChg = FALSE;
	if (is_null($oUser)) {
	    $idNew = NULL;
	} else {
	    $idNew = $oUser->KeyValue();
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
