<?php
/*
  FILE: form.php - form outer classes (the actual form)
  LIBRARY: ferreteria: forms
  PURPOSE: manages form rows; provides data storage
  HISTORY:
    2015-03-29 starting from scratch
    2015-06-13 trying to resolve redundancy between "new" (default) values being stored here and being stored in the Field object -- they *should* be stored in the Field.
    2015-07-16 resolving conflicts with other edited version
    2016-03-08 removed abstract methods in fcForm because nothing calls them. It is now a concrete class.
    2016-04-10 Discovered that Form.arCtrls is a duplicate of Field.ControlObject() -- so let's just go
      to the Field object to get the Control.
      * ControlArray() is now read-only.
      * ControlObject() has been deprecated.
      * ControlExists() has been removed.
*/

class fcForm {
    use ftVerbalObject;

    private $sName;
//    private $arCtrls;   // list of Control objects
    private $arFlds;	// list of Field objects

    // ++ SETUP ++ //

    public function __construct($sName) {
	$this->NameString($sName);
	$this->InitVars();
    }
    protected function InitVars() {
    }

    // -- SETUP -- //
    // ++ CONFIG ++ //

    public function NameString($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sName = $sName;
	}
	return $this->sName;
    }
    // USED BY: Control objects. The base class does not do keys.
    public function HasKey() {
	return FALSE;
    }

    // -- CONFIG -- //
    // ++ CONTROLS ++ //

    protected function ControlArray() {
	foreach ($this->arFlds as $key => $fld) {
	    $arCtrls[$key] = $fld->ControlObject();
	}
	return $arCtrls;
    }
    /*----
      PUBLIC so Controls can add themselves to the Form
    */
    public function ControlObject($sName,fcFormControl $oCtrl=NULL) {
	throw new exception('2016-04-10 Controls should add themselves to the Field object now.');
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
    /*
    protected function ControlExists($sName) {
	return array_key_exists($sName,$this->arCtrls);
    }//*/

    // -- CONTROLS -- //
    // ++ FIELDS ++ //

    /*----
      ACTION: Add a Field object to the Form, or retrieve one
    */
    public function FieldObject($sName,fcFormField $oField=NULL) {
	//return $this->ControlObject($sName)->FieldObject();
	if (!is_null($oField)) {
	    $this->arFlds[$sName] = $oField;
	}
	if (!array_key_exists($sName,$this->arFlds)) {
	    throw new exception("Attempting to retrieve nonexistent Field object [$sName].");
	}
	return $this->arFlds[$sName];
    }
    /*----
      PUBLIC so nonstandard data sources can connect with form data more easily.
	This is a bit of a kluge until I can work out a more general way for
	different data sources to interconnect.
    */
    public function FieldArray() {
	return $this->arFlds;
    }
    // 2016-02-04 might be useful after all
    protected function FieldArray_changed() {
	$ar = $this->FieldArray();
	$arOut = NULL;
	foreach ($ar as $key => $oField) {
	    if ($oField->IsChanged()) {
		$arOut[$key] = $oField->GetValue();
	    }
	}
	return $arOut;
    }
    protected function FieldArray_toWrite() {
	$ar = $this->FieldArray();
	$arOut = NULL;
	foreach ($ar as $key => $oField) {
	    if ($oField->ShouldWrite()) {
		$arOut[$key] = $oField->GetValue();
	    }
	}
	return $arOut;
    }
    // DEBUGGING
    public function DumpChanged($txt) {
	$ar = $this->FieldArray_changed();
	echo "CHANGED FIELDS ($txt):".clsArray::Render($ar);
    }

    // -- FIELDS -- //
    // ++ ACTIONS ++ //

    public function AddField(fcFormField $oField, fcFormControl $oCtrl) {
	throw new exception('Who calls this?');	// 2016-04-10 nobody, so far...
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
      PURPOSE: set or retrieve single value (native format)
    */
    public function RecordValue($sField,$val=NULL) {
	return $this->FieldObject($sField)->ValueNative($val);
    }
    /*----
      PURPOSE: allows explicitly setting NULL values.
    */
    /* 2016-02-04 This no longer works. Call ->FieldObject($sField)->SetValue($val)
    public function SetRecordValue($sField,$val) {
        $this->FieldObject($sField)->SetValueNative($val);
    }*/
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
      HISTORY:
	2016-03-03 Now writes new values back to memory-record object as well.
	  This is needed especially when editing the value of a key, so that
	  we can redirect to the record's new home. Using the old values will
	  attempt to pull up a record that doesn't exist.
	2016-03-25 Empty fields in new records caused a code-trap because
	  setting $rc->Value() with a NULL $val means it tries to read
	  from a nonexistent record. I've replaced ->Value() with ->SetValue(),
	  which I then had to write...
    */
    protected function RecordValues_asNative_set(array $arVals=NULL) {
	$arFlds = $this->FieldArray();
	$rc = $this->RecordsObject();
	foreach ($arVals as $key => $val) {
	    if (array_key_exists($key,$arFlds)) {
		// ignore data fields for which there is no Field object
		$oField = $arFlds[$key];
		$oField->SetValue($val);
		$rc->SetValue($key,$val);	// save to memory-record object also
	    }
	}
    }
    /*----
      PURPOSE: retrieve all values in record (native format)
      USAGE: called when the Form object is preparing a list of values to insert or update
      PUBLIC for vcCartDataManager.UpdateBlob() - maybe there's a better way?
    */
    public function RecordValues_asNative_get() {
	$arFlds = $this->FieldArray();
	$arOut = NULL;
	foreach ($arFlds as $key => $oField) {
	    $arOut[$key] = $oField->GetValue();
	}
	return $arOut;
    }
    // 2015-11-24 this will need fixing
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
      NOTE that in this case "display" format is actually the format received via
	HTTP (POST/GET) rather than how the data is actually displayed.
	
	We might later want to rename these routines from "Display*" to "Form*" or
	  something similar. Renaming only the input converters (to something like
	  "Post*") would just be confusing, though, since some of the methods
	  can be used to read *or* write, which doesn't work when the name for reading
	  is different from the name for writing.
    */
    protected function DisplayToNative_array(array $arDsp) {
	$arFlds = $this->FieldArray();
	foreach ($arDsp as $key => $val) {
	    $oFld = $arFlds[$key];
	    $arOut[$key] = $oFld->ControlObject()->toNative($val);
	}
	return $arOut;
    }
    /*----
      ACTION: set or return default values to use for new records.
      INPUT: array of field names and SQL values
	iVals[name] = raw SQL
	if a value is set to NULL, then a new row *must* set that value to non-null before it will be added.
      OUTPUT: Currently nothing. Used to return an array of new values to use -- if this
	is still needed, then document why.
      HISTORY:
	2015-10-20 rewritten to use new default-value storage mechanism (in objects)
	  Removed "=NULL" default.
	  ...and then determined that the caller was probably going about this wrong,
	    so restored the exception throw.
    */
    public function NewValues(array $arVals) {
        throw new exception('If anyone is calling this, they should probably be setting defaults via each Field object.');
	foreach ($arVals as $key => $val) {
	    $this->FieldObject($key)->SetDefaultNative($val);
	}
    }
    /*----
      RULE: Call this to initialize the form to default new values
	UNLESS field has been explicitly edited.
	Also ensures that every Control has a value (default is NULL).
      USAGE: call to initialize values for a NEW record
    */
    public function ClearValues() {
	$arCtrls = $this->ControlArray();
	foreach ($arCtrls as $sName => $oCtrl) {
	    $oField = $oCtrl->NativeObject();
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

    /*----
      ACTION: Set native fields from received values
      USAGE: External only
	This basically does only the data-receiving part of $this->Save(),
	  for a single record.
      HISTORY:
	2016-03-13 Written for vbzcart checkout forms.
    */
    public function Receive(array $arData) {
	$arCtrls = $this->ControlArray();
	$arBlank = NULL;
	$arMissed = NULL;
	foreach ($arCtrls as $sFieldKey => $oCtrl) {
	    $arStatus = $oCtrl->ReceiveForm($arData);
	    if ($arStatus['blank'] && $oCtrl->Required()) {
		$arBlank[] = $oCtrl;
	    }
	    if ($arStatus['absent']) {
		$arMissed[] = $oCtrl;
	    }
	}
	$arOut['blank'] = $arBlank;
	$arOut['absent'] = $arMissed;
	return $arOut;
    }
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
    
    /*----
      RETURNS: entered value for the given field in the given record
      USAGE: External only.
	
	This is so we can check if certain values are being changed, in case we need
	to trigger additional events when that happens.
	
	It will probably need to be developed further in order to handle weird value formats,
	but we'll get to that when it happens.
	
	This currently also doesn't check for missing fields. TODO
      HISTORY:
	2016-01-22 written
    */
    public function EnteredValue($idRec,$sName) {
	$sFormName = $this->NameString();
        if (array_key_exists($sFormName,$_POST)) {
	    $arOut = NULL;
	    $arFormData = $_POST[$sFormName];
	    $out = $arFormData[$idRec][$sName];
	    return $out;
        } else {
	    return NULL;
	}
    }
    /*----
      ACTION: Ask each of the Controls to check for received data
      RETURNS: ID of record saved
	This is so the caller can redirect to the new record, when creating one.
	Maybe there is a better way to do this. It would also be nice if it were
	  easier to retrieve (a) the error status, (b) error messages (if any).
      HISTORY:
	2015-11-25 Rewrote more or less from scratch. we need to ask the Controls
	  to check whether their state has changed, because unchecked checkboxes
	  don't send any data. The old version didn't do this.
    */
    public function Save() {
        $sName = $this->NameString();	// get form name
        if (array_key_exists($sName,$_POST)) {
	    $arCtrls = $this->ControlArray();
	    // iterate through post records
	    $arForm = $_POST[$sName];	// get just the form data
	    foreach ($arForm as $sRowKey => $arPostRec) {
                $this->Set_KeyString_toSave($sRowKey);	// the sql update key
		foreach ($arCtrls as $sFieldKey => $oCtrl) {
		    $oCtrl->ReceiveForm($arPostRec);
		}
	    
		// get native values from Controls
		$arSet = $this->FieldArray_toWrite();
		// save native values to database (SaveRecord() will use Storage objects to convert)
		$id = $this->SaveRecord($arSet);
		// clear this record's values in case there's another record
		$this->ClearValues();
		return $id;	// ...so we can redirect to the new record
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