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
    2017-06-06 In fcForm_DB constructor, moved InitVars() to last line of constructor so it can make use of RecordsObject.
*/

/*iiii
  IMPLEMENTED BY: ftSaveableRecord
  HISTORY:
    2017-05-25 Tentatively, removing Save(). It may need to be replaced with GetChangeValues() or something.
*/
interface fiEditableRecord {
    function GetActionKey();
    function IsNew();
    function SetFieldValues(array $arVals);
    function GetFieldValues();
    function ChangeFieldValues(array $arVals);
    function SetKeyValue($id);
    function GetKeyValue();
    //function Save($arSave=NULL);
}

class fcForm_DB extends fcForm_keyed {

    // ++ SETUP ++ //

//    public function __construct(fcRecord_keyed_single $rs) {
    public function __construct(fiEditableRecord $rs) {
	$sName = $rs->GetActionKey();
	$this->NameString($sName);
	$this->SetRecordsObject($rs);
	$this->InitVars();
    }

    // -- SETUP -- //
    // ++ SERVICES ++ //

    /*----
      ACTION: Takes data in a format suitable for data storage and sanitizes/quotes it for use in
	actual storage commands (typically SQL).
      HISTORY:
	2017-05-25 SanitizeValue() (was Sanitize_andQuote()) now handles NULL properly, so no need to do it here.
    */
    public function CookRawValue($val) {
    /*
	if (is_null($val)) {
	    $sql = 'NULL';
	} else {*/
	    $db = fcApp::Me()->GetDatabase();
	    $sql = $db->SanitizeValue($val);
//	}
	return $sql;
    }

    // -- SERVICES -- //
    // ++ CONFIGURATION ++ //

