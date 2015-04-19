<?php
/*
  FILE: field.php - single field classes
  LIBRARY: ferreteria: forms
  PURPOSE: Understands how to convert between stored format and internal representation.
  DEPENDS: none
  HISTORY:
    2015-03-29 starting from scratch
*/

class fcFormField {
    private $sName;
    private $vValue;

    // ++ SETUP ++ //

    public function __construct(fcForm $oForm, $sName) {
	$this->FormObject($oForm);
	$this->NameString($sName);
    }

    // -- SETUP -- //
    // ++ CONFIGURATION ++ //

    protected function FormObject(fcForm $oForm=NULL) {
	if (!is_null($oForm)) {
	    $this->oForm = $oForm;
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
    // ++ DATA ACCESS ++ //

    /*----
      ACTION: set and/or return the value of the field.
      VERSION: generic - stores/returns value directly
      HISTORY:
	2011-03-29 renamed from Value() to ValStore()
	2015-03-30 adapted from Forms v1
    */
    public function ValueNative($val=NULL) {
    /*
	if (!is_null($val)) {
	    $this->vValue = $val;
	}
	return $this->vValue; */
	return $this->FormObject()->RecordValue($this->NameString(),$val);
    }
    /*----
      ACTION: returns and/or sets the displayable representation of the field's value
      HISTORY:
	2011-03-29 created - we're distinguishing between "stored" and "displayed" values now
	2015-03-30 adapted from Forms v1
    */
    public function ValueDisplay($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->ValueNative(static::Convert_DisplayToNative($sVal));
	}
	return static::Convert_NativeToDisplay($this->ValueNative());
    }
    /*----
    ACTION: returns and/or sets the SQL representation of the field's value
      HISTORY:
	2015-03-30 adapted from Forms v1
    */
    public function ValueSQL($sqlVal=NULL) {
	if (!is_null($sqlVal)) {
	    $this->ValueNative(static::Convert_SQLToNative($sqlVal));
	}
	return static::Convert_NativeToSQL($this->ValueNative());
    }

    // -- DATA ACCESS -- //
    // ++ FORMAT CONVERSION ++ //

    static protected function Convert_DisplayToNative($sVal) { return $sVal; }
    static protected function Convert_NativeToDisplay($sVal) { return htmlspecialchars($sVal); }
    static protected function Convert_SQLToNative($sqlVal) { return $sqlVal; }
    static protected function Convert_NativeToSQL($sVal) { return is_null($sVal)?$sVal:'NULL'; }

    // -- FORMAT CONVERSION -- //
}

class fcFormField_Num extends fcFormField {
    static protected function Convert_ShowToNative($sVal) {
	if ($sVal == '') {
	    return NULL;
	} else {
	    return (float)$sVal;
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

    // ++ FORMAT CONVERSION ++ //

    static protected function Convert_DisplayToNative($sVal) {
	return strtotime($sVal);
    }
    static protected function Convert_NativeToDisplay($dtVal) {
	if (is_numeric($dtVal)) {
	    return date('Y/m/d H:i:s',$dtVal);
	} else {
	    return '??"'.$dtVal.'"';
	}
    }
    static protected function Convert_SQLToNative($sqlVal) {
	return strtotime($sVal);
    }
    static protected function Convert_NativeToSQL($dtVal) {
	return '"'.date('Y-m-d H:i:s',$dtVal).'"';
    }

    // -- FORMAT CONVERSION -- //
}