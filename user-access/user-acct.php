<?php
/*
  PURPOSE: classes for handling user logins
  HISTORY:
    2013-10-25 stripped Session classes out of VbzCart shop.php
    2013-10-27 (NS) adapted email token classes from VbzCart (for ATC bid)
    2013-11-14 brought email token classes into user.php
      Renamed clsEmailToken[s] -> clsUserToken[s]
    2016-12-25 added self-linking traits because we *always* need to be able to link to user profile editing
*/

/*::::
  PURPOSE: collectively handles all user records
*/
class fctUserAccts extends fcTable_keyed_single_standard {
    //use ftLinkableTable;

    // ++ CEMENTING ++ //
    
    protected function TableName() {
	return KS_TABLE_USER_ACCOUNT;
    }
    protected function SingularName() {
	return 'fcrUserAcct';
    }
    /* This should only be defined in the admin class
    public function GetActionKey() {
	return KS_ACTION_USER_ACCOUNT;
    }*/
    
    // -- CEMENTING -- //
    // ++ STATIC ++ //

    /*----
      PUBLIC so record-class can call it when resetting password
    */
    static public function HashPass($iPass,$iSalt) {
	$sToHash = $iSalt.$iPass;
	$sHashed = hash('whirlpool',$sToHash,TRUE);
	return $sHashed;
    }
    /*----
      PUBLIC so record-class can call it when resetting or checking password
    */
    static public function MakeSalt() {
	return openssl_random_pseudo_bytes(128);
    }

    // -- STATIC -- //
    // ++ STATUS ++ //
    
    private $sErrCode;
    protected function SetError_UserUnknown() {
	$this->sErrCode = 'UNK';
    }
    protected function SetError_WrongPassword() {
	$this->sErrCode = 'MIS';
    }
    public function GetError_IsUserUnknown() {
	return $this->GetErrorCode() == 'UNK';
    }
    public function GetError_IsWrongPassword() {
	return $this->GetErrorCode() == 'MIS';
    }
    public function DidSucceed() {
	return empty($this->sErrCode);
    }
    public function GetErrorCode() {
	return $this->sErrCode;
    }
    
    // -- STATUS -- //
    // ++ BASIC ACTIONS ++ //

    /*----
      RETURNS: user object if login is recognized, NULL otherwise
	Sets an error if the login is not successful.
    */
    public function Login($sUser,$sPass) {
	$rc = $this->FindUser($sUser);
	if (is_null($rc)) {
	    // username not found
	    $rcUser = NULL;
	    $this->SetError_UserUnknown();
	} elseif ($rc->PassMatches($sPass)) {
	    // success!
	    $rcUser = $rc;
	} else {
	    // username found, password wrong
	    $this->SetFailedUser($rc);	// we use this to warn the (real) user that someone tried to log in
	    $rcUser = NULL;
	    $this->SetError_WrongPassword();
	}
	return $rcUser;
    }
    static private $rcFailed;
    protected function SetFailedUser(fcrUserAcct $rc) {
	self::$rcFailed = $rc;
    }
    public function GetFailedUser() {
	return self::$rcFailed;
    }
    public function GetFailedUserWasFound() {
	return !empty(self::$rcFailed);
    }
    /*----
      ACTION: add a user to the database
      ASSUMES:
	* sEmail is valid because it was used earlier to receive
	the auth token which is required before you can set user/pw.
	* sLogin does not match the UserName of an existing record.
    */
    public function AddUser($sLogin,$sPass,$sEmail) {
	$sSalt = self::MakeSalt();
	$sHashed = self::HashPass($sSalt,$iPass);
	$db = $this->GetConnection();
	$ar = array(
	  'UserName'	=> $db->SanitizeValue($sLogin),
	  'PassHash'	=> $db->SanitizeValue($sHashed),
	  'PassSalt'	=> $db->SanitizeValue($sSalt),
	  'EmailAddr'	=> $db->SanitizeValue($sEmail),
	  'WhenCreated'	=> 'NOW()'
	);
	$rc = $this->Insert_andGet($ar);
	return $rc;
    }

