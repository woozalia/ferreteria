<?php
/*
  PURPOSE: classes for handling user authorization tokens
  HISTORY:
    2013-11-27 extracted from user.php
    2016-11-18 revising for db.v2 and for changes to table
      now supports more than one type of auth
      meaning of types must be defined by caller
*/

// ++ EVENTS ++ //

define('KS_EVENT_FERRETERIA_RECV_AUTHCODE','fe.authcode.recv');

// -- EVENTS -- //

/*::::
  PURPOSE: manages emailed authorization tokens
*/
class fcUserTokens extends fcTable_keyed_single_standard {

    // ++ SETUP ++ //

    protected function SingularName() {
	return 'fcUserToken';
    }
    protected function TableName() {
	return 'user_token';
    }
    
    // -- SETUP -- //
    // ++ STATIC ++ //

    /*----
      PUBLIC because the recordset type also needs to access it, for now.
	See note in MakeToken().
    */
    static public function MakeHash($sVal,$sSalt) {
	$sToHash = $sSalt.$sVal;
	$sHash = hash('whirlpool',$sToHash,TRUE);
	return $sHash;
    }
    // ASSUMES: There is an authcode to process
    static protected function ParseAuthCode($sAuth) {
	$id = strtok($sAuth, ':');	// string before ':' is auth-email ID
	$sHex = strtok(':');		// string after ':' is token (in hexadecimal)
	//$sToken = hex2bin($sHex);	// requires PHP 5.4
	$sBin = pack("H*",$sHex);	// equivalent to hex2bin()
	$arOut = array(
	  'auth'	=> $sAuth,	// token in text format -- for forms/links
	  'id'		=> $id,		// ID of auth email this is supposed to match
	  'bin'		=> $sBin	// token in binary format
	  );
	return $arOut;
    }


    // -- STATIC -- //
    // ++ STATUS ++ //
    
    private $sErrCode,$sErrText;
    protected function SetError($sCode,$sText) {
	$this->sErrCode = $sCode;
	$this->sErrText = $sText;
    }
    public function GetErrorCode() {
	return $this->sErrCode;
    }
    public function GetErrorText() {
	return $this->sErrText;
    }
    protected function SetError_TokenMismatch() {
	$this->SetError('MIS','token does not match stored value');
    }
    protected function SetError_TokenNotFound() {
	$this->SetError('NF','there is no record of the given token ID');
    }
    protected function SetError_TokenExpired($sWhen) {
	$this->SetError('EXP','token expired at '.$sWhen);
    }
    
    // -- STATUS -- //
    // ++ READ DB ++ //

    public function GetRecord_fromTokenString($s) {
	$arAuth = self::ParseAuthCode($s);
	
	$idEv = fcApp::Me()->CreateEvent(KS_EVENT_FERRETERIA_RECV_AUTHCODE,'authcode received',$arAuth);
	$tEvSub = fcApp::Me()->EventTable_Done();
	
	$id = $arAuth['id'];
	$sToken = $arAuth['bin'];
	$sAuth = $arAuth['auth'];

	$rcToken = $this->FindToken($id,$sToken);	// returns NULL or valid matching token record
	if (is_null($rcToken)) {
	    $sErrText = $this->GetErrorText();
	    $sErrCode = $this->GetErrorCode();
	    $tEvSub->CreateRecord($idEv,KS_EVENT_FAILED.':TOK:'.$sErrCode,'kept old password: '.$sErrText);
	    throw new exception('Token not found.');	// 2018-04-22 debugging
	} else {
	    echo 'GOT TO HERE<br>';
	    if (!$rcToken->HasExpired()) {
		// token has not expired
		$rcToken->Renew();	// extend the expiration
	    }
	    // TODO: should we log this?
	}
	if (is_null($rcToken)) {
	    throw new exception('NULL is being returned. This should not happen.');
	}
	return $rcToken;
    }
    /*----
      NOTE: Even if the token has expired, we want to return it so that we can
	tell the user it has expired. This should minimize frustration, and
	doesn't really pose a security risk as far as I can tell.
      RETURNS: token object if a matching token was found; NULL otherwise
    */
    public function FindToken($idToken,$sToken) {
	$rc = $this->GetRecord_forKey($idToken);	// this also loads the first/only row
	if ($rc->HasRows()) {
	    if (!$rc->HasRow()) {
		$qRows = $rc->RowCount();
		$sDesc = $qRows.' row'.fcString::Pluralize($qRows);
		throw new exception("Ferreteria internal error: Recordset has $sDesc but first row is empty.");
	    }
	    if ($rc->HashMatches($sToken)) {
		if ($rc->HasExpired()) {
		    $this->SetError_TokenExpired($rc->GetExpirationString());
		} else {
		    return $rc;
		}
	    } else {
		$this->SetError_TokenMismatch();
	    }
	} else {
	    $this->SetError_TokenNotFound();
	}
	// no valid matching token found
	return NULL;
    }
    
