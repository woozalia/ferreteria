<?php
/*
  FILE: form.php - form outer classes (the actual form)
  LIBRARY: ferreteria: forms
  PURPOSE: manages form rows; provides data storage
  HISTORY:
    2015-03-29 starting from scratch
    2015-06-13 trying to resolve redundancy between "new" (default) values being stored here and being stored in the Field object -- they *should* be stored in the Field.
*/

abstract class fcForm {
    private $sName;
    private $arCtrls;   // list of Control objects
    private $arFlds;	// list of Field objects

    // ++ SETUP ++ //

    public function __construct($sName) {
	$this->NameString($sName);
    }

    // -- SETUP -- //
    // ++ CONFIG ++ //

    public function NameString($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sName = $sName;
	}
	return $this->sName;
    }

    // -- CONFIG -- //
    // ++ CONTROLS ++ //

    protected function ControlArray(array $arCtrls=NULL) {
	if (!is_null($arCtrls)) {
	    $this->arCtrls = $arCtrls;
	}
	return $this->arCtrls;
    }
    /*----
      PUBLIC so Controls can add themselves to the Form
    */
    public function ControlObject($sName,fcFormControl $oCtrl=NULL) {
	if (!is_null($oCtrl)) {
	    $this->arCtrls[$sName] = $oCtrl;
	    //$this->arRec[$sName] = NULL;	// make sure data field exists
	}
	if (array_key_exists($sName,$this->arCtrls)) {
	    return $this->arCtrls[$sName];
	} else {
	    throw new exception('Attempting to retrieve unknown form field "'.$sName.'".');
	}
    }
    protected function ControlExists($sName) {
	return array_key_exists($sName,$this->arCtrls);
    }

    // -- CONTROLS -- //
    // ++ FIELDS ++ //

    /*----
      ACTION: Add a Field object to the Form, or retrieve one
    */
    public function FieldObject($sName,$val=NULL) {
	//return $this->ControlObject($sName)->FieldObject();
	if (!is_null($val)) {
	    $this->arFlds[$sName] = $val;
	}
	if (!array_key_exists($sName,$this->arFlds)) {
	    throw new exception("Attempting to retrieve nonexistent Field object [$sName].");
	}
	return $this->arFlds[$sName];
    }
    protected function FieldArray() {
	return $this->arFlds;
    }
