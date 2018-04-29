<?php
/*
  PURPOSE: A somewhat more controlled way of managing global values
  HISTORY:
    2017-03-13 created: fcGlobals and ftSingleton
*/
/*::::
  PURPOSE: for forcing a class to have only one object-instance
*/
trait ftSingleton {
    static protected $me;
    // ACTION: always creates object, throws error if one already exists
    public function __construct() {
	if (isset(self::$me)) {
	    throw new exception('Ferreteria usage error: attempted to create duplicate instance of '.get_class($this).'.');
	}
	self::$me = $this;	// there can be only one
    }
    // ACTION: returns existing object, throws error if one does not exist
    static public function Me() {
	if (!isset(self::$me)) {
	    throw new exception('Ferreteria usage error: attempting to access '.get_called_class().' object before it has been set.');
	}
	return self::$me;
    }
    // ACTION: returns existing object if there is one, otherwise creates one
    static public function Make() {
	if (isset(self::$me)) {
	    return self::Me();
	} else {
	    $sClass = get_called_class();
	    return new $sClass();
	}
    }
}
/*::::
  USAGE: Descend from this to set return values
*/
abstract class fcGlobals {
    use ftSingleton;
    
    // keys/names
    
    public function GetSessionCookieName() {
	return 'session';
    }
    public function GetStashCookieName() {
	return 'stash';
    }
    
    // file paths

    abstract protected function GetFilePath_forSiteFolders();
    public function GetFilePath_forDropins() {
	return $this->GetFilePath_forSiteFolders().'/dropins';
    }
    
    // web paths
    
    abstract protected function GetWebPath_forAppBase();
    abstract protected function GetWebPath_forStyleSheets();

    /*----
      PURPOSE: Deals with the root/non-root dilemma
	* If you use a slash for root:
	** If you want paths not to end in a slash:
	*** If you always add a slash between AppBase and RelPath, then you get double slashes if AppBase is root
	*** If you don't add a slash between AppBase and RelPath, then you get pathwreck (/parent/child becomes /parentchild).
	** If you end all paths in a slash, this tends to cause other issues (to be documented)
	* If you use blank for root:
	** If you want paths not to end in a slash:
	*** You have to specify AppBase without a beginning or ending slash
	*** You have to prefix constructed paths with a slash after assembly
	** If you end all paths with a slash:
	*** You have to remember to request BasePath as '/' and terminate all RelPaths with '/' as well.
	*** ...and ending all paths with a slash causes issues elsewhere
	Solution 1:
	  * Use blank for root.
	  * Prepend slash to all constructed paths.
	  * Specify nonblank BasePath with ending slash, no beginning slash
	  * Request RelPath with ending '/', even if blank (i.e. you're actually requesting BasePath)
	  * Implement MakeWebPath_forAppPath_noSlash() which removes ending slashes, in case this is needed
	Refinement (2017-05-14):
	  * We have to distinguish between paths and URLs --
	    the initial slash must only be added when converting from path to URL,
	    otherwise we end up with multiple initial slashes.
	  * The simplest way I could think of to do this is to always return an fcURL object.
	    When adding stuff to the path, use AddValue_Path(); when ready for the final result,
	    use GetValue().
	  * I *think* this means MakeWebPath_forAppPath() and MakeWebPath_forAppPath_noSlash() are no longer needed.
	  
	Solution 2: end all paths in a slash, find out what the problem is and DOCUMENT IT.
    */
    /*
    protected function MakeWebPath_forAppPath($fp) {
	if (substr($fp,-1) != '/') {
	    throw new exception("Ferreteria usage error: requested relative path [$fp] does not end with a '/'.");
	}
	if (substr($fp,0,1) == '/') {
	    throw new exception("Ferreteria usage error: requested relative path [$fp] begins with a '/'.");
	}
	$fpBase = $this->GetWebPath_forAppBase();
	return '/'.$fpBase.$fp;
    }
    protected function MakeWebPath_forAppPath_noSlash($fp) {
	if (substr($fp,-1) != '/') {
	    throw new exception("Ferreteria usage error: requested relative path [$fp] does not end with a '/'.");
	}
	$fpBase = $this->GetWebPath_forAppBase();
	return $fpBase.substr($fp,0,-1);	// omit the last character, which must be a slash
    }*/
    
    // -- individual files
    
    abstract public function GetWebSpec_forSuccessIcon();
    abstract public function GetWebSpec_forWarningIcon();
    abstract public function GetWebSpec_forErrorIcon();
    
    // -- text
    
    protected function GetText_SiteName() {
	return KS_SITE_NAME;
    }
    
    // WAS: constant KS_TEXT_EMAIL_SUBJ_FOR_NEW_ACCOUNT
    public function GetText_NewAccount_EmailSubject() {
    	return $this->GetText_SiteName().' new account request';
    }
    // WAS: constant KS_TPLT_EMAIL_TEXT_FOR_NEW_ACCOUNT
    public function GetTemplate_NewAccount_EmailContent() {
	return <<<__END__
Someone (hopefully you) has requested authorization to create a new account on {{site}}.

If you would like to do this, please go to this link:

	  {{url}}

You will be able to enter your account information there.
__END__;
    }
    // WAS: constant KS_TEXT_EMAIL_SUBJ_FOR_PASS_CHANGE
    public function GetText_PasswordChange_EmailSubject() {
	return $this->GetText_SiteName().' password reset authorization';
    }
    // WAS: constant KS_TPLT_EMAIL_TEXT_FOR_PASS_CHANGE
    public function GetTemplate_PasswordChange_EmailContent() {
	return <<<__END__
Someone (hopefully you) has made a request to change the password on {{site}} for user "{{user}}" at this email address ({{addr}}).

If you would like to do this, please go to this link:

	  {{url}}
	  
You will be able to enter your new password there.
__END__;
    }
    // WAS: constant KS_TPLT_AUTH_EMAIL_TO_SHOW
    public function GetTemplate_PasswordChange_ScreenMessage() {
	return <<<__END__
A password reset link has been emailed to you at <b>{{addr}}</b>.<br>
Clicking the link will {{action}}.
__END__;
    }
    public function GetTemplate_NewAccount_ScreenMessage() {
	return <<<__END__
A link for creating a new account has been emailed to you at <b>{{addr}}</b>.<br>
Clicking the link will {{action}}.
__END__;
    }
}