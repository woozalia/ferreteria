<?php
/*
  FILE: ctrl.php - single control classes
  LIBRARY: ferreteria: forms
  PURPOSE: Understands how to display a field object's value
  DEPENDS: field.php
  HISTORY:
    2015-03-29 starting from scratch
*/

abstract class fcFormControl {
    private $oForm;
    private $oField;

    public function __construct(fcForm $oForm, fcFormField $oField) {
	$this->FormObject($oForm);
	$this->FieldObject($oField);
	$oForm->ControlObject($oField->NameString(),$this);
    }

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
	if ($doEdit) {
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