    // -- READ DB -- //
    // ++ WRITE DB ++ //

    /*----
      ACTION: Ensure that a token record exists for the given type and entity values
      RETURNS: single record for matching or created token
      TODO: 2017-02-01 There's probably a tidy way to move most of this functionality into
	the recordset type so that MakeHash() can also go there and be protected/private,
	but at the moment I don't really have time to figure it out.
    */
    public function MakeToken($nType,$sEntity) {
	$db = $this->GetConnection();
    
	$isStrong = NULL;
	$nHashLen = 32;
	$sToken = openssl_random_pseudo_bytes($nHashLen,$isStrong);
	if (!$isStrong) {
	    throw fcSilentException("Could not use strong encryption for token. Type=$nType, $sEntity=[$sEntity]");
	}
	$sSalt = openssl_random_pseudo_bytes($nHashLen);
	// save the salt and hashed token
	$sHash = self::MakeHash($sToken,$sSalt);
	$ar = array(
	  'TokenHash'	=> $db->SanitizeValue($sHash),
	  'TokenSalt'	=> $db->SanitizeValue($sSalt),
	  'WhenExp'	=> 'NOW() + INTERVAL 1 HOUR'	// expires in 1 hour
	  );

	// -- check to see if there's already a hash for this entity
	$sqlEntity = $db->SanitizeValue($sEntity);
	$sqlFilt = "(Type=$nType) AND (Entity=$sqlEntity)";
	$rc = $this->SelectRecords($sqlFilt);
	if ($rc->HasRows()) {
	    $rc->NextRow();	// load the record
	    $rc->Update($ar);
	} else {
	    $ar['Type'] = $nType;
	    $ar['Entity'] = $sqlEntity;
	    $id = $this->Insert($ar);
	    if ($id === FALSE) {
		echo 'SQL='.$this->sql.'<br>';
		throw new exception('<b>Internal error</b>: could not create token record.');
	    }
	    $rc = $this->GetRecord_forKey($id);
	}
	$rc->SetToken($sToken);	// caller may need this, but it shouldn't be stored
	return $rc;
    }
    /*----
      ACTION: Delete all expired tokens
      USAGE: policy to be decided; right now, nothing is using this.
      TODO: Later, we might want to log unused tokens. Or maybe not.
	Right now, nothing is calling this -- because we don't want
	to delete tokens right after they expire. Not yet sure how
	long to leave them active... but that can be decided later.
	Maybe they should only be deleted once successfully used?
    */
    protected function CleanTokens() {
	$sql = 'DELETE FROM '.$this->TableName_Cooked().' WHERE WhenExp < NOW() + INTERVAL 1 DAY';
	$this->GetConnection()->ExecuteAction($sql);
    }
    
    // -- WRITE DB -- //

}
class fcUserToken extends fcRecord_standard {
    
    // ++ AUX FIELDS ++ //

    private $sToken;
    public function SetToken($sVal) {
	$this->sToken = $sVal;
    }
    public function GetToken() {
	return $this->sToken;
    }
    
    // -- AUX FIELDS -- //
    // ++ FIELD VALUES ++ //
    
    protected function GetTokenSalt() {
	return $this->GetFieldValue('TokenSalt');
    }
    protected function GetTokenHash() {
	return $this->GetFieldValue('TokenHash');
    }
    /*----
      PUBLIC because this tells the caller (login management) what the token means
	Specifically: what action it authorizes, what type of identity value is being used
    */
    public function GetTokenType() {
	return $this->GetFieldValue('Type');
    }
    // PUBLIC because this tells the caller (login management) who has been authorized (their identity value)
    public function GetTokenEntity() {
	return $this->GetFieldValue('Entity');
    }
    
    // -- FIELD VALUES -- //
    // ++ FIELD CALCULATIONS ++ //
    
    /*----
      RETURNS: TRUE iff expiration date has passed
    */
    public function HasExpired() {
	$sExp = $this->GetFieldValue('WhenExp');
	if (is_null($sExp)) {
	    return TRUE;	// for now, tokens MUST have an expiry
	} else {
	    $dtExp = strtotime($sExp);	// there's got to be a better function for this
	    return ($dtExp < time());	// expiry has passed?
	}
    }
    public function GetExpirationString() {
	return $this->WhenExpires();	// TODO: make it more friendly
    }
    public function HashMatches($sToken) {
	$sSalt = $this->GetTokenSalt();
	$sHash = $this->GetTableWrapper()->MakeHash($sToken,$sSalt);
	return ($sHash == $this->GetTokenHash());
    }

    // -- FIELD CALCULATIONS -- //
    // ++ WRITE DATA ++ //
    
    /*----
      ACTION: update the token's expiration date
    */
    public function Renew() {
	$ar = array('WhenExp'	=> 'NOW() + INTERVAL 1 HOUR');	// expires in 1 hour
	$this->Update($ar);
    }

    // -- WRITE DATA -- //
}
