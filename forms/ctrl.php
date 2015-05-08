<?php
/*
  FILE: ctrl.php - single control classes
  LIBRARY: ferreteria: forms
  PURPOSE: Understands how to display a field object's value
  DEPENDS: field.php
  HISTORY:
    2015-03-29 starting from scratch
    2015-05-02 "Hidden" HTML control class
*/

abstract class fcFormControl {
    private $oForm;
    private $oField;
    private $bCanEdit;

    // ++ SETUP ++ //

    public function __construct(fcForm $oForm, fcFormField $oField) {
	$this->FormObject($oForm);
	$this->FieldObject($oField);
	$oForm->ControlObject($oField->NameString(),$this);
	$this->Setup();
    }
    /*----
      PURPOSE: mainly for setting default options
    */
    protected function Setup() {
	$this->bCanEdit = TRUE;
    }

    // -- SETUP -- //
    // ++ OPTIONS ++ //

    public function Editable($bYes=NULL) {
	if (!is_null($bYes)) {
	    $this->bCanEdit = $bYes;
	}
	return $this->bCanEdit;
    }

    // -- OPTIONS -- //
    // ++ CONFIG ++ //

    protected function FormObject(fcForm $oForm=NULL) {
	if (!is_null($oForm)) {
	    $this->oForm = $oForm;
	}
	return $this->oForm;
    }
    public function FieldObject(fcFormField $oField=NULL) {
	if (!is_null($oField)) {
	    $this->oField = $oField;
	}
	return $this->oField;
    }

    // -- CONFIG -- //
    // ++ ACTIONS ++ //

    abstract public function Render($doEdit);		// render code to display the control
    //abstract public function Receive();	// receive user-entered value for this control

    // -- ACTIONS -- //
    // ++ SHORTCUTS ++ //

    protected function ValueNative() {
	return $this->FieldObject()->ValueNative();
    }
}

class fcFormControl_HTML extends fcFormControl {
    private $arTagAttr;
    private $sKey;

    public function __construct(fcForm $oForm, fcFormField $oField, array $arAttr) {
	parent::__construct($oForm,$oField);
	$this->TagAttributes($arAttr);
    }

    public function TagAttributes(array $arAttr=NULL) {
	if (!is_null($arAttr)) {
	    $this->arTagAttr = $arAttr;
	}
	return $this->arTagAttr;
    }
    protected function NameString() {
	return $this->FieldObject()->NameString();
    }
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

    // ++ RENDERING ++ //

    public function Render($doEdit) {
	if ($doEdit && $this->Editable()) {
	    $out = $this->RenderEditor();
	} else {
	    $out = $this->RenderValue();
	}
	return $out;
    }
    protected function RenderEditor() {
	$out = '<input name="'
	  .$this->NameSpec()
	  .'" value="'
	  .$this->RenderValue().'"'
	  .$this->RenderAttr().'>';
	return $out;
    }
    protected function RenderValue() {
	return htmlspecialchars($this->FieldObject()->ValueDisplay());
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

class fcFormControl_HTML_TextArea extends fcFormControl_HTML {
    protected function RenderEditor() {
	$out = '<textarea name="'
	  .$this->NameSpec().'"'
	  .$this->RenderAttr().'>'
	  .$this->RenderValue()
	  .'</textarea>';
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
}
class fcFormControl_HTML_DropDown extends fcFormControl_HTML {
    private $sNoData;		// string to return if no rows in recordset
    private $sNoRset;		// string to return if no recordset found
    private $mDefault;
    private $arExtraLines;

    // ++ SETUP ++ //

    protected function Setup() {
	parent::Setup();
        // option defaults:
        $this->sNoData = 'none found';
        $this->sNoRset =
	  '<span class=error title="'
	  .__CLASS__
	  .'::Records() needs to be set for '
	  .$this->NameString()
	  .'.">!no rset!</span>';
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
    public function NoDataString($s=NULL) {
        if (!is_null($s)) {
            $this->sNoData = $s;
        }
        return $this->sNoData;
    }
    public function NoObjectString($s=NULL) {
        if (!is_null($s)) {
            $this->sNoRset = $s;
        }
        return $this->sNoRset;
    }
    public function AddChoice($id,$sText) {
	$this->arExtraLines[] = new fcDropChoice($id,$sText);
    }
    protected function HasExtraChoices() {
	return is_array($this->arExtraLines);
    }
    protected function ExtraRows_array() {
	return $this->arExtraLines;
    }

    // -- OPTIONS -- //
    // ++ IMPLEMENTATION ++ //

    protected function RenderEditor() {

        $rs = $this->Records();
        if (is_null($rs)) {
            return $this->NoObjectString();
        }
	if ($rs->hasRows()) {
	    $out = "\n".'<select name="'
              .$this->NameSpec()
              .'">';

	    if ($this->HasExtraChoices()) {
		$arRows = $this->ExtraRows_array();
		foreach ($arRows as $oRow) {
		    $out .= $oRow->RenderHTML(FALSE);	// never selected by default
		}
	    }
            $vDeflt = $this->FieldObject()->ValueNative();
	    while ($rs->NextRow()) {
		$id = $rs->KeyValue();
		$oRow = new fcDropChoice($id,$rs->Text_forList());
		$out .= $oRow->RenderHTML($id == $vDeflt);
	    }
	    $out .= "\n</select>\n";
	    return $out;
	} else {
	    return $this->NoDataString();
	}
	return $out;
    }

    // -- IMPLEMENTATION -- //
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

    public function DisplayStrings($sOn,$sOff) {
	$this->sDispOn = $sOn;
	$this->sDispOff = $sOff;
    }
    protected function RenderValue() {
	return $this->ValueNative()?($this->sDispOn):($this->sDispOff);
    }

    // -- OPTIONS -- //

    protected function RenderEditor() {
	$out = '<input type=checkbox name="'
	  .$this->NameSpec()
	  .'" '
	  .($this->ValueNative()?' checked':'')
	  .$this->RenderAttr().'>';
	return $out;
    }
}