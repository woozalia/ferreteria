<?php
/*
  FILE: form.php - form outer classes (the actual form)
  LIBRARY: ferreteria: forms
  PURPOSE: manages form rows
  DEPENDS:
  HISTORY:
    2015-03-29 starting from scratch
*/

class fcForm {
    private $oForm;

    // ++ CONFIG ++ //

    public function ParentObject() {
	return $this->FormObject();
    }
    public function HasParent() {
	return !empty($this->oForm);
    }
    protected function FormObject($oForm=NULL) {
	if (is_null($oForm)) {
	    $this->oForm = $oForm;
	}
	return $this->oForm;
    }

    // -- CONFIG -- //
}

class fcForm_keyed extends fcForm {
    private $sKey;

    // ++ CALCULATIONS ++ //

    public function KeyString($sKey=NULL) {
	if (is_null($sKey)) {
	    $this->sKey = $sKey;
	}
	return $this->sKey;
    }

    // -- CALCULATIONS -- //
}