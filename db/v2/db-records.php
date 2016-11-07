<?php

/*%%%%
  PURPOSE: Database Recordset class
    This class can manage multiple records and access them individually in order.
*/

abstract class fcDataRecord {

    // ++ SETUP ++ //

    public function __construct(fcDataSource $t) {
	$this->SetTableWrapper($t);
	$this->InitVars();
    }
    protected function InitVars() {}

    // -- SETUP -- //
    // ++ OBJECT FAMILY ++ //

    protected function GetConnection() {
	return $this->GetTableWrapper()->GetConnection();
    }
    private $tw;
    protected function SetTableWrapper($t) {
	$this->tw = $t;
    }
    protected function GetTableWrapper() {
	return $this->tw;
    }

    // -- OBJECT FAMILY -- //
    // ++ VALUE STORAGE ++ //

    // The "driver blob" is whatever data the db driver needs to put in there. This class won't mess with it.
    private $binDriver;
    public function SetDriverBlob($val) {
	$this->binDriver = $val;
    }
    public function GetDriverBlob() {
	return $this->binDriver;
    }

    private $arRow;
    public function SetFieldValue($sKey,$val) {
	$this->arRow[$sKey] = $val;
    }
    public function GetFieldValue($sKey) {
	if ($this->FieldIsSet($sKey)) {
	    return $this->arRow[$sKey];
	} else {
	    echo 'FIELD VALUES:'.fcArray::Render($this->arRow);
	    $sClass = get_class($this);
	    throw new exception("Field array for class $sClass does not contain requested field '$sKey'.");
	}
    }
    // ACTION: Get a field value but use a default value (instead of throwing an exception) if the field isn't set.
    /* 2016-11-04 So far this hasn't *actually* been needed... yet...
    public function GetFieldValueNz($sKey,$vDefault) {
	if ($this->FieldIsSet($sKey)) {
	    return $this->arRow[$sKey];
	} else {
	    return $vDefault;
	}
    } */
    public function FieldIsSet($sKey) {
	return array_key_exists($sKey,$this->arRow);
    }
    /*----
      ACTION: Sets any field values defined in $arVals; does not clear any existing field values.
	To completely replace field values with $arVals, call ClearFields() first.
    */
    public function SetFieldValues(array $arVals) {
	if (is_array($this->arRow)) {
	    $this->arRow = array_merge($this->arRow,$arVals);
	} else {
	    $this->arRow = $arVals;
	}
    }
    public function GetFieldValues() {
	return $this->arRow;
    }
    protected function ClearFields() {
	$this->arRow = NULL;
    }

    // -- BLOB STORAGE -- //
    // ++ DATA ACCESS ++ //

    /*----
      RETURNS: number of rows in the resultset
    */
    public function RowCount() {
	return $this->GetConnection()->Result_RowCount($this);
    }
    public function HasRows() {
	return ($this->RowCount() != 0);
    }
    public function RewindRows() {
	$this->GetConnection()->Result_Rewind($this);
    }
    /*----
      ACTION: Retrieves the current row data and advances to the next
      RETURNS: row data as an array, or NULL if no more rows
    */
    public function NextRow() {
	$foConn = $this->GetConnection();
	$arVals = $foConn->Result_NextRow($this);
	$this->ClearFields();
	if (!is_null($arVals)) {
	    if (is_array($arVals)) {
		$this->SetFieldValues($arVals);
	    } else {
		throw new exception('Anomalous result from reading a row: '.print_r($arVals,TRUE));
	    }
	}
	return $arVals;
    }

    // -- DATA ACCESS -- //
    // ++ DATA CALCULATIONS ++ //
    
    /*----
      ACTION: For each record in the current recordset, the field values
	are added to an array which is keyed by the given field.
      INPUT: $sField = name of field to use as the array key
      RETURNS: keyed array
    */
    public function FetchRows_asArray($sField) {
	if ($this->HasRows()) {
	    while ($this->NextRow()) {
		$s = $this->GetFieldValue($sField);
		$ar[$s] = $this->GetFieldValues();
	    }
	    return $ar;
	} else {
	    return NULL;
	}
    }
    /*----
      ACTION: For each record in the set, the value of the given field is
	appended to the output string. The values are sanitized, quoted if necessary,
	and separated by commas.
      RETURNS: a string is suitable for use as the target of an "IN" clause
    */
    public function FetchColumnValues_asSQL($sField) {
	$out = NULL;
	$db = $this->GetConnection();
	if ($this->HasRows()) {
	    while ($this->NextRow()) {
		$s = $this->GetFieldValue($sField);
		$out .= $db->Sanitize_andQuote($s).',';
	    }
	    $out = trim($out,','); // remove trailing comma
	}
	return $out;
    }

    // -- DATA CALCULATIONS -- //
    
}