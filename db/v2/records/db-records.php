<?php
/*
  TERMINOLOGY:
    "rows" are sets of field data stored in a database
    "fields" are in-memory storage of an individual row.
  HISTORY:
    2017-10-13 added ftTabledDataRow
*/

interface fiInsertableTable {
    function Insert(array $arData);
}

/*::::
  PURPOSE: base class for recordset-like objects
    Does not require a source for the data.
*/
class fcDataRow {
    private $arRow;

    // ++ ENTIRE ROW ++ //

    // RETURNS: TRUE iff a row of data has been successfully loaded (via any means) into the field array
    public function HasRow() {
	return !is_null($this->GetFieldValues());
    }
    /*----
      NOTE: 2017-02-26 The potential issue with using this is that I'm not sure if $arVals is treated as a reference
	or a value. If it's a reference, then passing an array variable which is later altered could result in
	altering the row values as well. I have a recollection that this is why I used array_merge() in SetFieldValues().
    */
    public function SetRow(array $arVals) {
	$this->arRow = $arVals;
    }
    // NEEDED for array-data descendant class
    protected function ClearRow() {
	$this->arRow = NULL;
    }
    /*----
      ACTION: Sets any field values defined in $arVals; does not clear any existing field values.
	To completely replace field values with $arVals, call ClearFields() first or call SetRow().
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
    /*----
      PUBLIC for complicated reasons:
	Basically, the keyed table GetRecord_forKey() function needs to be able to load just one record,
	or clearly indicate that there are no records.
	
	If that were the whole story, we could just let HasRows() tell whether there were any rows --
	but sometimes (e.g. when caching records) we need to be able to stuff data into the recordset's
	local storage from an outside source -- in which case HasRows() is irrelevant, because we didn't
	get the data from the database.
	
	So now (2016-12-03) we have HasRow(), which tells us whether the recordset's local storage has data,
	and ClearFields(), which clearly sets conditions so that HasRow() returns FALSE.

      NOTE the split in terminology: "rows" are what's in the db, and "fields" are local storage of a row.
    */
    public function ClearFields() {
	$this->arRow = NULL;
    }
    // ACTION: returns a copy that just has the current row loaded
    public function GetDataClone() {
	$sClass = get_class($this);
	$rc = new $sClass($this->GetTableWrapper());	// not sure if this will work...
	$rc->SetFieldValues($this->GetFieldValues());
	return $rc;
    }
    
    // -- ENTIRE ROW -- //
    // ++ FIELDS ++ //
    
    public function SetFieldValue($sKey,$val) {
	$this->arRow[$sKey] = $val;
    }
    public function DeleteField($sKey) {
	unset($this->arRow[$sKey]);
    }
    public function FieldIsSet($sKey) {
	if ($this->HasRow()) {
	    return array_key_exists($sKey,$this->arRow);
	} else {
	    throw new exception('Ferreteria usage error: FieldIsSet() called on empty row. Call HasRow() to check first.');
	}
    }
    public function FieldIsNonBlank($sKey) {
	if ($this->FieldIsSet($sKey)) {
	    $v = $this->GetFieldValue($sKey);
	    return (!is_null($v) && ($v != ''));
	} else {
	    return FALSE;
	}
    }
    // TODO: 2017-02-09 Has not actually been tested with zero, NULL, or non-numeric strings.
    public function FieldIsNonZeroInt($sKey) {
	$n = (int)$this->GetFieldValue($sKey);
	return ($n != 0) && is_integer($n);
    }
    public function GetFieldValue($sKey) {
	if ($this->FieldIsSet($sKey)) {
	    return $this->arRow[$sKey];
	} else {
	    if (!isset($this->sql)) {
		throw new exception('Ferretria internal error: $sql member not set by query');
	    }
	    echo '<b>SQL</b>: '.$this->sql.'<br>';
	    echo '<b>FIELD VALUES</b>:'.fcArray::Render($this->arRow);
	    $sClass = get_class($this);
	    throw new exception("Ferreteria usage error: Field array for class $sClass does not contain requested field '$sKey'.");
	}
    }
    /*----
      NEW
      USED BY: descendant key-filtering methods
      NOTE: I *almost* put this in, uh, somewhere else
	(I almost finished typing that sentence, and now I can't remember where 'somewhere else' was.)
    */
    protected function GetFieldValueNz($sKey,$vDef=NULL) {
	if ($this->HasRow()) {
	    if ($this->FieldIsSet($sKey)) {
		return $this->arRow[$sKey];
	    }
	}
	return $vDef;
    }
    
