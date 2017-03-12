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
    static public function SendDownload($sMIME,$fsNameLocal,$fnNameAs) {
	header('Content-Description: File Transfer');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-type: '.$sMIME);
	//header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$fnNameAs.'"');
	$nSize = filesize($fsNameLocal);
	header('Content-Length: '.$nSize);
	ob_end_flush();

	readfile($fsNameLocal);
	exit;
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
// PURPOSE: fcInputData_array whose data is in $_REQUEST
class fcHTTP_REQUEST extends fcInputData_array {
    protected function Values() {
	return $_REQUEST;
    }
}
