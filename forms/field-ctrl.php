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
*/

abstract class fcFormControl {

    // ++ SETUP ++ //

    public function __construct(fcFormField $oNative) {
	$this->NativeObject($oNative);
	
	$oForm = $oNative->FormObject();
	$this->FormObject($oForm);
	$oForm->ControlObject($oNative->NameString(),$this);
	
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
    abstract public function toNative($sVal);
    /*----
      PURPOSE: convert from internal format to display format
      INPUT: internal (native) format
      RETURNS: value in displayable format
      NOT SURE if this is actually needed
	Functionality seems to be part of RenderValue(), but that takes
	  local input only. Could split up if needed.
    */
    abstract protected function fromNative($sVal);
    
    // -- CONVERSION -- //
    // ++ SHORTCUTS ++ //

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
    
    public function TagAttributes(array $arAttr=NULL) {
	if (!is_null($arAttr)) {
	    $this->arTagAttr = $arAttr;
	}
	return $this->arTagAttr;
    }
    protected function NameString() {
	return $this->NativeObject()->NameString();
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
    protected function Writable() {
	return $this->Editable() && $this->NativeObject()->OkToWrite();
    }

    // -- CALCULATIONS -- //
    // ++ CONVERSION ++ //
    
    /*----
      OUTPUT: array
	array['absent']: TRUE or FALSE
	array['blank']: TRUE or FALSE
	The plan is for the calling form to use these to compile lists of missing/blank elements
    */
    public function ReceiveForm(array $arData) {
	$arOut = array();
	if ($this->Writable()) {	// don't overwrite value with form data if not editable
	    $sName = $this->NameString();
	    $sMsg = NULL;
	    if (array_key_exists($sName,$arData)) {
		$arOut['absent'] = FALSE;
		$vPost = $arData[$sName];
		$arOut['blank'] = (is_null($vPost) || $vPost == '');
		$vVal = $this->toNative($vPost);
		$this->NativeObject()->SetValue($vVal);
	    } else {
		// this field is editable, but the form isn't sending it:
		$arOut['absent'] = TRUE;
		$arOut['blank'] = NULL;
		$sMsg = "Form error: Key [$sName] not found in post data: ".clsArray::Render($arData);
		$this->FormObject()->AddMessage($sMsg);
		// for debugging:
		//echo $sMsg;
		// this line may be redundant:
		$this->NativeObject()->OkToWrite(FALSE);	// don't try to save this field
	    }
	} else {
	    $arOut['absent'] = NULL;
	    $arOut['blank'] = NULL;
	    //echo '['.$this->NameString().'] current value: ['.$this->NativeObject()->GetValue().']<br>';
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
	if ($doEdit && $this->Writable()) {
	    $out = $this->RenderEditor();
	} else {
	    $out = $this->RenderValue();
	}
	return $out;
    }
    protected function RenderEditor_mainAttribs() {
	$htName = fcString::EncodeForHTML($this->NameSpec());
	$out =
	  " name='$htName'"
	  .($this->NativeObject()->OkToWrite()?'':' disabled')
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
    /*
	$out = '<input name="'
	  .$this->NameSpec()
	  .'" value="'
	  .$this->RenderValue().'"'
	  .$this->RenderAttr().'>'; */
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
	    $out = clsHTML::RespectLineBreaks($val);
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
    public function Records(clsRecs_keyed_abstract $rs=NULL) {
        if (!is_null($rs)) {
            $this->rs = $rs;
        }
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
	$rs = $this->Records();
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
	    $rs = $this->Records();
	    if (is_null($rs)) {
		$this->arRecs = NULL;
	    } elseif ($rs->hasRows()) {
		while ($rs->NextRow()) {
		    $id = $rs->KeyValue();
		    $arRecs[$id] = $rs->Values();
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
		$rs = $this->Records();
		foreach ($arRecs as $id => $arRow) {
		    $rs->Values($arRow);
		    $oChoice = new fcDropChoice($id,$rs->ListItem_Text());
		    $this->arRecChc[$id] = $oChoice;
		}
	    }
	}
	return $this->arRecChc;
    }
    protected function Choices() {
	$arOut = clsArray::Merge($this->ExtraChoices(),$this->RecordChoices());
	return $arOut;
    }

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
	if (clsArray::Exists($arExtra,$vCurr)) {
	    $arChoice = $arExtra[$vCurr];
	    $out = $arChoice->RenderText();
	} else {
	
	    // not found in extras, so now look in the recordset
	    $arRecs = $this->RecordArray();
	    if (is_null($arRecs)) {
		// There are no records to refer to, so just dump the value:
		$out = "<span title='no records found for lookup'>($vCurr)</span>";
	    } else {
		$arRec = clsArray::Nz($arRecs,$vCurr);
		if (is_null($arRec)) {
		    if (is_null($vCurr)) {
			$out = $this->NullDataString();
		    } else {
			$out = "<span title='value \"$vCurr\" not found in list'><i>?$vCurr?</i></span>";
		    }
		} else {
		    $rs = $this->Records();	// get a copy of the recordset object
		    if (method_exists($rs,'ListItem_Link')) {
			$rs->Values($arRec);	// give it the row we want to display
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
    // ++ OVERRIDES ++ //

    protected function RenderEditor() {

	if (!$this->HasRows()) {
	    if (!$this->HasRecords()) {
		if (is_null($this->Records())) {
		    // no recordeset object at all
		    return $this->NoObjectString();
		} else {
		    // no records in set and no extra records
		    return $this->NoDataString();
		}
	    }
        }

        // we've confirmed there's something to display
        
	$vDeflt = $this->NativeObject()->GetValue();

	$out = "\n".'<select'
	  .$this->RenderEditor_mainAttribs()
	  .'>'
	  ;

	// display extra choices first
	if ($this->HasExtraChoices()) {
	    $arRows = $this->ExtraChoices();
	    foreach ($arRows as $id => $oRow) {
		$out .= $oRow->RenderHTML($id == $vDeflt);
	    }
	}

	if ($this->HasRecords()) {
	    $rs = $this->Records();
	    if (method_exists($rs,'ListItem_Text')) {
	      
/*
		$arRecs = $this->RecordArray();
		foreach ($arRecs as $id => $arRow) {
		    $rs->Values($arRow);
		    $oRow = new fcDropChoice($id,$rs->ListItem_Text());
		    $out .= $oRow->RenderHTML($id == $vDeflt);
		}
*/
		$arRecs = $this->RecordChoices();
		foreach ($arRecs as $id => $oChoice) {
		    $out .= $oChoice->RenderHTML($id == $vDeflt);
		}
	    } else {
		throw new exception(get_class($rs).'::ListItem_Text() needs to be defined.');
	    }
	}
		
	$out .= "\n</select>\n";
	  
	return $out;
    }

    // -- OVERRIDES -- //

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
	$vVal = array_key_exists($sName,$arData);
	$this->NativeObject()->SetValue($vVal);
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
	return strtotime($sVal);
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