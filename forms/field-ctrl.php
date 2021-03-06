<?php
/*
  FILE: field-ctrl.php - single control classes
  LIBRARY: ferreteria: forms
  PURPOSE: Understands how to display a field object's value
  HISTORY:
    2015-03-29 starting from scratch
    2015-05-02 "Hidden" HTML control class
    2015-11-23 Renamed from ctrl.php -> field-ctrl.php
      Controls will now handle all display formatting.
    2016-04-08 Decided that toNative() and fromNative() did not need to be required in fcFormControl.
    2016-04-10 Control objects now add themselves to the Field, not the Form.
*/

abstract class fcFormControl {

    // ++ SETUP ++ //

    public function __construct(fcFormField $oNative) {
	$this->NativeObject($oNative);

	$oForm = $oNative->FormObject();
	$this->FormObject($oForm);
	//$oForm->ControlObject($oNative->NameString(),$this);
	$oNative->ControlObject($this);

	$this->Setup();
    }
    /*----
      PURPOSE: mainly for setting default options
    */
    protected function Setup() {
	// config defaults
	$this->Editable(TRUE);
	$this->Required(TRUE);
    }

    // -- SETUP -- //
    // ++ OPTIONS ++ //

    private $bCanEdit;
    public function Editable($bYes=NULL) {
	if (!is_null($bYes)) {
	    $this->bCanEdit = $bYes;
	}
	return $this->bCanEdit;
    }
    private $bIsReq;
    public function Required($bYes=NULL) {
	if (!is_null($bYes)) {
	    $this->bIsReq = $bYes;
	}
	return $this->bIsReq;
    }

    // -- OPTIONS -- //
    // ++ RELATED OBJECTS ++ //

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
    // PURPOSE: shortcut
    protected function GetStorageObject() {
	return $this->NativeObject()->StorageObject();
    }

    // -- RELATED OBJECTS -- //
    // ++ CONVERSION ++ //

    abstract public function ReceiveForm(array $arData);	// receive this control's data from form submission
    abstract public function Render($doEdit);		// render code to display the control
    protected function RenderValue() {			// render the (native) value only, in display format
	return $this->fromNative($this->GetValueNative());
    }
    /*----
      PURPOSE: convert from received-data (form input) format to internal (native) format
      INPUT: as received from user input on a form
      RETURNS: value in internal format
      USED BY: Form object when doing bulk conversion (DisplayToNative_array)
    */
//    abstract public function toNative($sVal);
    /*----
      PURPOSE: convert from internal format to display format
      INPUT: internal (native) format
      RETURNS: value in displayable format
      NOT SURE if this is actually needed
	Functionality seems to be part of RenderValue(), but that takes
	  local input only. Could split up if needed.
    */
//    abstract protected function fromNative($sVal);

    // -- CONVERSION -- //
    // ++ SHORTCUTS ++ //

    public function NameString() {
	return $this->NativeObject()->NameString();
    }
    protected function GetValueNative() {
	return $this->NativeObject()->GetValue();
    }
}

class fcFormControl_HTML extends fcFormControl {
    private $arTagAttr;
    private $sKey;

    // ++ SETUP ++ //

    public function __construct(fcFormField $oField, array $arAttr=array()) {
	parent::__construct($oField);
	$this->TagAttributes($arAttr);
    }

    // -- SETUP -- //
    // ++ CONFIGURATION ++ //

    private $bDisabled;
    public function Disabled($b=NULL) {
	if (!is_null($b)) {
	    $this->bDisabled = $b;
	} else {
	    if (empty($this->bDisabled)) {
		$this->bDisabled = FALSE;
	    }
	}
	return $this->bDisabled;
    }
    public function TagAttributes(array $arAttr=NULL) {
	if (!is_null($arAttr)) {
	    $this->arTagAttr = $arAttr;
	}
	return $this->arTagAttr;
    }
    // PURPOSE: Alias string for use in displaying lists (e.g. missing fields in a form)
    private $sAlias;
    public function DisplayAlias($s=NULL) {
	if (is_null($s)) {
	    if (empty($this->sAlias)) {
		$this->sAlias = $this->NameString();
	    }
	} else {
	    $this->sAlias = $s;
	}
	return $this->sAlias;
    }

    // -- CONFIGURATION -- //
    // ++ CALCULATIONS ++ //

