<?php
/*
  FILE: page.php
  PURPOSE: part of generalized application framework
  NOTE: It may be that Skin() should not defer to the App, because
    different types of pages may need different skin-types. On the other hand,
    all pages in an app generally *should* use a common skin...
  HISTORY:
    2012-05-08 split off from store.php
    2012-05-13 removed clsPageOutput (renamed clsPageOutput_WHO_USES_THIS some time ago)
    2013-09-16 clsVbzSkin_Standard renamed clsVbzPage_Standard
    2013-09-17 clsVbzPage_Standard renamed clsVbzPage_Browse
    2013-10-23 stripped for use with ATC app (renamed as app.php)
    2013-11-11 re-adapted for general library (split off page classes from app.php into page.php)
*/

abstract class clsPage {
    private $oApp;
    private $oDoc;

    public function __construct() {}

    /*-----
      USAGE: main entry point
      OUTPUT: depends on how document object is handled.
	Simplest is probably to create a child DoPage() which calls parent (this one) first,
	then tells Doc() to render itself.
    */
    public function DoPage() {
	try {
	    $this->ParseInput();
	    $this->HandleInput();
	    $this->PreSkinBuild();
	    $this->Skin()->Build();	// tell the skin to fill in its pieces
	    $this->PostSkinBuild();
	    $this->Skin()->DoPage();
	} catch(exception $e) {
	    $this->DoEmailException($e);
	}
    }

    // environmental objects
    public function App(clsApp $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->oApp = $iObj;
	}
	return $this->oApp;
    }
    private $oSkin;
    public function Skin() {
	if (empty($this->oSkin)) {
	    $this->oSkin = $this->NewSkin();
	}
	return $this->oSkin;
    }
    protected function Data() {
	return $this->App()->Data();
    }

    // STAGES OF PAGE GENERATION

    /*-----
      ACTION: Grab any expected input and interpret it
    */
    protected abstract function ParseInput();
    /*-----
      ACTION: Take the parsed input and do any needed processing (e.g. looking up data)
    */
    protected abstract function HandleInput();
    /*
      NOTE: there isn't really any logistical difference between HandleInput()
	and PreSkinBuild(); it's more a conceptual one:
	  HandleInput: do stuff with input after it has been parsed
	  PreSkinBuild: do anything that needs to be done before the skin is built
	    ...such as setting the title string
    */
    abstract protected function PreSkinBuild();
    abstract protected function PostSkinBuild();

    // EXCEPTION HANDLING

    abstract protected function DoEmailException(exception $e);
    abstract protected function Exception_Message_toEmail(array $arErr);
    abstract protected function Exception_Subject_toEmail(array $arErr);
    abstract protected function Exception_Message_toShow($iMsg);
}


/*%%%%
  PURPOSE: page which provides some basic services
    - default message/email for exceptions
    - calling Skin to render results (this should
      probably be eliminated, and have the App just
      call Skin->DoPage() directly)
*/
abstract class clsPageBasic extends clsPage {

    // ++ EXCEPTION HANDLING ++ //

