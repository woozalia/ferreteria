<?php
/*
  PURPOSE: classes for handling login functionality
  NOTE: We frequently have to do the logic at the beginning (e.g. so we know what cookie to emit
    when it's still okay to emit cookies) and then do the rendering later -- so input processing will
    save internal states which rendering will read.
  HISTORY:
    2016-11-13 started - adapting from existing code in old page system
*/
define('KSF_USER_CTRL_USERNAME'		,'uname');
define('KSF_USER_CTRL_ENTER_PASS'	,'upass');
define('KSF_USER_CTRL_SET_PASS1'	,'upass1');
define('KSF_USER_CTRL_SET_PASS2'	,'upass2');
define('KS_USER_URLQ_AUTHCODE'		,'auth');
define('KSF_USER_BTN_LOGIN'		,'btnLogIn');
define('KSF_USER_BTN_REQ_PASS'		,'btnReqPass');	// request the password reset form
define('KSF_USER_BTN_SET_PASS'		,'btnSetPass');	// submit a new password
define('KSF_USER_BTN_NEW_ACCT_EMAIL'	,'btnReqAcct');	// receiving email address to receive authlink
define('KSF_USER_BTN_NEW_ACCT_CREATE'	,'btnCreAcct');	// receiving details sufficient to create new account

define('KI_AUTH_TYPE_NEW_USER',1);
define('KI_AUTH_TYPE_RESET_PASS',2);

class fcpeLoginWidget extends fcpeSimple {
    use ftExecutableTwig;

    // ++ EVENTS ++ //
    
    protected function OnCreateElements(){}	// nothing to do
    protected function OnRunCalculations() {
	$this->ProcessInput();
    }
    
    // -- EVENTS -- //
    // ++ CLASSES ++ //
    
    protected function TokensClass() {
	return 'fcUserTokens';
    }
    
    // -- CLASSES -- //
    // ++ TABLES ++ //

    protected function TokenTable() {
	return $this->GetDatabase()->MakeTableWrapper($this->TokensClass());
    }
      
    // -- TABLES -- //
    // ++ FRAMEWORK ++ //
    
    protected function GetPageObject() {
	return fcApp::Me()->GetPageObject();
    }
    // PUBLIC so widgets can access it
    public function GetElement_PageContent() {
	return $this->GetPageObject()->GetElement_PageContent();
    }
    protected function AddErrorMessage($s) {
	$this->GetPageObject()->AddErrorMessage($s);
    }
    protected function AddWarningMessage($s) {
	$this->GetPageObject()->AddWarningMessage($s);
    }
    protected function AddSuccessMessage($s) {
	$this->GetPageObject()->AddSuccessMessage($s);
    }
    protected function SendEmail_forLoginSuccess() {
	$this->GetPageObject()->SendEmail_forLoginSuccess();
    }
    
    // -- FRAMEWORK -- //
    // ++ INPUT ++ //

	//++external input++//
    
    /*----
      PURPOSE: outside app sets this TRUE if it determines that a new account
	is being requested (could be via button, could be via fragument...)
	It needs to be set TRUE or FALSE, one way or the other, during OnRunCalculations().
    */
    /*
    private $isNewAcctReq;
    public function SetInput_IsNewAccountRequest($b) {
	$this->isNewAcctReq = $b;
    }
    protected function GetInput_IsNewAccountRequest() {
	if (!isset($this->isNewAcctReq)) {
	    throw new exception('Application must call SetInput_IsNewAccountRequest().');
	}
	return $this->isNewAcctReq;
    }*/
    /*----
      PURPOSE: outside app calls this if it determines that the user wants to log in.
    */
    public function SetInput_ShowLoginForm() {
	$this->SetStatus_DoNext(self::KS_STEP_SHOW_LOGIN);
    }
    /*----
      PURPOSE: set internally if login form data is detected
    */
    protected function SetInput_ReceiveLoginForm() {
	$this->SetStatus_DoNext(self::KS_STEP_RECV_LOGIN);
    }
    /*----
      PURPOSE: outside app calls this if it determines that the user wants to reset their password.
    */
    public function SetInput_ShowPasswordResetAuthRequestForm() {
	$this->SetStatus_DoNext(self::KS_STEP_SHOW_PASSRESET_USER);
    }

