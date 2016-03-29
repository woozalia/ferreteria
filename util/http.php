<?php
/*
  PURPOSE: HTTP functions
  HISTORY:
    2013-12-25 started
    2014-04-04 defined HTTP_REDIRECT_POST (is it supposed to be defined somewhere else?)
    2016-02-22 replaced clsHTTPInput with fcInputData_array_local, and created fcHTTP_REQUEST.
      TODO: There should probably also be fcHTTP_GET and fcHTTP_POST classes.
      TODO: fcInputData, fcInputData_array, and fcInputData_array_local probably belong somewhere else.
      TODO: rename clsHTTP to fcHTTP.
*/

define('HTTP_REDIRECT_POST',303);

class fcHTTP {

    /*----
      NOTES: Some builds of PHP don't have this function.
	Use it if it exists, otherwise replicate the functionality.
      TODO: implement $arParams and $bSession when built-in function not found.
      INPUT:
	([ string $url [, array $params [, bool $session = false [, int $status = 0 ]]
	See http://php.net/manual/en/function.http-redirect.php
    */
    static public function Redirect($url,array $arParams=NULL,$bSession=FALSE,$nStatus=0) {
	if (function_exists('http_redirect')) {
	    return http_redirect($url,$arParams,$bSession,$nStatus);
	} else {
	    if ($nStatus==0) {
		$nStatus = HTTP_REDIRECT_POST;	// 303
	    }
	    header("Status: $nStatus",TRUE);	// replace any previous Status header
	    header('Location: '.$url,TRUE);	// replace any previous Location header
	    return $nStatus;
	}
    }
    // ++ FORM DATA ++ //

    /* 2016-02-22 old version
    static private $oReq=NULL, $oGet=NULL, $oPost=NULL, $oCookie=NULL;
    static public function Request() {
	if (is_null(self::$oReq)) {
	    self::$oReq = new clsHTTPInput($_REQUEST);
	}
	return self::$oReq;
    } //*/
    static public function Request() {
	return new fcHTTP_REQUEST();
    }
    /*----
      PURPOSE: Saves program output so that it can be displayed after a redirect
      RETURNS: stored text - from argument (if not null) or from cookie
    */
    static public function DisplayOnReturn($sText=NULL) {
	$kCookie = 'ferreteria-text-to-show';
	if (is_null($sText)) {
	    // not setting cookie -- retrieve it
	    $sText = clsArray::Nz($_COOKIE,$kCookie);
	    // clear the cookie (expire it) so text is only displayed once
	    setcookie($kCookie,NULL,time()-3600,'/');
//	    "clsHTTP: found msg [$sText]";
	} else {
	    // set the cookie
	    setcookie($kCookie,$sText,0,'/');
//	    "clsHTTP: setting msg [$sText]";
	}
	return $sText;
    }
    static public function ClientAddress_string() {
	return $_SERVER['REMOTE_ADDR'];
    }
    static public function ClientBrowser_string() {
	return $_SERVER['HTTP_USER_AGENT'];
    }

    // -- FORM DATA -- //
}
class clsHTTP extends fcHTTP {}	// alias; deprecate later
abstract class fcInputData {
    abstract protected function Values();
    abstract protected function Value($sName);
    abstract public function KeyExists($sName);
    abstract public function GetBool($sName);
    abstract public function GetText($sName,$vDefault=NULL);
    abstract public function GetInt($sName,$vDefault=NULL);
    abstract public function GetArray($sName,$vDefault=array());
    public function DumpHTML() {
	return '<pre>'.print_r($this->Values(),TRUE).'</pre>';
    }
}
/*----
  IMPLEMENTS everything except Values(). We know we have all the data in an array,
    but we don't yet know how to access it.
*/
abstract class fcInputData_array extends fcInputData {
    protected function Value($sName) {
	$ar = $this->Values();
	if (array_key_exists($sName,$ar)) {
	    return $ar[$sName];
	} else {
	    return NULL;
	}
    }
    public function KeyExists($sName) {
	return array_key_exists($sName,$this->Values());
    }
    public function GetBool($sName) {
	return (boolean)$this->Value($sName);
    }
    public function GetText($sName,$vDefault=NULL) {
	$val = $this->Value($sName);
	if (is_string((string)$val)) {
	    return $val;
	} else {
	    return $vDefault;
	}
    }
    public function GetInt($sName,$vDefault=NULL) {
	$val = $this->Value($sName);
	if (is_integer((int)$val)) {
	    return $val;
	} else {
	    return $vDefault;
	}
    }
    public function GetArray($sName,$vDefault=array()) {
	$val = $this->Value($sName);
	if (is_array($val)) {
	    return $val;
	} else {
	    return $vDefault;
	}
    }
}
// PURPOSE: fcInputData_array whose data is stored internally
class fcInputData_array_local extends fcInputData_array {
    private $ar;	// raw form data
    public function __construct(array $ar) {
	$this->ar = $ar;
    }
    protected function Values() {
	return $this->ar;
    }
}
// PURPOSE: fcInputData_array whose data is in $_REQUEST
class fcHTTP_REQUEST extends fcInputData_array {
    protected function Values() {
	return $_REQUEST;
    }
}