    // -- BASIC ACTIONS -- //
    // ++ BUSINESS LOGIC ++ //

    protected function SQL_forUserName_filter($iName) {
	return 'LOWER(UserName)='.$this->GetConnection()->SanitizeValue(strtolower($iName));
    }

    public function FindUser($sName) {
	$sqlFilt = $this->SQL_forUserName_filter($sName);
	$rc = $this->SelectRecords($sqlFilt);
	$nRows = $rc->RowCount();
	if ($nRows == 0) {
	    $rc = NULL;
	} elseif ($nRows > 1) {
	    $nCount = $rc->RowCount();
	    $sText = 'Username "'.$sName.'" appears '.$nCount.' times in the user database.';
	    fcApp::Me()->EventTable()->CreateEvent(KS_EVENT_FERRETERIA_DB_INTEGRITY_ERROR,$sText);
	    //$this->Engine()->LogEvent(__FILE__.' line '.__LINE__,'name='.$sName,$sDescr,'UDUP',TRUE,TRUE);
	    $rc = NULL;
	} else {
	    $rc->NextRow();	// load the first (only) row
	}
	return $rc;
    }
    /*----
      RULES: Usernames are stored with case-sensitivity, but are checked case-insensitively
    */
    public function UserExists($iLogin) {
	$sqlFilt = $this->SQL_forUserName_filter($iLogin);
	$rc = $this->SelectRecords($sqlFilt);
	return $rc->HasRows();
    }
    public function FindEmail($sEmail) {
	$sqlEmail = SQLValue($sEmail);
	$sqlFilt = "EmailAddr=$sqlEmail";
	$rc = $this->GetData($sqlFilt);
	return $rc;
    }

    // -- BUSINESS LOGIC -- //
}
/*::::
  PURPOSE: user management
*/
class fcrUserAcct extends fcRecord_standard {
    use ftFrameworkAccess;
    use ftSaveableRecord;
    //use ftLinkableRecord;

    // ++ STATUS ++ //

    protected function IsRoot() {
	return ($this->GetKeyValue() == ID_USER_ROOT);
    }
    /*----
      RETURNS: TRUE iff the current record is the logged in user
    */
    public function IsLoggedIn() {
	throw new exception('2016-12-25 Is anything actually calling this?');
	$rcLogged = $this->UserRecord();
	if (is_null($rcLogged)) {
	    return FALSE;
	} else {
	    return ($rcLogged->GetKeyValue() == $this->GetKeyValue());
	}
    }

    // -- STATUS -- //
    // ++ FIELD VALUES ++ //

    public function LoginName() {
	return $this->GetFieldValue('UserName');
    }
    /*----
      HISTORY:
	2017-01-29 Added $doUseDefault so username won't be used for full name
	  in contexts where we don't want it (proxmiate case: login emails)
    */
    public function FullName($doUseDefault=TRUE) {
	$sFullName = $this->GetFieldValue('FullName');
	if (is_null($sFullName && $doUseDefault)) {
	    return $this->GetFieldValue('UserName');
	} else {
	    return $sFullName;
	}
    }
    public function EmailAddress() {
	return $this->GetFieldValue('EmailAddr');
    }
    public function SetPassword($sPass) {
	if (empty($sPass)) {
	    throw new exception('Internal error: setting blank password');
	}
	$t = $this->GetTableWrapper();
	$sSalt = $t->MakeSalt();
	$sHashed = $t->HashPass($sSalt,$sPass);
	$db = $this->GetConnection();
	$ar = array(
	  'PassHash'	=> $db->SanitizeValue($sHashed),
	  'PassSalt'	=> $db->SanitizeValue($sSalt),
	  );
	$ok = $this->Update($ar);
	return $ok;
    }

    // -- FIELD VALUES -- //
    // ++ CLASSES ++ //

    protected function PermsQueryClass() {
	return 'fcqtUserPerms';
    }
    protected function PermitsClass() {
	return KS_CLASS_USER_PERMISSIONS;
    }

    // -- CLASSES -- //
    // ++ TABLES ++ //