    /*----
      HISTORY:
	2011-03-31 added Page and Cookie to list of reported variables
    */
    protected function DoEmailException(exception $e) {
	$sMsgErr = $e->getMessage();

	$arErr = array(
	  'descr'	=> $e->getMessage(),
	  'stack'	=> $e->getTraceAsString(),
	  'guest.addr'	=> $_SERVER['REMOTE_ADDR'],
	  'guest.agent'	=> $_SERVER['HTTP_USER_AGENT'],
	  'guest.ref'	=> NzArray($_SERVER,'HTTP_REFERER'),
	  'guest.page'	=> $_SERVER['REQUEST_URI'],
	  'guest.ckie'	=> NzArray($_SERVER,'HTTP_COOKIE'),
	  );

	$out = $this->Exception_Message_toEmail($arErr);	// generate the message to email
	$sSubj = $this->Exception_Subject_toEmail($arErr);

//	$ok = mail(KS_TEXT_EMAIL_ADDR_ERROR,$subj,$out);	// email the message
	$this->App()->DoEmail_Auto(
	  KS_TEXT_EMAIL_ADDR_ERROR,
	  KS_TEXT_EMAIL_NAME_ERROR,
	  $sSubj,$sMsgErr);

	echo $this->Exception_Message_toShow($sMsgErr);		// display something for the guest

	throw $e;

	// FUTURE: log the error and whether the email was successful
    }
    protected function Exception_Subject_toEmail(array $arErr) {
	return 'error in '.KS_SITE_SHORT.' from IP '.$arErr['guest.addr'];
    }
    protected function Exception_Message_toEmail(array $arErr) {
	$guest_ip = $arErr['guest.addr'];
	$guest_br = $arErr['guest.agent'];
	$guest_pg = $arErr['guest.page'];
	$guest_rf = $arErr['guest.ref'];
	$guest_ck = $arErr['guest.ckie'];

	$out = 'Description: '.$arErr['descr'];
	$out .= "\nStack trace:\n".$arErr['stack'];
	$out .= <<<__END__

Client information:
 - IP Addr : $guest_ip
 - Browser : $guest_br
 - Cur Page: $guest_pg
 - Prv Page: $guest_rf
 - Cookie  : $guest_ck
__END__;

	return $out;
    }
    protected function Exception_Message_toShow($iMsg) {
	$msg = $iMsg;
	$htContact = '<a href="'.KWP_HELP_CONTACT.'">contact</a>';
	$out = <<<__END__
<b>Ack!</b> We seem to have a small problem here. (If it was a large problem, you wouldn't be seeing this message.)
The webmaster is being alerted about this. Feel free to $htContact the webmaster.
<br>Meanwhile, you might try reloading the page -- a lot of errors are transient,
which makes them hard to fix, which is why there are more of them than the other kind.
<br><br>
We apologize for the nuisance.
<br><br>
<b>Error Message</b>: $msg
<pre>
__END__;
	return $out;
    }
}

/*%%%%
  PURPOSE: page which:
    * handles user log-ins
    * provides methods for displaying the necessary forms
      for handling user login/setup (it's up to the implementer to
      call those methods and decide where the forms should display)
    * provides methods for managing admin-style menus
*/
abstract class clsPageLogin extends clsPageBasic {

    // ++ STATIC ++ //

    /*----
      RETURNS: URL fragment to add to page's home URL
	for requesting token validation
    */
    protected static function AuthURLPart($idToken,$sToken) {
	$sTokenHex = bin2hex($sToken);
	$url = '?auth='.$idToken.':'.$sTokenHex;
	return $url;
    }
    protected static function ParseAuth() {
	$sAuth = $_REQUEST['auth'];

	$id = strtok($sAuth, ':');	// string before ':' is auth-email ID
	$sHex = strtok(':');		// string after ':' is token (in hexadecimal)
	//$sToken = hex2bin($sHex);	// requires PHP 5.4
	$sBin = pack("H*",$sHex);	// equivalent to hex2bin()
	$arOut = array(
	  'auth'	=> $sAuth,	// unparsed -- for forms
	  'id'		=> $id,
	  'bin'		=> $sBin	// binary format
	  );
	return $arOut;
    }

    // --STATIC--
    // ++DYNAMIC++

    // user access stuff
    private $sLogin;
    private $sPass;
    private $sPassX;	// duplicate password for confirming change
    private $sAuth;	// auth token
    private $sEmail;
    private $isLogin;	// is login attempt
    private $isReset;
    private $isAuth;
    private $doEmail;
    private $doLogout;
    // display stuff
    private $sTitle;	// page title string
    private $arPageHdrWidgets;
    // menu
    private $arPath;	// array of path params
    private $oMHome;	// menu "home" node
    private $oMNode;	// selected menu node (if any)
    private $oMPaint;	// menu painter

    // ++ SETUP ++ //

    public function __construct() {
	$this->rcUser = NULL;
    }

    // -- SETUP -- //
    // ++ STATUS ACCESS ++ //

    /*----
      RETURNS: TRUE iff the user has submitted an authorization token
    */
    protected function IsAuthLink($b=NULL) {
	if (!is_null($b)) {
	    $this->isAuth = $b;
	}
	return $this->isAuth;
    }
    /*----
      RETURNS: TRUE iff this is a request to create an account (user & pw)
    */
    protected function IsCreateRequest() {
	return $this->isNew;
    }
    /*----
      RETURNS: TRUE iff this is a password reset submission
    */
    protected function IsResetRequest() {
	return $this->isReset;
    }
    /*----
      RETURNS: TRUE iff this is an attempt to log in
    */
    protected function IsLoginRequest() {
	return $this->isLogin;
    }
    /*----
      RETURNS: TRUE iff user is logged in
    */
    protected function IsLoggedIn() {
	return $this->App()->Session()->HasUser();
    }
    protected function Success($bOk=NULL) {
	if (!is_null($bOk)) {
	    $this->ok = $bOk;
	}
	return $this->ok;
    }

