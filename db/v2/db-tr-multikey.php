<?php
/*
  PURPOSE: tables with multiple keys, and their recordsets
  HISTORY:
    2017-01-06 started
    2017-03-24 some rearrangements to make GetRecord_forKeyString() possible
    2017-04-04 moved multi-key stuff into ftMultiKeyedTable
*/
trait ftMultiKeyedTable {
    use ftKeyedTable;

    // ++ SETUP ++ //

    /*----
      RETURNS: character for separating multiple keys in a single string
      PUBLIC so recordset can access it
    */
    public function GetSeparatorCharacter() {
	return '.';
    }
    /*----
      NEW
      PUBLIC so Records can access
      RETURNS: array of key field names
    */
    abstract public function GetKeyNames();

    // -- SETUP -- //
    // ++ RECORDS ++ //

    /*----
      INPUT: $id = value which uniquely identifies the wanted row (primary keys combined in a separable way)
      ASSUMES: For now --
	* values of keys in multi-key tables do not include $this->GetSeparatorCharacter()
	* in key strings, key values will always be separated by $this->GetSeparatorCharacter()
    */
    public function GetRecord_forKey($id) {
	$arVals = explode($this->GetSeparatorCharacter(),$id);
	$arNames = $this->GetKeyNames();
	$sqlFilt = NULL;
	$db = $this->GetConnection();
	foreach ($arVals as $nVal) {
	    if (!is_null($sqlFilt)) {
		$sqlFilt .= ' AND ';
	    }
	    $sName = array_shift($arNames);
	    $sqlVal = $db->Sanitize_andQuote($nVal);
	    $sqlFilt .= "(`$sName`=$sqlVal)";
	}
	$rc = $this->SelectRecords($sqlFilt);
	if ($rc->RowCount() == 0) {
	    $rc->ClearFields();	// so HasRow() will return FALSE
	} else {
	    $rc->NextRow();	// advance to first (only) row
	}
	return $rc;
    }

    // -- RECORDS -- //

}
/*::::
  ABSTRACT because: n/i = TableName(), SingularName()
*/
abstract class fcTable_keyed_multi extends fcTable_keyed {
    use ftMultiKeyedTable;
}
/*::::
  ABSTRACT because: abstract = GetKeyNames(), FieldNeedsQuote()
*/
abstract class fcRecord_keyed_multi extends fcRecord_keyed {

    protected function GetKeyNames() {
	return $this->GetTableWrapper()->GetKeyNames();
    }
    /*----
      CEMENT
      ASSUMES: all key values are integer
	This is the only method which makes this assumption; fix later if needed.
    */
    public function GetKeyValue() {
	$out = NULL;
	$arKeys = $this->GetKeyNames();
	$chSep = $this->GetTableWrapper()->GetSeparatorCharacter();
	foreach ($arKeys as $sKey) {
	    if (!is_null($out)) {
		$out .= $chSep;
	    }
	    $val = $this->GetFieldValueNz($sKey);
	    if (is_null($val)) {
		return NULL;	// for now, we're assuming both keys are NOT NULL
	    }
	    $out .= $val;
	}
	return $out;
    }
    public function SetKeyValue($id) {
	throw new exception('2017-03-24 Ferreteria code incomplete: implement this when needed.');
	// code in table::GetRecord_forKey() should be helpful
    }
    /*----
      NEW
      RETURNS: self-filter for the named key
    */
    protected function GetKeyFilter($sKey) {
	return $sName.'='.$this->GetKeyValue_cooked($sKey);
    }
    // CEMENT
    protected function GetSelfFilter() {
	$sql = NULL;
	foreach ($this->GetKeyNames() as $sKey) {
	    if (!is_null($sql)) {
		$sql .= ' AND ';
	    }
	    $sql .= '('.$this->GetKeyFilter($sKey).')';
	}
	return $sql;
    }
    /*----
      NEW
      RETURNS: SQL-safe value for the named field
      NOTE: This could be generally useful for other fields, in which case:
	* rename it GetFieldValue_cooked()
	* move it back up into fcDataRow
    */
    protected function GetKeyValue_cooked($sName) {
	if ($this->KeyNeedsQuote()) {
	    $s = $this->GetConnection()->SanitizeString($this->GetFieldValue($sName));
	    return "$s";
	} else {
	    return $this->GetFieldValue($sName);
	}
    }
    /*----
      NEW
      RETURNS: TRUE iff field value should be quoted in order to be SQL-safe, FALSE otherwise
    */
    abstract protected function KeyNeedsQuote($sKey);
}