    // RETURNS: calculated name spec (including parent form keys as needed)
    protected function NameSpec() {
	$oForm = $this->FormObject();
	$sCtrlID = $this->NameString();
	if ($oForm->HasKey()) {
	    $sForm = $oForm->NameString();
	    $sRowID = $oForm->Get_KeyString_loaded();
	    $sSpec = $sForm."[$sRowID][$sCtrlID]";
	} else {
	    $sSpec = $sCtrlID;
	}
	return $sSpec;
    }
    /* 2016-04-15 Tentatively, this is redundant.
    protected function Writable() {
	return $this->Editable() && $this->NativeObject()->OkToWrite();
    }//*/

    // -- CALCULATIONS -- //
    // ++ CONVERSION ++ //

    /*----
      OUTPUT: array
	array['absent']: TRUE or FALSE
	array['blank']: TRUE or FALSE
	The plan is for the calling form to use these to compile lists of missing/blank elements
      HISTORY:
	2016-04-15 Reworked the logic around Editable() (formerly Writable()) a bit.
    */
    public function ReceiveForm(array $arData) {
	$arOut = array();

	$sName = $this->NameString();
	$sMsg = NULL;
	if (array_key_exists($sName,$arData)) {
	    $arOut['absent'] = FALSE;
	    $vPost = $arData[$sName];
	    $arOut['blank'] = (is_null($vPost) || $vPost == '');
	    $vVal = $this->toNative($vPost);

	    // these are mainly for debugging:
	    $arOut['recd'] = $vPost;
	    $arOut['native'] = $vVal;
	    //echo 'SETTING NATIVE to ['.$vVal.']<br>';
	    $this->NativeObject()->SetValue($vVal,TRUE);
	} else {
	    // For some reason, we're not seeing form data for this control.
	    if ($this->Editable()) {
		// this field is editable, but the form isn't sending it:
		$arOut['absent'] = TRUE;
		$arOut['blank'] = NULL;
		$sMsg = "Form error: Key [$sName] not found in submitted data: "
		  .fcArray::Render($arData)
		  .'REQUEST data:'
		  .fcArray::Render($_REQUEST)
		  ;
		$this->FormObject()->AddMessage($sMsg);
		// for debugging:
		//echo $sMsg;
		// this line may be redundant:
		$this->GetStorageObject()->Writable(FALSE);	// don't try to save this field
	    } else {
		$arOut['absent'] = NULL;
		$arOut['blank'] = NULL;
		//echo '['.$this->NameString().'] current value: ['.$this->NativeObject()->GetValue().']<br>';
	    }
	}
	return $arOut;
    }
    /*----
      PURPOSE: convert from received-data (form input) format to internal (native) format
      INPUT: as received from user input on a form
      RETURNS: value in internal format
      USED BY: Form object when doing bulk conversion (DisplayToNative_array)
    */
    public function toNative($sVal) { return ($sVal=='')?NULL:$sVal; }
    /*----
      PURPOSE: convert from internal format to display format
      INPUT: internal (native) format
      RETURNS: value in displayable format
    */
    protected function fromNative($sVal) {
	return fcString::EncodeForHTML($sVal);
    }

    // -- CONVERSION -- //
    // ++ RENDERING ++ //

    public function Render($doEdit) {
	if ($doEdit && $this->Editable()) {
	    $out = $this->RenderEditor();
	} else {
	    $out = $this->RenderValue();
	}
	return $out;
    }
    /*----
      HISTORY:
	2016-04-16 At this point it's not clear under what circumstances we'd want to render
	  the editable version of a control in disabled mode -- so I'm adding a Disabled()
	  function which will determine this, but at this point nothing actually sets it.
    */
    protected function RenderEditor_mainAttribs() {
	$htName = fcString::EncodeForHTML($this->NameSpec());
	$out =
	  " name='$htName'"
	  .($this->Disabled()?' disabled':'')
	  .$this->RenderAttr()
	  ;
	return $out;
    }
    protected function RenderEditor() {
	$htVal = $this->RenderValue();
	$out = '<input'
	  .$this->RenderEditor_mainAttribs()
	  .' value="'.$htVal.'"'
	  .'>'
	  ;
	return $out;
    }
    protected function RenderAttr() {
	$out = '';
	$arAttr = $this->TagAttributes();
	if (is_array($arAttr)) {
	    foreach ($arAttr as $name => $value) {
		$out .= ' '.$name.'="'.$value.'"';
	    }
	}
	return $out;
    }

    // -- RENDERING -- //
}

