<?php
/*
  HISTORY:
    2014-04-02 Created clsURL from methods in clsHTTP
    2016-11-20 Renamed clsURL to fcURL.
    2017-02-04 Removed RemoveBasePath() (deprecated alias for PathRelativeTo()).
    2017-05-13 Changing internals: now stores parsed value instead of raw value.
      This makes it easier to operate on the pieces.
  TODO:
    fcURL is currently mainly about URLs that contain data in the path.
      That should be a descendant; fcURL should be basic URL functions.
*/

class fcURL {

  // // ++ DYNAMIC ++ // //
  
    // ++ SETUP ++ //
    
    public function __construct($url) {
	$this->SetValue($url);
    }
    
    // -- SETUP -- //
    // ++ VALUE ++ //

    
    private $url;
    public function SetValue($url) {
	$this->url = $url;
    }
    public function GetValue() {
	return $this->url;
    }
    /*
    private $arURL;
    public function SetValue($url) {
	$this->arURL = parse_url($url);
    }
    public function GetValue() {
	$parsed_url = $this->arURL;
	// copied verbatim from http://php.net/manual/en/function.parse-url.php#106731
	// This will probably need modification so it prepends '/' to the path.
	$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
	$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
	$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
	$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
	$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
	$pass     = ($user || $pass) ? "$pass@" : '';
	$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
	$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
	$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
	return "$scheme$user$pass$host$port$path$query$fragment";
    }
    */
    public function GetValue_Path() {
	return parse_url($this->GetValue(),PHP_URL_PATH);
    }
    
    /* 2017-05-14 Tentatively, this actually isn't needed.
    public function SetValue_Path($fp) {
	$this->arURL['path'] = $fp;
    }
    public function GetValue_Path() {
	return $this->arURL['path'];
    }
    public function AddValue_Path($fp) {
	$this->arURL['path'] .= $fp;
    } */

    // -- VALUE -- //

  // // -- DYNAMIC -- // //
  // // ++ STATIC ++ // // 
    
    // ++ ENVIRONMENT ++ //
    
    static public function GetCurrentString() {
	return $_SERVER["REQUEST_URI"];
    }
    static public function GetCurrentObject() {
	$sClass = __CLASS__;	// LATER: Replace this with a method so descendant classes can spawn themselves
	return new $sClass(self::GetCurrentString());
    }
    
    // -- ENVIRONMENT -- //
    // ++ CALCULATIONS ++ //

    /*----
      TODO: This should probably be renamed -- it doesn't *just* remove the base,
	it also removes the ?query and #fragment. Something like NonBasePathOnly()
	would be accurate but clumsy.
      USED BY PathInfo functions
      NOTES:
	parse_url() doesn't properly handle paths that begin with a segment
	  that looks like a port number on a domain, e.g. something:34. I'm
	  therefore not using it to strip off ?query and #fragment anymore.
	  It can be made to work by tacking on a scheme and domain (http://dummy.com)
	  at the beginning, but that seems more klugey than just truncating the string
	  after the first "?" or "#".
      HISTORY:
	2014-04-02 Rewritten; renamed from RemoveBaseURI() to RemoveBasePath()
	2014-04-27 Found bug in something but forgot to finish making a note about it.
	2017-02-04 Rewrote substantially; parts didn't make sense. Fixing even though it ain't broke.
	  * Using self::GetCurrentString()
	  * Nobody is using $wpFull and it's not clear exactly how it would be used, so I'm removing it.
	  * Now returning NULL if $urlBase is not part of current path.
      TODO: Do we need special handling for $urlBase='/', or does it work out ok?
    */
    static public function PathRelativeTo($urlBase) {
	$wpFull = self::GetCurrentObject()->GetValue_Path();
	if ($urlBase == '') {
	    echo ' - no change needed<br>';
	    // base is root = nothing to calculate
	    $wpOut = $wpFull;
	} else {
	    $wpBase = parse_url($urlBase,PHP_URL_PATH);		// BASE: just the path
	    $idx = strpos($wpFull,$wpBase);
	    if ($idx == 0) {
		$wpOut = substr($wpFull,strlen($wpBase));	// remove URL base
	    } else {
		$wpOut = NULL;	// base does not match beginning of full path; can't operate
	    }	    
	}
	return $wpOut;
    }

    // -- CALCULATIONS -- //
    // ++ PATHINFO ++ //

    static public function FromArray(array $arArgs=NULL,$sSep=KS_CHAR_URL_ASSIGN) {
	$fpArgs = NULL;
	foreach ($arArgs as $key => $val) {
	    if ($val === TRUE) {
		$sPart = $key;
	    } elseif (($val === FALSE) || is_null($val)) {
		$sPart = NULL;
	    } else {
		$sPart = $key.$sSep.$val;
	    }
	    if (!is_null($sPart)) {
		$fpArgs .= $sPart.'/';
	    }
	}
	return $fpArgs;
    }
    /*----
      ACTION: Parses paths formatted using KS_CHAR_PATH_SEP and KS_CHAR_URL_ASSIGN
	1. Explode path into segments (folder names) using KS_CHAR_PATH_SEP
	2. Explode each segment into key/value pairs using KS_CHAR_URL_ASSIGN
    */
    static public function ParsePath($sPath,$sPathSep=KS_CHAR_PATH_SEP,$sArgSep=KS_CHAR_URL_ASSIGN) {
	if (!is_string($sPath)) {
	    throw new exception('ParsePath() was handed something other than a string for the path.');
	}
	$fp = fcString::GetBeforeFirst($sPath,'?');	// remove query, if any
	$fp = trim($fp,$sPathSep);			// remove beginning/ending path separators
	$arPath = explode($sPathSep,$fp);
	foreach ($arPath as $fn) {
	    $arFrag = explode($sArgSep,$fn);	// argument separator
	    $cnt = count($arFrag);
	    $key = array_shift($arFrag);
	    if ($cnt == 1) {
		// no value, just a key
		$val = TRUE;
	    } elseif($cnt == 2) {
		// key and single value
		$val = array_shift($arFrag);
	    } else {
		// key and multiple values (list)
		$val = $arFrag;
	    }
	    $arOut[$key] = $val;
	}
	return $arOut;
    }

    // -- PATHINFO - //

  // // -- STATIC -- // // 
}