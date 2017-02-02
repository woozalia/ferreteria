<?php
/*
  PURPOSE: classes for handling user authorization tokens
  HISTORY:
    2013-11-27 extracted from user.php
    2016-11-18 revising for db.v2 and for changes to table
      now supports more than one type of auth
      meaning of types must be defined by caller
*/

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
    public static function MakeHash($sVal,$sSalt) {
	$sToHash = $sSalt.$sVal;
	$sHash = hash('whirlpool',$sToHash,TRUE);
	return $sHash;
    }

    // -- STATIC -- //
    // ++ READ DB ++ //

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
		return $rc;
	    } else {
		$this->SetError_TokenMismatch();
	    }
	} else {
	    $this->SetError_TokenNotFound();
	}
	// no matching token found
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
	  'TokenHash'	=> $db->Sanitize_andQuote($sHash),
	  'TokenSalt'	=> $db->Sanitize_andQuote($sSalt),
	  'WhenExp'	=> 'NOW() + INTERVAL 1 HOUR'	// expires in 1 hour
	  );

	// -- check to see if there's already a hash for this entity
	$sqlEntity = $db->Sanitize_andQuote($sEntity);
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
	Maybe they should only be deleted once the user has successfully
	reset their password?
    */
    protected function CleanTokens() {
	$sql = 'DELETE FROM '.$this->TableName_Cooked().' WHERE WhenExp < NOW()';
	$this->GetConnection()->ExecuteAction($sql);
    }
    
    // -- WRITE DB -- //

    /*----
      ACTION: Send a password reset request to the given address.
      RETURNS: HTML to display showing status of request
      INPUT:
	$sAddr: address to which the email should be sent
	$sSubj: subject for the email
	$stMsgEmail : template for emailed message -- may include the following variables:
	  {{addr}} : address to which the email is being sent
	  {{url}} (required) : link to click
	$stMsgWeb : template for message to display after sending email. Uses {{addr}} only.
    */
/*
    public function SendPassReset_forAddr($sAddr,$sName,$sSubj,$stMsgEmail,$stMsgWeb) {
	// generate and store the auth token:
	$rc = $this->MakeToken($sAddr);
	// calculate the authorization URL:
	$url = self::AuthURL($rc->GetKeyValue(),$rc->Token());
	// replace template variables:
	$ar = array(
	  'addr'	=> $sAddr,
	  'url'		=> $url
	  );
	$oTplt = new clsStringTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT,$ar);
	$sMsg = $oTplt->Replace($stMsgEmail);
	// send the email
	$sSubj = KS_TEXT_AUTH_EMAIL_SUBJ;
	$this->Engine()->App()->DoEmail_Auto($sAddr,$sName,$sSubj,$sMsg);

	// display status message
	$sMsg = $oTplt->Replace($stMsgWeb);

	$oSkin = $this->App()->Skin();
	$out = $oSkin->RenderSuccess($sMsg);
	$out .= $oSkin->RenderHLine();
	return $out;
    }
*/

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
    protected function GetTokenType() {
	return $this->GetFieldValue('Type');
    }
    // PUBLIC because login functions need to get this value
    public function GetTokenEntity() {
	return $this->GetFieldValue('Entity');
    }
    
    // -- FIELD VALUES -- //
    // ++ FIELD CALCULATIONS ++ //
    
    /*----
      RETURNS: TRUE iff expiration date has not passed
    */
    public function IsActive() {
	$sExp = $this->GetFieldValue('WhenExp');
	$dtExp = strtotime($sExp);	// there's got to be a better function for this
	return ($dtExp > time());
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
