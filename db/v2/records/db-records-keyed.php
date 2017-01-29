<?php
/*
 PURPOSE: Keyed tables can be reliably addressed by row.
 HISTORY:
  2016-10-23 started (adapting from db.v1.data-records.fcRecs_keyed_abstract etc.)
    Will write code as needed...
*/

abstract class fcRecord_keyed extends fcDataRecord {
    // TODO: Rename this to GetKeyValueString();
    abstract public function GetKeyString();	// might represent more than one key field
    /*----
      RETURNS: array of recordsets:
	array[ID] = Values()
      SEE ALSO: ColumnValues_array() (related but not identical)
      TODO: There is a native function for this, which would be slightly faster if the db wrapper class supported it. Write that.
    */
    public function asKeyedArray() {
	$ar = array();
	$this->RewindRows();	// rewind to before the first row
	while ($this->NextRow()) {
	    $id = $this->GetKeyString();
	    $ar[$id] = $this->GetFieldValues();
	}
	return $ar;
    }
    public function IsNew() {
	return is_null($this->GetKeyString());
    }
    abstract protected function GetSelfFilter();
    /*-----
      ACTION: Reloads only the current row
      HISTORY:
	2012-03-04 moved from clsRecs_key_single to clsRecs_keyed_abstract
	2012-03-05 removed code referencing iFilt -- it is no longer in the arg list
	2016-11-04 adapted from db.v1 -- mostly rewritten from scratch
    */
    public function Reload() {
	$sqlFilt = $this->GetSelfFilter();
	$rc = $this->GetTableWrapper()->SelectRecords($sqlFilt);
	$rc->NextRow();		// load only row
	$this->SetFieldValues($rc->GetFieldValues());	// copy loaded row to here
    }

}
// PAIRS WITH: fcTable_keyed_single
abstract class fcRecord_keyed_single extends fcRecord_keyed {
    abstract protected function GetKeyValue_cooked();
    
    public function GetKeyValue() {
	$tbl = $this->GetTableWrapper();
	if (method_exists($tbl,'GetKeyName')) {
	    $sKey = $tbl->GetKeyName();
	    return $this->GetFieldValueNz($sKey);
	} else {
	    // 2017-01-08 Not actually sure who is at fault here, the class or the caller.
	    throw new exception('Class '.get_class($tbl).' does not implement GetKeyName().');
	}
    }
    public function SetKeyValue($id) {
	$sKey = $this->GetTableWrapper()->GetKeyName();
	$this->SetFieldValue($sKey,$id);
    }
    public function GetKeyString() {	// TODO: maybe this should be GetKeyValue_asString()
	$v = $this->GetKeyValue();
	return is_null($v)?NULL:(string)$this->GetKeyValue();
    }
    protected function GetSelfFilter() {
	return $this->GetTableWrapper()->GetKeyName().'='.$this->GetKeyValue_cooked();
    }
    protected function FetchKeyValues_asSQL() {
	$sKey = $this->GetTableWrapper()->GetKeyName();
	return $this->FetchColumnValues_asSQL($sKey);
    }
    public function FetchRows_asKeyedArray() {
	return $this->FetchRows_asArray($this->GetTableWrapper()->GetKeyName());
    }
    public function Update(array $arChg) {
	$sqlFilt = $this->GetSelfFilter();
	$this->GetTableWrapper()->Update($arChg,$sqlFilt);
	$this->sql = $this->GetTableWrapper()->sql;
    }
}
class fcRecord_keyed_single_integer extends fcRecord_keyed_single {
    protected function GetKeyValue_cooked() {
	return $this->GetKeyValue();
    }
}
class fcRecord_keyed_single_string extends fcRecord_keyed_single {
    protected function GetKeyValue_cooked() {
	$sID = $this->GetConnection()->Sanitize($this->GetKeyValue());
	return "'$sID'";
    }
}
// PURPOSE: currently just a convenient alias
class fcRecord_standard extends fcRecord_keyed_single_integer {
}