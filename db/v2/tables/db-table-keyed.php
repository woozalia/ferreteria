<?php

trait ftKeyedTable {

    // ++ RECORDS ++ //
    
    /*----
      INPUT: $id = scalar containing the key value(s) for the wanted row
    */
    abstract public function GetRecord_forKey($id);
    
    // -- RECORDS -- //
}
trait ftSingleKeyedTable {
    use ftKeyedTable;
    
    // ++ SETUP ++ //
    
    // PUBLIC because Recordset wrapper class needs to use it
    abstract public function GetKeyName();

    // -- SETUP -- //
    // ++ RECORDS ++ //
    
    public function GetRecord_forKey($id) {
	$sqlFilt = $this->GetKeyName().'='.$this->GetConnection()->SanitizeValue($id);
	$rc = $this->SelectRecords($sqlFilt);
	if ($rc->RowCount() == 0) {
	    $rc->ClearFields();	// so HasRow() will return FALSE
	} else {
	    $rc->NextRow();	// advance to first (only) row
	}
	return $rc;
    }
    // 2017-03-18 created for EventPlex
    public function GetRecords_forKeyList($sqlIDs) {
	$sqlWhere = $this->GetKeyName().' IN ('.$sqlIDs.')';
	return $this->SelectRecords($sqlWhere);
    }
}

abstract class fcTable_keyed extends fcTable_wName_wSource_wRecords {
}

abstract class fcTable_keyed_single extends fcTable_keyed {
    use ftSingleKeyedTable;
    
    public function Insert_andGet(array $arData) {
	$id = $this->Insert($arData);
	if ($id === FALSE) {
	    return NULL;
	} else {
	    return $this->GetRecord_forKey($id);
	}
    }
}

abstract class fcTable_keyed_single_standard extends fcTable_keyed_single {

    // ++ SETUP ++ //

    // CEMENT
    public function GetKeyName() {
	return 'ID';
    }
    
    // -- SETUP -- //
}