    private $rs;
    protected function RecordsObject(fcRecord_keyed_single $rs=NULL) {
	throw new exception('2017-01-15 Call SetRecordsObject() or GetRecordsObject().');
    }
    protected function SetRecordsObject(fiEditableRecord $rs) {
	$this->rs = $rs;
    }
    protected function GetRecordsObject() : fiEditableRecord {
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
    
    // TODO: asSQL should probably be asStorage
    
    /*----
      ACTION: set internal data from array of SQL-format values
    */
    protected function RecordValues_asSQL_set(array $arSQL) {
	$arFlds = $this->GetFieldArray();
	foreach ($arSQL as $key => $val) {
	    if (array_key_exists($key,$arFlds)) {
		// ignore data fields for which there is no Field object
		$oField = $arFlds[$key];
		$oField->StorageObject()->SetValue($val);
	    }
	}
    }
    protected function RecordValues_asSQL_get() {
	$arF = $this->GetFieldArray();
	foreach ($arF as $key => $oField) {
	    if ($oField->ShouldWrite()) {
		$arO[$key] = $oField->StorageObject()->GetValue();
	    }
	}
	return $arO;
    }
    /*----
      PURPOSE: set record fields from all passed values
      ACTION: For each element of $arVals:
	* get the corresponding field object (error if not found)
	* set its value
	* set the corresponding record value
      INPUT: $arVals = array[field name, native-format value]
      OUTPUT: Internal - (1) record array, (2) fields in native format
      USAGE:
	* when loading record from db into memory (unknown fields are discarded)
	* when saving edited record from form into db
      HISTORY:
	2016-03-03 Now writes new values back to memory-record object as well.
	  This is needed especially when editing the value of a key, so that
	  we can redirect to the record's new home. Using the old values will
	  attempt to pull up a record that doesn't exist.
	2016-03-25 Empty fields in new records caused a code-trap because
	  setting $rc->Value() with a NULL $val means it tries to read
	  from a nonexistent record. I've replaced ->Value() with ->SetValue(),
	  which I then had to write...
	2017-05-26 This *was* writing the *storage* values back to local, which is wrong.
	  Local is native-format. Fixed.
	2017-06-12 Moved from fcForm in form.php to fcForm_DB in form-data.php
    */
    protected function SetRecordValues_asNative(array $arVals=NULL) {
	$arFlds = $this->GetFieldArray();
	$rc = $this->GetRecordsObject();
	echo 'SUPPOSEDLY NATIVE VALUES:'.fcArray::Render($arVals);
	foreach ($arVals as $key => $val) {
	    if (array_key_exists($key,$arFlds)) {
		// ignore data fields for which there is no Field object
		$oField = $arFlds[$key];
		// set memory-field in native format
		$oField->SetValue($val,TRUE);
		/*
		// get storage format
		$sStor = $oField->StorageObject()->GetValueRaw();
		// save storage format to memory-record-object's field
		$rc->SetFieldValue($key,$sStor);
		*/
		$rc->SetFieldValue($key,$val);
	    } else {
		echo "WARNING: field [$key] has no field object; cannot set to [$val].<br>";
	    }
	}
    }
    /*----
      PURPOSE: retrieve all values in record (native format)
      USAGE: not sure; was being used incorrectly (2016-10-11)
      PUBLIC for vcCartDataManager.UpdateBlob() - maybe there's a better way?
      HISTORY:
	2017-06-12 Moved from fcForm in form.php to fcForm_DB in form-data.php
    */
    public function GetRecordValues_asNative() {
	$arFlds = $this->GetFieldArray();
	$arOut = NULL;
	foreach ($arFlds as $key => $oField) {
	    $arOut[$key] = $oField->GetValue();
	}
	return $arOut;
    }
    /*----
      ACTION: loads data from the Recordset object
      RULE: Call this before attempting to read data
    */
    public function LoadRecord() {
	$rc = $this->GetRecordsObject();
	
	if ($rc->IsNew()) {
	    throw new exception('Ferreteria internal error: trying to load nonexistent record.');
	}
	$ar = $rc->GetFieldValues();
	if (is_null($ar)) {
	    throw new exception('Ferreteria internal error: failed to get field values.');
	}
	$this->RecordValues_asSQL_set($ar);
	$idRec = $rc->IsNew()?KS_NEW_REC:$rc->GetKeyValue();
	$this->Set_KeyString_loaded($idRec);
    }
    /*----
      RULE: Call this to store data after changing
      INPUT:
	$this->GetRecordValues_asNative(): main list of values to save
	$arStor: array of additional values to save, in storage format
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
	2017-05-26 Rearranged some things with regard to which formats are returned by various functions,
	  and also eliminating the Save() function on the recordset.
	2017-06-15 Complete rewrite; changed some stuff in ftSaveableRecord too.
	2017-09-15 Turns out we need to receive the values in storage format, not native format,
	  because this is how the fields are formatted.
	2017-09-17 Revising yesterday and today; apparently working now.
      TODO:
	2017-05-26 Conversion to/from storage format really ought to be a property of the recordset, but this requires
	  some additional low-level changes probably best left for Ferreteria3.
    */
    public function SaveRecord(array $arStor) {
	$rc = $this->GetRecordsObject();
	$idUpd = $this->Get_KeyString_toSave();
	$rc->ChangeFieldValues($arStor);	// update the record from the form input, and flag changes
	
	$idUpd = $this->Get_KeyString_toSave();

	if ($idUpd == KS_NEW_REC) {
	    // creating a new record
	    $arStorChg = $rc->GetStorableValues_toInsert();
	    $this->RecordValues_asSQL_set($arStorChg);	// set form fields from what needs to be inserted
	    $arStoreFinal = $this->RecordValues_asStorageSane_get();	// get storage-format values for all form fields
	    if (is_array($arStoreFinal)) {				// if there's anything to save...
		$arStoreOv = $rc->GetInsertStorageOverrides();		// get any class-specific storage overrides
		$arStoreFinal = array_merge($arStoreFinal,$arStoreOv);	// override standard stuff with overrides
		$rc->FormInsert($arStoreFinal);			// insert with the results
	    }
	} else {
	    // updating an existing record
	    $arStorChg = $rc->GetStorableValues_toUpdate();
	    $this->RecordValues_asSQL_set($arStorChg);	// set form fields from what needs to be updated
	    $arStoreFinal = $this->RecordValues_asStorageSane_get();	// get storage-format values for all form fields
	    if (is_array($arStoreFinal)) {				// if there's anything to save...
		$arStoreOv = $rc->GetUpdateStorageOverrides();		// get any class-specific storage overrides
		$arStoreFinal = array_merge($arStoreFinal,$arStoreOv);		// override standard stuff with overrides
		$rc->FormUpdate($arStoreFinal);				// update with the results
	    }
	}
	
	$db = $rc->GetConnection();
	if (!$db->IsOkay()) {
	    $sErr = $db->ErrorString();
	    $oPage = fcApp::Me()->GetPageObject();
	    $oPage->AddErrorMessage(
	      '<b>Error</b>: '.$sErr.'<br>'
	      .'<b>SQL</b>: '.$rc->sql
	    );
	    //throw new exception('How do we get here?');
	    echo "THERE WAS AN ERROR: $sErr<br><b>SQL</b>: ".$rc->sql
	      .'<br>Storable values, from form:'
	      .fcArray::Render($arStor)
	      .'Storable values to change, from record object:'
	      .fcArray::Render($arStorChg)
	      .'Storable values, overrides from record object:'
	      .fcArray::Render($arStoreOv)
	      .'Storable values - what actually gets stored:'
	      .fcArray::Render($arStoreFinal)
	      ;
	    $sClass = get_class($rc);
	    throw new exception("Ferreteria data error: could not save form data to record in class '$sClass'.");
	    
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
	$ar = $this->GetRecordValues_asNative();
	$this->GetRecordsObject()->SetFieldValues($ar);
    }
    /*----
      PURPOSE: in case any data massaging is needed before saving values
	Descendants can override this to calculate values to save.
	Array is incoming form data for a single record in native format,
    */ /* 2017-06-12 This has not been needed, and at this point is just confusing things.
    protected function ProcessIncomingRecord(array $ar) {
	return $ar;	// by default, do nothing
    } */

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

    public function __construct($sName, $oBlobField) {
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
    public function SaveFields_toBlob() {
	//$arBlob = $this->GetRecordValues_asNative();
	$arBlob = $this->GetFieldArray_toWrite_native();
	$this->BlobObject()->SetArray($arBlob);
    }
    // ACTION: Load form fields from blob data
    public function LoadFields_fromBlob() {
	$arBlob = $this->BlobObject()->GetArray();
	//$this->SetRecordValues_asNative($arBlob);
	$this->SetFieldArray_toWrite_native($arBlob);
    }
    /*----
      NOTE that the base class defines GetFieldArray_toWrite_native().
      HISTORY:
	2018-02-26 written because LoadFields_fromBlob() needs it
    */
    protected function SetFieldArray_toWrite_native(array $ar) {
	foreach ($ar as $key => $val) {
	    $oField = $this->FieldObject($key);
	    $oField->SetValue($val);
	}
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