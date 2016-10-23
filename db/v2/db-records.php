<?php

/*%%%%
  PURPOSE: Database Recordset class
    This class can manage multiple records, but only accesses them one at a time.
*/

class fcRecords {

    // ++ SETUP ++ //

    public function __construct(fcDataConn $fo) {
	$this->Connection($fo);
    }

    // -- SETUP -- //
    // ++ OBJECT FAMILY ++ //

    private $foConn;
    protected function Connection(fcDataConn $fo=NULL) {
	if (!is_null($fo)) {
	    $this->foConn = $fo;
	}
	return $this->foConn;
    }

    // -- OBJECT FAMILY -- //
    // ++ VALUE STORAGE ++ //

    private $arBlob;
    public function BlobValue($sKey,$val=NULL) {
	if (!is_null($val)) {
	    $this->arBlob[$sKey] = $val;
	}
	return $this->arBlob[$sKey];
    }
    private $arRow;
    public function FieldValue($sKey,$val=NULL) {
	if (!is_null($val)) {
	    $this->arRow[$sKey] = $val;
	}
	return $this->arRow[$sKey];
    }
    public function FieldValues($arVals=NULL) {
	if (!is_null($arVals)) {
	    $this->arRow = $arVals;
	}
	return $this->arRow;
    }
    protected function FieldsClear() {
	$this->arRow = NULL;
    }

    // -- BLOB STORAGE -- //
    // ++ VALUE ACCESS ++ //

    /*----
      RETURNS: number of rows in the resultset
    */
    public function RowCount() {
	return $this->Connection()->Result_RowCount($this);
    }
    public function HasRows() {
	return ($this->RowCount() != 0);
    }
    /*----
      ACTION: Retrieves the current row data and advances to the next
      RETURNS: row data as an array, or NULL if no more rows
    */
    public function NextRow() {
	$foConn = $this->Connection();
	$arVals = $foConn->Result_NextRow($this);
	if (is_null($arVals)) {
	    $this->FieldsClear();
	    return NULL;
	} else {
	    return $this->FieldValues($arVals);
	}
    }

    // -- DATA ACCESS -- //
}