	//--external input--//
	//++input reading++//

    // INPUT: existing username, existing password
    protected function GetInput_IsLoginRequest() {
	return !empty($_POST[KSF_USER_BTN_LOGIN]);
    }
    // INPUT: button for requesting the password reset form
    protected function GetInput_IsPasswordResetForm() {
	return !empty($_POST[KSF_USER_BTN_REQ_PASS]);
    }
    // INPUT: button for submitting a new password
    protected function GetInput_IsPasswordSubmission() {
	return !empty($_POST[KSF_USER_BTN_SET_PASS]);
    }
    // INPUT: button for form that just has an email address
    protected function GetInput_IsNewAccountEmailForm() {
	return !empty($_POST[KSF_USER_BTN_NEW_ACCT_EMAIL]);
    }
    // INPUT: button for form with username and password(x2)
    protected function GetInput_IsNewAccountCreateForm() {
	return !empty($_POST[KSF_USER_BTN_NEW_ACCT_CREATE]);
    }
    // INPUT: authcode from URL
    protected function GetInput_AuthCodeValue() {
	return $_GET[KS_USER_URLQ_AUTHCODE];
    }
    protected function GetInput_AuthCodeFound() {
	return array_key_exists(KS_USER_URLQ_AUTHCODE,$_GET);
    }
    protected function GetInput_Username() {
	return trim(fcArray::Nz($_POST,KSF_USER_CTRL_USERNAME));
    }
    protected function GetInput_Password() {
	return trim(fcArray::Nz($_POST,KSF_USER_CTRL_ENTER_PASS));
    }
    protected function GetInput_PasswordOne() {
	return trim(fcArray::Nz($_POST,KSF_USER_CTRL_SET_PASS1));
    }
    protected function GetInput_PasswordTwo() {
	return trim(fcArray::Nz($_POST,KSF_USER_CTRL_SET_PASS2));
    }
    
	//--input reading--//
	//++input calculations++//
    
    /*----
      ACTION: receive input (POST/GET), and set internal states and any needed cookies
	Render() will determine what to display based on internal states.
      STEPS:
	PassReset --
	  STEP 0: Display a form for entering a username
	  STEP 1: Authcode request (with username) received (generate PR authcode, look up email address for username, and email authcode)
	  STEP 2: Receive a PR authcode (if valid, display PR form)
	  STEP 3: Receive PR form data (if valid, reset password)
	NewAcct --
	  STEP 0: Display a form for entering an email address
	  STEP 1: Receive email address (generate NA authcode and email it)
	  STEP 2: Receive NA authcode (if valid, display NewAcct form)
	  STEP 3: NewAcct form received (if everything is valid, create new account)
    */
    const KS_STEP_SHOW_LOGIN		= 'LIS';	// show the existing-user login form
    const KS_STEP_RECV_LOGIN		= 'LIR';	// login form received
    const KS_STEP_SHOW_PASSRESET_USER	= 'PR0';	// show the password-reset initiation form (asks for username)
    const KS_STEP_SHOW_PASSRESET_PASS	= 'PR2';	// show the password-reset request form (asks for new password)
    const KS_STEP_SHOW_EMAIL_ADDR	= 'NA0';	// show form for entering an email address to request a new account
    const KS_STEP_SHOW_NEW_ACCT_REQ	= 'NA2';	// show new account request form (asks for username and password (x2))
    
