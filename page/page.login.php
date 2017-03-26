<?php
/*
  PURPOSE: classes for handling login functionality
  NOTE: We frequently have to do the logic at the beginning (e.g. so we know what cookie to emit
    when it's still okay to emit cookies) and then do the rendering later -- so input processing will
    save internal states which rendering will read.
  HISTORY:
    2016-11-13 started - adapting from existing code in old page system
    2017-02-04 reworking the processing logic; also, new acct form written
*/

// ++ FORMS AND PATHARGS ++ //

define('KSF_USER_CTRL_USERNAME'		,'uname');
define('KSF_USER_CTRL_ENTER_PASS'	,'upass');
define('KSF_USER_CTRL_SET_PASS1'	,'upass1');
define('KSF_USER_CTRL_SET_PASS2'	,'upass2');
define('KS_USER_URLQ_AUTHCODE'		,'auth');
define('KS_PATH_ACTION_KEY'		,'do');		// path key for actions (currently, only new acct and login)

// NEW ACCOUNT
define('KS_USER_PATH_REQ_NEW_ACCT'	,'newacct');	// request to initiate new account process
define('KF_USER_EMAIL_ADDRESS'		,'uemail');	// name of form field for entering email address
define('KSF_USER_BTN_NEW_ACCT_EMAIL'	,'btnReqAcct');	// receiving email address to receive authlink
define('KSF_USER_BTN_NEW_ACCT_CREATE'	,'btnCreAcct');	// receiving details sufficient to create new account

// PASSWORD RESET
define('KS_USER_PATH_REQ_PASS_RESET'	,'pwreset');		// request the lost-password reset form
define('KS_USER_BTN_PASS_RESET_USER'	,'btnReqReset');	// submit username for password reset
define('KSF_USER_BTN_SET_PASS'		,'btnSetPass');		// submit a new password

// LOGIN
define('KS_USER_PATH_REQ_LOGIN_FORM'	,'login');	// request for login form
define('KSF_USER_BTN_LOGIN'		,'btnLogIn');	// submit user/pass to log in
define('KS_USER_PATH_REQ_LOGOUT'	,'logout');
define('KS_USER_PATH_REQ_PROFILE_PAGE'	,'profile');

define('KI_AUTH_TYPE_NEW_USER',1);
define('KI_AUTH_TYPE_RESET_PASS',2);

// -- FORMS AND PATHARGS -- //
// ++ EVENTS ++ //

define('KS_EVENT_FERRETERIA_RECV_PW_RESET','fe.login.PR2');

// -- EVENTS -- //


abstract class fcpeLoginWidget extends fcpeSimple {
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
	return fcApp::Me()->GetDatabase()->MakeTableWrapper($this->TokensClass());
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
    
    // -- FRAMEWORK -- //
    // ++ INPUT ++ //

	//++internal states++// -- can be set internally or by external caller
    
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

	//--internal states--//
	//++user input states++//

    protected function GetInput_PathRequest() {
	return fcApp::Me()->GetKioskObject()->GetInputObject()->GetString(KS_PATH_ACTION_KEY);
    }

    // vv 2017-02-04 these may all be obsolete now
    
    // INPUT: URL path includes "do:login"
    
