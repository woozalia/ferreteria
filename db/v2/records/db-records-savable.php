<?php
/*
  HISTORY:
    2016-10-31 Adapting from db.v1 to db.v2.
    2018-02-24 GetStorableValues_Changed() wasn't sanitizing values. Fixed.
*/

trait ftSaveableRecord {

    // ++ CHANGE TRACKING ++ //

    private $arMod;
    protected function TouchField($sKey) {
	$this->arMod[] = $sKey;
    }
    /*----
      RETURNS: array of touched fields (just the field names, without values)
    */
    protected function TouchedArray() {
	return $this->arMod;
    }

    // -- CHANGE TRACKING -- //
    // ++ NATIVE VALUES ++ //

    public function ChangeNativeValues(array $ar) {
	throw new exception('2017-09-15 Call ChangeFieldValues() instead.');
    }
    /*----
      ACTION: modify the local field values from the given array, AND keep track of what has changed
	Same as SetFieldValues() but also touches each field that is being set.
      FUTURE: should we only Touch if the new value is different?
    */
    public function ChangeFieldValues(array $ar) {
	foreach ($ar as $key => $value) {
	    $this->SetFieldValue($key,$value);
	    $this->TouchField($key);
	}
    }
    /*----
      ACTION: Takes list of fields which have been changed, fetches the corresponding values, and returns an array of both.
      RETURNS: array of key-value pairs
      PUBLIC because Form objects need to access it.
    */
    public function GetStorableValues_Changed() {
	$arTouch = $this->TouchedArray();	// just a list; no values
	$arOut = NULL;
	$db = $this->GetConnection();
	if (is_array($arTouch)) {
	    $sKey = $this->GetTableWrapper()->GetKeyName();
	    foreach ($arTouch as $sField) {
		if ($sField != $sKey) {		// don't write to the key field
		    $v = $this->GetFieldValue($sField);
		    $sqlVal = $db->SanitizeValue($v);
		    $arOut[$sField] = $sqlVal;
		}
	    }
	}
	return $arOut;
    }

    /*====
      FX() GROUP: GetStorableValues_to{$op}()
      RETURNS array of values to use for db writes, formatted for $this->Insert() (i.e. SQL-format.
      PURPOSE: originally so it can be overridden to calculate specific fields
	when a record is updated, e.g. set WhenCreated or WhenEdited
	but this is now probably better accomplished with Get{$op}StorageOverrides() in most cases.
      HISTORY:
	2016-04-19 This couldn't have been working reliably until now, because the values
	  were not being sanitize-and-quoted. (Now they are.)
	2017-05-25 API changes:
	  * Renamed from {$op}Array() to Get{$op}Values() and then Get{$op}NativeValues()
	  * Removed array parameter
	  * Made public so Form object can access (replacing functionality of $this->Save())
	2017-09-16 Renamed from Get{$op}NativeValues to GetStorableValues_to{$op}
      PUBLIC so Form object can use it when saving received values to database
    */
      /*----
	DEFAULT
	VERSION: {$op} = INSERT
	RETURNS: array of values formatted for $this->Insert()
	HISTORY:
	  2016-10-16 Now defaults to ChangeArray() instead of UpdateArray().
      */
    public function GetStorableValues_toInsert() {
	return $this->GetStorableValues_Changed();
    }
      /*----
	DEFAULT
	VERSION: $op = UPDATE
	RETURNS: array of values formatted for $this->Update()
	HISTORY:
	  2016-10-16 Calculations moved to ChangeArray().
      */
    public function GetStorableValues_toUpdate() {
	return $this->GetStorableValues_Changed();
    }

    //====

    // -- NATIVE VALUES -- //
    // ++ STORAGE VALUES ++ //

    /*----
      FX(): GetInsertStorageOverrides(), GetUpdateStorageOverrides()
      INPUT: $ar (optional) = list of key-value pairs (values, not SQL-formatted) to be included
      RETURNS array of record values to set, formatted for $this->Insert()/Update().
      PURPOSE: Mainly so it can be overridden to calculate specific fields
	when a record is inserted or updated.
      FORMAT: Sanitized SQL; can include expressions like "NOW()".
	Default is just to return all changed values, sanitized.
      TODO:
      * Find out if anyone is using the $ar parameter.
      * This won't work with keyless or multi-key tables because of GetKeyName(). Fix when needed.
      HISTORY:
	2017-05-24 Sanitizing is insufficient to make this data reliably writable; form storage objects must do the translation.
	  Removing Sanitize_andQuote().
	2017-05-25 Renamed for clarity: call GetChangedStorableValues() (was GetChangedNativeValues), with no argument.
	  * Possibly we also need to write GetChangeStorageValues()...?
	2017-06-15 
	  * Finally sorted things out, I hope. We now have Get*NativeValues() and Get*StorageValues().
	  * eliminated $ar parameter
	  * and then (I guess?) renamed from Get*StorageValues() to Get*StorageOverrides().
    */
    