    // -- STATUS ACCESS -- //
    // ++ PAGE VALUES ++ //

    /*----
      RETURNS: string to use for page title
      NOTES: Needs to be public so page rendering classes
	can change it from the default.
    */
    public function TitleString($sTitle=NULL) {
	if (!is_null($sTitle)) {
	    $this->sTitle = $sTitle;
	}
	return $this->sTitle;
    }
    protected function AuthToken($sNew=NULL) {
	if (!is_null($sNew)) {
	    $this->sToken = $sNew;
	}
	return $this->sToken;
    }
    protected function EmailAddress($sNew=NULL) {
	if (!is_null($sNew)) {
	    $this->sEmail = $sNew;
	}
	return $this->sEmail;
    }
    protected function LoginName($sNew=NULL) {
	if (!is_null($sNew)) {
	    $this->sLogin = $sNew;
	}
	return $this->sLogin;
    }

    // -- PAGE VALUES -- //
    // ++ CLASS NAMES ++ //

    protected function UsersClass() {
	return 'clsUserAccts';
    }

    // -- CLASS NAMES -- //
    // ++ DATA TABLE ACCESS ++ //

    protected function UserTable($id=NULL) {
	return $this->Data()->Make($this->UsersClass(),$id);
    }

    // -- DATA TABLE ACCESS -- //
    // ++ DATA RECORD ACCESS -- //

    protected function UserRecord() {
	if (is_null($this->rcUser)) {
	    $this->SetUserRecord($this->App()->User());
	}
	return $this->rcUser;
    }
    protected function SetUserRecord(clsUserAcct $rc) {
	$rc->FirstRow();	// make sure first (only) row is loaded
	$this->rcUser = $rc;
	$this->LoginName($rc->UserName());
    }

    // -- DATA RECORD ACCESS -- //
    // ++ PATH/URL/REQUEST MANAGEMENT ++ //