    protected function ProcessInput() {
	$nState = $this->GetStatus_DoNext();
	
	if ($this->GetInput_IsLoginRequest()) {

	    // try to authorize existing account
	    $rcSess = fcApp::Me()->GetSessionRecord();
	    $rcSess->UserLogin(
	      $this->GetInput_Username(),
	      $this->GetInput_Password()
	      );
	    if ($rcSess->UserIsLoggedIn()) {
		$sUser = $rcSess->UserString();
		$this->AddSuccessMessage("You are now logged in, $sUser.");
// DEACTIVATE WHILE DEBUGGING		$this->SendEmail_forLoginSuccess();	// if appropriate, email the user a notif about the login
		// redirect to non-login page
		$this->RedirectToEraseRequest();
	    } else {
		$this->AddErrorMessage('Log-in attempt failed. Sorry!');
		$this->SendEmail_forLoginFailure();	// email the user a notif about the failed attempt
		$this->RedirectToEraseRequest();
	    }

	} elseif ($this->GetInput_AuthCodeFound()) {
	    // could be either PR STEP 2 or NA STEP 2
	    $this->SetFromInput_AuthCodeStatus();
	} elseif ($nState == self::KS_STEP_SHOW_EMAIL_ADDR) {
	    // PR STEP 1: generate authcode, look up username, send authcode to user's email
	    $rcUser = $this->UserTable()->FindUser($this->GetInput_Username());
	    if ($rcUser->RowCount == 1) {
		$sEmail = $rcUser->EmailAddress();
		$rcToken = $this->TokenTable()->MakeToken(KI_AUTH_TYPE_RESET_PASS,$sEmail);
		$this->SendEmail_forPassReset($rcToken);
	    } else {
		$sUser = $rcUser->UserName();
		throw new fcDebugException("Ferreteria Data Error: Multiple records found for username [$sUser].");
	    }
	} elseif ($this->GetInput_IsPasswordResetForm()) {
	    // PR STEP 3: new password received: check authcode, reset password if everything is valid
	    $this->SetFromInput_AuthCodeStatus();
	    if ($this->GetStatus_AuthCodeValid()) {
		if ($this->GetFromInput_NewPasswordOkay()) { 
		    $this->SetFromInput_NewPassword();
		}
	    }
	} elseif ($this->GetInput_IsNewAccountEmailForm()) {
	    // NA STEP 1: receive email address for authcode; generate and send authcode
	    $rcToken = $this->TokenTable()->MakeToken(KI_AUTH_TYPE_NEW_USER,$sEmail);
	    $this->SendEmail_forNewUser($rcToken);
	} elseif ($this->GetInput_IsNewAccountCreateForm()) {
	    // NA STEP 3: NA form received; if ok, create account
	    $this->CheckInput_NewAccountCreateForm();
	}
    }
    protected function RedirectToEraseRequest() {
	$this->GetElement_PageContent()->RedirectToEraseRequest();
    }
    // NOTE: can override this for more stringent requirements
    protected function SetStatus_GivenPasswordIsValid($s) {
	$this->SetStatus_InputPasswordIsValid(
	  strlen($s) > 6
	  );
    }
    // NOTE: can override this for more stringent requirements
    protected function SetStatus_GivenUsernameIsValid($s) {
	$this->SetStatus_InputUsernameIsValid(
	  $strlen($s) > 2
	  );
    }
    protected function SetFromInput_NewPasswordOkay() {
	$sPass1 = $this->GetInput_PasswordOne();
	$sPass2 = $this->GetInput_PasswordTwo();
	$this->SetStatus_GivenPasswordIsValid($sPass1);
	if ($this->GetStatus_GivenPasswordIsValid()) {
	    if ($sPass1 == $sPass2) {
		$this->SetStatus_NewPasswordOkay(TRUE);
	    } else {
		$this->SetError_PasswordMismatch();
	    }
	} else {
	    $this->SetError_UnacceptablePassword();
	}
	$this->SetStatus_NewPasswordOkay(FALSE);
    }
    // ASSUMES: token is valid
    protected function CheckInput_NewAccountCreateForm() {
	$this->SetSuccess(FALSE);
	$sUser = $this->GetInput_Username();
	// check to see if username already exists
	$this->SetStatus_GivenUsernameIsValid($sUser);
	if ($this->GetStatus_GivenUsernameIsValid()) {
	    $this->SetFromInput_NewPasswordOkay();
	    if ($this->GetStatus_NewPasswordOkay()) {
		$sEmail = $this->TokenRecord()->GetEntity();	// for new account tokens, entity is email address
		$sPass = $this->GetInput_PasswordOne();
		$this->CreateNewAccount($sUser,$sPass,$sEmail);
		$this->SetSuccess(TRUE);
	    }
	}
    }
    protected function SetFromInput_AuthCodeStatus() {
	$arAuth = $this->ParseAuthCode($this->GetInput_AuthCodeValue());
	$this->Check_AuthCodeStatus($arAuth);
	$rcToken = $this->Get_AuthTokenRecord();
	if (is_null($rcToken)) {
	    $this->AddErrorMessage('Authorization token received, but it was not valid. Sorry!');
	    // LATER: maybe the token URL should include enough info that we could re-display the request form
	} else {
	    $nType = $rcToken->GetTokenType();
	    switch ($nType) {
	      case KI_AUTH_TYPE_NEW_USER:
		$this->SetStatus_DoNext(self::KS_STEP_SHOW_NEW_ACCT_REQ);
		break;
	      case KI_AUTH_TYPE_RESET_PASS:
		$this->SetStatus_DoNext(self::KS_STEP_SHOW_NEW_ACCT_OK);
		break;
	      default:
		throw new exception('2016-12-18 Invalid token type in stored data. This should never happen.');
	    }
	}
    }