    /*----
      STUB
    */
    public function GetInsertStorageOverrides() {
	return array();
    }
    /*----
      STUB
    */
    public function GetUpdateStorageOverrides() {
	return array();
    }

    // -- OVERRIDEABLE -- //
    // ++ OVERRIDES ++ //
    
    public function SetFieldValue($sKey,$val) {
	parent::SetFieldValue($sKey,$val);
	$this->TouchField($sKey);
    }
    /*----
      HISTORY:
	2017-05-28 This *was* "public function Insert(...)", with the note:
	  "PUBLIC OVERRIDE so form can save new records"
	  ...but it turns out that this conflicts with other traits in some way
	  that I don't know how to resolve, so I've given the public version
	  a different name (PublicInsert()). That should make it easier to find
	  usages.
	2017-08-30 It turns out that actually, it's good that we have a different name
	  to use when external entities (usually forms) want to insert things, because
	  sometimes we don't want to just stick the data in a record. Wiki Nodes need
	  to be able to create a complex set of records in different tables in response
	  to an insert request from a form, for example.
	  
	  Therefore: renaming this from PublicInsert() to FormInsert(), and also
	    creating FormUpdate().
    */
    public function FormInsert(array $arRow) {
	return parent::Insert($arRow);
    }
    /*----
      HISTORY:
	2017-08-30 created to be overridable by classes that need to do more complicated things
	  than just sticking the data in a single record
    */
    public function FormUpdate(array $arRow) {
	return parent::Update($arRow);
    }

    // -- OVERRIDES -- //
    // ++ DEPRECATED ++ //

    /*----
      INPUT: $arSave (optional) is a list of key-sqlvalue pairs to include in the save.
      HISTORY:
	2016-06-04 I'm not sure why this was private; it seems like a good general-use function.
	  I specifically needed it to be public when loading up Customer Address records via either
	  of two different methods. I *could* have written public SaveThis() and SaveThat() methods,
	  but that would have increased the amount of special coding needed for This and That yet again.
	2016-10-12 Added $arSave argument so forms could inject additional SQL-format data to save.
	  Isn't there some other way to do this? Can't think of it.
	2017-04-26 I'm pretty sure this was not in a trait until sometime in the last few months.
	  Not sure exactly where it was before that, or exactly when it was moved.
	2017-05-24 This now seems unnecessary; functionality should be in Form object. Commented out.
	2018-02-19 ...but apparently shopping carts need it, so re-enabling.
	2018-02-21 After much documentation, I suddenly realized this is a perfect opportunity to get rid
	  of the $arSave input parameter.
    */
    public function Save() {
	//$out = NULL;
	//$sql = NULL;	// for debugging
	if ($this->IsNew()) {
	    $ar = $this->GetStorableValues_toInsert();
	    if (is_array($ar)) {
		$ar = array_merge($ar,$this->GetInsertStorageOverrides());
		$this->Insert($ar);
	    }
	} else {
	    $ar = $this->GetStorableValues_toUpdate();
	    if (is_array($ar)) {
		$ar = array_merge($ar,$this->GetUpdateStorageOverrides());
		$this->Update($ar);
	    }
	}
    }
    /*
      USAGE NOTE:
	* GetStorableValues_to*() generates a list of values from scratch
	* Get*StorageOverrides() is just a list of values to override the defaults
      TODO: Add the above to the Ferreteria usage documentation.
    */
    protected function InsertArray($ar=NULL) {
	throw new exception('2017-05-25 Renamed for clarity: call GetStorableValues_toInsert() or GetInsertStorageOverrides(), with no argument.');
    }
    protected function UpdateArray($ar=NULL) {
	throw new exception('2017-05-25 Renamed for clarity: call GetStorableValues_toUpdate() or GetUpdateStorageOverrides(), with no argument.');
    }
    protected function ChangeArray($ar=NULL) {
	throw new exception('2017-05-25 Renamed for clarity: call GetStorableValues_Changed(), with no argument, unless you need SQL.');
	$arTouch = $this->TouchedArray();	// just a list; no values
	if (is_array($arTouch)) {
	    //$db = $this->GetConnection();
	    $sKey = $this->GetTableWrapper()->GetKeyName();
	    foreach ($arTouch as $sField) {
		if ($sField != $sKey) {		// don't write to the key field
		    $ar[$sField] = $this->GetFieldValue($sField);
		}
	    }
	}
	return $ar;
    }

    // -- DEPRECATED -- //
}
