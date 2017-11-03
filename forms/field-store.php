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
	$this->Writable(TRUE);	// default
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
    /*----
      PURPOSE: Form object should check this before saving data to disk.
	Only save if TRUE.
    */
    private $bWritable;
    public function Writable($b=NULL) {
	if (!is_null($b)) {
	    $this->bWritable = $b;
	}
	return $this->bWritable;
    }

    // -- CONFIG -- //
    // ++ FORMAT CONVERSION ++ //

    /*----
      PURPOSE: convert from received-data format to internal format
      INPUT: as received from database engine (raw)
      RETURNS: value in internal format
    */
    protected function toNative($sVal) { return $sVal; }
    protected function fromNativeRaw($sVal) {
	return $sVal;	// default
    }
    /*----
      PURPOSE: convert from internal format to writable-data format (SQL)
      INPUT: internal (native) format
      RETURNS: raw value in db-writable format -- but may still need sanitization
      METHOD: DB-sanitizes the raw converted value
      PUBLIC so Form object can use it to convert arrays of native values before saving
      HISTORY:
	2017-05-25 made publib
    */
    public function fromNativeSane($sVal) {
	return $this->FormObject()->CookRawValue($this->fromNativeRaw($sVal));
    }
    /*----
      ACTION: converts the given value from storage format to native format and saves it in the native object.
    */
    public function SetValue($vStore) {
	$vNative = $this->toNative($vStore);
	$this->NativeObject()->SetValue($vNative,TRUE);
    }
    /*----
      NOTE: This method may only need to exist just to keep it *mentally* clear
	that GetValueRaw()'s output should not be sanitized; it just needs to be
	in the right format (e.g. for dates, a date string rather than an integer).
    */
    public function GetValueSane() {
	$vNative = $this->NativeObject()->GetValue();
	$vStore = $this->fromNativeSane($vNative);
	return $vStore;
    }
    public function GetValueRaw() {
	$vNative = $this->NativeObject()->GetValue();
	$vStore = $this->fromNativeRaw($vNative);
	return $vStore;
    }

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

    /* 2016-10-17 This *should* be unnecessary now - default is to cook the raw converted value
    protected function fromNativeSane($sVal) {
	$sqlOut = $this->FormObject()->CookRawValue($sVal);
	// There's probably a better way to do this: ensure that all values get quoted
	if (is_numeric($sqlOut)) {
	    return '"'.$sqlOut.'"';
	} else {
	    return $sqlOut;
	}
    }
    */
}

class fcFieldStorage_Num extends fcFieldStorage {
}

// FORMAT: "native" means integer UNIX time
class fcFieldStorage_Time extends fcFieldStorage {
    protected function toNative($sVal) {
	if (is_null($sVal)) {
	    $out = NULL;
	} else {
	    $out = strtotime($sVal);
	}
	return $out;
    }
    protected function fromNativeRaw($nVal) {
	if (empty($nVal)) {
	    $out = NULL;	// '' or 0 -> NULL
	} else {
	    if (is_integer($nVal)) {
		$out = date('Y-m-d H:i:s',$nVal);
	    } else {
		echo "Received non-integer as native date value: [$nVal]";
		throw new exception('Where is this problem coming from?');
	    }
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
    /* 2016-10-17 This *should* be unnecessary now - default is to cook the raw converted value
    protected function fromNativeSane($sVal) {
	$sSane = $this->fromNativeRaw();
	if (is_null($sVal) or ($sVal=='')) {
	    $out = 'NULL';
	} else {
	    $out = '"'.date('Y-m-d H:i:s',$sVal).'"';
	}
	return $out;
    } */
}
class fcFieldStorage_Bit extends fcFieldStorage {

    // ++ UTILITY ++ //
    
    // ACTION: convert from storage format to native
    // TODO: Who uses this externally? Why?
    static public function FromStorage($ch) {
	//return (ord($ch) != 0);
	return $ch != 0;	// OFF value seems to be stored as '0' (2017-04-14)
    }
    // ACTION: convert from native to storage format
    // TODO: Who uses this externally? Why?
    static public function ToStorage($b) {
	return $b?"b'1'":"b'0'";
    }

    // ++ CEMENTING ++ //

    /*----
      PURPOSE: convert from received-data format to internal format
      INPUT: as received from database engine
      RETURNS: value in internal format
    */
    protected function toNative($val) {
	return self::FromStorage($val);
    }
    /*----
      PURPOSE: convert from internal format to sanitized (cooked) data format
      INPUT: internal (native) format
      RETURNS: value in db-writable format, no additional sanitization needed
      NOTE: chr($bVal) should also work, and would produce a string that is
	the same as what is received when reading from the db (i.e. the input to
	toNative()), but isn't human-readable (for debugging).
    */
    public function fromNativeSane($bVal) {
	return self::ToStorage($bVal);
    }

    // -- CEMENTING -- //

}