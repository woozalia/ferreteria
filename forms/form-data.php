<?php
/*
  FILE: form-data.php - manages display of a recordset
  LIBRARY: ferreteria: forms
  PURPOSE: Understands how to display a single record from a database
  DEPENDS: ctrl.php, form.php, ferreteria db
  HISTORY:
    2015-03-30 starting from scratch
*/

class fcForm_DB extends fcForm_keyed {
    private $oRecs;

    // ++ SETUP ++ //

    public function __construct($sName,clsRecs_abstract $oRecs) {
	parent::__construct($sName);
	$this->RecordsObject($oRecs);
    }

    // -- SETUP -- //
    // ++ CONFIGURATION ++ //

    protected function RecordsObject(clsRecs_abstract $oRecs=NULL) {
	if (!is_null($oRecs)) {
	    $this->oRecs = $oRecs;
	}
	return $this->oRecs;
    }
/*    public function KeyString($sKey=NULL) {
	if (!is_null($sKey)) {
	    $sKeyName = $this->RecordsObject()->Table()->KeyName();
	}
	$sKey = $this->RecordsObject()->KeyString();
	return $sKey;
    }
    public function HasKey() {
	return method_exists($this->RecordsObject(),'KeyString');
    }*/

    // -- CONFIGURATION -- //
    // ++ DATA STORAGE ++ //

    /*----
      ACTION: get or set array of record values in SQL format
      NOTE: ONLY sets values for which a control exists
      RETURNS: array of record values, SQL-ready to update or insert
    */
    protected function RecordValues_asSQL(array $arSQL=NULL) {
	if (!is_null($arSQL)) {
	    foreach ($arSQL as $key => $val) {
		if ($this->ControlExists($key)) {
		    $oField = $this->FieldObject($key);
		    $oField->SetValueSQL($val);
		}
	    }
	}
	$arI = $this->RecordValues_asNative();
	foreach ($arI as $key => $val) {
	    $oField = $this->FieldObject($key);
	    $oField->ValueNative($val);
	    $arO[$key] = $oField->ValueSQL();
	}
	return $arO;
    }
    /*----
      RULE: Call this before attempting to read data
    */
    public function LoadRecord() {
	$this->RecordValues_asSQL($this->RecordsObject()->Values());
	$this->Set_KeyString_loaded($this->RecordsObject()->KeyValue());
    }
    /*----
      RULE: Call this to store data after changing
    */
    public function SaveRecord() {
	$tbl = $this->RecordsObject()->Table();
	$arUpd = $this->RecordValues_asSQL();
	$idUpd = $this->Get_KeyString_toSave();
	if ($idUpd == KS_NEW_REC) {
	    $tbl->Insert($arUpd);
	    //echo 'SQL: '.$tbl->sqlExec; die();
	} else {
	    $tbl->Update_Keyed($arUpd,$idUpd);
	    //echo 'SQL: '.$tbl->sqlExec; die();
	}
    }

    // -- DATA STORAGE -- //
    // ++ DEBUGGING ++ //

    public function DumpValues() {
	echo clsArray::Render($this->RecordValues());
    }

    // -- DEBUGGING -- //
}