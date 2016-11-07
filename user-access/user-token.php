<?php
/*
  PURPOSE: classes for handling user authorization tokens
  HISTORY:
    2013-11-27 extracted from user.php
*/

/*%%%%
  PURPOSE: manages emailed authorization tokens
*/
class clsUserTokens extends clsTable {

    // STATIC

    private static function MakeHash($sVal,$sSalt) {
	$sToHash = $sSalt.$sVal;
	$sHash = hash('whirlpool',$sToHash,TRUE);
	return $sHash;
    }

    // / STATIC
    // DYNAMIC

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name('user_tokens');
	  $this->KeyName('ID');
	  $this->ClassSng('clsUserToken');
    }
    public function MakeToken($sEmail) {
	$isStrong = NULL;
	$nHashLen = 32;
	$sToken = openssl_random_pseudo_bytes($nHashLen,$isStrong);
	if (!$isStrong) {
	    $this->Engine()->LogEvent(__FILE__.' line '.__LINE__,NULL,'Could not use strong encryption for token.','WENC',TRUE,FALSE);
	}
	$sSalt = openssl_random_pseudo_bytes($nHashLen);
	// save the salt and hashed token
/*
	$sToHash = $sSalt.$sToken;
	$sHash = hash('whirlpool',$sToHash,TRUE);
*/
	$sHash = self::MakeHash($sToken,$sSalt);

	$ar = array(
	  'TokenHash'	=> SQLValue($sHash),
	  'TokenSalt'	=> SQLValue($sSalt),
	  'WhenExp'	=> 'NOW() + INTERVAL 1 HOUR'	// expires in 1 hour
	  );

	// -- check to see if there's already a hash for this email address
	$sqlEmail = SQLValue($sEmail);
	$sqlFilt = 'Email='.$sqlEmail;
	$rc = $this->GetData($sqlFilt);
	if ($rc->HasRows()) {
	    $rc->NextRow();	// load the record
	    $rc->Update($ar);
	} else {
	    $ar['Email'] = $sqlEmail;
	    $id = $this->Insert($ar);
	    if ($id === FALSE) {
		echo 'SQL='.$this->sqlExec.'<br>';
		throw new exception('<b>Internal error</b>: could not create token record.');
	    }
	    $rc = $this->GetItem($id);
	}
	$rc->Token($sToken);	// caller may need this, but it shouldn't be stored
	return $rc;
    }
    /*----
      NOTE: Even if the token has expired, we want to return it so that we can
	tell the user it has expired. This should minimize frustration, and
	doesn't really pose a security risk as far as I can tell.
      RETURNS: token object if a matching token was found; NULL otherwise
    */
    public function FindToken($idToken,$sToken) {
	//$sqlFilt = '(ID_Email='.$idEmail.') AND (WhenExp > NOW())';
	$sqlFilt = 'ID='.$idToken;
	$rc = $this->GetData($sqlFilt);
	if ($rc->HasRows()) {
	    $rc->NextRow();	// assume there's only 1 row, and load it
	    $sSalt = $rc->Value('TokenSalt');
	    $sHash = self::MakeHash($sToken,$sSalt);
	    if ($sHash == $rc->Value('TokenHash')) {
		return $rc;
	    }
	} else {
	    // no tokens for that email - fail
	    return NULL;
	}
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
	$sql = 'DELETE FROM '.$this->Name().' WHERE WhenExp < NOW()';
	$this->Engine()->Exec($sql);
    }
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
class clsUserToken extends fcRecord_standard {
    private $sToken;

    public function Token($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->sToken = $iVal;
	}
	return $this->sToken;
    }
    /*----
      RETURNS: TRUE iff expiration date has not passed
    */
    public function IsActive() {
	$sExp = $this->Value('WhenExp');
	$dtExp = strtotime($sExp);	// there's got to be a better function for this
	return ($dtExp > time());
    }
    /*----
      ACTION: update the token's expiration date
    */
    public function Renew() {
	$ar = array('WhenExp'	=> 'NOW() + INTERVAL 1 HOUR');	// expires in 1 hour
	$this->Update($ar);
    }
}
