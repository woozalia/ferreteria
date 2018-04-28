<?php
/*
  PURPOSE: nav/menu information classes
  HISTORY:
    2017-01-10 extracted from items.php for easier reference
    2017-05-14 removed class fcMenuKiosk_keyed_standard because it no longer does anything (as of 2017-01-23)
    2018-03-12 fcMenuKiosk_autonomous so we can have Kiosks that get the path data from elsewhere
      Immediate need was MediaWiki SpecialPages, which pass the path data via execute().
*/
/*::::
  PURPOSE: API for creating URLs and determining significant values within them
    Descend from this to determine what the actual URL rules should be.
    Rules can be overridden by descendants if this format just isn't good enough...
*/
abstract class fcMenuKiosk {
  
    abstract public function GetInputString();
    abstract public function GetPagePath();
}
/*::::
  PURPOSE: Kiosk that has to do its own figuring to separate out the path data from the full URL
*/
abstract class fcMenuKiosk_autonomous extends fcMenuKiosk {

    abstract protected function GetBasePath();	// changes depending on which kiosk
    
    private $wpPage;
    public function SetPagePath($wp) {
	$this->wpPage = $wp;
    }
    // CEMENT
    public function GetPagePath() {
	$wp = $this->wpPage;
	return isset($wp) ? $wp : $this->GetBasePath();
    }
    /*----
      RETURNS: The full URL for the given input string
	This format can be overridden if you want the string to be presented some other way,
	such as in a ?query
    */
    public function MakeURLFromString($sPath) {
	return $this->GetBasePath().$sPath;
    }
    /*----
      RETURNS: The string from which all input arguments may be parsed
	This format can be overridden if you want to get the string from somewhere else,
	such as a ?query
    */
    public function GetInputString() {
	return fcURL::PathRelativeTo($this->GetBasePath());
    }
}
/*::::
  PURPOSE: Kiosk for URLs that contain key-value pairs in some as-yet-unspecified format
*/
abstract class fcMenuKiosk_keyed extends fcMenuKiosk_autonomous {

    abstract public function GetInputObject();
}
/*::::
  PURPOSE: adds admin-style navigation -- /key:value/key:value
  NOTE: There seems to be some overlap in functionality between this and
    * fcLinkBuilder
    * (fcInputData_array_local - now integrated)
    ...but perhaps this is illusory. Re-evaluate when things are working.
*/
trait ftMenuKiosk_admin {

    // ++ CEMENT ++ //

    public function GetInputObject() {
	$wp = $this->GetInputString();
	if (strlen($wp) > 1) {
	    // make an object for querying the path
	    $arPath = fcURL::ParsePath($wp);
	} else {
	    $arPath = array();
	}
	$oReq = new fcInputData_array_local($arPath);
	return $oReq;
    }

    // -- CEMENT -- //

}
abstract class fcMenuKiosk_admin extends fcMenuKiosk_keyed {
    use ftMenuKiosk_admin;
}
