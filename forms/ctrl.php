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
    private $oField;

    public function __construct(fcForm $oForm) {
	$this->FormObject($oForm);
    }

    // ++ CONFIG ++ //

    protected function FormObject(fcForm $oForm=NULL) {
    }
    public function FieldObject(fcFormField $oField=NULL) {
	if (!is_null($oField)) {
	    $this->oField = $oField;
	}
	return $this->oField;
    }

    // -- CONFIG -- //
    // ++ ACTIONS ++ //

    abstract public function Render();		// render code to display the control
    abstract public function Receive();	// receive user-entered value for this control

    // -- ACTIONS -- //
}

abstract class fcFormControl_HTML extends fcFormControl {
    private $arTagAttr;
    private $sKey;


    public function TagAttributes(array $arAttr=NULL) {
	if (!is_null($arAttr)) {
	    $this->arTagAttr = $arAttr;
	}
	return $this->arTagAttr;
    }
    protected function NameString($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sTagName = $sName;
	}
	return $this->sTagName;
    }
    // RETURNS: calculated name spec (including parent form keys as needed)
    protected function NameSpec() {
	$oForm = $this->FormObject();
	$sForm = $oForm->KeyString();
	$sForm .= '.'.$this->NameString();	// test this... might need different separator
	if ($oForm->HasParent()) {
	    $oForm = $oForm->ParentObject();
	    $sForm = $oForm->KeyString().'['.$sForm.']';
	}
	return $sForm;
    }

    // ++ RENDERING ++ //

    public function Render() {
	$out = '<input name="'
	  .$this->NameOut()
	  .'" value="'
	  .$this->RenderValue().'"'
	  .$this->RenderAttr().'>';
	return $out;
    }
    public function RenderValue() {
	return htmlspecialchars($this->FieldObject()->Value_show());
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

