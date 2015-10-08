<?php
/*
  FILE: field.php - single field classes
  LIBRARY: ferreteria: forms
  PURPOSE: Understands how to convert between stored format and internal representation.
  DEPENDS: none
  HISTORY:
    2015-03-29 starting from scratch
    2015-05-02 changed format conversion functions from static to dynamic.
      Although they don't need to access any object-local data, they may need to be
        affected by object-local options.
    2015-06-13 values are now stored locally (vValue) rather than in Form object.
*/

class fcFormField {
    private $sName;
    private $vValue;
    private $vDefault;
    private $sqlForBlank;

    // ++ SETUP ++ //

    public function __construct(fcForm $oForm, $sName) {
	$this->NameString($sName);	// set name before connecting to form
	$this->FormObject($oForm);	// connect to form
	$this->sqlForBlank = 'NULL';	// default SQL value
	$this->vDefault = NULL;		// default native value
    }

    // -- SETUP -- //
    // ++ CONFIGURATION ++ //

    protected function FormObject(fcForm $oForm=NULL) {
	if (!is_null($oForm)) {
	    $this->oForm = $oForm;
	    $oForm->FieldObject($this->NameString(),$this);
	}
	return $this->oForm;
    }
    /*----
      PUBLIC because each Control (at least) needs to know what its Field is named.
    */
    public function NameString($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sName = $sName;
	}
	return $this->sName;
    }

    // -- CONFIGURATION -- //
    // ++ OPTIONS ++ //

    public function SQL_forBlank($sql=NULL) {
        if (!is_null($sql)) {
            $this->sqlForBlank = $sql;
        }
        return $this->sqlForBlank;
    }

    // -- OPTIONS -- //
    // ++ DATA ACCESS ++ //

    /*----
      RETURNS: TRUE iff the value has been explicitly set
      NOTE: We may eventually need to have a flag for this; for now, we just look
	at whether the value is NULL or not.
      USAGE: for when code needs to explicitly set certain values to be saved. The Form object
	will allow explicitly-set values to override $_POSTed values.
    */
    public function IsChanged() {
	return !is_null($this->vValue);
    }
    /*----
      ACTION: set and/or return the value of the field.
      VERSION: generic - stores/returns value directly
      HISTORY:
	2011-03-29 renamed from Value() to ValStore()
	2015-03-30 adapted from Forms v1
	2015-06-14 changed the way Form values are stored
    */
    public function ValueNative($val=NULL) {
//	return $this->FormObject()->RecordValue($this->NameString(),$val);
	if (!is_null($val)) {
	    $this->vValue = $val;
	}
	return $this->vValue;
    }
    /*----
      PURPOSE: allows explicitly setting NULL values.
    */
    public function SetValueNative($val) {
	$this->vValue = $val;
    }
    /*----
      PURPOSE: set the default value (value to use for new records)
    */
    public function SetDefaultNative($val) {
        $this->vDefault = $val;
    }
    /*----
      PURPOSE: set the current value to the default value
      USAGE: when initializing a field for editing a new record
    */
    public function UseDefault() {
        $this->SetValueNative($this->vDefault);
    }
    /*----
      ACTION: returns and/or sets the displayable representation of the field's value
      HISTORY:
	2011-03-29 created - we're distinguishing between "stored" and "displayed" values now
	2015-03-30 adapted from Forms v1
    */
    public function ValueDisplay($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->ValueNative($this->Convert_DisplayToNative($sVal));
	}
	return $this->Convert_NativeToDisplay($this->ValueNative());
    }
    /*----
    ACTION: returns and/or sets the SQL representation of the field's value
      HISTORY:
	2015-03-30 adapted from Forms v1
    */
    public function ValueSQL($sqlVal=NULL) {
	if (!is_null($sqlVal)) {
	    $this->ValueNative($this->Convert_SQLToNative($sqlVal));
	}
	return $this->Convert_NativeToSQL($this->ValueNative());
    }
    /*----
      PURPOSE: allows explicitly setting NULL values.
    */
    public function SetValueSQL($sqlVal) {
	$this->SetValueNative($this->Convert_SQLToNative($sqlVal));
    }

    // -- DATA ACCESS -- //
    // ++ FORMAT CONVERSION ++ //

    // These have to be PUBLIC so FORM can convert arrays without changing values.

    public function Convert_DisplayToNative($sVal) { return $sVal; }
    public function Convert_NativeToDisplay($sVal) { return htmlspecialchars($sVal); }
    public function Convert_SQLToNative($sqlVal) { return $sqlVal; }
    //protected function Convert_NativeToSQL($sVal) { return is_null($sVal)?'NULL':$sVal; }

    // -- FORMAT CONVERSION -- //
}
class fcFormField_Text extends fcFormField {