    /* 2017-02-12 Apparently this is no longer used.
    protected function GetInput_IsLoginFormRequest() {
	$sDo = fcApp::Me()->GetKioskObject()->GetInputObject()->GetString(KS_PATH_ACTION_KEY);
	return $sDo == KS_USER_PATH_REQ_LOGIN_FORM;
    } */
    // INPUT: existing username, existing password
    protected function GetInput_IsLoginAttempt() {
	return !empty($_POST[KSF_USER_BTN_LOGIN]);
    }
    // DETECTS: button for submitting the password reset form
    protected function GetInput_IsPasswordResetForm() {
	return !empty($_POST[KSF_USER_BTN_SET_PASS]);
    }
    /* 2017-02-05 no longer needed
    // INPUT: URL path includes "do:newacct"
    protected function GetInput_IsNewAccountRequest() {
	$sDo = fcApp::Me()->GetKioskObject()->GetInputObject()->GetString(KS_PATH_ACTION_KEY);
	return $sDo == KS_USER_PATH_REQ_NEW_ACCT;
    }
    */
    // DETECTS: button for new account auth email address form
    protected function GetInput_IsNewAccountEmailForm() {
	return !empty($_POST[KSF_USER_BTN_NEW_ACCT_EMAIL]);
    }
    // DETECTS: button for password reset email address form
    protected function GetInput_IsPasswordUserForm() {
	return !empty($_POST[KS_USER_BTN_PASS_RESET_USER]);
    }
    // DETECTS: button for form with username and password(x2)
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
    protected function GetInput_EmailAddress() {
	return trim(fcArray::Nz($_POST,KF_USER_EMAIL_ADDRESS));
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
    
	//--user input states--//
	//++input calculations++//
    
    /*----
      ACTION: receive input (POST/GET), and set internal states and any needed cookies
	Render() will determine what to display based on internal states.
      LOGIC:
	CONDITIONS:
	  ACR = authcode required
	  DETA = step is detected from authcode
	  DETF = step is detected from form input (button name)
	  DETP = step is detected from path content (do:$x)
	SEQUENCE:
	  (NA) NewAcct --
	    STEP 0 (DETP): Display a form for entering an email address (NA email form)
	    STEP 1 (DETF): Receive email address (generate NA authcode and email it)
	    STEP 2 (DETA): Display form for entering new account info (NA final form)
	    STEP 3 (DETF+ACR): NA final form received (if everything is valid, create new account; else repeat step 2)
	  (PR) PassReset --
	    STEP 0 (DETP): Display a form for entering a username (PR username form)
	    STEP 1 (DETF): Username received; generate PR authcode, look up email address for username, and email authcode
	    STEP 2 (DETA): Display password reset form (PR final form)
	    STEP 3 (DETF+ACR): Receive PR final form data (if valid, reset password; if not, repeat step 2)
	  (LGN) Login --
	    STEP 0 (DETP): Display user/pass form (LGN form)
	    STEP 1 (DETF): Receive entered user/pass (if valid, log user in; if not, repeat step 0)
	  
	BY DETECTION METHOD:
	  DETP: NA0, PR0, LGN0
	  DETF: NA1, PR1, LGN1
	  DETA: NA2, PR2
	
    */
    const KS_STEP_SHOW_LOGIN		= 'LIS';	// show the existing-user login form
    const KS_STEP_RECV_LOGIN		= 'LIR';	// login form received
    const KS_STEP_PR0_USERNAME_FORM	= 'PR0';	// show the password-reset initiation form (asks for username)
    const KS_STEP_PR2_PASSWORD_FORM	= 'PR2';	// show the password-reset request form (asks for new password)
    const KS_STEP_NA0_EMAIL_ADDR_FORM	= 'NA0';	// show form for entering an email address to request a new account
    const KS_STEP_NA2_ACCT_ENTRY_FORM	= 'NA2';	// show new account request form (asks for username and password (x2))
    
    protected function ProcessInput() {

      // STAGE 0: check DETA - set flags - these may affect other states - but don't do anything yet (need to check DETF)

	if ($this->GetInput_AuthCodeFound()) {
	    $this->SetFromInput_AuthCodeStatus();	// sets DO-NEXT status as appropriate
	    if (!$this->DidSucceed()) {
		// a bad authcode prevents anything else from happening
		$this->RedirectToDefaultPage();	// remove the authcode from the URL and go home
	    }
	    $gotAuth = TRUE;
	} else {
	    $gotAuth = FALSE;
	}
    
      // STAGE 1: check DETF - these aren't persistent, so if you see one something has happened

	$doStage2 = FALSE;
	if ($this->GetInput_IsLoginAttempt()) {			// LGN1
	    $this->TryLogin_fromInput();
	    // redirects & does not return
	} elseif ($this->GetInput_IsPasswordResetForm()) {	// PR3
	    if ($gotAuth) {
		$this->TryPasswordReset_fromInput();
	    }
	} elseif ($this->GetInput_IsPasswordUserForm()) {	// PR1
	    $this->SendPasswordResetEmail_fromInput();
	} elseif ($this->GetInput_IsNewAccountCreateForm()) {	// NA3
	    if ($gotAuth) {
		$this->HandleInput_NewAccountForm();
	    }
	} elseif ($this->GetInput_IsNewAccountEmailForm()) {	// NA1
	    $this->SendNewAccountAuthEmail_fromInput();
	}
	// If any of the above is triggered, it redirects and exits.
        
      // STAGE 2: if no other triggers, check for DETP events (DETP can remain active during DETF, so have to check it last)
      
	$sDo = $this->GetInput_PathRequest();
	switch ($sDo) {
	  case KS_USER_PATH_REQ_NEW_ACCT:	// NA0
	    $this->SetStatus_DoNext(self::KS_STEP_NA0_EMAIL_ADDR_FORM);
	    break;
	  case KS_USER_PATH_REQ_PASS_RESET:	// PR0
	    $this->SetStatus_DoNext(self::KS_STEP_PR0_USERNAME_FORM);
	    break;
	  case KS_USER_PATH_REQ_LOGIN_FORM:	// LGN0
	    $this->SetInput_ShowLoginForm();
	    break;
	  case KS_USER_PATH_REQ_LOGOUT:		// log out immediately
	    $rcSess = fcApp::Me()->GetSessionRecord();
	    $rcSess->UserLogout();
	    $this->RedirectToDefaultPage();	// done; remove the logout request
	}
	// If any of these are detected (except logout), set a state and Render() will do the rest.
	
    /* take 1
	$nState = $this->GetStatus_DoNext();
	
	if ($this->GetInput_IsLoginAttempt()) {

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
		$this->StoreSubmittedUsername();	// stash the submitted username
		$this->RedirectToEraseRequest();
	    }
	} elseif ($this->GetInput_IsLoginFormRequest()) {
	    $this->SetInput_ShowLoginForm();
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
	    // We have to check for this *before* checking the URL, otherwise we just keep getting the form (NA0).
	    $sEmail = $this->GetInput_EmailAddress();
	    $rcToken = $this->TokenTable()->MakeToken(KI_AUTH_TYPE_NEW_USER,$sEmail);
	    $this->SendEmail_forNewUser($rcToken);
	} elseif ($this->GetInput_IsNewAccountRequest()) {
	    // NA STEP 0: display form to get email address for account request link
	    $this->SetStatus_DoNext(self::KS_STEP_SHOW_EMAIL_ADDR);
	} elseif ($this->GetInput_IsNewAccountCreateForm()) {
	    // NA STEP 3: NA form received; if ok, create account
	    $this->CheckInput_NewAccountCreateForm();
	} elseif ($this->GetInput_AuthCodeFound()) {
	    // have to do this last, because a lot of other steps require a valid authcode too
	    // could be either PR STEP 2 or NA STEP 2
	    $this->SetFromInput_AuthCodeStatus();
	}
	*/
    }
    protected function RedirectToDefaultPage() {
	$this->GetElement_PageContent()->RedirectToDefaultPage();
    }
    protected function RedirectToSamePage() {
	$this->GetElement_PageContent()->RedirectToSamePage();
    }
    protected function TryLogin_fromInput() {
	$rcSess = fcApp::Me()->GetSessionRecord();
	$rcSess->UserLogin(
	  $this->GetInput_Username(),
	  $this->GetInput_Password()
	  );

	if ($rcSess->UserIsLoggedIn()) {
	    $sUser = $rcSess->UserString();
	    $this->AddSuccessMessage("You are now logged in, $sUser.");
	    $this->SendEmail_forLoginSuccess();	// if appropriate, email the user a notif about the login
	    // redirect to non-login page
	    $this->RedirectToDefaultPage();
	} else {
	    $this->AddErrorMessage('Log-in attempt failed. Sorry!');
	    $this->SendEmail_forLoginFailure();	// email the user a notif about the failed attempt
	    $this->StoreSubmittedUsername();	// stash the submitted username
	    $this->RedirectToSamePage();	// redirect to same URL -- let user try again
	}
    }
    protected function TryPasswordReset_fromInput() {
	$rcToken = $this->Get_AuthTokenRecord();
	$idType = $rcToken->GetTokenType();
	if ($idType != KI_AUTH_TYPE_RESET_PASS) {
	    // I'm not even sure this can actually happen, but why not check.
	    $sErr = "suspicious input: password reset form recieved with wrong auth token type ($idType)";
	    fcApp::Me()->CreateEvent(KS_EVENT_FERRETERIA_SUSPICIOUS_INPUT,$sErr);
	    // TODO: unfortunately, we don't know who the real user is, so we can't send them an email.
	    $this->RedirectToDefaultPage();	// leave the form
	}
	$idUser = $rcToken->GetTokenEntity();	// token entity for this type is user ID
	$rcUser = fcApp::Me()->UserTable($idUser);
    
	// log that a new password has been received
    
	$arEv = array(
	  'uname'	=> $rcUser->UserName(),
	  'uid'		=> $rcUser->GetKeyValue(),
	  );
	$idEv = fcApp::Me()->CreateEvent(KS_EVENT_FERRETERIA_RECV_PW_RESET,'new password received',$arEv);
	$tEvSub = fcApp::Me()->EventTable_Done();

	// determine if the new password data is usable:
	if ($this->GetFromInput_NewPasswordIsOkay()) {
	    
	    // try to set password from input
	    $sPass = $this->GetInput_PasswordOne();
	    $rcUser->SetPassword($sPass);
	    $sUser = $rcUser->UserName();
	    $this->AddSuccessMessage("The password for '$sUser' has been updated.");
	    
	    $tEvSub->CreateRecord($idEv,KS_EVENT_SUCCESS,'password changed');

	    $this->RedirectToDefaultPage();	// leave the form
	} else {
	    $sErrCode = $this->GetErrorCode();
	    $sErrText = $this->GetErrorText();

	    $tEvSub->CreateRecord($idEv,KS_EVENT_FAILED.':','kept old password: '.$sErrText);

	    $this->RedirectToSamePage();	// redirect to same URL -- let user try again
	}
    }
    // NOTE: can override this for more stringent requirements
    protected function IsGivenPasswordValid($s) {
	return strlen($s) > 6;
    }
    // NOTE: can override this for more stringent requirements
    protected function IsGivenUsernameValid($s) {
	return strlen($s) > 2;
    }
    protected function IsGivenUsernameAvailable($s) {
	$t = fcApp::Me()->UserTable();
	return !$t->UserExists($s);
    }
    /*----
      ACTION: check new password for validity; post error message for any problem found
	Does not post a success message if there is no problem, because presumably more
	things remain to be done (any of which might fail).
      RETURNS: TRUE if new password should be set, FALSE if there was a problem.
    */
    protected function IsInput_NewAccountPassword_Okay() {
	$sPass1 = $this->GetInput_PasswordOne();
	$sPass2 = $this->GetInput_PasswordTwo();
	if ($this->IsGivenPasswordValid($sPass1)) {
	    if ($sPass1 == $sPass2) {
		return TRUE;
	    } else {
		$this->AddErrorMessage('Passwords do not match! Please re-type.');
		return FALSE;
	    }
	} else {
	    // TODO: message should be specific about requirements
	    $this->AddErrorMessage('Password is too short. Please enter a longer one.');
	    return FALSE;
	}
    }
    protected function GetFromInput_NewPasswordIsOkay() {
	$sPass1 = $this->GetInput_PasswordOne();
	$sPass2 = $this->GetInput_PasswordTwo();
	if ($this->IsGivenPasswordValid($sPass1)) {
	    if ($sPass1 == $sPass2) {
		return TRUE;
	    } else {
		$this->SetError_PasswordMismatch();
	    }
	} else {
	    $this->SetError_UnacceptablePassword();
	}
	return FALSE;
    }
    // ASSUMES: token is valid
    protected function HandleInput_NewAccountForm() {
	$sUser = $this->GetInput_Username();
	// check to see if username already exists
	if ($this->IsGivenUsernameValid($sUser)) {
	    if ($this->IsGivenUsernameAvailable($sUser)) {
		if ($this->IsInput_NewAccountPassword_Okay()) {
		    $sEmail = $this->Get_AuthTokenRecord()->GetTokenEntity();	// for new account tokens, entity is email address
		    $sPass = $this->GetInput_PasswordOne();
		    $this->CreateNewAccount($sUser,$sPass,$sEmail);
		    $this->RedirectToDefaultPage();	// success -- leave the login form
		} else {
		    $this->RedirectToSamePage();	// password failure -- let the user try again
		}
	    } else {
		$this->AddErrorMessage("Username '$sUser' is already taken. Sorry! Please try something else.");
	    }
	} else {
	    // TODO: this message should describe the minimum length
	    $this->AddErrorMessage("Username '$sUser' is too short. Try something else.");
	}
	$this->RedirectToSamePage();	// username failure -- let the user try again
    }
    protected function SetFromInput_AuthCodeStatus() {
	$tToken = $this->TokenTable();
	$rcToken = $tToken->GetRecord_fromTokenString($this->GetInput_AuthCodeValue());
	$this->Set_AuthTokenRecord($rcToken);
	
	if (is_null($rcToken)) {
	    $this->AddErrorMessage('There was a problem with the authorization: '.$sErr);
	    // LATER: maybe the token URL should include enough info that we could re-display the request form
	} else {
	    $nType = $rcToken->GetTokenType();
	    switch ($nType) {
	      case KI_AUTH_TYPE_NEW_USER:
		$this->SetStatus_DoNext(self::KS_STEP_NA2_ACCT_ENTRY_FORM);
		break;
	      case KI_AUTH_TYPE_RESET_PASS:
		$this->SetStatus_DoNext(self::KS_STEP_PR2_PASSWORD_FORM);
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
    protected function StoreSubmittedUsername() {
	$sUser = $this->GetInput_Username();
	fcApp::Me()->GetSessionRecord()->SetStashValue('login-username',$sUser);
    }
    protected function FetchSubmittedUsername() {
    	return fcApp::Me()->GetSessionRecord()->GetStashValue('login-username');
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
    private $sErrCode, $sErrText;
    protected function SetError_InvalidAuthCode() {
	$this->sErrCode = 'INV';
	$this->sErrText = 'invalid authorization code';
    }
    protected function SetError_ExpiredAuthCode() {
	$this->sErrCode = 'EXP';
	$this->sErrText = 'expired authorization code';
    }
    protected function SetError_PasswordMismatch() {
	$this->sErrCode = 'MIS';
	$this->sErrText = 'passwords do not match';
    }
    protected function SetError_FailedWritingPassword() {
	$this->sErrCode = 'WRI';
	$this->sErrText = 'could not write new password to database';
    }
    protected function SetError_TokenError($sCode,$sText) {
	$this->sErrCode = 'TOK.'.$sCode;
	$this->sErrText = $sText;
    }
    protected function DidSucceed() {
	return empty($this->sErrCode);
    }
    protected function GetErrorCode() {
	return $this->sErrCode;
    }
    protected function GetErrorText() {
	return $this->sErrText;
    }
    
	//--input-to-rendering states--//
    
    // -- INPUT -- //
    // ++ LOGIC ++ //

    private $rcToken;
    protected function Set_AuthTokenRecord(fcUserToken $rc) {
	$this->rcToken = $rc;
    }
    protected function Get_AuthTokenRecord() {
	return $this->rcToken;
    }
    protected function FigureURL_forAuthCode($id,$sToken) {
	$sTokenHex = bin2hex($sToken);
	$oKiosk = fcApp::Me()->GetKioskObject();
	$urlPath = $oKiosk->MakeURLFromString("?auth=$id:$sTokenHex");
	$url = 'https://'.$_SERVER["HTTP_HOST"].$urlPath;
	/* 2017-02-05 This includes pathinfo, which we don't want (makes the logic unnecessarily complex).
	$url = 'https://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."?auth=$id:$sTokenHex";
	*/
	return $url;
    }
    // NOTE: 2017-02-11 Now I'm not sure what I actually needed this for.
    protected function RedactedEmailAddress($sAddr) {
	list($sUser,$sDomain) = explode('@',$sAddr);
	// replace all but first and last character of user with '*'
	$sLen = strlen($sUser);
	$chFirst = substr($sUser,0,1);
	$chFinal = substr($sUser,-1);
	$sUser = $chFirst.str_repeat('*',strlen($sUser)-2).$chFinal;
	return $sUser.'@'.$sDomain;
    }
    
    // -- LOGIC -- //
    // ++ ACTIONS ++ //  

    protected function CreateNewAccount($sUser,$sPass,$sEmail) {
	$t = fcApp::Me()->UserTable();
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
    
    /*----
      ACTION: Displays forms as needed according to internal states
    */
    public function Render() {

	switch ($this->GetStatus_DoNext()) {
	  case self::KS_STEP_NA0_EMAIL_ADDR_FORM:	// NA0
	    return $this->RenderForm_GetEmailAddressForNewAcct();
	  case self::KS_STEP_NA2_ACCT_ENTRY_FORM:	// NA2
	    return $this->RenderForm_GetNewAccountSpecs();
	  case self::KS_STEP_PR0_USERNAME_FORM:		// PR0
	    return $this->RenderForm_GetUserForPasswordReset();
	  case self::KS_STEP_PR2_PASSWORD_FORM:		// PR2
	    return $this->RenderForm_ResetLostPassword();
	  case self::KS_STEP_SHOW_LOGIN:		// LGN0
	    return $this->RenderForm_Login();
	  default:
	    return NULL;
	}

    /* 2017-02-05 Replacing this.
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
    */

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
	    $out = 
	      $this->Render_UserProfileMessage(fcApp::Me()->GetUserRecord())
	      .' &bull; ['
	      .$this->Render_LogoutRequestControl()
	      .']'
	      ;
	} else {
	    $out = $this->Render_LoginRequestControl();
	}
	return $out;
    }
    public function Render_LogoutRequestControl() {
	$urlBase = fcApp::Me()->GetKioskObject()->GetBasePath();
	return '<a href="'
	  .$urlBase
	  .KS_PATH_ACTION_KEY		// do
	  .KS_CHAR_URL_ASSIGN		// :
	  .KS_USER_PATH_REQ_LOGOUT	// logout
	  .KS_CHAR_PATH_SEP		// /
	  .'" title="immediately log out">log out</a>'	// TODO: make these strings config options too
	  ;
    }
    protected function Render_LoginRequestControl() {
	$urlBase = fcApp::Me()->GetKioskObject()->GetBasePath();
	return '<a href="'
	  .$urlBase
	  //.KS_CHAR_PATH_SEP		// /
	  .KS_PATH_ACTION_KEY		// do
	  .KS_CHAR_URL_ASSIGN		// :
	  .KS_USER_PATH_REQ_LOGIN_FORM	// login
	  .KS_CHAR_PATH_SEP		// /
	  .'" title="display the login form">log in</a>'	// TODO: make these strings config options too
	  ;
    }
    // ACTION: renders the control for requesting the page for editing current user's profile
    protected function Render_ProfileRequestControl() {
	$urlBase = fcApp::Me()->GetKioskObject()->GetBasePath();
	return '<a href="'
	  .$urlBase
	  //.KS_CHAR_PATH_SEP			// /
	  .KS_PATH_ACTION_KEY			// do
	  .KS_CHAR_URL_ASSIGN			// :
	  .KS_USER_PATH_REQ_PROFILE_PAGE	// profile
	  .KS_CHAR_PATH_SEP			// /
	  .'" title="display the login form">log in</a>'	// TODO: make these strings config options too
	  ;
    }
    // ACTION: renders the control (usually a link) for starting the new account process
    protected function Render_NewAccountRequestControl() {
	$oeLink = new fcUtilityLink(
	  KS_PATH_ACTION_KEY,
	  KS_USER_PATH_REQ_NEW_ACCT,
	  'new',
	  'request a new account'
	  );
	return $oeLink->Render();
    /*
	$urlBase = fcApp::Me()->GetKioskObject()->GetBasePath();
	return '<a href="'
	  .$urlBase
	  .KS_CHAR_PATH_SEP		// /
	  .KS_PATH_ACTION_KEY		// do
	  .KS_CHAR_URL_ASSIGN		// :
	  .KS_USER_PATH_REQ_NEW_ACCT	// newacct
	  .KS_CHAR_PATH_SEP		// /
	  .'" title="request a new account">new</a>'	// TODO: make these strings config options too
	  ; */
    }
    protected function Render_ResetPasswordRequestControl() {
	$oeLink = new fcUtilityLink(
	  KS_PATH_ACTION_KEY,
	  KS_USER_PATH_REQ_PASS_RESET,
	  'reset',
	  'change the password for this account'
	  );
	return $oeLink->Render();
    }
    protected function RenderForm_GetEmailAddressForNewAcct() {
	$htEA = KF_USER_EMAIL_ADDRESS;
	$htBtn = KSF_USER_BTN_NEW_ACCT_EMAIL;
	return <<<__END__

<table class="form-block-login"><tr><td>
Please enter an email address to which you have access.<br>
An authorization link will be sent to that address.
<form method=post>
<b>Email address</b>: <input name="$htEA" size=40>
<input type=submit value="Request Access" name="$htBtn">
</form>
</td></tr></table>
__END__;
    }
    // TODO: Not sure how to make <title> tag display. Or maybe that's the wrong tag.
    protected function RenderForm_GetNewAccountSpecs() {
	$sUser = $this->FetchSubmittedUsername();	// fetch submitted userame (if any) from session stash
	$htvUser = htmlspecialchars($sUser);		// previous value of username (if any)
	$htnUser = KSF_USER_CTRL_USERNAME;		// field name for username
	$htnPass1 = KSF_USER_CTRL_SET_PASS1;		// desired password
	$htnPass2 = KSF_USER_CTRL_SET_PASS2;		// password double-check
	$htnBtn = KSF_USER_BTN_NEW_ACCT_CREATE;
	return <<<__END__

<table class="form-block-login">
<caption>Create a New Account</caption>
<tr><td>
The username you enter will be checked for availability when you submit the form.
  <form method=post>
    <table>
      <tr>
	<td align=right title="the log-in ID you'd like to use"><b>Username</b>:</td>
	<td><input name="$htnUser" size=20></td>
	</tr>
      <tr>
	<td align=right title="the password you'd like to use"><b>Password</b>:</td>
	<td><input type=password name="$htnPass1" size=20></td>
	</tr>
      <tr>
	<td align=right title="the same password, just to make sure you typed it the way you meant to"><b>Again</b>:</td>
	<td><input type=password name="$htnPass2" size=20></td>
	</tr>
      <tr>
	<td colspan=2 align=center><input type=submit value="Create" name="$htnBtn"></td>
	</tr>
    </table>
  </form>
</td></tr>
</table>
__END__;
    }
    protected function RenderForm_GetUserForPasswordReset() {
	$htnUser = KSF_USER_CTRL_USERNAME;	// field name for username
	$htnBtn = KS_USER_BTN_PASS_RESET_USER;	// name of button for this form
	return <<<__END__

<table class="form-block-login">
<caption>Request Password Reset Link</caption>
<tr><td>
  <form method=post>
    Enter your username to request a password reset authorization:<br>
    <b>Username</b>:
    <input name="$htnUser" size=20>
    <input type=submit value="Request" name="$htnBtn"><br>
    The link will be sent to the given user's email address.
  </form>
</td></tr>
</table>
__END__;
    }
    /*----
      PURPOSE: This is for resetting the password when the user can't log in.
	Possibly there should be additional security; right now all it does is
	ensure that only someone with access to the user's email can change
	the password.
      TODO: Write a set of methods for handling logged-in password reset (RenderForm_ChangeKnownPassword()).
	That form should ask for the current password, but does not need to email an authlink.
    */
    protected function RenderForm_ResetLostPassword() {
	$htnPass1 = KSF_USER_CTRL_SET_PASS1;	// desired password
	$htnPass2 = KSF_USER_CTRL_SET_PASS2;	// password double-check
	$htnBtn =   KSF_USER_BTN_SET_PASS;	// name of button for this form 
	return <<<__END__

<table class="form-block-login">
<caption>Enter New Password</caption>
<tr><td>
  <form method=post>
    <table>
      <tr>
	<td align=right title="the new password you'd like to use"><b>New Password</b>:</td>
	<td><input type=password name="$htnPass1" size=20></td>
	</tr>
      <tr>
	<td align=right title="the same password, just to make sure you typed it the way you meant to"><b>Same, Again</b>:</td>
	<td><input type=password name="$htnPass2" size=20></td>
	</tr>
      <tr>
	<td colspan=2 align=center><input type=submit value="Change" name="$htnBtn"></td>
	</tr>
    </table>
  </form>
</td></tr>
</table>
__END__;
	throw new exception('2017-02-10 This form still under construction.');
    }
    abstract protected function RenderForm_Login();
    protected function Render_UserProfileMessage(fcrUserAcct $rcUser) {
	$htUser = $rcUser->SelfLink_name();
	return "Hi, $htUser!";
    }
    
	//--screen--//
	//++email++//

    protected function SendPasswordResetEmail_fromInput() {
	$tUser = fcApp::Me()->UserTable();
	$sUser = $this->GetInput_Username();
	$rcUser = $tUser->FindUser($sUser);
	if (is_null($rcUser)) {
	} else {
	    $idUser = $rcUser->GetKeyValue();
	    $rcToken = $this->TokenTable()->MakeToken(KI_AUTH_TYPE_RESET_PASS,$idUser);
	    $this->SendEmail_forLostPassReset($rcToken);
	    $this->RedirectToDefaultPage();	// leave the form
	}
    }
    protected function SendNewAccountAuthEmail_fromInput() {
	$sEmail = $this->GetInput_EmailAddress();
	$rcToken = $this->TokenTable()->MakeToken(KI_AUTH_TYPE_NEW_USER,$sEmail);
	$this->SendEmail_forNewUser($rcToken);
	$this->RedirectToDefaultPage();	// leave the form
    }
    /*----
      ACTION: Sends email with an authlink to create a new user account
      TODO:
	* Constants used here should perhaps be options retrieved from the App object?
	* There's probably some way to have less redundancy between these two methods.
    */
    protected function SendEmail_forNewUser(fcUserToken $rcToken) {
    
	$oTplt = new fcTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT);

	// these arguments are used both for the email text and the web text
	$url = $this->FigureURL_forAuthCode($rcToken->GetKeyValue(),$rcToken->GetToken());
	$sAddr = $rcToken->GetTokenEntity();
	$ar = array(
	  'site'	=> KS_SITE_NAME,
	  'addr'	=> $sAddr,
	  'action'	=> 'allow you to create a new user account',
	  'url'		=> $url
	  );
	$oTplt->VariableValues($ar);

	// generate the email
	$oTplt->Template(KS_TPLT_EMAIL_TEXT_FOR_NEW_ACCOUNT);
	$sMsg = $oTplt->Render();
	$sSubj = KS_TEXT_EMAIL_SUBJ_FOR_NEW_ACCOUNT;
	$sName = NULL;	// user's name not yet known
	fcApp::Me()->DoEmail_fromAdmin_Auto($sAddr,$sName,$sSubj,$sMsg);

	// generate status message
	$oTplt->Template(KS_TPLT_AUTH_EMAIL_TO_SHOW);
	$sMsg = $oTplt->Render();
	$this->AddSuccessMessage($sMsg);
	
	$this->RedirectToDefaultPage();
    }
    /*----
      TODO:
	* Constants used here should perhaps be options retrieved from the App object?
	* There's probably some way to have less redundancy between these two methods.
    */
    protected function SendEmail_forLostPassReset(fcUserToken $rcToken) {

	$oTplt = new fcTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT);

	// these areguments are used both for the email text and the web text
	$url = $this->FigureURL_forAuthCode($rcToken->GetKeyValue(),$rcToken->GetToken());
	$idUser = $rcToken->GetTokenEntity();
	$rcUser = fcApp::Me()->UserTable()->GetRecord_forKey($idUser);
	$sAddr = $rcUser->EmailAddress();
	$ar = array(
	  'site'	=> KS_SITE_NAME,
	  'user'	=> $rcUser->UserName(),
	  //'addr'	=> $this->RedactedEmailAddress($sAddr),	// there is no reason to redact here
	  'addr'	=> $sAddr,
	  'action'	=> 'allow you to change your password',
	  'url'		=> $url
	  );
	$oTplt->VariableValues($ar);
	
	// log the email about to be sent
	$arEv = array(
	  'user'	=> $rcUser->UserName(),
	  'addr'	=> $sAddr,
	  );
	fcApp::Me()->EventPlex()->CreateBaseEvent(KS_EVENT_FERRETERIA_SENDING_ADMIN_EMAIL,'lost password reset',$arEv);

	// generate the email
	$oTplt->Template(KS_TPLT_EMAIL_TEXT_FOR_PASS_CHANGE);
	$sMsg = $oTplt->Render();
	$sSubj = KS_TEXT_EMAIL_SUBJ_FOR_PASS_CHANGE;
	$sName = $rcUser->FullName();
	fcApp::Me()->DoEmail_fromAdmin_Auto($sAddr,$sName,$sSubj,$sMsg);

	// generate status message
	$oTplt->Template(KS_TPLT_AUTH_EMAIL_TO_SHOW);
	$sMsg = $oTplt->Render();
	$this->AddSuccessMessage($sMsg);
	
	$this->RedirectToDefaultPage();
    }
    /*----
      PUBLIC so other elements can use it (e.g. login widget)
      TODO:
	* should be template-based, for i18n
	* should include links for notifying us and changing password
    */
    public function SendEmail_forLoginSuccess() {
	// for now, we'll always send email; later, this should probably be a user preference
	$rcUser = fcApp::Me()->GetUserRecord();

	$sUser = $rcUser->UserName();
	$sSiteName = KS_SITE_NAME;
	$sAddress = $_SERVER['REMOTE_ADDR'];
	$sBrowser = $_SERVER['HTTP_USER_AGENT'];

	$sMsg = <<<__END__
Someone, presumably you, just logged in to $sSiteName with username "$sUser". Please make sure this was actually you.
* IP address: $sAddress
* Browser: $sBrowser

If it wasn't you, please let us know, and change your password.
__END__;
	$sToAddr = $rcUser->EmailAddress();
	$sToName = $rcUser->FullName();
	$sSubj = 'login notification from '.KS_SITE_NAME;
	fcApp::Me()->EventTable()->CreateBaseEvent(KS_EVENT_FERRETERIA_SENDING_ADMIN_EMAIL,'successful login notification');
	$ok = fcApp::Me()->DoEmail_fromAdmin_Auto($sToAddr,$sToName,$sSubj,$sMsg);
	return $ok;
    }
    /*----
      NOTE: As useful as it would be for legit users to see if someone *almost* knows their password,
	an illegit user might gain a legit account's password by setting up accounts using obvious typos
	of a legit account's username and then waiting for the legit user to make a mistake typing their username.
	(Illegit user would then probably receive the correct password for legit username, or something
	close to it.)
	
	I don't know if this is a likely-enough scenario to be worth guarding against, but for now we are.
    */
    protected function SendEmail_forLoginFailure() {
	//$rcSess = fcApp::Me()->GetSessionRecord();
	$tUsers = fcApp::Me()->UserTable();
	//$rcUser = $rcSess->GetFailedUserRecord();
	if ($tUsers->GetFailedUserWasFound()) {
	    // user exists, but this login attempt failed
	    $rcFailed = $tUsers->GetFailedUser();
	    $sToAddr = $rcFailed->EmailAddress();
	    $sToName = $rcFailed->FullName(FALSE);
	    $sSite = KS_SITE_SHORT;
	    $sSubj = $sSite.' login attempt failed';
	    $sMsg = "Someone (hopefully you!) attempted to log in to $sSite, but the password did not match.";
	    // TODO: IP address/domain, browser string
	    fcApp::Me()->DoEmail_fromAdmin_Auto($sToAddr,$sToName,$sSubj,$sMsg);
	}
    }

    // -- OUTPUT -- //
}
/*::::
  PURPOSE: Implements the login form inline, for minimum vertical space
    This was originally so it could be inserted in a VbzCart checkout form,
    but it may have other uses.
*/
class fcpeLoginWidget_inline extends fcpeLoginWidget {
    /*----
      NOTE: Can't seem to get the HTTP REFERER, otherwise I'd include it as a hidden input here
	so a successful login could redirect there.
    */
    protected function RenderForm_Login() {
	$sUser = $this->FetchSubmittedUsername();	// fetch submitted userame (if any) from session stash
	$htvUser = htmlspecialchars($sUser);		// previous value of username (if any)
	$htnUser = KSF_USER_CTRL_USERNAME;		// field name for username
	$htnPass = KSF_USER_CTRL_ENTER_PASS;		// field name for password
	$htnBtn = KSF_USER_BTN_LOGIN;			// field name for login button
	$htNewAcct = $this->Render_NewAccountRequestControl();		// link for creating an account
	$htPWReset = $this->Render_ResetPasswordRequestControl();	// link for resetting password
	return <<<__END__

<table class="form-block-login"><tr><td><form method=post>
<b>Username</b>: <input name="$htnUser" size=10 value="$htvUser">
<b>Password</b>: <input type=password name="$htnPass" size=10>
<input type=submit value="Log In" name="$htnBtn">
[$htNewAcct]
[$htPWReset]
</form></td></tr></table>
__END__;
    }
}
/*::::
  PURPOSE: Implements the login form as a block, optimized for use on a page with no other content.
  HISTORY:
    2017-03-15 backported from Greenmine into Ferreteria
*/
class fcpeLoginWidget_block extends fcpeLoginWidget {
    protected function RenderForm_Login() {
	// re-display submitted username, if any
	$sName = $this->FetchSubmittedUsername();	// fetch submitted userame (if any) from session stash
	$htName = htmlspecialchars($sName);
	$htBtn = KSF_USER_BTN_LOGIN;
	$htNewAcct = $this->Render_NewAccountRequestControl();
	$htPWReset = $this->Render_ResetPasswordRequestControl();	// link for resetting password
	return <<<__END__

<table class="form-block-login"><tr><td>
<form method=post>
<table>
<tr><td align=right><b>Username</b>:</td><td><input name=uname size=10 value="$htName"></td></tr>
<tr><td align=right><b>Password</b>:</td><td><input type=password name=upass size=10></td></tr>
<tr><td align=center colspan=2>
  <input type=submit value="Log In" name="$htBtn">
  <div style="float: right;">[$htNewAcct] [$htPWReset]</div>
</td></tr>
</table>
</form></td></tr></table>
__END__;
    }
}

/*::::
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
    // ++ CLASSES ++ //

    // CEMENT
    protected function Class_forLoginWidget() {
	return 'fcpeLoginWidget_block';
    }

    // -- CLASSES -- //

}
    