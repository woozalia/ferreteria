<?php
/*
  FILE: form-rec.php - single record in a form
  LIBRARY: ferreteria: forms
  PURPOSE: Understands how to display a single record
  DEPENDS: ctrl.php
  HISTORY:
    2015-03-29 starting from scratch
*/

class fcSubForm extends fcForm_keyed {

    // ++ SETUP ++ //

    public function __construct(fcForm $oForm) {
	$this->FormObject($oForm);
    }

    // -- SETUP -- //
}