    // ++ FORMAT CONVERSION ++ //

    protected function Convert_NativeToSQL($sVal) {
	if (is_null($sVal) || ($sVal == '')) {
	    return $this->SQL_forBlank();
	} else {
	    //return SQLValue($sVal);
	    $app = clsApp::Me();
	    $db = $app->Data();
	    return $db->SanitizeAndQuote($sVal);
	}
    }

    // -- FORMAT CONVERSION -- //
}

class fcFormField_Num extends fcFormField {
    protected function Convert_ShowToNative($sVal) {
	//if (($sVal == '') || is_null($sVal)) {
	if (is_numeric($sVal)) {
	    return (float)$sVal;
	} else {
	    return NULL;
	}
    }
    protected function Convert_NativeToSQL($nVal) {
	if (is_numeric($nVal)) {
	    return $nVal;
	} else {
	    return $this->SQL_forBlank();
	}
    }
}
/*%%%%
  IMPLEMENTATION: SQL seems to use a displayed date format
    I'm assuming that 'Y-m-d H:i:s' will work for all SQL engines.
  LATER:
    * Could add user-selected timestamp format for display.
*/
class fcFormField_Time extends fcFormField {
    private $sFmt;  // display format

    // ++ SETUP ++ //

    public function __construct(fcForm $oForm, $sName) {
        parent::__construct($oForm, $sName);
        $this->sFmt = 'Y/m/d H:i:s';  // default display format
    }

    // -- SETUP -- //
    // ++ OPTIONS ++ //

    public function Format($sFormat=NULL) {
        if (!is_null($sFormat)) {
            $this->sFmt = $sFormat;
        }
        return $this->sFmt;
    }

    // -- OPTIONS -- //
    // ++ FORMAT CONVERSION ++ //

    public function Convert_DisplayToNative($sVal) {
	return strtotime($sVal);
    }
    public function Convert_NativeToDisplay($dtVal) {
        if (empty($dtVal)) {
            return '';
	} elseif (is_numeric($dtVal)) {
	    return date($this->Format(),$dtVal);
	} else {
	    return '??"'.$dtVal.'"';
	}
    }
    public function Convert_SQLToNative($sqlVal) {
	$dt = strtotime($sqlVal);
	if ($dt === FALSE) {
	    // strtotime() returns FALSE if it can't parse the string
	    // This includes blank/NULL.
	    $dt = NULL;
	}
	return $dt;
    }
    public function Convert_NativeToSQL($dtVal) {
	if (is_numeric($dtVal)) {
	    return '"'.date('Y-m-d H:i:s',$dtVal).'"';
	} elseif (empty($dtVal)) {
	    return 'NULL';
	} else {
	    $sMsg = 'Trying to use "'
	      .$dtVal
	      .'" as a UNIX (integer) timestamp in field "'
	      .$this->NameString()
	      .'".';
	    throw new exception($sMsg);
	}
    }

    // -- FORMAT CONVERSION -- //
}

class fcFormField_Bit extends fcFormField {
    public function Convert_DisplayToNative($sVal) {
	// TODO: not sure how this shows up...
	return $sVal;
    }
    public function Convert_NativeToDisplay($bVal) {
	return $bVal?'YES':'no';
    }
    public function Convert_SQLToNative($sqlVal) {
	return (ord($sqlVal) != 0);
    }
    public function Convert_NativeToSQL($bVal) {
	 return $bVal?chr(1):chr(0);
    }
}