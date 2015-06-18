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
	throw new exception('DEPRECATED; call either RecordValues_asSQL_set() or RecordValues_asSQL_get().');

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
      ACTION: set internal data from array of SQL-format values
    */
    protected function RecordValues_asSQL_set(array $arSQL) {
	$arFlds = $this->FieldArray();
	foreach ($arSQL as $key => $val) {
	    if (array_key_exists($key,$arFlds)) {
		// ignore data fields for which there is no Field object
		$oField = $arFlds[$key];
		$oField->SetValueSQL($val);
	    }
	}
    }
    protected function RecordValues_asSQL_get() {
	$arF = $this->FieldArray();
	foreach ($arF as $key => $oField) {
	    //echo "FIELD [$key] XLATES [".$oField->ValueNative().'] AS ['.$oField->ValueSQL().']<br>';
	    $arO[$key] = $oField->ValueSQL();
	}
	return $arO;
    }
    /*----
      ACTION: loads data from the Recordset object
      RULE: Call this before attempting to read data
    */
    public function LoadRecord() {
	$ar = $this->RecordsObject()->Values();
	$this->RecordValues_asSQL_set($ar);
	$this->Set_KeyString_loaded($this->RecordsObject()->KeyValue());
    }
    /*----
      RULE: Call this to store data after changing
      INPUT:
	$this->RecordValues_asNative_get(): main list of values to save
	$arUpd: array of additional values to save, in native format
    */
    public function SaveRecord(array $arUpd) {
	//echo 'SaveRecord:'.clsArray::Render($arUpd);
	$tbl = $this->RecordsObject()->Table();
	$this->RecordValues_asNative_set($arUpd);
	$arUpd = $this->RecordValues_asSQL_get();
	//echo 'FINAL SQL ARUPD:'.clsArray::Render($arUpd);
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