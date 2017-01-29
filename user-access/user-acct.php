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
    use ftLinkableTable;

    // ++ CEMENTING ++ //
    
    protected function TableName() {
	return KS_TABLE_USER_ACCOUNT;
    }
    protected function SingularName() {
	return 'fcrUserAcct';
    }
    public function GetActionKey() {
	return KS_ACTION_USER_ACCOUNT;
    }
    
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
      RETURNS: user object if login successful, NULL otherwise
    */
    public function Login($sUser,$sPass) {
	$rc = $this->FindUser($sUser);
	if (is_null($rc)) {
	    // username not found
	    $rcUser = NULL;
	    $this->SetError_UserUnknown();
	} elseif ($rc->PassMatches($sPass)) {
	    $rcUser = $rc;
	} else {
	    // username found, password wrong
	    $rcUser = NULL;
	    $this->SetError_WrongPassword();
	}
	return $rcUser;
    }
    /*----
      ACTION: add a user to the database
      ASSUMES: iEmail is valid because it was used earlier to receive
	the auth token which is required before you can set user/pw.
    */
    public function AddUser($sLogin,$sPass,$sEmail) {
	$sSalt = self::MakeSalt();
	$sHashed = self::HashPass($sSalt,$iPass);
	$db = $this->GetConnection();
	$ar = array(
	  'UserName'	=> $db->Sanitize_andQuote($sLogin),
	  'PassHash'	=> $db->Sanitize_andQuote($sHashed),
	  'PassSalt'	=> $db->Sanitize_andQuote($sSalt),
	  'EmailAddr'	=> $db->Sanitize_andQuote($sEmail),
	  'WhenCreated'	=> 'NOW()'
	);
	$rc = $this->Insert_andGet($ar);
	return $rc;
    }

    // -- BASIC ACTIONS -- //
    // ++ BUSINESS LOGIC ++ //

    protected function SQL_forUserName_filter($iName) {
	return 'LOWER(UserName)='.$this->GetConnection()->Sanitize_andQuote(strtolower($iName));
    }

    public function FindUser($iName) {
	$sqlFilt = $this->SQL_forUserName_filter($iName);
	$rc = $this->SelectRecords($sqlFilt);
	$nRows = $rc->RowCount();
	if ($nRows == 0) {
	    $rc = NULL;
	} elseif ($nRows > 1) {
	    $nCount = $rc->RowCount();
	    $sDescr = 'Username "'.$iName.'" appears '.$nCount.' times in the user database.';
	    $this->Engine()->LogEvent(__FILE__.' line '.__LINE__,'name='.$iName,$sDescr,'UDUP',TRUE,TRUE);
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
	$rc = $this->GetData($sqlFilt);
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
    use ftLinkableRecord;

    // ++ STATUS ++ //

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

    public function UserName() {
	return $this->GetFieldValue('UserName');
    }
    public function FullName() {
	$sFullName = $this->GetFieldValue('FullName');
	if (is_null($sFullName)) {
	    return $this->GetFieldValue('UserName');
	} else {
	    return $this->GetFieldValue('FullName');
	}
    }
    public function EmailAddress() {
	return $this->GetFieldValue('EmailAddr');
    }
    public function SetPassword($sPass) {
	if (empty($sPass)) {
	    throw new exception('Internal error: setting blank password');
	}
	$t = $this->Table();
	$sSalt = $t->MakeSalt();
	$sHashed = $t->HashPass($sSalt,$sPass);
	$ar = array(
	  'PassHash'	=> SQLValue($sHashed),
	  'PassSalt'	=> SQLValue($sSalt),
	  );
	$ok = $this->Update($ar);
	return $ok;
    }

    // -- FIELD VALUES -- //
    // ++ FIELD CALCULATIONS ++ //
    
    // TAGS: admin function, trait helper, cement
    public function SelfLink_name() {
	$sName = $this->UserName();
	return $this->SelfLink($sName);
    }

    // -- FIELD CALCULATIONS -- //
    // ++ CLASS NAMES ++ //

    protected function PermsQueryClass() {
	return 'fcqtUserPerms';
    }

    // -- CLASS NAMES -- //
    // ++ DATA TABLE ACCESS ++ //

    protected function XGroupTable() {
	return $this->GetConnection()->MakeTableWrapper($this->XGroupClass());
    }
    /*
    protected function PermTable() {
	return $this->Engine()->Make($this->PermsClass());
    }*/
    protected function PermsQuery() {
	return $this->GetConnection()->MakeTableWrapper($this->PermsQueryClass());
    }

    // -- DATA TABLE ACCESS -- //
    // ++ DATA RECORDS ACCESS ++ //

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

    // -- DATA RECORDS ACCESS -- //
    // ++ BUSINESS LOGIC ++ //

    public function PassMatches($iPass) {
	// get salt for this user
	$sSalt = $this->GetFieldValue('PassSalt');

	// hash [stored salt]+[given pass]
	$sThisHashed = $this->GetTableWrapper()->HashPass($sSalt,$iPass);
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
    */
    public function CanDo($sPerm) {
	if ($this->IsNew()) {
	    throw new exception('Ferreteria usage error: attempting to determine permissions for an empty user record.');
	}
	if (defined('ID_USER_ROOT')) {
	    if ($this->GetKeyValue() == ID_USER_ROOT) {
		return TRUE;
	    }
	}
	if (is_null($sPerm)) {
	    return FALSE;
	} else {
	    $ar = $this->UPermArray();
	    if (is_null($ar)) {
		return FALSE;	// user has NO permissions (yet)
	    } else {
		return (array_key_exists($sPerm,$ar));
	    }
	}
    }

    // -- BUSINESS LOGIC -- //
}
