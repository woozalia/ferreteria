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
      RETURNS: array of record values, SQL-ready to update or insert
    */
    protected function RecordValues_SQL() {
	$arI = $this->RecordValues();
	foreach ($arI as $key => $val) {
	    $sql = SQLValue($val);
	    $arO[$key] = $sql;
	}
	return $arO;
    }
    /*----
      RULE: Call this before attempting to read data
    */
    public function LoadRecord() {
	$this->RecordValues($this->RecordsObject()->Values());
	$this->Set_KeyString_loaded($this->RecordsObject()->KeyValue());
    }
    /*----
      RULE: Call this to store data after changing
    */
    public function SaveRecord() {
	$tbl = $this->RecordsObject()->Table();
	$arUpd = $this->RecordValues_SQL();
	$idUpd = $this->Get_KeyString_toSave();
	if ($idUpd == KS_NEW_REC) {
	    $tbl->Insert($arUpd);
	} else {
	    $tbl->Update_Keyed($arUpd,$idUpd);
	}
    }

    // -- DATA STORAGE -- //
    // ++ DEBUGGING ++ //

    public function DumpValues() {
	echo clsArray::Render($this->RecordValues());
    }

    // -- DEBUGGING -- //
}