    abstract protected function BaseURL();	// the home URL for the current page/class
    protected function AuthURL($idToken,$sToken) {
	return $this->BaseURL().static::AuthURLPart($idToken,$sToken);
    }
    /*----
      USED BY: SelfLink(), AuthURL(), and clsActionLink_base->SelfURL()
      INPUT:
	$doRel: TRUE=get URL relative to current subpage; FALSE=only include $arArgs params
    */
    public function SelfURL(array $arArgs=NULL,$doRel=FALSE) {
	if (is_null($arArgs)) {
	    $arArgs = array();
	}
	// this part stays the same:
	//$urlBase = $this->MenuPainter()->BaseURL();	// shouldn't the Page define this?
	$urlBase = $this->BaseURL();
//	$urlNode = $this->PathStr();

	$oNode = $this->MenuNode();
	if ($doRel) {
	    // this part might get altered:
	    $arNPath = $this->PathArgs();	// args which make the node path
	    //$arNArgs = $oNode->Args();	// args known to the menu node
	    $arAArgs = $arArgs;		// args being added

	    //$arAll = array_merge($arNPath,$arNArgs,$arAArgs);
	    $arAll = array_merge($arNPath,$arAArgs);
	    //die('NPATH:<pre>'.print_r($arNPath,TRUE).'</pre>ADDED:<pre>'.print_r($arAArgs,TRUE).'</pre>RESULT:<pre>'.print_r($arAll,TRUE));
	} else {
	    $arAll = $arArgs;
	}
	$url = $urlBase.clsURL::FromArray($arAll,KS_CHAR_URL_ASSIGN);		// rebuild the path
	//die('DOREL:['.$doRel.'] URL:'.$url);
	return $url;
    }
    /*----
      ACTION: Reload the page
      PURPOSE: Removes form submissions so we can hit reload without repeating actions
    */
    public function Reload() {
	clsHTTP::Redirect($this->SelfURL());
    }
    private $oPath;
    protected function ParsePath() {
	$fp = clsURL::RemoveBasePath($this->BaseURL());
	if (is_string($fp)) {
	    $arPath = clsURL::ParsePath($fp);
	} else {
	    $arPath = array();	// no path
	}
	$this->arPath = $arPath;
	$this->oPath = new clsHTTPInput($arPath);;
    }
    public function PathObj() {
	return $this->oPath;
    }
    /*----
      RETURNS: keyed array, of either:
	* all path arguments, if arKeys is NULL
	* just the named path arguments, if arKeys is a list of keys
      NOTE: This needs to be public so we can build page-relative links
	inside link-widgets (headers).
    */
    public function PathArgs($arKeys=NULL) {
	if (is_null($arKeys)) {
	    return $this->arPath;
	} else {
	    foreach ($arKeys as $key) {
		$arVals[$key] = $this->PathArg($key);
	    }
	    return $arVals;
	}
    }
    /*----
      RETURNS: The value of the named argument as specified by the current URL
      USED BY:
	* internally
	* action links need this in order to tell if they've been activated
    */
    public function PathArg($sName) {
	if (is_array($this->arPath)) {
	    if (array_key_exists($sName,$this->arPath)) {
		$sVal = $this->arPath[$sName];
		return $sVal;
	    }
	}
	return NULL;
    }
    /*----
      TODO: replace this with clsHTTP::Request()->GetBool()
    */
    public function ReqArgBool($sName) {
	if (array_key_exists($sName,$_REQUEST)) {
	    $sVal = $_REQUEST[$sName];
	    if (is_null($sVal)) {
		return FALSE;
	    } elseif ($sVal === FALSE) {
		return FALSE;
	    } else {
		return TRUE;
	    }
	}
    }
    /*----
      TODO: replace this with clsHTTP::Request()->GetInt()
    */
    public function ReqArgInt($sName) {
	if (array_key_exists($sName,$_REQUEST)) {
	    $sVal = $_REQUEST[$sName];
	    if (is_null($sVal)) {
		return NULL;
	    } elseif (is_numeric($sVal)) {
		return $sVal;
	    } else {
		return NULL;
	    }
	}
    }
    /*----
      RETURNS: named argument from HTTP request, as string or NULL
	NULL means argument not present or is not scalar.
      TODO: replace this with clsHTTP::Request()->GetText()
    */
    public function ReqArgText($sName) {
	if (array_key_exists($sName,$_REQUEST)) {
	    $sVal = $_REQUEST[$sName];
	    if (is_null($sVal)) {
		return NULL;
	    } else {
		return ''.$sVal;
	    }
	}
    }
    /*----
      RETURNS: named argument from HTTP request, as array
	If it's not an array, returns the default value.
      TODO: replace this with clsHTTP::Request()->GetArray()
    */
    public function ReqArgArray($sName,$vDef=array()) {
	if (array_key_exists($sName,$_REQUEST)) {
	    $val = $_REQUEST[$sName];
	    if (is_null($val)) {
		return $vDef;
	    } elseif (is_array($val)) {
		return $val;
	    } else {
		return $vDef;
	    }
	}
    }

    // -- PATH/URL/REQUEST MANAGEMENT -- //
    // ++ PAGE ELEMENTS ++ //

    protected function RenderLogin($iUName=NULL) {
	return $this->Skin()->RenderLogin($iUName);
    }
    protected function RenderLogout($iText='log out') {
	return $this->Skin()->RenderLogout($iText);
    }
    /*----
      ACTION: sets the action widgets to use in the page header
    */
    public function PageHeaderWidgets(array $arWidgets=NULL) {
	if (is_array($arWidgets)) {
	    $this->arPageHdrWidgets = $arWidgets;
	}
	return $this->arPageHdrWidgets;
    }
    /*----
      ACTION: Renders a header with action widgets
    */
    public function ActionHeader($sTitle,array $arWidgets=NULL,$cssClass='section-header-sub') {
/*	$htWidgets = NULL;
	if (is_array($arWidgets)) {
	    $arMenu = array_reverse($arWidgets);	// right-alignment reverses things
	    foreach ($arMenu as $oLink) {
		$oLink->Page($this);
		$htWidgets .= "\n".$oLink->Render();
	    }
	} */
	return $this->SectionHeader($sTitle,$arWidgets,$cssClass);
    }
    /*----
      NOTES:
	This needs to be rethought some more... for now, this loads up each Link object with
	  a pointer to the Page object ($this), renders it, and then passes the rendering
	  to Skin->SectionHeader() as a separate parameter so the Skin can decide where to
	  insert it.
    */
    public function SectionHeader($sTitle,array $arWidgets=NULL,$cssClass='section-header-sub') {
	$htMenu = NULL;
	if (!is_null($arWidgets)) {
	    $arMenu = array_reverse($arWidgets);	// right-alignment reverses things
	    foreach ($arMenu as $oLink) {
		$oLink->Page($this);
		$htMenu .= "\n".$oLink->Render();
	    }
	}
	$out = $this->Skin()->SectionHeader($sTitle,$htMenu,$cssClass);
	return $out;
    }

