<?php
/*
  PURPOSE: nav/menu information classes
  HISTORY:
    2017-01-10 extracted from items.php for easier reference
*/
/*::::
  PURPOSE: API for creating URLs and determining significant values within them
    Descend from this to determine what the actual URL rules should be.
    Rules can be overridden by descendants if this format just isn't good enough...
*/
abstract class fcMenuKiosk {
    abstract protected function GetBasePath();
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
abstract class fcMenuKiosk_keyed extends fcMenuKiosk {

    abstract public function GetInputObject();
}
/*::::
  PURPOSE: Keyed Kiosk that implements some conventions which have turned out to be useful:
    * standard key for invoking a table
    * standard key for pulling up a record from a table
    It's up to the calling class to determine if a table is actually being invoked.
*/
abstract class fcMenuKiosk_keyed_standard extends fcMenuKiosk_keyed {
    /* 2017-01-23 This stuff goes in the dropin link class
    abstract protected function GetTableKeyName();
    abstract protected function GetRecordKeyName();
    public function GetInputTableKey() {
	return $this->GetInputObject()->GetString($this->GetTableKeyName());
    }
    public function GetInputRecordKey() {
	return $this->GetInputObject()->GetString($this->GetRecordKeyName());
    }
    abstract public function MakeURLForTable($sTable,$sRecord=NULL);
    */
}
/*::::
  PURPOSE: admin-style navigation -- /key:value/key:value
  NOTE: There seems to be some overlap in functionality between this and
    * fcLinkBuilder
    * (fcInputData_array_local - now integrated)
    ...but perhaps this is illusory. Re-evaluate when things are working.
*/
abstract class fcMenuKiosk_admin extends fcMenuKiosk_keyed_standard {

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
    /* 2017-01-23 belongs in record key class
    public function GetTableKeyName() {
	return 'page';
    }
    public function GetRecordKeyName() {
	return 'id';
    } */
    // NOTE: Not sure if this will actually be needed; if not; fold back into MakeURLForTable().
    /*
    public function MakeLinkArrayForTable($sTable,$sRecord=NULL) {
	return array(
	  $this->GetTableKeyName()	=> $sTable,
	  $this->GetRecordKeyName()	=> $sRecord
	  );
    }
    public function MakeURLForTable($sTable,$sRecord=NULL) {
	$arPath = $this->MakeLinkArrayForTable($sTable,$sRecord);
	$sPathString = fcURL::FromArray($arPath);
	return $this->MakeURLFromString($sPathString);
    } */

    // -- CEMENT -- //

}
