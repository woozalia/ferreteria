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
    private $arFlds;	// list of Field objects

    // ++ SETUP ++ //

    public function __construct($sName) {
	$this->NameString($sName);
	$this->InitVars();
    }
    protected function InitVars() {}

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
    // ++ CONTROL OBJECTS ++ //

    protected function ControlArray() {
	foreach ($this->arFlds as $key => $fld) {
	    $arCtrls[$key] = $fld->ControlObject();
	}
	return $arCtrls;
    }
    public function GetControlObject($sName) {
	return $this->FieldObject($sName)->ControlObject();
    }

    // -- CONTROL OBJECTS -- //
    // ++ STORAGE OBJECTS ++ //
    
    /*----
      PUBLIC so callers can set write-ready storage values
      HISTORY:
	2018-05-18 made public
    */
    public function GetStorageObject($sName) {
	return $this->FieldObject($sName)->StorageObject();
    }
    
    // -- STORAGE OBJECTS-- //
    // ++ FIELDS ++ //

    /*----
      ACTION: Add a Field object to the Form, or retrieve one
      TODO: This should be split into GetFieldObject() / SetFieldObject()
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
    public function GetFieldArray() {
	return $this->arFlds;
    }
    /* 2018-02-26 This is only used for debugging, as far as I can tell. Commenting out until needed.
      If needed, should probably be renamed GetFieldArray_changed().
    // 2016-02-04 might be useful after all
    protected function FieldArray_changed() {
	$ar = $this->GetFieldArray();
	$arOut = NULL;
	foreach ($ar as $key => $oField) {
	    if ($oField->IsChanged()) {
		$arOut[$key] = $oField->GetValue();
	    }
	}
	return $arOut;
    } */
    /*----
      PUBLIC for event logging
      TODO: Should there be a form variant which automatically logs events?
	Then this could be protected again.
      NOTE: 2018-02-26 There is now a SetFieldArray_toWrite_native(), but
	it's only used in fcForm_blob at this point.
      RETURNS: array[key] = value
      HISTORY:
	2017-04-14 changed from protected to public
    */
    public function GetFieldArray_toWrite_native() {
	$ar = $this->GetFieldArray();
	$arOut = NULL;
	foreach ($ar as $key => $oField) {
	    if ($oField->ShouldWrite()) {
		$arOut[$key] = $oField->GetValue();
	    }
	}
	return $arOut;
    }
    /*----
      RETURNS: SQL-formatted values to be written to the database
      HISTORY:
	2016-10-13 written but apparently unneeded
	2017-09-15 Actually, we do need it.
    */
    protected function GetFieldArray_toWrite_storage() {
	$ar = $this->GetFieldArray();
	$arOut = NULL;
	foreach ($ar as $key => $oField) {
	    if ($oField->ShouldWrite()) {
		$arOut[$key] = $oField->StorageObject()->GetValueRaw();	// 2017-09-15 not sure if should be "raw" or "sane"
	    }
	}
	return $arOut;
    }
    /* 2018-02-26 Hidden until needed, for tidying purposes
    // DEBUGGING
    public function DumpChanged($txt) {
	$ar = $this->FieldArray_changed();
	echo "CHANGED FIELDS ($txt):".fcArray::Render($ar);
    } */

    // -- FIELDS -- //
    // ++ DATA STORAGE ++ //

    /*----
      PURPOSE: set or retrieve single value (native format)
    */
    public function RecordValue($sField,$val=NULL) {
	return $this->FieldObject($sField)->ValueNative($val);
    }
    /*----
      USAGE: When form data is being saved, this retrieves the SQL values to write
      NOTE: Only returns the fields which have been modified ($oField->IsChanged()).
      RETURNS: sanitized SQL ready to send
      HISTORY:
	2016-10-11 written
	2016-10-17 deactivated because it seemed unnecessary
	2016-10-18 reactivated for delivering *sanitized* SQL
	2017-01-17 only return modified fields -- else unedited fields get overwritten with NULL
	2018-05-17 Renamed from RecordValues_asStorageSane_get() to SetRecordValues_forStorage_Writable().
    */
    public function GetRecordValues_forStorage_Writable() {
	$arFlds = $this->GetFieldArray();
	$arOut = NULL;
	foreach ($arFlds as $key => $oField) {
	    if ($oField->IsChanged()) {
		$arOut[$key] = $oField->StorageObject()->GetValueSane();
	    }
	}
	return $arOut;
    }
    // 2015-11-24 this will need fixing
    protected function RecordValues_asDisplay(array $arVals=NULL) {
    throw new exception('2018-02-26 Does anything still call this?');
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
	$arFlds = $this->GetFieldArray();
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
    /* 2017-01-17 Does not seem to be used.
    public function NewValues(array $arVals) {
        throw new exception('If anyone is calling this, they should probably be setting defaults via each Field object.');
	foreach ($arVals as $key => $val) {
	    $this->FieldObject($key)->SetDefaultNative($val);
	}
    }*/
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
    // ++ WEB OUTPUT ++ //

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

    // -- WEB OUTPUT -- //
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
      TODO: This should probably be named something more specific like SaveFields_toRecord().
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
		    $arConv = $oCtrl->ReceiveForm($arPostRec);
		    //echo "RECEIVING [$sFieldKey]:".fcArray::Render($arConv);
		    //echo 'CTRL->NATIVE: ['.$oCtrl->NativeObject()->GetValue().']<br>';
		    //echo 'FIELD: ['.$this->FieldObject($sFieldKey)->GetValue().']<br><hr />';
		}

		// get native values from Controls
		$arSet = $this->GetFieldArray_toWrite_storage();
		// save native values to database (SaveRecord() will use Storage objects to convert)
		$id = $this->SaveRecord($arSet);
		// clear this record's values in case there's another record
		$this->ClearValues();
		return $id;	// ...so we can redirect to the new record
	    }
        } else {
	    // 2018-04-29 no, I don't remember why I did it this way instead of just throwing an exception
            echo '<b>Internal error</b>: POST contains no "'.$sName.'" data.<br>';
            echo 'POST:'.fcArray::Render($_POST);
            echo '<pre>';
            debug_print_backtrace();
            echo '</pre>';
            die();
        }
    }

    // -- FORM PROCESSING -- //
}