    // -- FIELDS -- //

}
abstract class fcSourcedDataRow extends fcDataRow {

    // ++ STATUS ++ //

    public function HasRows() {
	return ($this->RowCount() != 0);
    }
    abstract public function RowCount();

    // -- STATUS -- //
    // ++ CONTROL ++ //
    
    abstract public function RewindRows();
    abstract public function NextRow();

    // -- CONTROL -- //
    // ++ DATA READ ++ //

    /*----
      ACTION: For each record in the current record source, the field values
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
	    return array();
	}
    }
    /*----
      RETURNS: Array consisting of just the values of the given field as found within the current recordset
      HISTORY:
	2017-05-27 written for title-topic display in VbzCart, because I couldn't find a replacement for ColumnValues_array().
	  This should be equivalent to what ColumnValues_array() used to do.
    */
    public function FetchColumnValues_asArray($sField) {
	$ar = array();
	if ($this->HasRows()) {
	    while ($this->NextRow()) {
		$ar[] = $this->GetFieldValue($sField);
	    }
	}
	return $ar;
    }
    
    // -- DATA READ -- //
}
trait ftTabledDataRow {

    // ++ SETUP ++ //

    public function __construct(fcTable_wRecords $t) {
	$this->SetTableWrapper($t);
	$this->InitVars();
    }
    protected function InitVars() {}

    // -- SETUP -- //
    // ++ OBJECT FAMILY ++ //
    
    private $tw;
    protected function SetTableWrapper($t) {
	$this->tw = $t;
    }
    /*----
      NOTE: 2018-02-12 there was a note here saying "PUBLIC so Form classes can access it"
	but it was actually marked PROTECTED.
	I'm temporarily making it PUBLIC for debugging purposes only, but maybe it does need to be public.
    */
    public function GetTableWrapper() {
	return $this->tw;
    }
    protected function GetDatabase() {
	return $this->GetTableWrapper()->GetConnection();	// 2018-04-02 to be changed back to GetDatabase() also.
    }

    // -- OBJECT FAMILY -- //
}
/*::::
  PURPOSE: Database Recordset class
    This class can manage multiple records and access them individually in order.
*/
abstract class fcDataRecord extends fcSourcedDataRow {
    use ftTabledDataRow;

    // ++ OBJECT FAMILY ++ //

    // PUBLIC because other objects that work with recordsets might need to interact with the same Connection object
    public function GetConnection() {
	$t = $this->GetTableWrapper();
	return $t->GetConnection();
    }

    // -- OBJECT FAMILY -- //
    // ++ BLOB STORAGE ++ //

    // The "driver blob" is whatever data the db driver needs to put in there. This class won't mess with it.
    private $binDriver;
    public function SetDriverBlob($val) {
	$this->binDriver = $val;
    }
    public function GetDriverBlob() {
	return $this->binDriver;
    }

    // -- BLOB STORAGE -- //
    // ++ STORED DATA ++ //

    /*----
      RETURNS: number of rows in the resultset
      CEMENT
    */
    public function RowCount() {
	return $this->GetConnection()->Result_RowCount($this);
    }
    public function RewindRows() {
	$this->GetConnection()->Result_Rewind($this);
    }
    /*----
      ACTION: Retrieves the current row data and advances to the next
      RETURNS: row data as an array, or NULL if no more rows
      CEMENT
    */
    public function NextRow() {
	$foConn = $this->GetConnection();
	$arVals = $foConn->Result_NextRow($this);
	$this->ClearFields();
	if (!is_null($arVals)) {
	    if (is_array($arVals)) {
		$this->SetFieldValues($arVals);
	    } else {
		throw new exception(
		  'Ferreteria error: Anomalous result from reading a row. Row data: [BEGIN] '
		  .print_r($arVals,TRUE)
		  .'[END] SQL: '
		  .$this->sql
		);
	    }
	}
	return $arVals;
    }

      //++array calculations++//
    
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
	    $this->RewindRows();
	    while ($this->NextRow()) {
		$s = $this->GetFieldValue($sField);
		$out .= $db->SanitizeValue($s).',';
	    }
	    $out = trim($out,','); // remove trailing comma
	}
	return $out;
    }

      //--array calculations--//
    // -- STORED DATA --- //
    
}
class fcUsableRecordset extends fcDataRecord {
}