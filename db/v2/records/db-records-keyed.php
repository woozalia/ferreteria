<?php
/*
 PURPOSE: Keyed tables can be reliably addressed by row.
 HISTORY:
  2016-10-23 started (adapting from db.v1.data-records.fcRecs_keyed_abstract etc.)
    Will write code as needed...
*/
abstract class fcRecord_keyed extends fcDataRecord {

    // TODO: Rename this to GetKeyValueString();
    //abstract public function GetKeyString();	// might represent more than one key field
    abstract public function SetKeyValue($id);
    abstract public function GetKeyValue();
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
	    $id = $this->GetKeyValue();
	    $ar[$id] = $this->GetFieldValues();
	}
	return $ar;
    }
    public function IsNew() {
	return is_null($this->GetKeyValue());
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
/*----
  HISTORY:
    2018-04-23 added GetKeyName() to reduce instances of confusedly hard-coding it where it doesn't belong
      (mainly debugging, I think?)
*/
trait ftRecord_keyed_single {
    protected function GetKeyName() {
	$tbl = $this->GetTableWrapper();
	if (method_exists($tbl,'GetKeyName')) {
	    $sKey = $tbl->GetKeyName();
	} else {
	    $sKey = NULL;
	}
	return $sKey;
    }
    public function GetKeyValue() {
	$sKey = $this->GetKeyName();
	if (is_null($sKey)) {
	    throw new exception('Ferreteria error: Could not retrieve key value because no key name is available.');
	} else {
	    $v = $this->GetFieldValueNz($sKey);
	    return $v;
	}
    }
    public function SetKeyValue($id) {
	$sKey = $this->GetTableWrapper()->GetKeyName();
	$this->SetFieldValue($sKey,$id);
    }
}
// PAIRS WITH: fcTable_keyed_single
abstract class fcRecord_keyed_single extends fcRecord_keyed {
    use ftRecord_keyed_single;

    abstract protected function GetKeyValue_cooked();
    
    /* 2017-03-24 There is no apparent need for this.
    public function GetKeyString() {	// TODO: maybe this should be GetKeyValue_asString()
	$v = $this->GetKeyValue();
	return is_null($v)?NULL:(string)$this->GetKeyValue();
    } */
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
    // PURPOSE: This is just an interface-checked version of GetTableWrapper().
    protected function GetTableWrapper_Insertable() : fiInsertableTable {
	return $this->GetTableWrapper();
    }
    public function Update(array $arChg,$isNativeData=FALSE) {
	$sqlFilt = $this->GetSelfFilter();
	$this->GetTableWrapper()->Update($arChg,$sqlFilt,$isNativeData);
	$this->sql = $this->GetTableWrapper()->sql;
    }
    protected function Insert(array $arRow) {
	$tbl = $this->GetTableWrapper_Insertable();
	$id = $tbl->Insert($arRow);
	$this->sql = $tbl->sql;
	if ($id === FALSE) {
	    $id = NULL;
	} else {
	    $this->SetKeyValue($id);	// remember new record's ID
	}
	return $id;
    }
}
class fcRecord_keyed_single_integer extends fcRecord_keyed_single {
    protected function GetKeyValue_cooked() {
	return $this->GetKeyValue();
    }
}
class fcRecord_keyed_single_string extends fcRecord_keyed_single {
    protected function GetKeyValue_cooked() {
	return $this->GetConnection()->SanitizeValue($this->GetKeyValue());
    }
}
// PURPOSE: currently just a convenient alias
class fcRecord_standard extends fcRecord_keyed_single_integer {
}