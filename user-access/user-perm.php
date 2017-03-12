<?php
/*
  PURPOSE: user access control classes: available security permissions
  HISTORY:
    2013-12-29 started
    2017-01-04 This will need some updating to work with Ferreteria revisions.
*/
class fctUserPerms extends fcTable_keyed_single_standard {

    // ++ SETUP ++ //
    
    protected function TableName() {
	return KS_TABLE_USER_PERMISSION;
    }
    protected function SingularName() {
	return KS_CLASS_USER_PERMISSION;
    }

    // -- SETUP -- //
    // ++ ARRAYS ++ //

    /* 2017-02-05 Call FetchRows_asKeyedArray() instead.
    private $arData;
    public function AsArray() {
	throw new exception('2017-01-27 Does anything actually call this? Use FetchRows_asKeyedArray() instead.');
	if (empty($this->arData)) {
	    $rs = $this->GetData();
	    $this->arData = $rs->AsArray();
	}
	return $this->arData;
    } */

    // -- ARRAYS -- //

}
class fcrUserPermit extends fcRecord_standard {

    // ++ FIELD ACCESS ++ //

    public function Name() {
	return $this->Value('Name');
    }
    public function Descr() {
	return $this->Value('Descr');
    }

    // -- FIELD ACCESS -- //
    // ++ DATA RECORDS ACCESS ++ //

    /*----
      RETURNS: The current recordset as an associative array, indexed by ID
    */
    /* 2017-02-05 Call asKeyedArray() instead.
    public function AsArray() {
	throw new exception("2016-10-28 Isn't there a base class function for this?");
	$ar = array();
	while ($this->NextRow()) {
	    $id = $this->GetKeyValue();
	    $ar[$id] = $this->Values();
	}
	return $ar;
    } */

    // -- DATA RECORDS ACCESS -- //
}