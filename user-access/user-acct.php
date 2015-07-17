<?php
/*
  PURPOSE: classes for handling user logins
  HISTORY:
    2013-10-25 stripped Session classes out of VbzCart shop.php
    2013-10-27 (NS) adapted email token classes from VbzCart (for ATC bid)
    2013-11-14 brought email token classes into user.php
      Renamed clsEmailToken[s] -> clsUserToken[s]
*/

// define('KS_USER_SESSION_KEY','some-string');	// some-string should be unique within your domain name
/*%%%%
  PURPOSE: collectively handles all user records
*/
class clsUserAccts extends clsTable {

    // ++ STATIC ++ //

    /*----
      PUBLIC so record-class can call it when resetting password
    */
    public static function HashPass($iPass,$iSalt) {
	$sToHash = $iSalt.$iPass;
	$sHashed = hash('whirlpool',$sToHash,TRUE);
	return $sHashed;
    }
    /*----
      PUBLIC so record-class can call it when resetting or checking password
    */
    public static function MakeSalt() {
	return openssl_random_pseudo_bytes(128);
    }
    protected static function UserName_SQL_filt($iName) {
	return 'LOWER(UserName)='.SQLValue(strtolower($iName));
    }

    // -- STATIC -- //
    // ++ SETUP ++ //

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name(KS_TABLE_USER_ACCOUNT);
	  $this->KeyName('ID');
	  $this->ClassSng('clsUserAcct');
    }

    // -- SETUP -- //
    // ++ BASIC ACTIONS ++ //

    /*----
      RETURNS: user object if login successful, NULL otherwise
    */
    public function Login($iUser,$iPass) {
	$rc = $this->FindUser($iUser);
	if (is_null($rc)) {
	    // username not found
	    $oUser = NULL;
	} elseif ($rc->PassMatches($iPass)) {
	    $oUser = $rc;
	} else {
	    // username found, password wrong
	    $oUser = NULL;
	}
	return $oUser;
    }
    /*----
      ACTION: add a user to the database
      ASSUMES: iEmail is valid because it was used earlier to receive
	the auth token which is required before you can set user/pw.
    */
    public function AddUser($iLogin,$iPass,$iEmail) {
	$sSalt = self::MakeSalt();
	$sHashed = self::HashPass($sSalt,$iPass);
	$ar = array(
	  'UserName'	=> SQLValue($iLogin),
	  'PassHash'	=> SQLValue($sHashed),
	  'PassSalt'	=> SQLValue($sSalt),
	  'EmailAddr'	=> SQLValue($iEmail),
	  'WhenCreated'	=> 'NOW()'
	);
	$rc = $this->Insert_andGet($ar);
	return $rc;
    }

    // -- BASIC ACTIONS -- //
    // ++ BUSINESS LOGIC ++ //

    public function FindUser($iName) {
	$sqlFilt = self::UserName_SQL_filt($iName);
	$rc = $this->GetData($sqlFilt);
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
	$sqlFilt = self::UserName_SQL_filt($iLogin);
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
/*%%%%
  PURPOSE: user management
*/
class clsUserAcct extends fcDataRecs {
    private $arPerm;	// cache of permissions for this user

    // ++ INITIALIZATION ++ //

    protected function InitVars() {
	parent::InitVars();
	$this->arPerm = NULL;
    }

    // -- INITIALIZATION -- //
    // ++ STATUS ++ //

    /*----
      RETURNS: TRUE iff the current record is the logged in user
    */
    public function IsLoggedIn() {
	$rcLogged = $this->Engine()->App()->User();
	if (is_null($rcLogged)) {
	    return FALSE;
	} else {
	    return ($rcLogged->KeyValue() == $this->KeyValue());
	}
    }

    // -- STATUS -- //
    // ++ FIELD ACCESS ++ //

    public function UserName() {
	return $this->Value('UserName');
    }
    public function FullName() {
	$sFullName = $this->Value('FullName');
	if (is_null($sFullName)) {
	    return $this->Value('UserName');
	} else {
	    return $this->Value('FullName');
	}
    }
    public function EmailAddress() {
	return $this->Value('EmailAddr');
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

    // -- FIELD ACCESS -- //
    // ++ CLASS NAMES ++ //

    protected function PermsClass() {
	return KS_CLASS_USER_PERMISSIONS;
    }
    protected function PermClass() {
	return KS_CLASS_USER_PERMISSION;
    }
    protected function XGroupClass() {
	return KS_CLASS_UACCT_X_UGROUP;
    }

    // -- CLASS NAMES -- //
    // ++ DATA TABLE ACCESS ++ //

    protected function XGroupTable() {
	return $this->Engine()->Make($this->XGroupClass());
    }
    protected function PermTable() {
	return $this->Engine()->Make($this->PermsClass());
    }

    // -- DATA TABLE ACCESS -- //
    // ++ DATA RECORDS ACCESS ++ //

    protected function UGroupRecords() {
	return $this->XGroupTable()->UGroupRecords($this->KeyValue());
    }
    protected function UPermRecords() {
	$idAcct = $this->KeyValue();
	$sql =
	  'SELECT up.*'
	  .' FROM (('.KS_TABLE_UACCT_X_UGROUP.' AS uxg'
	  .' LEFT JOIN '.KS_TABLE_USER_GROUP.' AS ug'
	  .' ON ug.ID=uxg.ID_UGrp)'
	  .' LEFT JOIN '.KS_TABLE_UGROUP_X_UPERM.' AS uxp'
	  .' ON uxp.ID_UGrp=ug.ID)'
	  .' LEFT JOIN '.KS_TABLE_USER_PERMISSION.' AS up'
	  .' ON up.ID=uxp.ID_UPrm'
	  ." WHERE ID_User=$idAcct";
	// if there is a group to which all users automatically belong...
	if (defined('ID_GROUP_USERS')) {
	    // ...include permissions for that group too
	    $sql .= ' UNION DISTINCT SELECT up.*'
	      .' FROM '.KS_TABLE_UGROUP_X_UPERM.' AS uxp'
	      .' LEFT JOIN '.KS_TABLE_USER_PERMISSION.' AS up'
	      .' ON up.ID=uxp.ID_UPrm'
	      .' WHERE uxp.ID_UGrp='.ID_GROUP_USERS;
	}
	$rs = $this->Table()->DataSQL($sql,$this->PermClass());
	$rs->Table($this->PermTable());
	return $rs;
    }
    protected function UPermArray() {
	if (is_null($this->arPerm)) {
	    $rs = $this->UPermRecords();
	    while ($rs->NextRow()) {
		$sName = $rs->Value('Name');
		$this->arPerm[$sName] = $rs->Values();
	    }
	}
	return $this->arPerm;
    }

    // -- DATA RECORDS ACCESS -- //
    // ++ BUSINESS LOGIC ++ //

    public function PassMatches($iPass) {
	// get salt for this user
	$sSalt = $this->Value('PassSalt');

	// hash [stored salt]+[given pass]
	$sThisHashed = $this->Table()->HashPass($sSalt,$iPass);
	// get stored hash
	$sSavedHash = $this->Value('PassHash');

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
	if (defined('ID_USER_ROOT')) {
	    if ($this->KeyValue() == ID_USER_ROOT) {
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