/*    protected function FieldArray_changed_asSQL() {
	$ar = $this->FieldArray();
	$arOut = NULL;
	foreach ($ar as $key => $oField) {
	    if ($oField->IsChanged()) {
		$arOut[$key] = $oField->ValueSQL();
	    }
	}
	return $arOut;
    }*/
    protected function FieldArray_changed() {
	$ar = $this->FieldArray();
	$arOut = NULL;
	foreach ($ar as $key => $oField) {
	    if ($oField->IsChanged()) {
		$arOut[$key] = $oField->ValueNative();
	    }
	}
	return $arOut;
    }

    // -- FIELDS -- //
    // ++ ACTIONS ++ //

    public function AddField(fcFormField $oField, fcFormControl $oCtrl) {
	throw new exception('Who calls this?');
	// should probably rewrite something

	// get the control's name, for indexing
	$sName = $oField->NameString();
	// add objects to local lists:
	$this->ControlObject($sName,$oCtrl);
	$this->FieldObject($sName,$oField);
	// point the control at its associated field
	$oCtrl->FieldObject($oField);
	// point the control at this form
	$oCtrl->FormObject($this);
    }

    // -- ACTIONS -- //
    // ++ DATA STORAGE ++ //

    /*----
      RULE: Call this before attempting to read data
    */
    abstract public function LoadRecord();
    /*----
      RULE: Call this to store data after changing
    */
    abstract public function SaveRecord(array $arUpd);
    /*----
      PURPOSE: set or retrieve single value (native format)
    */
    public function RecordValue($sField,$val=NULL) {
	return $this->FieldObject($sField)->ValueNative($val);
    }
    /* 2015-06-13 old version
    public function RecordValue($sField,$val=NULL) {
	if (!is_null($val)) {
	    $this->SetRecordValue($sField,$val);
	}
	if (is_array($this->arRec)) {
	    if (array_key_exists($sField,$this->arRec)) {
		return $this->arRec[$sField];
	    } else {
		echo 'FIELDS:'.clsArray::Render($this->arRec);
		throw new exception('Attempting to read unknown field "'.$sField.'".');
	    }
	} else {
	    throw new exception('Attempting to retrieve record field "'.$sField.'", but record is empty.');
	}
    } */
    /*----
      PURPOSE: allows explicitly setting NULL values.
    */
    public function SetRecordValue($sField,$val) {
        $this->FieldObject($sField)->SetValueNative($val);
    }
    /* 2015-06-13 old version
    public function SetRecordValue($sField,$val) {
	$this->arRec[$sField] = $val;
    } */
    /*----
      PURPOSE: set or retrieve all values in record (native format)
      DEPRECATED
    */
    protected function RecordValues_asNative(array $arVals=NULL) {
        throw new exception('Who calls this?');	// probably call RecordValues_asNative_get() instead
	if (!is_null($arVals)) {
	    $this->arRec = $arVals;
	}
	return $this->arRec;
    }
    /*----
      PURPOSE: set all passed values
      INPUT: $arVals = array[field name, native-format value]
      USAGE:
	* when loading record from db into memory (unknown fields are discarded)
	* when saving edited record from form into db
    */
    protected function RecordValues_asNative_set(array $arVals=NULL) {
	$arFlds = $this->FieldArray();
	foreach ($arVals as $key => $val) {
	    if (array_key_exists($key,$arFlds)) {
		// ignore data fields for which there is no Field object
		$oField = $arFlds[$key];
		$oField->SetValueNative($val);
	    }
	}
    }
    /*----
      PURPOSE: retrieve all values in record (native format)
      USAGE: called when the Form object is preparing a list of values to insert or update
    */
    protected function RecordValues_asNative_get() {
	$arFlds = $this->FieldArray();
	$arOut = NULL;
	foreach ($arFlds as $key => $oField) {
	    $arOut[$key] = $oField->ValueNative();
	}
	return $arOut;
    }
    protected function RecordValues_asDisplay(array $arVals=NULL) {
	if (!is_null($arVals)) {
	    foreach ($arVals as $key => $val) {
		if ($this->ControlExists($key)) {
		    $oField = $this->FieldObject($key);
		    $oField->ValueDisplay($val);
		}
	    }
	}
    }
    /*----
      USAGE: called during save process to convert all input to native format
	before adding in overrides.
    */
    protected function DisplayToNative_array(array $arDsp) {
	$arFlds = $this->FieldArray();
	foreach ($arDsp as $key => $val) {
	    $oFld = $arFlds[$key];
	    $arOut[$key] = $oFld->Convert_DisplayToNative($val);
	}
	return $arOut;
    }
    /*----
      ACTION: set an individual default value to use for new records
      WARNING: NewValues() will override any that you set here!
    */
    public function NewValue($sName,$val) {
        $this->FieldObject($sName)->SetDefaultNative($val);
        /* 2015-06-13 old version
        $this->arNewVals[$sName] = $val;
        */
    }
    /*----
      ACTION: set or return default values to use for new records.
      INPUT: array of field names and SQL values
	iVals[name] = raw SQL
	if a value is set to NULL, then a new row *must* set that value to non-null before it will be added.
    */
    public function NewValues(array $arVals=NULL) {
        throw new exception('If anyone is calling this, it needs to be rewritten slightly.');

	if (!is_null($arVals)) {
	    $this->arNewVals = $arVals;
	}
	if (!is_array($this->arNewVals)) {
	    // need to be able to pass return value to array functions
	    //   so make sure it's an array even if empty.
	    $this->arNewVals = array();
	}
	return $this->arNewVals;
    }
    /*----
      RULE: Call this to initialize the form to default new values
	UNLESS field has been explicitly edited.
	Also ensures that every Control has a value (default is NULL).
      TODO: Look at this process later to see if it makes sense.
	Why do we clear values? There *was* a good reason...
    */
    public function ClearValues() {
	$arCtrls = $this->ControlArray();
	foreach ($arCtrls as $sName => $oCtrl) {
	    $oField = $oCtrl->FieldObject();
	    if (!$oField->IsChanged()) {
		$oField->UseDefault();
	    }
	}
	$this->Set_KeyString_loaded(KS_NEW_REC);
    }

    // -- DATA STORAGE -- //
    // ++ RENDERING ++ //

    /*----
      RETURNS: array[control name] = rendering of control
    */
    public function RenderControls($doEdit) {
	$arCtrls = $this->ControlArray();
	if (!is_array($arCtrls)) {
	    throw new exception('Attempting to access controls before they have been set.');
	}
	foreach ($arCtrls as $sKey => $oCtrl) {
	    $arOut[$sKey] = $oCtrl->Render($doEdit);
	}
	return $arOut;
    }

    // -- RENDERING -- //
    // ++ FORM PROCESSING ++ //

    public function Save() {
	throw new exception('Saving of non-keyed forms is not yet written.');
    }

    // -- FORM PROCESSING -- //
}

abstract class fcForm_keyed extends fcForm {
    private $sKeySave;
    private $sKeyLoad;

    // ++ CONFIGURATION ++ //

    public function Get_KeyString_loaded() {
	return $this->sKeyLoad;
    }
    protected function Set_KeyString_loaded($sKey) {
	$this->sKeyLoad = $sKey;
	$this->Set_KeyString_toSave($sKey);
    }
    public function Get_KeyString_toSave() {
	return $this->sKeySave;
    }
    protected function Set_KeyString_toSave($sKey) {
	$this->sKeySave = $sKey;
    }
    public function HasKey() {
	return !is_null($this->Get_KeyString_loaded());
    }

    // -- CONFIGURATION -- //
    // ++ FORM PROCESSING ++ //

    public function Save() {
        $sName = $this->NameString();
        if (array_key_exists($sName,$_POST)) {
            $arPost = $_POST[$sName];
            $arFlds = $this->FieldArray();
            foreach ($arPost as $sKey => $arDsp) {
                $this->Set_KeyString_toSave($sKey);
                $arNtv = $this->DisplayToNative_array($arDsp);
                //echo __FILE__.' line '.__LINE__."<br>\n";
                $arSet = $this->FieldArray_changed();
                //echo 'CHANGED:'.clsArray::Render($arSet);
                //echo 'RECEIVED:'.clsArray::Render($arNtv);
                $arAll = clsArray::Merge($arNtv,$arSet);
                //echo 'UPDATE:'.clsArray::Render($arAll);
                $this->SaveRecord($arAll);
                // clear this record's values in case there's another record
                $this->ClearValues();
            }
        } else {
            echo '<b>Internal error</b>: POST contains no "'.$sName.'" data.<br>';
            echo 'POST:'.clsArray::Render($_POST);
            echo '<pre>';
            debug_print_backtrace();
            echo '</pre>';
            die();
        }
    }

    // -- FORM PROCESSING -- //
}