    protected function XGroupTable() {
	return $this->GetConnection()->MakeTableWrapper($this->XGroupClass());
    }
    protected function PermitTable() {
	return $this->GetConnection()->MakeTableWrapper($this->PermitsClass());
    }
    protected function PermsQuery() {
	return $this->GetConnection()->MakeTableWrapper($this->PermsQueryClass());
    }

    // -- TABLES -- //
    // ++ RECORDS ++ //

    protected function UGroupRecords() {
	return $this->XGroupTable()->UGroupRecords($this->GetKeyValue());
    }
    protected function UPermRecords() {
	$idAcct = $this->GetKeyValue();
	if (empty($idAcct)) {
	    throw new exception('Internal error: trying to look up permissions for null account ID.');
	}
	$tbl = $this->PermsQuery();
	return $tbl->PermissionRecords($idAcct);
    }
    private $arPerm;	// cache of permissions for this user
    protected function UPermArray() {
	if (empty($this->arPerm)) {
	    $rs = $this->UPermRecords();
	    $this->arPerm = $rs->FetchRows_asArray('Name');
	    /* 2017-01-07 old version
	    while ($rs->NextRow()) {
		$sName = $rs->GetNameString();
		$this->arPerm[$sName] = $rs->Values();
	    }*/
	}
	return $this->arPerm;
    }

    // -- RECORDS -- //
    // ++ CALCULATIONS ++ //

    public function PassMatches($sPass) {
	// get salt for this user
	$sSalt = $this->GetFieldValue('PassSalt');

	// hash [stored salt]+[given pass]
	$sThisHashed = $this->GetTableWrapper()->HashPass($sSalt,$sPass);
	// get stored hash
	$sSavedHash = $this->GetFieldValue('PassHash');
	
	// see if they match
	$ok = ($sThisHashed == $sSavedHash);
	return $ok;
    }

    /*----
      RULES: For now, if a permission record with the given name exists and is assigned
	to a group to which the user belongs, then the user has that permission.
	Later we might want to have the ability to void or disable permissions, but at
	the moment I can't see the need. Renaming them effectively disables them, too,
	since they are looked up by name rather than ID.
      HISTORY:
	2014-01-25 changed default (NULL) access to FALSE, to prevent accidentally
	  giving access by not assigning a permission. (All users can be deliberately
	  given access to a feature by simply not checking CanDo().
	2017-03-25 Added check to make sure that the requested permit actually exists.
    */
    public function CanDo($sPerm) {
	if ($this->IsNew()) {
	    throw new exception('Ferreteria usage error: attempting to determine permissions for an empty user record.');
	}
	if (KB_USE_ANONYMOUS_ROOT) {
	    // TODO: display this message nicely
	    echo '<b>WARNING</b>: USING ANONYMOUS ROOT! DISABLE THIS AS SOON AS POSSIBLE.';
	    return TRUE;
	}
	if ($sPerm == KS_PERM_FE_ROOT) {
	    // this permission means user must be root
	    return $this->IsRoot();
	}
	$hasRoot = FALSE;
	if (defined('ID_USER_ROOT')) {
	    if ($this->GetKeyValue() == ID_USER_ROOT) {
		$hasRoot = TRUE;
	    }
	}

	if ($this->PermitTable()->PermitExists($sPerm)) {
	    if (is_null($sPerm)) {
		return $hasRoot;	// user can only do this if ROOT
	    } else {
		$ar = $this->UPermArray();
		if (is_null($ar)) {
		    // user has NO permissions (yet)
		    $hasPerm = FALSE;	
		} else {
		    $hasPerm = array_key_exists($sPerm,$ar);
		}
		return $hasPerm || $hasRoot;
	    }
	} else {
	    if ($hasRoot) {
		$this->PermitTable()->SetMissingPermit($sPerm);	// make a note that code expects this to exist
		return TRUE;
	    } else {
		throw new exception("Ferreteria configuration error: permission '$sPerm' does not exist in database.");
	    }
	}
    }

    // -- CALCULATIONS -- //
}
