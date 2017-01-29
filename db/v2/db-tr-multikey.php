<?php
/*
  PURPOSE: tables with multiple keys, and their recordsets
  HISTORY:
    2017-01-06 started
*/
/*::::
  ABSTRACT because: n/i = TableName(), SingularName()
*/
abstract class fcTable_keyed_multi extends fcTable_keyed {
}
/*::::
  ABSTRACT because: abstract = GetKeyNames(), FieldNeedsQuote()
*/
abstract class fcRecord_keyed_multi extends fcRecord_keyed {

    /*----
      CEMENT
      ASSUMES: all key values are integer
	This is the only method which makes this assumption; fix later if needed.
    */
    public function GetKeyString() {
	$out = NULL;
	foreach ($this->GetKeyNames() as $sKey) {
	    if (!is_null($out)) {
		$out .= '.';
	    }
	    $out .= $this->GetFieldValueNz($sKey);
	}
	return $out;
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
      RETURNS: array of key field names
    */
    abstract protected function GetKeyNames();
    /*----
      NEW
      RETURNS: self-filter for the named key
    */
    protected function GetKeyFilter($sKey) {
	return $sName.'='.$this->GetKeyValue_cooked($sKey);
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