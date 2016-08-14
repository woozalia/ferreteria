<?php
/*
  FILE: field.php - single field classes
  LIBRARY: ferreteria: forms
  PURPOSE: Basic internal representation for various types of data
    Knows default Control and Storage types to use, but these can be overridden.
  HISTORY:
    2015-03-29 starting from scratch
    2015-05-02 changed format conversion functions from static to dynamic.
      Although they don't need to access any object-local data, they may need to be
        affected by object-local options.
    2015-06-13 values are now stored locally (vValue) rather than in Form object.
    2015-11-23 Renamed from field.php -> field-native.php
      Field objects now handle only internal (native) values, not display (field-display) or SQL (field-sql) formats.
*/

abstract class fcFormField {

    // ++ SETUP ++ //

    public function __construct(fcForm $oForm, $sName) {
	$this->NameString($sName);	// set name before connecting to form
	$this->FormObject($oForm);	// connect to form
	//$this->SQL_forBlank('NULL');	// default SQL value
	$this->vDefault = NULL;		// default native value
	//$this->OkToWrite(TRUE);		// default: writeable field
    }

    // -- SETUP -- //
    // ++ RELATED OBJECTS ++ //
    
    // PUBLIC so Control objects can access it at setup time
    private $oForm;
    public function FormObject(fcForm $oForm=NULL) {
	if (!is_null($oForm)) {
	    $this->oForm = $oForm;
	    $oForm->FieldObject($this->NameString(),$this);
	}
	return $this->oForm;
    }
    abstract protected function ControlClass();
    private $oControl;
    public function ControlObject(fcFormControl $obj=NULL) {
	if (!is_null($obj)) {
	    $this->oControl = $obj;
	}
	if (empty($this->oControl)) {
	    $sClass = $this->ControlClass();
	    $obj = new $sClass($this);
	    $this->oControl = $obj;
	}
	return $this->oControl;
    }
    abstract protected function StorageClass();
    private $oStorage;
    public function StorageObject(fcFieldStorage $obj=NULL) {
	if (!is_null($obj)) {
	    $this->oStorage = $obj;
	}
	if (empty($this->oStorage)) {
	    $sClass = $this->StorageClass();
	    $obj = new $sClass($this);
	    $this->oStorage = $obj;
	}
	return $this->oStorage;
    }
    
    // -- RELATED OBJECTS -- //
    // ++ CONFIGURATION ++ //
    
    /*----
      PUBLIC because each Control (at least) needs to know what its Field is named.
    */
    private $sName;
    public function NameString($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sName = $sName;
	}
	return $this->sName;
    }

    // -- CONFIGURATION -- //
    // ++ OPTIONS ++ //
    
    //private $okToWrite;	// enable modifying stored field
    public function OkToWrite($bOk=NULL) {
	throw new exception('Call StorageObject->Writable() or ControlObject->Editable() instead.');
	if (!is_null($bOk)) {
	    $this->okToWrite = $bOk;
	}
	return $this->okToWrite;
    }

    // -- OPTIONS -- //
    // ++ MEMBER CALCULATIONS ++ //
    
    /*----
      RETURNS: TRUE iff the value has been explicitly set
      NOTE: We may eventually need to have a flag for this; for now, we just look
	at whether the value is NULL or not.
      USAGE: for when code needs to explicitly set certain values to be saved. The Form object
	will allow explicitly-set values to override $_POSTed values.
	Form->ClearValues() also only clears fields that haven't been Changed.
      HISTORY
	2015-11-24 now uses "isSet" flag instead of assuming that NULL means no change
    */
    private $isSet;
    public function IsChanged() {
	$is = !empty($this->isSet);
	return $is;
    }
    protected function MarkChanged() {
	$this->isSet = TRUE;
    }
    /*----
      RETURNS: TRUE iff the value should be written to the database
      HISTORY:
	2016-02-04 This was originally set to return "$this->IsChanged() && $this->OkToWrite()",
	  but that prevented me from code-setting read-only values which nonetheless needed to
	  be set for new records. (Setting Default does not work. Making the field OkToWrite()
	  gives an error when the control doesn't see the value in the incoming form data.)
	  
	  We'll just have to see if returning only IsChanged() causes problems.
    */
    public function ShouldWrite() {
	return $this->IsChanged() && $this->StorageObject()->Writable();
    }
    
    // -- MEMBER CALCULATIONS -- //
    // ++ VALUE ACCESS ++ //

    /*----
      PURPOSE: ensure that the value is sane for the current class
      INPUT: internal (native) format
      RETURNS: TRUE if it is, FALSE otherwise
    */
