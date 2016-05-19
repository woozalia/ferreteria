<?php
/*
  FILE: form-data.php - manages display of a recordset
  LIBRARY: ferreteria: forms
  PURPOSE: Understands how to display a single record from a database
  DEPENDS: ctrl.php, form.php, ferreteria db
  HISTORY:
    2015-03-30 starting from scratch
    2016-02-23 Combining constructor parameters - get $sName from $rs->Table()->ActionKey().
      Descendants can override this by calling $this->NameString() directly.
      Possibly fcForm_DB should be renamed fcForm_Records or similar... later.
*/

class fcForm_DB extends fcForm_keyed {

    // ++ SETUP ++ //

    public function __construct(clsRecs_abstract $rs) {
	$this->InitVars();
	$sName = $rs->Table()->ActionKey();
	$this->NameString($sName);
	$this->RecordsObject($rs);
    }

    // -- SETUP -- //
    // ++ SERVICES ++ //

    /*----
      ACTION: Takes data in a format suitable for data storage and sanitizes/quotes it for use in
	actual storage commands (typically SQL).
    */
    public function CookRawValue($val) {
	$db = $this->RecordsObject()->Table()->Engine();
	return $db->SanitizeAndQuote($val);
    }

    // -- SERVICES -- //
    // ++ CONFIGURATION ++ //

    private $rs;
    protected function RecordsObject(clsRecs_abstract $rs=NULL) {
	if (!is_null($rs)) {
	    $this->rs = $rs;
	}
	return $this->rs;
    }

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
		$oField->StorageObject()->SetValue($val);
	    }
	}
    }
    protected function RecordValues_asSQL_get() {
	$arF = $this->FieldArray();
	foreach ($arF as $key => $oField) {
	    if ($oField->ShouldWrite()) {
		$arO[$key] = $oField->StorageObject()->GetValue();
	    }
	}
	return $arO;
    }
    /*----
      ACTION: loads data from the Recordset object
      RULE: Call this before attempting to read data
    */
    public function LoadRecord() {
	$rc = $this->RecordsObject();
	if ($rc->IsNew()) {
	    throw new exception('Internal error: trying to load nonexistent record ID='.$rc->KeyString());
	}
	$ar = $rc->Values();
	$this->RecordValues_asSQL_set($ar);
	$idRec = $rc->IsNew()?KS_NEW_REC:$rc->KeyValue();
	$this->Set_KeyString_loaded($idRec);
    }
    /*----
      RULE: Call this to store data after changing
      INPUT:
	$this->RecordValues_asNative_get(): main list of values to save
	$arUpd: array of additional values to save, in native format
    */
    public function SaveRecord(array $arUpd) {
	$rc = $this->RecordsObject();
	$sqlIDFilt = $rc->SelfFilter();
	$arUpd = $this->ProcessIncomingRecord($arUpd);
	$this->RecordValues_asNative_set($arUpd);
	$arUpd = $this->RecordValues_asSQL_get();
	$tbl = $this->RecordsObject()->Table();
	$idUpd = $this->Get_KeyString_toSave();
	if ($idUpd == KS_NEW_REC) {
	    $id = $tbl->Insert($arUpd);
	} else {
	    //$tbl->Update_Keyed($arUpd,$idUpd);
	    /*
	    if ($idUpd = $this->RecordsObject()->KeyString()) {
		$rc = $this->RecordsObject();
	    } else {
		$rc = $tbl->GetItem($idUpd);	// not tested; using $sqlIDFilt might work better
	    }//*/
	    $rc->Update($arUpd,$sqlIDFilt);
	    /* Debugging
	    echo '<b>CLASS</b>: '.get_class($rc).'<br>';
	    echo "<b>ID FILT</b>: $sqlIDFilt<br>";
	    echo '<b>SQL FOR UPDATE</b>: '.$rc->SQL_forUpdate($arUpd,$sqlIDFilt).'<br>';
	    echo '<b>FINAL SQL</b>: '.$rc->sqlExec.'<br>';
	    */
	    $id = $idUpd;
	}
	$sErr = $tbl->Engine()->getError();
	if (!empty($sErr)) {
	    $this->AddMessage('<b>Error</b>: '.$sErr);
	    $this->AddMessage('<b>SQL</b>: '.$tbl->sqlExec);
	}
	return $id;
    }
    /*----
      ACTION: copy Field values to Recordset
      USAGE: descendent classes that do specialized field calculations
    */
    protected function StoreRecord() {
	$ar = $this->RecordValues_asNative_get();
	$this->RecordsObject()->Values($ar);
    }
    /*----
      PURPOSE: in case any data massaging is needed before saving values
	Descendants can override this to calculate values to save.
	Array is incoming form data for a single record in native format,
    */
    protected function ProcessIncomingRecord(array $ar) {
	return $ar;	// by default, do nothing
    }

    // -- DATA STORAGE -- //
    // ++ DEBUGGING ++ //

    public function DumpValues() {
	echo clsArray::Render($this->RecordValues());
    }

    // -- DEBUGGING -- //
}