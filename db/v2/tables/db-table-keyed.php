<?php

abstract class fcTable_keyed extends fcDataTable {
}

abstract class fcTable_keyed_single extends fcTable_keyed {
    // PUBLIC because Recordset wrapper class needs to use it
    abstract public function GetKeyName();
    
    // ++ RECORDS ++ //
    
    public function GetRecord_forKey($id) {
	$sqlFilt = $this->GetKeyName().'='.$this->GetConnection()->Sanitize_andQuote($id);
	$rc = $this->SelectRecords($sqlFilt);
	if ($rc->RowCount() == 0) {
	    $rc->ClearFields();	// so HasRow() will return FALSE
	} else {
	    $rc->NextRow();	// advance to first (only) row
	}
	return $rc;
    }
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

    // ++ CEMENTING ++ //

    public function GetKeyName() {
	return 'ID';
    }
    
    // -- CEMENTING -- //
}