// TODO: move text-control-specific methods here, and make fcFormControl_HTML abstract
class fcFormControl_HTML_Text extends fcFormControl_HTML {
}

class fcFormControl_HTML_TextArea extends fcFormControl_HTML {

    public function Render($doEdit) {
	if ($doEdit && $this->Editable()) {
	    $out = $this->RenderEditor();
	} else {
	    $val = parent::RenderValue();
	    $out = fcHTML::RespectLineBreaks($val);
	}
	return $out;
    }
    protected function RenderEditor() {
	$out = '<textarea'
	  .$this->RenderEditor_mainAttribs()
	  .'>'
	  .$this->RenderValue()
	  .'</textarea>'
	  ;
	return $out;
    }
}
class fcFormControl_HTML_Hidden extends fcFormControl_HTML {
    protected function RenderEditor() {
	$out = '<input type=hidden name="'
	  .$this->NameSpec()
	  .'" value="'
	  .$this->RenderValue().'"'
	  .$this->RenderAttr().'>';
	return $out;
    }
}
class fcDropChoice {
    private $id;
    private $sShow;

    public function __construct($id,$sShow) {
	$this->id = $id;
	$this->sShow = $sShow;
    }
    public function RenderHTML($bSelected) {
	if ($bSelected) {
	    $htSelect = " selected";
	} else {
	    $htSelect = '';
	}
	$out = "\n".'  <option'.$htSelect.' value="'.$this->id.'">'.$this->sShow.'</option>';
	return $out;
    }
    public function RenderText() {
	return $this->sShow;
    }
}
class fcFormControl_HTML_DropDown extends fcFormControl_HTML {
    private $mDefault;
    private $arExtraLines;

    // ++ SETUP ++ //

    protected function Setup() {
	parent::Setup();
        // option defaults:
        $this->NullDataString('(not set)');
        $this->NoDataString('none found');
        $this->NoObjectString(
	  '<span class=error title="'
	  .__CLASS__
	  .'::Records() or ExtraChoices() needs to be set for '
	  .$this->NameString()
	  .'.">!no rset!</span>'
	  );
        $this->mDefault = NULL;
        $this->arExtraLines = NULL;
    }

    // -- SETUP -- //
    // ++ OPTIONS ++ //

    private $rs;
    // NOTE: Can be NULL because sometimes a particular recordset is empty or unavailable
    public function SetRecords(fcRecord_keyed_single $rs=NULL) {
	$this->rs = $rs;
    }
    public function GetRecords() {
        return $this->rs;
    }
    private $sNullData;		// string to return if value is NULL and the list doesn't have a NULL entry
    public function NullDataString($s=NULL) {
	if (!is_null($s)) {
	    $this->sNullData = $s;
	}
	return $this->sNullData;
    }
    private $sNoData;		// string to return if no rows in recordset
    public function NoDataString($s=NULL) {
        if (!is_null($s)) {
            $this->sNoData = $s;
        }
        return $this->sNoData;
    }
    private $sNoRset;		// string to return if no recordset found
    public function NoObjectString($s=NULL) {
        if (!is_null($s)) {
            $this->sNoRset = $s;
        }
        return $this->sNoRset;
    }
    public function AddChoice($id,$sText) {
	$this->arExtraLines[$id] = new fcDropChoice($id,$sText);
    }
    protected function HasExtraChoices() {
	return is_array($this->arExtraLines);
    }
    protected function ExtraChoices() {
	return $this->arExtraLines;
    }

    // -- OPTIONS -- //
    // ++ STATUS CALCULATIONS ++ //

    protected function HasRows() {
	return $this->HasRecords() || $this->HasExtraChoices();
    }
    protected function HasRecords() {
	$rs = $this->GetRecords();
	if (is_null($rs)) {
	    return FALSE;	// no recordset data
	} elseif ($rs->HasRows()) {
	    return TRUE;	// recordset with data
	} else {
	    return FALSE;	// recordset, but no data
	}
    }

    // -- STATUS CALCULATIONS -- //
    // ++ CACHING ++ //

