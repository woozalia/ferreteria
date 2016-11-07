<?php

abstract class fcTable_keyed extends fcDataTable {
}

abstract class fcTable_keyed_single extends fcTable_keyed {
    // PUBLIC because Recordset wrapper class needs to use it
    abstract public function KeyName();
    
    // ++ RECORDS ++ //
    
    public function GetRecord_forKey($id) {
	$sqlFilt = $this->KeyName().'='.$this->GetConnection()->Sanitize_andQuote($id);
	$rc = $this->SelectRecords($sqlFilt);
	$rc->NextRow();	// advance to first (only) row
	return $rc;
    }
}

abstract class fcTable_keyed_single_standard extends fcTable_keyed_single {

    // ++ CEMENTING ++ //

    public function KeyName() {
	return 'ID';
    }
    
    // -- CEMENTING -- //
}