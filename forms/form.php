<?php
/*
  FILE: form.php - form outer classes (the actual form)
  LIBRARY: ferreteria: forms
  PURPOSE: manages form rows; provides data storage
  DEPENDS:
  HISTORY:
    2015-03-29 starting from scratch
*/

abstract class fcForm {
    private $sName;
    private $arCtrls;
    private $arRec;

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
	}
	return $this->arCtrls[$sName];
    }

    // -- CONFIG -- //
    // ++ ACTIONS ++ //

    public function AddField(fcFormField $oField, fcFormControl $oCtrl) {
	// get the control's name, for indexing
	$sName = $oField->NameString();
	// add it to the form's control array
	$this->ControlObject($sName,$oCtrl);
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
    abstract public function SaveRecord();
    /*----
      PURPOSE: set or retrieve single value
    */
    public function RecordValue($sField,$val=NULL) {
	if (!is_null($val)) {
	    $this->arRec[$sField] = $val;
	}
	if (is_array($this->arRec)) {
	    if (array_key_exists($sField,$this->arRec)) {
		return $this->arRec[$sField];
	    } else {
		throw new exception('Attempting to read unknown field "'.$sField.'".');
	    }
	} else {
	    throw new exception('Attempting to retrieve record field "'.$sField.'", but record is empty.');
	}
    }
    /*----
      PURPOSE: set or retrieve all values in record
    */
    protected function RecordValues(array $arVals=NULL) {
	if (!is_null($arVals)) {
	    $this->arRec = $arVals;
	}
	return $this->arRec;
    }

    /*----
      PURPOSE: sets default values to use for new records.
      INPUT: array of field names and SQL values
	iVals[name] = raw SQL
	if a value is set to NULL, then a new row *must* set that value to non-null before it will be added.
    */
    public function NewValues(array $arVals=NULL) {
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
	Also ensures that every Control has a value (default is NULL).
    */
    public function ClearValues() {
	$this->RecordValues($this->NewValues());
	$arCtrls = $this->ControlArray();
	foreach ($arCtrls as $sName => $oCtrl) {
	    if (!array_key_exists($sName,$this->arRec)) {
		$this->arRec[$sName] = NULL;
	    }
	}
	$this->Set_KeyString_loaded(KS_NEW_REC);
//	$this->Set_KeyString_loaded(NULL);
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
	$arPost = $_POST[$this->NameString()];
	foreach ($arPost as $sKey => $arRec) {
	    $this->ClearValues();
	    $this->RecordValues($arRec);
	    $this->Set_KeyString_toSave($sKey);
	    $this->SaveRecord();
	}
    }

    // -- FORM PROCESSING -- //
}