//    abstract protected function IsInRange($sVal);	// NOTE: not sure if input arg is actually needed here

    // ++ ++ CURRENT ++ ++ //

    private $vValue;
    /*----
      ACTION: set and/or return the value of the field.
      VERSION: generic - stores/returns value directly
      HISTORY:
	2011-03-29 renamed from Value() to ValStore()
	2015-03-30 adapted from Forms v1
	2015-06-14 changed the way Form values are stored
	2015-11-23 renamed from ValueNative() back to Value()
    */
    public function GetValue() {
	return $this->vValue;
    }
    /*----
      PURPOSE: allows explicitly setting NULL values.
      HISTORY:
	2015-11-23 renamed from SetValueNative() to SetValue()
	2015-11-24 now sets "isSet" flag
	2016-04-17 When $val is blank, does not override default.
	  If this behavior is ever undesirable, we'll need an option flag...
    */
    public function SetValue($val) {
	if (!fcString::IsBlank($val)) {
	    $this->vValue = $val;
	    $this->MarkChanged();
	}
    }
    // USAGE: Internal only. Caller is responsible for calling MarkChanged();
    protected function ForceValue($val) {
	$this->vValue = $val;
    }

    // -- -- CURRENT -- -- //
    // ++ ++ DEFAULT ++ ++ //
    /*++++
      PURPOSE: get/set/use default value (value to use for new records)
      HISTORY:
	2015-11-23 renamed from *DefaultNative() to *Default()
	2016-04-14 Now also sets value if not already set.
    */

    private $vDefault;
    public function SetDefault($val) {
        $this->vDefault = $val;
        if (!$this->IsChanged()) {
	    $this->SetValue($val);
	}
    }
    protected function GetDefault() {
	return $this->vDefault;
    }
    /*----
      PURPOSE: set the current value to the default value
      USAGE: when initializing a field for editing a new record
    */
    public function UseDefault() {
        $this->SetValue($this->GetDefault());
    }

    // -- -- DEFAULT -- -- //
    // -- VALUE ACCESS -- //

}
class fcFormField_Text extends fcFormField {

    // ++ CEMENTING ++ //

    protected function ControlClass() {
	return 'fcFormControl_HTML';
    }
    protected function StorageClass() {
	return 'fcFieldStorage_Text';
    }
    
    // -- CEMENTING -- //

}

class fcFormField_Num extends fcFormField_Text {

    // ++ CEMENTING ++ //
/*
    protected function IsInRange($sVal) {
	return is_numeric($sVal);
    } */
    protected function StorageClass() {
	return 'fcFieldStorage_Num';
    }

    // -- CEMENTING -- //

}
/*%%%%
  IMPLEMENTATION: SQL seems to use a displayed date format
    I'm assuming that 'Y-m-d H:i:s' will work for all SQL engines.
  LATER:
    * Could add user-selected timestamp format for display.
*/
class fcFormField_Time extends fcFormField_Text {
/*
// debugging (temporary):
    public function GetValue() {
	$v = parent::GetValue();
	//echo "GETTING VALUE ($v)<br>";
	return $v;
    }
    public function SetValue($val) {
	//echo "SETTING VALUE ($val)<br>";
	parent::SetValue($val);
    }
//*/

    // ++ CEMENTING ++ //
    
    protected function ControlClass() {
	return 'fcFormControl_HTML_Timestamp';
    }
    protected function StorageClass() {
	return 'fcFieldStorage_Time';
    }

    // -- CEMENTING -- //

    // -- FORMAT CONVERSION -- //
}

trait ftFormField_Boolean {

    // ++ OVERRIDES ++ //

    /*
      NOTES: 
	* With booleans, a blank value is considered "set" if the value has been changed or the value had not been set previously.
	* However, the way this is set up, we don't load values from the disk before calculating what to save -- so until there's
	  some way to query the disk value, we'll just say any time this is called, that's considered setting the value.
    */
    public function SetValue($val) {
	$this->ForceValue($val);
	$this->MarkChanged();
    }
    
    // -- OVERRIDES -- //
    // ++ CEMENTING ++ //
    
    protected function ControlClass() {
	return 'fcFormControl_HTML_CheckBox';
    }
    
    // -- CEMENTING -- //

}

class fcFormField_BoolInt extends fcFormField_Text {
    use ftFormField_Boolean;

    // ++ CEMENTING ++ //
    
    protected function StorageClass() {
	return 'fcFieldStorage_Num';
    }
    
    // -- CEMENTING -- //

}
class fcFormField_Bit extends fcFormField {
    use ftFormField_Boolean;

    // ++ CEMENTING ++ //
    
    protected function StorageClass() {
	return 'fcFieldStorage_Bit';
    }
    
    // -- CEMENTING -- //

/* This should be handled by the Control
    public function Convert_DisplayToNative($sVal) {
	return ($sVal=='on');
    }
    public function Convert_NativeToDisplay($bVal) {
	return $bVal?'YES':'no';
    }
    public function Convert_dbToNative($sqlVal) {
	return (ord($sqlVal) != 0);
    }
    public function Convert_NativeToSQL($bVal) {
	$r = $bVal?"b'1'":"b'0'";
	return $r;
    } */
}