	//--input calculations--//
	//++input-to-rendering states++//
    
    private $nStep;
    protected function SetStatus_DoNext($nStep) {
	$this->nStep = $nStep;
    }
    protected function GetStatus_DoNext() {
	return $this->nStep;
    }
    private $isUsernameValid;
    protected function SetStatus_InputUsernameIsValid($b) {
	$this->isUsernameValid = $b;
	// TODO: add an error message if FALSE
    }
    protected function GetStatus_InputUsernameIsValid() {
	return $this->isUsernameValid;
    }
    private $isPassValid;
    protected function SetStatus_InputPasswordIsValid($b) {
	$this->isPassValid = $b;
	// TODO: add an error message if FALSE
    }
    protected function GetStatus_InputPasswordIsValid() {
	return $this->isPassValid;
    }
    private $isNewPassOk;
    protected function SetStatus_NewPasswordOkay($b) {
	$this->$isNewPassOk = $b;
    }
    protected function GetStatus_NewPasswordOkay() {
	return $this->$isNewPassOk;
    }
    private $isAuthValid;
    protected function SetStatus_AuthCodeValid($b) {
	$this->isAuthValid = $b;
    }
    protected function GetStatus_AuthCodeValid() {
	return $this->isAuthValid;
    }
    private $sErrCode;
    protected function SetError_InvalidAuthCode() {
	$this->sErrCode = 'INV';
    }
    protected function SetError_ExpiredAuthCode() {
	$this->sErrCode = 'EXP';
    }
    protected function SetError_FailedWritingPassword() {
	$this->sErrCode = 'WRI';
    }
    protected function SetError_TokenError($sCode) {
	$this->sErrCode = 'TOK.'.$sCode;
    }
    protected function DidSucceed() {
	return empty($this->sErrCode);
    }
    protected function GetErrorCode() {
	return $this->sErrCode;
    }
    
	//--input-to-rendering states--//
    
    // -- INPUT -- //
    // ++ LOGIC ++ //