    /*----
      RETURNS: array of field arrays, one per dataset row: array[key,field name] = field value
    */
    private $arRecs;
    protected function RecordArray() {
	if (empty($this->arRecs)) {
	    $rs = $this->GetRecords();
	    if (is_null($rs)) {
		$this->arRecs = NULL;
	    } elseif ($rs->hasRows()) {
		while ($rs->NextRow()) {
		    $id = $rs->GetKeyValue();
		    $arRecs[$id] = $rs->GetFieldValues();
		}
		$this->arRecs = $arRecs;
	    }
	}
	return $this->arRecs;
    }
    private $arRecChc;
    protected function RecordChoices() {
	if (empty($this->arRecChc)) {
	    $arRecs = $this->RecordArray();
	    if (is_null($arRecs)) {
		$this->arRecChc = NULL;
	    } else {
		$rs = $this->GetRecords();
		foreach ($arRecs as $id => $arRow) {
		    $rs->SetFieldValues($arRow);
		    $oChoice = new fcDropChoice($id,$rs->ListItem_Text());
		    $this->arRecChc[$id] = $oChoice;
		}
	    }
	}
	return $this->arRecChc;
    }
    /* 2017-03-11 This seems not to be in use. Maybe it should be?
    protected function Choices() {
	$arOut = fcArray::Merge($this->ExtraChoices(),$this->RecordChoices());
	return $arOut;
    }*/

    // -- CACHING -- //
    // ++ CONVERSION ++ //

    /*----
      PURPOSE: When field is not being edited, we want to display the value staticly -- which also lets us
	show something more than plaintext, e.g. a link to view more information about the currently-chosen value.
      NOTE: There's got to be a way of doing this that doesn't require iterating through all records.
    */
    protected function fromNative($vCurr) {

	// first look in the extras, because it's faster
	$arExtra = $this->ExtraChoices();
	if (fcArray::Exists($arExtra,$vCurr)) {
	    $arChoice = $arExtra[$vCurr];
	    $out = $arChoice->RenderText();
	} else {

	    // not found in extras, so now look in the recordset
	    $arRecs = $this->RecordArray();
	    if (is_null($arRecs)) {
		// There are no records to refer to, so just dump the value:
		$out = "<span title='no records found for lookup'>($vCurr)</span>";
	    } else {
		$arRec = fcArray::Nz($arRecs,$vCurr);
		if (is_null($arRec)) {
		    if (is_null($vCurr)) {
			$out = $this->NullDataString();
		    } else {
			$out = "<span title='value \"$vCurr\" not found in list'><i>?$vCurr?</i></span>";
		    }
		} else {
		    $rs = $this->GetRecords();	// get a copy of the recordset object
		    if (method_exists($rs,'ListItem_Link')) {
			$rs->SetFieldValues($arRec);	// give it the row we want to display
			$out = $rs->ListItem_Link();
		    } else {
			throw new exception(get_class($rs).'::ListItem_Link() needs to be defined.');
		    }
		}
	    }

	}

	return $out;
    }

    // -- CONVERSION -- //
    // ++ WEB UI ++ //

    /*----
      OVERRIDE
      HISTORY:
	2017-03-09 Rewriting to better handle no-data conditions
	2017-03-11 Rewrote core logic more or less from scratch.
    */
    protected function RenderEditor() {

    // 2017-04-03 revising logic
    // doRows = there are rows (could be data-records or extras)
    // doRecords = there are data-records
    // doExtra = there are extras
    // doObject = there is a data-recordset object (might not have any data-rows)
	$rs = $this->GetRecords();
	if ($this->HasRecords()) {
	    $doRecords = TRUE;
	} else {
	    $doRecords = FALSE;
	    $hasObject = !is_null($rs);
	}
	if ($this->HasExtraChoices()) {
	    $doExtras = TRUE;
	} else {
	    $doExtras = FALSE;	// no extra rows
	}
	$doRows = $doRecords || $doExtras;
	if ($doRows) {
	    // we've confirmed there's something to display
	    $htTag = 'select';
	    $htCloser = '';
	    $out = NULL;
	} else {
	    if ($hasObject) {
		$out = $this->NoDataString();
	    } else {
		$out = $this->NoObjectString();
	    }
	    $htTag = 'input';
	    $this->TagAttributes(
	      array(
		'type'=>'hidden',
		'value'=>''
	      )
	    );
	    $htCloser = '/';
	}
    
	$out .= "\n<$htTag"
	  .$this->RenderEditor_mainAttribs()
	  .$htCloser
	  .'>'
	  ;
	  
	$vDeflt = $this->NativeObject()->GetValue();

	// display extra choices first
	if ($doExtras) {
	    $arRows = $this->ExtraChoices();
	    foreach ($arRows as $id => $oRow) {
		$out .= $oRow->RenderHTML($id == $vDeflt);
	    }
	}

	if ($doRecords) {
	    $sClass = get_class($rs);
	    if (method_exists($rs,'ListItem_Text')) {	// TODO: this should be an interface

		$arRecs = $this->RecordChoices();
		foreach ($arRecs as $id => $oChoice) {
		    $out .= $oChoice->RenderHTML($id == $vDeflt);
		}
	    } else {
		throw new exception("$sClass::ListItem_Text() needs to be defined.");
	    }
	}
	if ($doRows) {
	    $out .= "\n</select>\n";
	}

	return $out;
    }

