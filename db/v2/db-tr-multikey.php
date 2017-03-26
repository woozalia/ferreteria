<?php
/*
  PURPOSE: tables with multiple keys, and their recordsets
  HISTORY:
    2017-01-06 started
    2017-03-24 some rearrangements to make GetRecord_forKeyString() possible
*/
/*::::
  ABSTRACT because: n/i = TableName(), SingularName()
*/
abstract class fcTable_keyed_multi extends fcTable_keyed {
    const ksINDEX_SEPARATOR_CHAR = '.';

    // ++ SETUP ++ //
    
    /*----
      NEW
      PUBLIC so Records can access
      RETURNS: array of key field names
    */
    abstract public function GetKeyNames();

    // -- SETUP -- //
    // ++ RECORDS ++ //

    /*----
      INPUT: $id = value which identifies the wanted row (primary keys combined in a separable way)
      ASSUMES: For now --
	* values of keys in multi-key tables do not include self::ksINDEX_SEPARATOR_CHAR
	* in key strings, key values will always be separated by self::ksINDEX_SEPARATOR_CHAR
    */
    public function GetRecord_forKey($id) {
	$arVals = explode(self::ksINDEX_SEPARATOR_CHAR,$id);
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
	return $rc;
    }

    // -- RECORDS -- //
    
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
	foreach ($arKeys as $sKey) {
	    if (!is_null($out)) {
		$out .= fcTable_keyed_multi::ksINDEX_SEPARATOR_CHAR;
	    }
	    $out .= $this->GetFieldValueNz($sKey);
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
	    $s = $this->GetConnection()->Sanitize($this->GetFieldValue($sName));
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