<?php
/*
  PURPOSE: user access control classes: security groups
  HISTORY:
    2013-12-19 started
*/
class clsUserGroups extends clsTable {
    private $arData;

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name(KS_TABLE_USER_GROUP);
	  $this->KeyName('ID');
	  $this->ClassSng('clsUserGroup');
	$this->arData = NULL;
    }
    // ++ BUSINESS LOGIC ++ //

    public function AsArray() {
	if (is_null($this->arData)) {
	    $rs = $this->GetData();
	    $this->arData = $rs->AsArray();
	}
	return $this->arData;
    }

    // -- BUSINESS LOGIC -- //
}
class clsUserGroup extends fcDataRecs {

    // ++ FIELD ACCESS ++ //

    public function Name() {
	return $this->Value('Name');
    }
    public function Descr() {
	return $this->Value('Descr');
    }

    // -- FIELD ACCESS -- //
    // ++ DATA TABLE ACCESS ++ //

    protected function XPermTable() {
	$tbl = $this->Engine()->Make(KS_CLASS_UGROUP_X_UPERM);
	return $tbl;
    }

    // -- DATA TABLE ACCESS -- //
    // ++ DATA RECORDS ACCESS ++ //

    protected function UPermRecords() {
	return $this->XPermTable()->UPermRecords($this->KeyValue());
    }

    /*----
      RETURNS: The current recordset as an associative array, indexed by ID
    */
    public function AsArray() {
	$ar = array();
	while ($this->NextRow()) {
	    $id = $this->KeyValue();
	    $ar[$id] = $this->Values();
	}
	return $ar;
    }

    // -- DATA RECORDS ACCESS -- //
}