    // -- PAGE ELEMENTS -- //
    // ++ ACCOUNT MANAGEMENT ++ //

    /*----
      ACTION: Checks the login credentials and logs the user in if they're good.
    */
    protected function DoLoginCheck() {
	$this->App()->Session()->UserLogin($this->LoginName(),$this->sPass);
    }
    public function DoLogout() {
	$this->App()->Session()->UserLogout();
    }
    protected function ParseInput_Login() {
	$this->isLogin	= $isLogin	= !empty($_POST[KSF_USER_BTN_LOGIN]);
	$this->isReset	= $isReset	= !empty($_POST[KSF_USER_BTN_SET_PASS]);
	$this->isNew	= $isNew	= !empty($_POST[KSF_USER_BTN_NEW_ACCT]);
	$this->doEmail	= $isEmReq	= !empty($_POST['btnSendAuth']);
	$this->isAuth	= $doGetAuth	= !empty($_GET['auth']);
	$this->doLogout	= $doLogout	= $this->ReqArgBool('exit');

	if ($isLogin) {
	    $this->LoginName($_POST['uname']);
	    $this->sPass = $_POST[KSF_USER_CTRL_ENTER_PASS];
	} elseif ($isReset) {
	    $this->sPass = $_POST[KSF_USER_CTRL_SET_PASS1];
	    $this->sPassX = $_POST[KSF_USER_CTRL_SET_PASS2];
	    $this->AuthToken(clsHTTP::Request()->GetText('auth'));
	} elseif ($isEmReq) {	// requesting password request email
	    $this->EmailAddress($_POST['uemail']);
	}
    }
    protected function HandleInput_Login() {
	if ($this->doLogout) {
	    $this->DoLogout();
	    clsHTTP::Redirect($this->BaseURL());
	}
 	if ($this->doEmail) {
	    $this->sTitle = 'Send Password Reset Email';
	} elseif ($this->IsCreateRequest()) {
	    $this->sTitle = 'Creating User Account';
	} elseif ($this->IsResetRequest()) {
	    $this->sTitle = 'Setting Password';
	} elseif ($this->IsAuthLink()) {
	    $this->sTitle = 'Authorize Password Reset';
	} else {
	    $this->sTitle = 'User Login';
	    if ($this->IsLoginRequest()) {
		$this->DoLoginCheck();
	    }
	}
    }
    /*----
      ACTION: gets the user logged in by rendering/handling the following:
	* log-in form
	* account creation form with email authorization
	* email auth confirmation
      RETURNS: rendered HTML
      ASSUMES: user is not (yet) logged in
    */
    protected function RenderUserAccess() {
	$oSkin = $this->Skin();
	$ht = $this->SectionHeader($this->TitleString());
	$oEmAuth = $this->Data()->EmailAuth();
	$doShowLogin = TRUE;	// By default, we'll still show the login form if not logged in
	$isEmailAuth = FALSE;	// Assume this page is not an email authorization link...

	$ht = NULL;
	$ok = FALSE;	// set false initially so we do one iteration
	while (!$ok) {
	    $ok = TRUE; 	// assume success

	  // check auth link and display form if it checks out
	    if ($this->IsAuthLink()) {
		// auth pre-empts regular log-in stuff, to avoid confusion
		$ar = $this->CheckAuth();	// check token
		if ($ar['ok']) {
		    // find out if this email address matches an existing user account
		    $this->sEmail	= $ar['em_s'];	// grab the email to go back into the "request another" form
		    $sAuth		= $ar['auth'];	// authorization code
		    $rcU = $this->UserTable()->FindEmail($this->sEmail);
		    $qU = $rcU->RowCount();
		    if ($qU == 0) {
			// this is a new account
			$ht .= $oSkin->SuccessMessage('You can now set your username and password.');
			$ht .= $oSkin->RenderUserCreate($sAuth);
		    } elseif ($qU == 1) {
			$rcU->NextRow();	// load the only row
			// updating an existing account
			$sLogin = $rcU->UserName();
			$ht .= $oSkin->SuccessMessage('You can now change your password.');
			$ht .= $oSkin->RenderUserUpdate($sAuth,$sLogin);
		    } else {
			// two or more accounts have that same email address
			throw new exception('Two accounts with the same email -- handling to be written!');
		    }
		    $doShowLogin = FALSE;	// no need to show login option
    //		$isEmailAuth = TRUE;	// this is an email auth -- modify the message
		    $ht .= $oSkin->HLine();
		} else {
		    switch($ar['err']) {
		      case 'EXP':	// token has expired
			$ht .= $oSkin->ErrorMessage('Sorry, that token seems to have expired.');
			// TODO : log this so we have data for determining if tokens need to live longer
			break;
		      case 'INV':	// invalid token
			$ht .= $oSkin->ErrorMessage('That does not seem to be a valid authorization token.');
			// TODO : log this as a possible hacking attempt
			break;
		    }
		}

	    } elseif ($this->IsCreateRequest()) {
	  // CREATE USER form has been submitted

		$this->CheckAuth();
		if ($this->Success()) {
		    // check for duplicate username
		    $sUser = $this->UserName();
		    $tUsers = $this->App()->Users();
		    if ($tUsers->UserExists($sUser)) {
			// the username already exists -- can't create it
			$ht .= $oSkin->ErrorMessage('The username "'.$sUser.'" already exists; please choose another.<br>');
			$ht .= $oSkin->Input_UserSet($ar['auth'],NULL);
			$ht .= $oSkin->HLine();
		    } else {
			// name is available -- attempt to create the record
			$ht .= $this->CreateAccount($this->EmailAddress());
		    }
		}

	    } elseif ($this->IsResetRequest()) {	// password change request submitted
	  // CHANGE PASSWORD form has been submitted

		// check token, but don't display messages
		$this->CheckAuth();
		if ($this->Success()) {
		    // auth token checks out
		    // check for duplicate username
		    $tblUsers = $this->App()->Users();
		    $sUser = $this->LoginName();
		    $ht .= $this->ChangePassword($this->EmailAddress(),$this->sPass,$this->sPassX);
		    if (!$this->Success()) {	// if that didn't work...
			$ok = FALSE;
			$this->IsAuthLink(TRUE);	// display form again
		    }

		}	// END authorized
	    // END is reset
	    } elseif($this->doEmail) {
		$ht .= $this->SendPassReset_forAddr(
		  $this->EmailAddress(),
		  $this->LoginName()
		  );
	    // END do email
	    } elseif($this->isLogin) {
		// login was tried, but we're still here (not logged in), so it must have failed:

		$ht .= $oSkin->ErrorMessage('Sorry, the given username/password combination was not valid.');
		$ht .= $oSkin->HLine();
	    // END is login
	    } else {
		// TODO : log as possible illicit hacking attempt
	    }

	    if ($doShowLogin) {
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
    }
    protected function CreateAccount($sEmail) {
	$out = NULL;
	$oSkin = $this->Skin();
	$tblUsers = $this->App()->Users();
	$rcUser = $tblUsers->AddUser($this->LoginName(),$this->sPass,$sEmail);
	if (is_null($rcUser)) {
	    // display error message
	    $out .= $oSkin->ErrorMessage('There has been a mysterious database problem.');

	    $sSubj = KS_SITE_SHORT.' database glitch';
	    $sMsgErr = 'Could not create new user "'.$this->LoginName().'".'
	      ."\nSQL: ".$tblUsers->sqlExec;
	    $this->App()->DoEmail_Auto(
	      KS_TEXT_EMAIL_ADDR_ERROR,
	      KS_TEXT_EMAIL_NAME_ERROR,
	      $sSubj,$sMsgErr);

	    $out .= 'For some reason, your username could not be created. The admin is being alerted.<hr>';
	    $doShowLogin = FALSE;	// not much point in giving them a login option when they're trying to create an account
	    //throw new exception('Dumping stack to help with debugging.');
	} else {
	    // display success message
	    $out .= $oSkin->SuccessMessage('Account created -- welcome, <b>'.$sUser.'</b>!');
	    // record user ID in session (so we're logged in)
	    $idUser = $rcUser->KeyValue();
	    $oSess = $this->App()->Session();
	    $oSess->SaveUserID($idUser);
	} // END account created
	return $out.'[/ChangePassword]';
    }
    protected function ChangePassword($sEmail,$sPass,$sPassX) {
	$out = NULL;
	$ok = FALSE;
	$oSkin = $this->Skin();
	if ($sPass != $sPassX) {
	    $out .= $oSkin->ErrorMessage('Passwords do not match. Please try again.');
	} else {
	    $tUsers = $this->App()->Users();
	    $sUser = $this->LoginName();
	    $rcUser = $tUsers->FindUser($sUser);
	    if (is_null($rcUser)) {
		$out .= $oSkin->ErrorMessage('Internal error: user "'.$sUser.'" not found!');
	    } else {
		if ($rcUser->EmailAddress() == $sEmail) {
		    $ok = $rcUser->SetPassword($sPass);
		    if ($ok) {
			$out .= $oSkin->SuccessMessage('Password updated for <b>'.$sUser.'</b>.');
		    } else {
			$out .= $oSkin->ErrorMessage('Internal error: could not change password for "'.$sUser.'"!');
		    }
		} else {
		    // could this be tripped by a hack? To be investigated...
		    throw new exception('This really should not happen: token email address does not match found user record.');
		}
	    }
	}
	$this->Success($ok);
	return $out;
    }
    /*----
      ACTION: Send a password reset request to the given address.
      RETURNS: HTML to display showing status of request
      INPUT:
	$sAddr: address to which the email should be sent
	$sName: recipient's name, if known; blank if not
    */
    public function SendPassReset_forAddr($sAddr,$sName) {
	$sAddr_clean = filter_var($sAddr, FILTER_SANITIZE_EMAIL);
	$oSkin = $this->App()->Skin();
	if (filter_var($sAddr_clean, FILTER_VALIDATE_EMAIL)) {
	    $sSubj = KS_TEXT_AUTH_EMAIL_SUBJ;
	    $stMsgEmail = KS_TPLT_AUTH_EMAIL_TEXT;
	    $stMsgWeb = KS_TPLT_AUTH_EMAIL_WEB;
	    // generate and store the auth token:
	    $tTokens = $this->App()->Data()->EmailAuth();
	    $rc = $tTokens->MakeToken($sAddr);
	    // calculate the authorization URL:
	    $url = $this->AuthURL($rc->KeyValue(),$rc->Token());

	    // this template is used both for the email text and the web text
	    $ar = array(
	      'addr'	=> $sAddr,
	      'action'	=> 'allow you to set or update your username and password',
	      'url'	=> $url
	      );
	    $oTplt = new clsStringTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT,$ar);

	    // generate the email
	    $sMsg = $oTplt->Replace($stMsgEmail);
	    $sSubj = KS_TEXT_AUTH_EMAIL_SUBJ;
	    $this->App()->DoEmail_Auto($sAddr,$sName,$sSubj,$sMsg);

	    // display status message
	    $sMsg = $oTplt->Replace($stMsgWeb);
	    $out = $oSkin->SuccessMessage($sMsg);
	} else {
	    if (empty($sAddr_clean)) {
		$sMsg = 'Your entry did not contain an email address.';
	    } else {
		$sMsg = 'The text "'.$sAddr_clean.'"is not a valid email address.';
	    }
	    $out = $oSkin->ErrorMessage($sMsg);
	}
	$out .= $oSkin->HLine();
	return $out;
    }
    /*----
    */
    protected function CheckAuth() {
	$ar = self::ParseAuth();
	$id = $ar['id'];
	$sToken = $ar['bin'];
	$sAuth = $ar['auth'];
	$tbl = $this->App()->Data()->EmailAuth();
	$oToken = $tbl->FindToken($id,$sToken);

	// -- look up email address (we'll need it later)
	$rc = $tbl->GetItem($id);
	$sEmail = $rc->Value('Email');

	$ok = FALSE;
	$sErr = NULL;	// error code string
	if (!is_null($oToken)) {
	    if ($oToken->IsActive()) {
		$oToken->Renew();	// keep the token from expiring
		$ok = TRUE;
	    } else {
		$sErr = 'EXP';	// expired token
	    }
	} else {
	    $sErr = 'INV';	// invalid token
	}

	// save results

	$this->AuthToken($sAuth);
	$this->Success($ok);
	if ($ok) {
	    $this->EmailAddress($sEmail);	// grab the email to go back into the "request another" form
	    $rcU = $this->UserTable()->FindEmail($sEmail);
	    $this->SetUserRecord($rcU);
	}

	$arOut = array(
	  'ok'		=> $ok,
	  'err'		=> $sErr,
//	  'html'	=> $htOut,
	  'auth'	=> $sAuth,
	  //'em_id'	=> $idEmail,	// not needed yet
	  'em_s'	=> $sEmail
	  );
	return $arOut;
    }

    // -- ACCOUNT MANAGEMENT -- //
    // ++ MENU HANDLING ++ //

    protected function MenuHome(clsMenuItem $oNode=NULL) {
	if (!is_null($oNode)) {
	    $this->oMHome = $oNode;
	    // initialize dependent vars
	    $this->oMNode = NULL;
	    $this->oMPaint = NULL;
	}
	return $this->oMHome;
    }
    /*----
      USED: internally and by drop-in controllers
    */
    protected function MenuNode() {
	if (is_null($this->oMNode)) {
	    // figure out which menu item has been invoked
	    $this->ParsePath();
	    $sPage = $this->PathArg('page');
/*	    if (empty($sPage)) {
		throw new exception('Page key is not being specified in URL.');
	    }	*/
	    if (is_null($this->MenuHome())) {
		throw new exception('Trying to access menu  when there is no home node.');
	    }
	    // get the menu item object
	    $this->oMNode = $this->MenuHome()->FindNode_debug($sPage);
	}
	return $this->oMNode;
    }
    protected function MenuPainter() {
	if (is_null($this->oMPaint)) {
	    $this->oMPaint = $this->MenuPainter_new();
	}
	return $this->oMPaint;
    }
    abstract protected function MenuPainter_new();
    protected function RenderHome() {
	return $this->RenderLogout();	// ok to override this
    }
    /*----
      ACTION: Executes the main action for the currently chosen menu selection
	(as derived from URL path info)
      LATER: (2013-12-03) This probably needs to be generalized a bit more,
	but hopefully it will do for now.
      HISTORY:
	2014-01-27 Renamed from HandleMenu() to MenuNode_Exec()
    */
    protected function MenuNode_Exec() {
	$oNode = $this->MenuNode();
	if (!is_null($oNode)) {
	    if (is_null($oNode->GoCode())) {
		// if no Go Code, attempt to use controller
		$sCtrler = $oNode->Controller();
		$ok = FALSE;
		if (!is_null($sCtrler)) {
		    $id = $this->PathArg('id');
		    $obj = $this->Data()->Make($sCtrler,$id);
		    $tbl = $this->Data()->Make($sCtrler);
		    if (is_null($id)) {
			// surely this duplicates other code -- but where, and why isn't it being triggered? (https://vbz.net/admin/page:ord/)
			$out = $tbl->MenuExec($this->PathArgs());
		    } else {
			$rc2 = $tbl->GetItem($id);
			$out = $obj->MenuExec($this->PathArgs());
		    }
		    return $out;
		}
		if (!$ok) {
		    return 'No action defined for menu item "'.$oNode->Name().'".';
		}
	    } else {
		$php = $oNode->GoCode();
		return eval($php);	// execute the menu choice
	    }
	} else {
	    return 'Choose an item from the menu, or '.$this->RenderHome().'.';
	}
    }
    /*----
      ACTION: Does any initialization needed for the currently chosen menu selection
    */
    protected function MenuNode_Init() {
	$oNode = $this->MenuNode();
	if (!is_null($oNode)) {
	    $oNode->Selected(TRUE);
	    $sCtrler = $oNode->Controller();
	    if (!is_null($sCtrler)) {
		$id = $this->PathArg('id');
		$obj = $this->Data()->Make($sCtrler,$id);
		if (method_exists($obj,'MenuInit')) {
		    $out = $obj->MenuInit($this->PathArgs());
		} else {
		    $out = NULL;
		}
		return $out;
	    }
	}
    }

    // -- MENU HANDLING -- //
}