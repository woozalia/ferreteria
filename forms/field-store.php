<?php
/*
  FILE: field-store.php -- field interpreters for disk storage
  NOTE: This may eventually need to become db-engine-specific. For now, it's tested with MySQL
    but *should* work with anything that takes standard SQL. I'm naming the file and classes "*Storage"
    because the nature of the underlying storage engine shouldn't matter at this level. (It does now,
    to some extent, but later it shouldn't.)
  HISTORY:
    2015-11-23 started
*/

abstract class fcFieldStorage {

    // ++ SETUP ++ //

    public function __construct(fcFormField $oNative) {
	$oForm = $oNative->FormObject();
	$this->FormObject($oForm);
	$this->NativeObject($oNative);
    }
    
    // -- SETUP -- //
    // ++ CONFIG ++ //

    private $oForm;
    protected function FormObject(fcForm $oForm=NULL) {
	if (!is_null($oForm)) {
	    $this->oForm = $oForm;
	}
	return $this->oForm;
    }
    private $oNative;
    public function NativeObject(fcFormField $oNative=NULL) {
	if (!is_null($oNative)) {
	    $this->oNative = $oNative;
	}
	return $this->oNative;
    }

    // -- CONFIG -- //
    // ++ FORMAT CONVERSION ++ //
    
    /*----
      ACTION: Takes data in a format suitable for SQL and sanitizes/quotes it for use in
	actual SQL statements.
    */ /* 2015-11-24 this seems unnecessary
    protected function CookRawSQL($sql) {
	return $this->FormObject()->CookRawValue($sql);
    } */
    /*----
      PURPOSE: convert from received-data format to internal format
      INPUT: as received from database engine
      RETURNS: value in internal format
    */
    protected function toNative($sVal) { return $sVal; }
    /*----
      PURPOSE: convert from internal format to writable-data format (SQL)
      INPUT: internal (native) format
      RETURNS: value in db-writable format, no additional sanitization needed
    */
    protected function fromNative($sVal) {
	return $this->FormObject()->CookRawValue($sVal);
    }
    /*----
      ACTION: converts the given value from storage format to native format and saves it in the native object.
    */
    public function SetValue($vStore) {
	$vNative = $this->toNative($vStore);
	$this->NativeObject()->SetValue($vNative);
    }
    public function GetValue() {
	$vNative = $this->NativeObject()->GetValue();
	$vStore = $this->fromNative($vNative);
	return $vStore;
    }
    /*----
      ACTION: returns and/or sets the SQL representation of the field's value
      USED BY: fcForm_DB::SaveRecord() => fcForm_DB::RecordValues_asSQL_get()
      HISTORY:
	2015-03-30 adapted from Forms v1
	2015-11-23 moved from Field (native) class to DB class and adapted
    */
    /* 2015-11-23 not sure if this is actually needed
    public function Value($sqlVal=NULL) {
	if (!is_null($sqlVal)) {
	    $this->NativeObject()->Value($this->toNative($sqlVal));
	}
	return $this->fromNative($this->NativeObject()->Value());
    } */
    /*----
      PURPOSE: allows explicitly setting NULL values, converting from string-based timestamps
	to internal format, etc.
      NOTE: The input here is not, strictly speaking, SQL; it's just whatever format the database
	returns its data types in. This set of methods should probably be renamed to something
	like SetValueDB.
    */
    /* 2015-11-23 not sure if this is actually needed
    public function SetValue($sqlVal) {
	$this->NativeObject()->SetValue($this->Convert_toNative($sqlVal));
    }*/
    
    // -- FORMAT CONVERSION -- //
    
}
class fcFieldStorage_Text extends fcFieldStorage {

    /*----
      PURPOSE: convert from internal format to writable-data format (SQL)
      INPUT: internal (native) format
      RETURNS: value in db-writable format, no additional sanitization needed
      NOTE: This function quotes the value whether or not it is numeric, because it
	might be a catalog number with leading zeros that would get removed.
    */
    protected function fromNative($sVal) {
	$sqlOut = $this->FormObject()->CookRawValue($sVal);
	// There's probably a better way to do this: ensure that all values get quoted
	if (is_numeric($sqlOut)) {
	    return '"'.$sqlOut.'"';
	} else {
	    return $sqlOut;
	}
    }
}

class fcFieldStorage_Num extends fcFieldStorage {
}

class fcFieldStorage_Time extends fcFieldStorage {
    protected function toNative($sVal) {
	if (is_null($sVal)) {
	    $out = NULL;
	} else {
	    $out = strtotime($sVal);
	}
	return $out;
    }
    /*----
      HISTORY:
	2016-01-31 Dates entered as blank seem to come through as "" rather than NULL,
	  at least some of the time -- so I'm checking for NULL or blank. For now, I'll
	  allow "1969-12-31 19:00:00", which comes through as zero, as a valid date --
	  but might change that later.
    */
    protected function fromNative($sVal) {
	if (is_null($sVal) or ($sVal=='')) {
	    $out = 'NULL';
	} else {
	    $out = '"'.date('Y-m-d H:i:s',$sVal).'"';
	}
	return $out;
    }
}
class fcFieldStorage_Bit extends fcFieldStorage {

    // ++ CEMENTING ++ //

    /*----
      PURPOSE: convert from received-data format to internal format
      INPUT: as received from database engine
      RETURNS: value in internal format
    */
    protected function toNative($sVal) {
	return (ord($sVal) != 0);
    }
    /*----
      PURPOSE: convert from internal format to writable-data format (SQL)
      INPUT: internal (native) format
      RETURNS: value in db-writable format, no additional sanitization needed
    */
    protected function fromNative($bVal) {
	return $bVal?"b'1'":"b'0'";
    }
    
    // -- CEMENTING -- //

}