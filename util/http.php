<?php
/*
  PURPOSE: HTTP functions
  HISTORY:
    2013-12-25 started
    2014-04-04 defined HTTP_REDIRECT_POST (is it supposed to be defined somewhere else?)
*/

define('HTTP_REDIRECT_POST',303);

class clsHTTP {

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

    static private $oReq=NULL, $oGet=NULL, $oPost=NULL, $oCookie=NULL;
    static public function Request() {
	if (is_null(self::$oReq)) {
	    self::$oReq = new clsHTTPInput($_REQUEST);
	}
	return self::$oReq;
    }
    /*
    static private $oPath;
    static public function PathArgs(array $arPath=NULL,$sPathSep=KS_CHAR_PATH_SEP,$sArgSep=KS_CHAR_URL_ASSIGN) {
	if (is_null(self::$oPath)) {
	    self::$oPath = new clsHTTPInput($arPath);
	}
	return self::$oPath;
    }
    */
    /*----
      PURPOSE: Saves program output so that it can be displayed after a redirect
    */
    static public function DisplayOnReturn($sText=NULL) {
	if (is_null($sText)) {
	    // not setting cookie -- retrieve it
	    $sText = clsArray::Nz($_COOKIE,'text-to-show');
	    // clear the cookie (expire it) so text is only displayed once
	    setcookie('text-to-show',NULL,time()-3600,'/');
	} else {
	    // set the cookie
	    setcookie('text-to-show',$sText,0,'/');
	}
	return $sText;
    }

    // -- FORM DATA -- //
}
class clsHTTPInput {
    private $ar;	// raw form data
    private $isFnd;	// element was found for previous request

    public function __construct(array $ar) {
	$this->ar = $ar;
    }
    public function DumpHTML() {
	return '<pre>'.print_r($this->Values(),TRUE).'</pre>';
    }
    protected function Values() {
	return $this->ar;
    }
    protected function Value($sName) {
	$this->isFnd = array_key_exists($sName,$this->ar);
	if ($this->isFnd) {
	    return $this->ar[$sName];
	} else {
	    return NULL;
	}
    }
    public function KeyExists($sName) {
	return array_key_exists($sName,$this->ar);
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