    // -- WEB UI -- //

}
class fcFormControl_HTML_CheckBox extends fcFormControl_HTML {

    private $sDispOn;
    private $sDispOff;

    // ++ SETUP ++ //

    protected function Setup() {
	parent::Setup();
        // option defaults:
        $this->sDispOn = 'YES';
        $this->sDispOff = 'no';
    }

    // -- SETUP -- //
    // ++ OPTIONS ++ //

    public function DisplayStrings($sOn,$sOff=NULL) {
	$this->sDispOn = $sOn;
	$this->sDispOff = $sOff;
    }

    // -- OPTIONS -- //
    // ++ CONVERSION ++ //

    public function ReceiveForm(array $arData) {
	$sName = $this->NameString();
	// checkboxes are basically TRUE if the value is reported, FALSE otherwise
	$vVal = array_key_exists($sName,$arData)?'1':'0';	// FALSE is treated like 'no value', but zero is seen.
	$oNative = $this->NativeObject()->SetValue($vVal,TRUE);
	
	$arOut['absent'] = FALSE;	// technically, this is ambiguous for checkboxes
	$arOut['blank'] = FALSE;	// same
	// these are mainly for debugging:
	$arOut['recd'] = fcArray::Nz($arData,$sName);	// not terribly useful
	$arOut['native'] = $vVal;
	return $arOut;
    }
    public function toNative($sVal) {
	return ($sVal=='on');
    }
    protected function fromNative($sVal) {
	return $sVal?($this->sDispOn):($this->sDispOff);
    }

    // -- CONVERSION -- //
    // ++ DISPLAY ++ //

    protected function RenderEditor() {
	$out = '<input type=checkbox'
	  .$this->RenderEditor_mainAttribs()
	  .($this->GetValueNative()?' checked':'')
	  .'>'
	  ;
	return $out;
    }

    // -- DISPLAY -- //

}
class fcFormControl_HTML_Timestamp extends fcFormControl_HTML {

    // ++ SETUP ++ //

    protected function Setup() {
	parent::Setup();
        // option defaults:
        $this->Format('Y/m/d H:i:s');  // default display format
    }

    // -- SETUP -- //
    // ++ OPTIONS ++ //

    private $sFmt;  // display format
    public function Format($sFormat=NULL) {
        if (!is_null($sFormat)) {
            $this->sFmt = $sFormat;
        }
        return $this->sFmt;
    }

    // -- OPTIONS -- //
    // ++ CONVERSION ++ //

    public function toNative($sVal) {
	if (empty($sVal)) {
	    return NULL;	// empty string = NULL time value
	} else {
	    return strtotime($sVal);
	}
    }
    protected function fromNative($val) {
	if (!is_numeric($val) && !empty($val)) {
	    throw new exception('Who is passing this a non-numeric value ('.$val.')?');
	}
	if (is_null($val)) {
	    $out = NULL;
	} else {
	    $out = date($this->Format(),$val);
	}
	return $out;
    }

    // -- CONVERSION -- //
}

// This will need to be adapted for the current version of Ferreteria forms:

/*%%%%
  CLASS: clsCtrlHTML_Rating -- for entering a rating on a given scale
  LATER:
    * This version is crude; eventually we should do something more intuitive and user-friendly with JavaScript.
    * Add ability to set the range and interval; starting with -10 to +10 increment 1.
*/
/*
class clsCtrlHTML_Rating extends clsCtrlHTML {
    public function Render() {
	$out = NULL;
	$idxVal = $this->Field()->ValShow();
	for ($idx=-10;$idx<=+10;$idx++) {
	    $out .= "\n".'<input type=radio name="'.$this->NameOut().'"';
	    $out .= $this->RenderAttr();
	    $out .= ' value='.$idx.' title='.$idx;
	    if ($idx == $idxVal) {
		$out .= ' CHECKED';
	    }
	    $out .= '>';
	}
	return $out;
    }
}*/