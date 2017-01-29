<?php
/*
  HISTORY:
    2014-04-02 Created clsURL from methods in clsHTTP
    2016-11-20 Renamed clsURL to fcURL.
*/

class fcURL {
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
      TODO: This should probably be renamed -- it doesn't *just* remove the base,
	it also removes the ?query and #fragment. Something like NonBasePathOnly()
	would be accurate but clumsy.
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
    */
    static public function PathRelativeTo($urlBase,$wpFull=NULL) {
	if (is_null($wpFull)) {
	    $wsRight = $_SERVER["REQUEST_URI"];
	} else {
	    $wsRight = $wpFull;
	}
	if ($urlBase == '') {
	    // base is root = nothing to calculate
	    $wpOut = $wsRight;
	} else {
	    $urlLeft = $urlBase;
	    // $fpRight is the part after the domain; $urlLeft is a complete URL
	    $wpLeft = parse_url($urlLeft,PHP_URL_PATH);	// get the post-domain part
	    // remove ?query and #fragment
	    //$wpRight = parse_url($wsRight,PHP_URL_PATH);	// this doesn't work reliably
	    $wpRight = fcString::DelTail($wsRight,'#?');
	    $idx = strpos($wpRight,$wpLeft);
	    if ($idx == 0) {
		$wpOut = substr($wpRight,strlen($wpLeft));		// remove URL base
	    } else {
		$wpOut = $wpRight;	// base does not match beginning of full path
	    }
	}
	return $wpOut;
    }
    static public function RemoveBasePath($urlBase,$wpFull=NULL) {
	// TODO: this is DEPRECATED - replace all references with calls to PathRelativeTo()
	return self::PathRelativeTo($urlBase,$wpFull);
    }
    static public function RemoveBaseURI_OLD($fpBaseURL,$sPath=NULL) {
	if (is_null($sPath)) {
	    $fp = $_SERVER["REQUEST_URI"];
	} else {
	    $fp = $sPath;
	}

	$fpBaseURI = parse_url($fpBaseURL,PHP_URL_PATH);
echo "FPBASEURL=[$fpBaseURL] SPATH=[$sPath]<br>";
echo "FPBASEURI=[$fpBaseURI]<br>";
	$arURI = parse_url($fpBaseURL);
echo "PARSED:<pre>".print_r($arURI,TRUE).'</pre>';
	$idx = strpos($fpBaseURI,$fp);
	if ($idx == 0) {
	    $fp = substr($fp,strlen($fpBaseURI));		// remove URL base
	}
	echo 'FP(out)=['.$fp.']<br>';
	return $fp;
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
	$fp = fcString::GetBefore($sPath,'?');	// remove query, if any
	$fp = trim($fp,$sPathSep);		// remove beginning/ending path separators
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
}