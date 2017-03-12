<?php
/*
  PURPOSE: data source and recordset where the data is all stored locally in an array
  RULES:
    * GetAllRecords() copies all data from Table to a new Recordset.
      Fetching only a subset is a thing for the future or for descendants.
  HISTORY:
    2017-01-02 started, to better support the admin UI for Dropins
*/
class fcDataTable_array extends fcDataSource {

    // ++ CEMENT ++ //
    
    protected function SingularName() {
	return 'fcDataRow_array';
    }

    // -- CEMENT -- //
    // ++ NEW ++ //
    
    public function GetAllRecords() {
	$rs = $this->SpawnRecordset();
	$ar = $this->GetAllRows();
	if (is_array($ar)) {
	    $rs->SetAllRows($ar);
	} else {
	    throw new exception('Ferreteria usage error: attempting to GetAllRecords() when there aren\'t any.');
	}
	return $rs;
    }
    
    private $arRows;
    protected function SetRow($id,array $arRow) {
	$this->arRows[$id] = $arRow;
    }
    protected function SetAllRows(array $ar) {
	$this->arRows = $ar;
    }
    protected function GetAllRows() {
	return $this->arRows;
    }
    public function RowCount() {
	return count($this->arRows);
    }
    protected function HasRow($id) {
	return array_key_exists($id,$this->arRows);
    }
}
class fcDataRow_array extends fcSourcedDataRow {
    private $arRows, $nRow;

    // ++ CEMENT ++ //
    
    public function RowCount() {
	return count($this->arRows);
    }
    public function RewindRows() {
	$this->nRow = 0;
	reset($this->arRows);	// point native pointer at first element
	$this->ClearRow();	// clear current-row buffer
    }
    /*----
      HISTORY:
	2017-02-23 Fixed counting logic so this finally renders all rows
    */
    public function NextRow() {
	$nRow = $this->nRow;
	$this->nRow++;
	if ($nRow < $this->RowCount()) {
	    $arElem = each($this->arRows);
	    //this->ClearFields();
	    //$this->SetFieldValues($arElem['value']);
	    $this->SetRow($arElem['value']);
	    return TRUE;
	} else {
	    return FALSE;	// no more row-elements in the array
	}
    }
    
    // -- CEMENT -- //
    // ++ NEW ++ //

    public function SetAllRows(array $ar) {
	$this->arRows = $ar;
	$this->RewindRows();
    }
    // NOTE: Same output as FetchRows_asArray(), but does not alter the current-row pointer
    public function GetAllRows() {
	return $this->arRows;
    }
    
    // -- NEW -- //
}