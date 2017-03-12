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

    public function __construct(fcRecord_keyed_single $rs) {
	$this->InitVars();
	$sName = $rs->GetActionKey();
	$this->NameString($sName);
	$this->SetRecordsObject($rs);
    }

    // -- SETUP -- //
    // ++ SERVICES ++ //

    /*----
      ACTION: Takes data in a format suitable for data storage and sanitizes/quotes it for use in
	actual storage commands (typically SQL).
    */
    public function CookRawValue($val) {
	if (is_null($val)) {
	    $sql = 'NULL';
	} else {
	    $db = fcApp::Me()->GetDatabase();
	    $sql = $db->Sanitize_andQuote($val);
	}
	return $sql;
    }

    // -- SERVICES -- //
    // ++ CONFIGURATION ++ //

    private $rs;
    protected function RecordsObject(fcRecord_keyed_single $rs=NULL) {
	throw new exception('2017-01-15 Call SetRecordsObject() or GetRecordsObject().');
    }
    protected function SetRecordsObject(fcRecord_keyed_single $rs) {
	$this->rs = $rs;
	//echo 'RECORD:'.fcArray::Render($rs->GetFieldValues());
	//throw new exception('2017-01-15 Debugging: How is this called?');
    }
    protected function GetRecordsObject() {
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
	throw new exception('DEPRECATED (as of 2017-01-11); call either RecordValues_asSQL_set() or RecordValues_asSQL_get().');

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
	$rc = $this->GetRecordsObject();
	
	if ($rc->IsNew()) {
	    throw new exception('Internal error: trying to load nonexistent record ID='.$rc->KeyString());
	}
	$ar = $rc->GetFieldValues();
	$this->RecordValues_asSQL_set($ar);
	$idRec = $rc->IsNew()?KS_NEW_REC:$rc->GetKeyValue();
	$this->Set_KeyString_loaded($idRec);
    }
    /*----
      RULE: Call this to store data after changing
      INPUT:
	$this->RecordValues_asNative_get(): main list of values to save
	$arNat: array of additional values to save, in native format
      HISTORY:
	2016-06-12
	  * Changed INSERT code so it uses rc->SetValues(native) instead of tbl->Insert(native)
	  * Changed UPDATE code so it uses rc->SetValues(native) instead of something complicated
	2016-10-11
	  * Changed INSERT code to use tbl->Insert(storage)
	  * TODO changed UPDATE code to use rc->Update(storage)
	2016-10-18 When saving a single Session record (in WorkFerret), Save() is expecting
	  SQL values. Earlier, I had decided it needed to receive Field objects -- but I can't
	  find the implementation of Save() which is expecting that, so switching back (for now).
    */
    public function SaveRecord(array $arNat) {
	$rc = $this->GetRecordsObject();
	//$sqlIDFilt = $rc->GetSelfFilter();
	$arNat = $this->ProcessIncomingRecord($arNat);
	$this->RecordValues_asNative_set($arNat);
	
	$arSave = $this->RecordValues_asStorageSane_get();

	$idUpd = $this->Get_KeyString_toSave();

	// 2016-10-12 new version
	if ($idUpd == KS_NEW_REC) {
	    // Make sure recordset's ID field indicates NEW, so it will INSERT instead of UPDATE:
	    $rc->SetKeyValue(NULL);
	}
	if (method_exists($rc,'Save')) {
	    $rc->Save($arSave);
	} else {
	    throw new exception(
	      'Ferreteria caller error: class "'
	      .get_class($rc)
	      .'" must use trait "ftSaveableRecord".'
	      );
	}
	//echo "SQL={$rc->sql}<br>";

	$db = fcApp::Me()->GetDatabase();
	$sErr = $db->ErrorString();
	//$tbl = $this->GetRecordsObject()->GetTableWrapper();	// seems likely there's another way to get the executed SQL
	if (!empty($sErr)) {
	    $oPage = fcApp::Me()->GetPageObject();
	    $oPage->AddErrorMessage(
	      '<b>Error</b>: '.$sErr.'<br>'
	      .'<b>SQL</b>: '.$rc->sql
	    );
	    //throw new exception('How do we get here?');
	    //die('THERE WAS AN ERROR');
	    
	    /*
	    $this->AddMessage('<b>Error</b>: '.$sErr);
	    $this->AddMessage('<b>SQL</b>: '.$rc->sql);	// not sure if $rc->sql will work for $rc->Save()... but it kinda *should*
	    die('YES THERE WERE ERRORS');
	    */
	}
	return $db->CreatedID();
    }
    /*----
      ACTION: copy Field values to Recordset
      USAGE: descendent classes that do specialized field calculations
    */
    protected function StoreRecord() {
	$ar = $this->RecordValues_asNative_get();
	$this->GetRecordsObject()->SetFieldValues($ar);
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
	echo fcArray::Render($this->RecordValues());
    }

    // -- DEBUGGING -- //
}

// USED BY: fcForm_blob
class fcBlobField {
    private $ar;
    public function ClearArray() {
	$this->ar = array();
    }
    public function SetArray(array $ar) {
	$this->ar = $ar;
    }
    public function GetArray() {
	return $this->ar;
    }
    public function MergeArray(array $ar) {
	$this->ar = fcArray::Merge($this->ar,$ar);
    }
    public function SetString($s) {
	if (is_string($s)) {
	    $v = unserialize($s);
	    if ($v === FALSE) {
		throw new exception("Received a non-deserializable string: [$s].");
	    }
	    $this->SetArray($v);
	} elseif (is_null($s)) {
	    $this->ClearArray();
	} else {
	    // NULL is okay, but anything besides string or NULL is not.
	    throw new exception('Received a non-string, non-NULL value of type ['.gettype($s).'].');
	}
    }
    public function GetString() {
	return serialize($this->GetArray());
    }
    public function SetValue($sName,$sValue) {
	$this->ar[$sName] = $sValue;
    }
    public function GetValue($sName) {
	return fcArray::Nz($this->ar,$sName);
    }

    // ++ DEBUGGING ++ //

    public function Render() {
	return fcArray::Render($this->GetArray());
    }

    // -- DEBUGGING -- //

}

/*::::
  PURPOSE: form that serializes itself into a text blob
*/
class fcForm_blob extends fcForm {

    // ++ SETUP ++ //

    public function __construct($sName,fcBlobField $oBlobField) {
	parent::__construct($sName);
	$this->BlobObject($oBlobField);
    }

    private $oBlobField;
    protected function BlobObject($o=NULL) {
	if (!is_null($o)) {
	    $this->oBlobField = $o;
	}
	return $this->oBlobField;
    }

    // -- SETUP -- //
    // ++ DATA I/O ++ //

    // ACTION: Save form fields to blob data
    public function Save() {
	$arBlob = $this->RecordValues_asNative_get();
	$this->BlobObject()->SetArray($arBlob);
    }
    // ACTION: Load form fields from blob data
    public function Load() {
	$arBlob = $this->BlobObject()->GetArray();
	$this->RecordValues_asNative_set($arBlob);
    }

    // -- DATA I/O -- //
    // ++ REQUIRED ++ //

    /*----
      NOTE: This is a minor kluge, in that it's not really returning a DataSet
	but just an object that has a SetValue(key,value) method. Fortunately,
	the blob object does.
    */
    protected function RecordsObject() {
	return $this->BlobObject();
    }

    // -- REQUIRED -- //
    // ++ DEBUGGING ++ //

    public function Render() {
	return $this->BlobObject()->Render();
    }

    // ++ DEBUGGING ++ //

}