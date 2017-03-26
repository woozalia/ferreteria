<?php
/*
  PURPOSE: user access control classes: security groups
  HISTORY:
    2013-12-19 started
*/
class fctUserGroups extends fcTable_keyed_single_standard {

    // ++ SETUP ++ //

    protected function TableName() {
	return KS_TABLE_USER_GROUP;
    }
    protected function SingularName() {
	return KS_CLASS_USER_GROUP;
    }

    // -- SETUP -- //
    // ++ BUSINESS LOGIC ++ //

    private $arData;
    public function AsArray() {
	throw new exception('2017-01-14 Get a recordset of ALL records and then call $rs->asKeyedArray().');
	if (empty($this->arData)) {
	    $rs = $this->GetData();
	    $this->arData = $rs->AsArray();
	}
	return $this->arData;
    }

    // -- BUSINESS LOGIC -- //

}
class fcrUserGroup extends fcRecord_standard {
    //use ftSaveableRecord;

    // ++ FIELD VALUES ++ //

    public function NameString() {
	return $this->GetFieldValue('Name');
    }
    public function Descr() {
	throw new exception('2017-01-27 Call UsageText() instead.');
    }
    public function UsageText() {
	return $this->GetFieldValue('Descr');
    }

    // -- FIELD VALUES -- //
    // ++ CLASSES ++ //
    
    protected function XPermitsClass() {
	return KS_CLASS_UPERMITS_FOR_UGROUP;
    }
    
    // -- CLASSES -- //
    // ++ TABLES ++ //

    protected function XPermTable() {
	return $this->GetConnection()->MakeTableWrapper($this->XPermitsClass());
    }

    // -- TABLES -- //
    // ++ RECORDS ++ //

    protected function UPermRecords() {
	return $this->XPermTable()->UPermRecords($this->GetKeyValue());
    }
    /*----
      RETURNS: The current recordset as an associative array, indexed by ID
    */
    public function AsArray() {
	throw new exception('2017-01-14 Call $this->asKeyedArray() instead.');
	throw new exception("2016-10-28 Isn't there a base class function for this?");
	$ar = array();
	while ($this->NextRow()) {
	    $id = $this->GetKeyValue();
	    $ar[$id] = $this->Values();
	}
	return $ar;
    }

    // -- RECORDS -- //
}