    // ASSUMES: There is an authcode to process
    protected function ParseAuthCode($sAuth) {
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
    protected function SetFromInput_NewPassword() {
	// TODO
    }
    /*----
      INPUT: $arAuth = array containing elements of parsed authcode, as returned by ParseAuthCode()
      OUTPUT: sets status properties
      RETURNS: nothing
      NOTES:
      * During render phase, if the token is invalid, a form for requesting another should be displayed.
      * It's arguable that renewing the token should be done somewhere else, but I'm not going to worry about it now.
    */
    protected function Check_AuthCodeStatus(array $arAuth) {
	$id = $arAuth['id'];
	$sToken = $arAuth['bin'];
	$sAuth = $arAuth['auth'];
	
	$tToken = $this->TokenTable();
	$oToken = $tToken->FindToken($id,$sToken);	// returns NULL or matching token record

	if (is_null($oToken)) {
	    $this->SetError_TokenError($tToken->GetErrorCode());
	} else {
	    $isMatch = TRUE;	// token matches records
	    if ($oToken->IsActive()) {
		// token has not expired
		$oToken->Renew();	// extend the expiration
	    }
	}
    }
    private $rcToken;
    protected function Set_AuthTokenRecord(fcUserToken $rc) {
	$this->rcToken = $rc;
    }
    protected function Get_AuthTokenRecord() {
	return $this->rcToken;
    }
    protected function FigureURL_forAuthCode($id,$sToken) {
	$sTokenHex = bin2hex($sToken);
	$url = 'https://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."?auth=$id:$sTokenHex";
	return $url;
    }
    protected function RedactedEmailAddress($sAddr) {
	list($sUser,$sDomain) = explode('@',$sAddr);
	// replace all but first and last character of user with '*'
	$sLen = strlen($sUser);
	$chFirst = substr($sUser,0);
	$chFinal = substr($sUser,-1);
	$sUser = $chFirst.str_repeat(strlen($sUser)-2,'*').$chLast;
	return $sUser.'@'.$sDomain;
    }
    
    // -- LOGIC -- //
    // ++ ACTIONS ++ //  

    protected function CreateNewAccount($sUser,$sPass,$sEmail) {
	$t = $this->UserTable();
	$rc = $t->AddUser($sUser,$sPass,$sEmail);
	// 2017-01-16 Hang on. Do we really want to log the user in automatically? What if an admin is creating an account?
	//$this->SessionRecord()->SetUserRecord($rc);
	// In any case, SetUserRecord() ls protected.
    }
    // ASSUMES: it's okay to update the password (all authorization has been verified)
    protected function ChangePassword($sPass) {
	$rcUser = $this->GetUserRecord();
	$ok = $rcUser->SetPassword($sPass);
	if (!$ok) {
	    $this->SetError_FailedWritingPassword();
	}
    }
    
    // -- DATA ACCESS -- //
    // ++ OUTPUT ++ //

	//++screen++//
    
    // ACTION: Displays forms as needed according to internal states
    public function Render() {
    
	switch ($this->GetStatus_DoNext()) {
	  case self::KS_STEP_SHOW_LOGIN:		// show the existing-user login form
	    return $this->RenderForm_Login();
	  case self::KS_STEP_SHOW_PASSRESET_USER:	// show the password-reset initiation form (asks for username)
	    return $this->RenderForm_GetUserForPasswordReset();
	  case self::KS_STEP_SHOW_PASSRESET_PASS:	// show the password-reset request form (asks for new password)
	    return $this->RenderForm_GetNewPasswordsForUser();
	  case self::KS_STEP_SHOW_EMAIL_ADDR:		// show the account-creation initiation form (asks for email address)
	    return $this->RenderForm_GetEmailAddressForNewAcct();
	  case self::KS_STEP_SHOW_NEW_ACCT_REQ:
	    return $this->RenderForm_GetNewAccountSpecs();
	  default:
	    return NULL;	// nothing happening with the login widget
	}

/* 2016-12-18 This is probably all completely redundant now...

	// 2016-11-18 TO BE REWRITTEN - must only do output, based on internal states
    
	$this->SetMode_ShowLogin(TRUE);	// By default, we'll still show the login form if not logged in
	$isEmailAuth = FALSE;	// Assume this page is not an email authorization link until proven guilty.
	
	$ht = NULL;
	$ok = FALSE;	// set false initially so we do one iteration
	while (!$ok) {
	    $ok = TRUE; 	// assume success

	    // check auth link and display form if it checks out
	    if ($this->GetMode_IsAuthLink()) {
		// this is an AUTH link, so ignore any other stuff

		// parse the authcode
		$arAuth = $this->ParseAuthCode($this->GetInput_AuthCodeValue());
		$arStatus = $this->CheckData_AuthCodePasses($arAuth);
		
		$ar = $this->CheckAuth();	// check token
		$ht = $this->UserAccess_ProcessAuth($ar);

		if ($this->IsCreateRequest()) {
		    $ht .= $this->UserAccess_CreateRequest($ar);
		} elseif ($this->IsResetRequest()) {	// password change request submitted
		    $ht .= $this->UserAccess_ResetRequest();
		}

	    } elseif($this->doEmail) {

		// REQUEST AUTH LINK form has been submitted

		$ht .= $this->SendPassReset_forAddr(
		  $this->EmailAddress(),
		  $this->LoginName()
		  );
	    // END do email
	    } elseif($this->isLogin) {
		if ($this->IsLoggedIn()) {
		    $sUser = $this->LoginName();
		    $sSite = KS_SITE_SHORT;
		    $htMsg = $oSkin->SuccessMessage("Welcome to $sSite, $sUser.");
		    $this->RedirectHome($htMsg);
		}

		// LOGIN FAILED: login was tried, but we're still here (not logged in), so it must have failed:

		$ht .= $oSkin->ErrorMessage('Sorry, the given username/password combination was not valid.');
		$ht .= $oSkin->HLine();
	    // END is login
	    } else {
		// TODO : log as possible illicit hacking attempt
	    }

	    if ($this->doShowLogin) {
		$ht .= "\n<b>If you already have a user account on this site</b>, you can log in now:<br>"
		  .$this->RenderLogin($this->LoginName())
		  .$oSkin->HLine();
	    }
	    if ($this->IsAuthLink()) {
		$htMsgPre = 'You can request another authorization email here';
		$htMsgPost = NULL;
	    } else {
		$htMsgPre = '<b>If you have forgotten your password or have not set up an account</b>';
		$htMsgPost = '<br>This will email you a link to set or reset your password.';
	    }

	    $ht .= "\n$htMsgPre:<br>"
		  .$oSkin->RenderForm_Email_RequestReset($this->EmailAddress())
		  ."\n$htMsgPost";
	}
	return $ht;
*/
    }
    /*----
      PURPOSE: indicates login status, provides link to log in if not already logged in
      USAGE: typically called from outside, because the layout depends on the Page design
    */
    public function Render_StatusControl() {
	if (fcApp::Me()->UserIsLoggedIn()) {
	    $out = $this->Render_UserProfileMessage(fcApp::Me()->GetUserRecord());
	} else {
	    $out = $this->Render_LoginRequestControl();
	}
	return $out;
    }
    // 2016-12-25 Temporarily not sure why this needs to be public.
    protected function Render_LoginRequestControl() {
	$urlBase = fcApp::Me()->GetKioskObject()->GetBasePath();
	return "<a href='$urlBase/do:login/'>log in!!</a>";	// 2016-12-18 kluge for now
    }
    /*----
      NOTE: Can't seem to get the HTTP REFERER, otherwise I'd include it as a hidden input here
	so a successful login could redirect there.
    */
    protected function RenderForm_Login() {
	$sName = '';	// TODO: fill in submitted userame, if this is a repeat attempt
	$htName = htmlspecialchars($sName);
	$htBtn = KSF_USER_BTN_LOGIN;
	return <<<__END__

<table class="form-block-login"><tr><td><form method=post>
Username:<input name=uname size=10 value="$htName">
Password:<input type=password name=upass size=10>
<input type=submit value="Log In" name="$htBtn">
</form></td></tr></table>
__END__;
    }
    protected function Render_UserProfileMessage(fcrUserAcct $rcUser) {
	$htUser = $rcUser->SelfLink_name();
	return "Hi, $htUser!";
    }
    
	//--screen--//
	//++email++//

    /*----
      TODO:
	* Constants used here should perhaps be options retrieved from the App object?
	* There's probably some way to have less redundancy between these two methods.
    */
    protected function SendEmail_forNewUser(fcUserToken $rcToken) {
    
	$oTplt = new fcTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT);

	// these areguments are used both for the email text and the web text
	$url = $this->FigureURL_forAuthCode($rcToken->GetKeyValue(),$rcToken->GetToken());
	$ar = array(
	  'addr'	=> $rcToken->GetEntity(),
	  'action'	=> 'allow you to create a new user account',
	  'url'		=> $url
	  );
	$oTplt->VariableValues($ar);

	// generate the email
	$oTplt->Template(KS_TPLT_AUTH_EMAIL_TO_SEND);
	$sMsg = $oTplt->Render();
	$sSubj = KS_TEXT_AUTH_EMAIL_SUBJ;
	fcApp::Me()->DoEmail_Auto($sAddr,$sName,$sSubj,$sMsg);

	// generate status message
	$oTplt->Template(KS_TPLT_AUTH_EMAIL_TO_SHOW);
	$sMsg = $oTplt->Render();
	$this->SetStatus_DisplayMessage($sMsg);
    }
    /*----
      TODO:
	* Constants used here should perhaps be options retrieved from the App object?
	* There's probably some way to have less redundancy between these two methods.
    */
    protected function SendEmail_forPassReset(fcUserToken $rcToken) {
    
	$oTplt = new fcTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT);

	// these areguments are used both for the email text and the web text
	$url = $this->FigureURL_forAuthCode($rcToken->GetKeyValue(),$rcToken->GetToken());
	$idUser = $rcToken->GetEntity();
	$rcUser = $this->UserTable()->GetRecord_forKey($idUser);
	$ar = array(
	  'addr'	=> $this->RedactedEmailAddress($rcUser->EmailAddress()),
	  'action'	=> 'allow you to change your password',
	  'url'		=> $url
	  );
	$oTplt->VariableValues($ar);

	// generate the email
	$oTplt->Template(KS_TPLT_AUTH_EMAIL_TO_SEND);
	$sMsg = $oTplt->Render();
	$sSubj = KS_TEXT_AUTH_EMAIL_SUBJ;
	fcApp::Me()->DoEmail_Auto($sAddr,$sName,$sSubj,$sMsg);

	// generate status message
	$oTplt->Template(KS_TPLT_AUTH_EMAIL_TO_SHOW);
	$sMsg = $oTplt->Render();
	$this->SetStatus_DisplayMessage($sMsg);
    }

    // -- OUTPUT -- //
}
/*----
  USAGE: use in whatever element should contain the Login Widget node but:
    * ONLY USE IN ONE ELEMENT, else you will get multiple LWs, resulting
      in stuff happening twice whenever it's supposed to happen once.
    * DO NOT CALL EVENTS (including Render()) directly, otherwise the above will happen.
      Render_LoginWidget() is an exception, because it probably needs to display
      within a different element than the one where the login widget proper lives.
*/
trait ftLoginContainer {

    // ++ ABSTRACT ++ //
    
    abstract protected function Class_forLoginWidget();
    abstract protected function GetElement_LoginWidget();	// get the login widget, wherever it is hiding
    

    // -- ABSTRACT -- //
    // ++ OVERRIDES ++ //

    protected function InitNodes() {
	parent::InitNodes();
    }

    // -- OVERRIDES -- //
    // ++ NEW ELEMENTS ++ //
    
    protected function MakeElement_LoginWidget() {
	return $this->MakeNode('login',$this->Class_forLoginWidget());
    }

    // -- NEW ELEMENTS -- //

}
trait ftLoginContainer_standard {
    use ftLoginContainer;

    // ++ ABSTRACT ++ //
    
    // 2016-12-25 This now seems unnecessary.
    //abstract protected function Render_LoginRequestControl();	// like "log in" with a link
    
    // -- ABSTRACT -- //
    // ++ CEMENTING ++ //
    
    protected function Class_forLoginWidget() {
	return 'fcpeLoginWidget';
    }

    // -- CEMENTING -- //

}
    