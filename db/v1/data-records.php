<?php
/*
  PURPOSE: classes for handling data recordsets
  HISTORY:
    2013-12-19 split off from data.php
*/

define('KS_NEW_REC','new');	// value to use for key of new records

/*=============
  NAME: clsRecs_abstract -- abstract recordset
    Does not deal with keys.
  NOTE: We have to maintain a local copy of Row because sometimes we don't have a Res object yet
    because we haven't done any queries yet.
  HISTORY:
    2011-11-21 changed Table() from protected to public because Scripting needed to access it
*/
abstract class clsRecs_abstract {
    public $objDB;	// deprecated; use Engine()
    public $sqlMake;	// optional: SQL used to create the dataset -- used for reloading
    public $sqlExec;	// last SQL executed on this dataset
    public $Table;	// public access deprecated; use Table()
    protected $objRes;	// result object returned by Engine
    public $Row;	// public access deprecated; use Values()/Value() (data from the active row)
    private $arMod;	// list of modified fields -- for auto-update

//    public function __construct(clsDatabase $iDB=NULL, $iRes=NULL, array $iRow=NULL) {
    public function __construct(clsDatabase $iDB=NULL) {
	$this->objDB = $iDB;
	$this->InitVars();
    }
    protected function InitVars() {
	$this->objRes = NULL;
	$this->Row = NULL;
	$this->arMod = NULL;
    }
    public function ResultHandler($iRes=NULL) {
	if (!is_null($iRes)) {
	    $this->objRes = $iRes;
	}
	return $this->objRes;
    }
    public function Table(clsTable_abstract $iTable=NULL) {
	if (!is_null($iTable)) {
	    $this->Table = $iTable;
	}
	return $this->Table;
    }
    protected function TouchField($sName) {
	$this->arMod[] = $sName;
    }
    protected function TouchedArray() {
	return $this->arMod;
    }
    /*----
      PURPOSE: for debugging -- quick/easy way to see what data we have
      HISTORY:
	2011-09-24 written for vbz order import routine rewrite
    */
    public function DumpHTML() {
	$out = '<b>Table</b>: '.$this->Table->Name();
	if ($this->hasRows()) {
	    $out .= '<ul>';
	    $this->StartRows();	// make sure to start at the beginning
	    while ($this->NextRow()) {
		$out .= "\n<li><ul>";
		foreach ($this->Row as $key => $val) {
		    $out .= "\n<li>[$key] = [$val]</li>";
		}
		$out .= "\n</ul></li>";
	    }
	    $out .= "\n</ul>";
	} else {
	    $out .= " -- NO DATA";
	}
	$out .= "\n<b>SQL</b>: ".$this->sqlMake;
	return $out;
    }
    public function Engine() {
	if (is_null($this->objDB)) {
	    assert('!is_null($this->Table()); /* SQL: '.$this->sqlMake.' */');
	    return $this->Table()->Engine();
	} else {
	    return $this->objDB;
	}
    }
    public function IsNew() {
	return is_null($this->Values());
    }
    
    // ++ FIELD ACCESS ++ //
    
    //++overloaded++//
    
    /*----
      ACTION: sets/returns associative array of fields/values for the current row
      HISTORY:
	2011-01-08 created
	2011-01-09 actually working; added option to write values
    */
    public function Values(array $iRow=NULL) {
	if (is_array($iRow)) {
	    $this->Row = $iRow;
	    return $iRow;
	} else {
	    return $this->Row;
	}
    }
    /*----
      FUNCTION: Value(name)
      RETURNS: Value of named field
      HISTORY:
	2010-11-19 Created to help with data-form processing.
	2010-11-26 Added value-setting, so we can set defaults for new records
	2011-02-09 replaced direct call to array_key_exists() with call to new function HasValue()
    */
    public function Value($iName,$iVal=NULL) {
	if (is_null($iVal)) {
	    if (!$this->HasValue($iName)) {
		if (is_object($this->Table())) {
		    $htTable = ' from table "'.$this->Table()->Name().'"';
		} else {
		    $htTable = ' from query';
		}
		$strMsg = 'Attempted to read nonexistent field "'.$iName.'"'.$htTable.' in class '.get_class($this);

		echo $strMsg.'<br>';
		echo '<b>Source SQL</b>: '.$this->sqlMake.'<br>';
		echo '<b>Row contents</b>:<pre>'.print_r($this->Values(),TRUE).'</pre>';
		throw new Exception($strMsg);
	    }
	} else {
	    $this->SetValue($iName,$iVal);
	}
	return $this->Row[$iName];
    }
    
    //--overloaded--//
    //++read-only++//

    /*----
      PURPOSE: Like Value() but handles new records gracefully, and is read-only
	makes it easier for new-record forms not to throw exceptions
      RETURNS: Value of named field; if it isn't set, returns $iDefault instead of raising an exception
      HISTORY:
	2011-02-12 written
	2011-10-17 rewritten by accident
    */
    public function ValueNz($iName,$iDefault=NULL) {
	if (array_key_exists($iName,$this->Row)) {
	    return $this->Row[$iName];
	} else {
	    return $iDefault;
	}
    }
    
    //--read-only--//
    //++write-only++//
    
    /*----
      ACTION: only sets the given values in the current row;
	does not clear any missing values.
    */
    public function SetValues(array $arVals) {
	foreach ($arVals as $key => $val) {
	    $this->Row[$key] = $val;
	}
    }
    public function ValuesSet(array $arVals) {
	// deprecated; use SetValues()
	$this->SetValues($arVals);
    }
    // 2016-03-25 Not sure if the whole comparison-and-touch thing should be here.
    public function SetValue($sKey,$val) {
	if (!$this->ValueEquals($sKey,$val)) {
	    $this->Row[$sKey] = $val;
	    $this->TouchField($sKey);
	}
    }
    
    //--write-only--//
    
    // -- FIELD ACCESS -- //
    // ++ FIELD STATUS ++ //

    /*----
      HISTORY:
	2011-02-09 created so we can test for field existence before trying to access
    */
    public function HasValue($iName) {
	if (is_array($this->Row)) {
	    return array_key_exists($iName,$this->Row);
	} else {
	    return FALSE;
	}
    }

    // -- FIELD STATUS -- //
    // ++ FIELD CALCULATIONS ++ //

    // 2016-03-25 Who actually uses this?
    protected function ValueEquals($sName,$val) {
	if ($this->HasValue($sName)) {
	    return $this->Value($sName) == $val;
	} else {
	    return FALSE;
	}
    }
    /*----
      RETURNS: For each record, the value of the given field is
	appended to the output string. The values are separated by commas.
	The resulting string is suitable for use as the target of an "IN" clause.
      ASSUMES: The values do not need to be sanitized or quoted. (If this is
	needed, write a separate method called SQL_ValueList_safe() with a $doQuote
	parameter.
    */
    public function ColumnValues_SQL($sField) {
	$out = NULL;
	if ($this->HasRows()) {
	    while ($this->NextRow()) {
		$s = $this->Value($sField);
		$out .= $s.',';
	    }
	    $out = trim($out,','); // remove trailing comma
	}
	return $out;
    }
    public function ColumnValues_array($sField) {
	$ar = NULL;
	if ($this->HasRows()) {
	    while ($this->NextRow()) {
		$v = $this->Value($sField);
		$ar[] = $v;
	    }
	}
	return $ar;
    }
    
    // -- FIELD CALCULATIONS -- //
    // ++ ACTIONS ++ //
    
    /*----
      FUNCTION: Clear()
      ACTION: Clears Row[] of any leftover data
    */
    public function Clear() {
	$this->Row = NULL;
    }
    /*----
      FUNCTION: ClearValue(field_name)
      ACTION: Sets the given field's value to NULL
	This is useful because Value(field_name,NULL) doesn't do anything.
    */
    public function ClearValue($iName) {
	$this->Row[$iName] = NULL;
    }
    /*----
      USAGE: caller should always check this and throw an exception if it fails.
    */
    private function QueryOkay() {
	if (is_object($this->objRes)) {
	    $ok = $this->objRes->is_okay();
	} else {
	    $ok = FALSE;
	}
	return $ok;
    }
    public function Query($iSQL) {
	$this->objRes = $this->Engine()->engine_db_query($iSQL);
	$this->sqlMake = $iSQL;
	if (!$this->QueryOkay()) {
	    echo '<pre>';
	    throw new exception ('Query failed -- SQL='.$iSQL);
	}
    }
    /*----
      ACTION: Checks given values for any differences from current values
      RETURNS: TRUE if all values are same
    */
    public function SameAs(array $iValues) {
	$isSame = TRUE;
	foreach($iValues as $name => $value) {
	    $oldVal = $this->Row[$name];
	    if ($oldVal != $value) {
		$isSame = FALSE;
	    }

	}
	return $isSame;
    }
    /*-----
      RETURNS: # of rows iff result has rows, otherwise FALSE
      HISTORY:
	2016-03-22 Sometimes when a Ferreteria session is no longer valid, we get a Record
	  object with no Resource object set. I had an exception set to throw whenever that
	  happened, but the problem is difficut to reproduce (so figuring out the root cause
	  will take an inordinate amount of time), so for now I'm just going to return FALSE
	  when that happens, and see if the rest of the code recovers nicely.
    */
    public function hasRows() {
	if (!is_object($this->objRes)) {
	    //throw new exception('Internal error: Resource object not set.');
	    return FALSE;
	}
	$rows = $this->objRes->get_count();
	if ($rows === FALSE) {
	    return FALSE;
	} elseif ($rows == 0) {
	    return FALSE;
	} else {
	    return $rows;
	}
    }
    public function hasRow() {
	return $this->objRes->is_filled();
    }
    public function RowCount() {
	if (is_object($this->ResultHandler())) {
	    return $this->ResultHandler()->get_count();
	} else {
	    // 2016-01-20 Let's just assume that if ResultHandler() is not set, then there wasn't a successful query.
	    // TODO: maybe set an internal status code/message in order to make it easier to figure out what happened.
	    return 0;
	}
    }
    public function RewindRows() {
	if ($this->hasRows()) {
	    $this->objRes->do_rewind();
	    return TRUE;
	} else {
	    return FALSE;
	}
    }
    public function StartRows() {	// DEPRECATED - use RewindRows()
	return $this->RewindRows();
    }
    public function FirstRow() {
	if ($this->StartRows()) {
	    return $this->NextRow();	// get the first row of data
	} else {
	    return FALSE;
	}
    }
    /*=====
      ACTION: Fetch the next row of data into $this->Row.
	If no data has been fetched yet, then fetch the first row.
      RETURN: TRUE if row was fetched; FALSE if there were no more rows
	or the row could not be fetched.
    */
    public function NextRow() {
	if (!is_object($this->objRes)) {
	    throw new exception('Result object not loaded');
	}
	$this->Row = $this->objRes->get_next();
	return $this->hasRow();
    }
}
class clsRecs_generic extends clsRecs_abstract {
}
/*=============
  NAME: clsRecs_keyed_abstract -- abstract recordset for keyed data
    Adds abstract and concrete methods for dealing with keys.
*/
abstract class clsRecs_keyed_abstract extends clsRecs_abstract {
    // ABSTRACT methods
    abstract public function SelfFilter();
    abstract public function KeyString();
    abstract public function SQL_forUpdate(array $iSet,$iWhere);
    abstract public function SQL_forUpdateMe(array $iSet);
    abstract public function SQL_forMake(array $iSet);

    protected function KeyName() {
	$sKeyName = $this->Table()->KeyName();
	assert('!empty($sKeyName); /* TABLE: '.$this->Table->Name().' */');
	assert('is_string($sKeyName); /* TABLE: '.$this->Table->Name().' */');
	return $sKeyName;
    }
    protected function UpdateArray() {
	$arUpd = NULL;
	$arTouch =$this->TouchedArray();
	if (is_array($arTouch)) {
	    foreach ($arTouch as $sField) {
		$arUpd[$sField] = $this->Value($sField);
	    }
	}
	return $arUpd;
    }
    protected function Save() {
	$arSave = $this->UpdateArray();
	if (is_array($arSave)) {
	    if ($this->IsNew()) {
		$out = $this->Table()->Insert($arSave);
	    } else {
		$out = $this->Update($arSave);
	    }
	} else {
	    // nothing to do
	    $out = NULL;
	}
	return $out;
    }
    /*-----
      ACTION: Saves the data in $iSet to the current record (or records filtered by $iWhere)
      HISTORY:
	2010-11-20 Calculation now takes place in SQL_forUpdate()
	2010-11-28 SQL saved to table as well, for when we might be doing Insert() or Update()
	  and need a single place to look up the SQL for whatever happened.
	2013-07-17 Modified to handle SQL_forUpdate()/SQL_forUpdateMe() split.
    */
    public function Update(array $arUpd,$iWhere=NULL) {
	//$ok = $this->Table->Update($iSet,$sqlWhere);
	if (is_null($iWhere)) {
	    $sql = $this->SQL_forUpdateMe($arUpd);
	} else {
	    $sql = $this->SQL_forUpdate($arUpd,$iWhere);
	}
	$this->sqlExec = $sql;
	$this->Table()->sql = $sql;
	$ok = $this->Engine()->Exec($sql);
	return $ok;
    }
    public function Make(array $iarSet) {
	return $this->Indexer()->Make($iarSet);
    }
    /*-----
      ACTION: Reloads only the current row unless $iFilt is set
      HISTORY:
	2012-03-04 moved from clsRecs_key_single to clsRecs_keyed_abstract
	2012-03-05 removed code referencing iFilt -- it is no longer in the arg list
    */
    public function Reload() {
	if (is_string($this->sqlMake)) {
	    $sql = $this->sqlMake;
	} else {
	    $sql = 'SELECT * FROM `'.$this->Table->Name().'` WHERE ';
	    $sql .= $this->SelfFilter();
	}
	$this->Query($sql);
	$this->NextRow();	// load the data
    }
}
/*=============
  NAME: clsDataSet_bare
  DEPRECATED - USE clsRecs_key_single INSTEAD
  PURPOSE: base class for datasets, with single key
    Does not add field overloading. Field overloading seems to have been a bad idea anyway;
      Use Value() instead.
*/
class clsRecs_key_single extends clsRecs_keyed_abstract {
    /*----
      HISTORY:
	2010-11-01 iID=NULL now means object does not have data from an existing record
	2016-03-03 Rather than checking the keys specifically, let's just see if Values()
	  is NULL (not an array) -- which can be done in a base class.
    */
    public function IsNew() {
	return is_null($this->KeyValue());
    }
    public function KeyName($sKey=NULL) {
	if (!is_null($sKey)) {
	    $this->sKey = $sKey;
	}
	if (isset($this->sKey)) {
	    return $this->sKey;
	} else {
	    return $this->Table()->KeyName($sKey);
	}
    }
    /*----
     RETURNS: TRUE iff the recordset is attached to a table.
      This is mainly for debugging, because orphaned recordsets have very limited capabilities.
    */
    public function HasTable() {
	return is_object($this->Table());
    }
    /*-----
      FUNCTION: KeyValue()
    */
    public function KeyValue($iVal=NULL) {
	if (!$this->HasTable()) {
	    throw new Exception('Recordset needs a Table object to retrieve key value.');
	}
	if (!is_array($this->Values()) && !is_null($this->Row)) {
	    throw new Exception('Row needs to be an array or NULL, but type is '.gettype($this->Row).'. This may be an internal error.');
	}
	$strKeyName = $this->KeyName();
	if (is_null($iVal)) {
	    if (!isset($this->Row[$strKeyName])) {
		$this->Row[$strKeyName] = NULL;
	    }
	} else {
	    $this->Row[$strKeyName] = $iVal;
	}
	return $this->Row[$strKeyName];
    }
    public function KeyString($sNew=KS_NEW_REC) {
	$sKey = (string)$this->KeyValue();
	return ($sKey=='')?$sNew:$sKey;
    }
    public function ClearKeyValue() {
	$sKeyName = $this->KeyName();
	$this->Row[$sKeyName] = NULL;
    }
    /*----
      RETURNS: list of key values from the current recordset, formatted for use in an SQL "IN (...)" clause
      SEE ALSO: ColumnValues_SQL()
      TODO: This method could probably use that one.
    */
    public function KeyListSQL() {
	$sql = NULL;
	$this->StartRows();	// rewind to before the first row
	while ($this->NextRow()) {
	    if (!is_null($sql)) {
		$sql .= ',';
	    }
	    $sql .= $this->KeyValue();
	}
	$this->FirstRow();	// a bit of a kluge
	return $sql;
    }
    /*----
      RETURNS: array of recordsets:
	array[ID] = Values()
      SEE ALSO: ColumnValues_array() (related but not identical)
    */
    public function asKeyedArray() {
	$ar = array();
	$this->StartRows();	// rewind to before the first row
	while ($this->NextRow()) {
	    $id = $this->KeyValue();
	    $ar[$id] = $this->Values();
	}
	return $ar;
    }
    /*----
      FUNCTION: Load_fromKey()
      ACTION: Load a row of data whose key matches the given value
      HISTORY:
	2010-11-19 Created for form processing.
    */
    public function Load_fromKey($iKeyValue) {
	$this->sqlMake = NULL;
	$this->KeyValue($iKeyValue);
	$this->Reload();
    }
    /*----
      FUNCTION: LoadArray()
      ACTION: Loads the current recordset into an array, with the recordset's KeyValue as the array key
	There's probably a native function call for this -- connect to it later.
      HISTORY:
	2014-03-22 created
    */
    public function LoadArray() {
	$arOut = NULL;
	$this->RewindRows();
	while ($this->NextRow()) {
	    $id = $this->KeyValue();
	    $arOut[$id] = $this->Values();
	}
	return $arOut;
    }
    /*-----
      FUNCTION: SelfFilter()
      RETURNS: SQL for WHERE clause which will select only the current row, based on KeyValue()
      USED BY: Update(), Reload()
      HISTORY:
	2012-02-21 KeyValue() is now run through SQLValue() so it can handle non-numeric keys
    */
    public function SelfFilter() {
	if (!is_object($this->Table)) {
	    throw new exception('Table not set in class '.get_class($this));
	}
	$strKeyName = $this->KeyName();
	$sqlWhere = '`'.$strKeyName.'`='.$this->Table()->Engine()->SanitizeAndQuote($this->KeyValue());
	return $sqlWhere;
    }
    /*-----
      ACTION: Reloads only the current row unless $iFilt is set
      TO DO: iFilt should probably be removed, now that we save
	the creation SQL in $this->sql.
    */
/*
    public function Reload($iFilt=NULL) {
	if (is_string($this->sqlMake)) {
	    $sql = $this->sqlMake;
	} else {
	    $sql = 'SELECT * FROM `'.$this->Table->Name().'` WHERE ';
	    if (is_null($iFilt)) {
		$sql .= $this->SelfFilter();
	    } else {
		$sql .= $iFilt;
	    }
	}
	$this->Query($sql);
	$this->NextRow();
    }
*/
    /*----
      HISTORY:
	2010-11-20 Created
	2013-07-17 splitting into SQL_forUpdate and SQL_forUpdateMe
    */
    public function SQL_forUpdate(array $iSet,$iWhere) {
	return $this->Table()->SQL_forUpdate($iSet,$iWhere);
    }
    /*----
      HISTORY:
	2013-07-17 Created
    */
    public function SQL_forUpdateMe(array $iSet) {
	$sqlWhere = $this->SelfFilter();
	return $this->SQL_forUpdate($iSet,$sqlWhere);
    }
    /*----
      HISTORY:
	2010-11-23 Created
    */
    public function SQL_forMake(array $iarSet) {
	$strKeyName = $this->Table->KeyName();
	if ($this->IsNew()) {
	    $sql = $this->Table->SQL_forInsert($iarSet);
	} else {
	    $sql = $this->SQL_forUpdate($iarSet);
	}
	return $sql;
    }
    /*-----
      ACTION: Saves to the current record; creates a new record if ID is 0 or NULL
      HISTORY:
	2010-11-03
	  Now uses this->IsNew() to determine whether to use Insert() or Update()
	  Loads new ID into KeyValue
    */
    public function Make(array $iarSet) {
	if ($this->IsNew()) {
	    $tbl = $this->Table;
	    $id = $tbl->Insert($iarSet);
	    $this->sqlExec = $tbl->sqlExec;
	    if ($id !== FALSE) {
		$this->KeyValue($id);
	    }
	    return $id;
	} else {
	    return $this->Update($iarSet);
	}
    }
    // DEPRECATED -- should be a function of Table type
    public function HasField($iName) {
	return isset($this->Row[$iName]);
    }
    // DEPRECATED - use Values()
    public function RowCopy() {
	$strClass = get_class($this);
	if (is_array($this->Row)) {
	    $objNew = new $strClass;
// copy critical object fields so methods will work:
	    $objNew->objDB = $this->objDB;
	    $objNew->Table = $this->Table;
// copy data fields:
	    foreach ($this->Row AS $key=>$val) {
		$objNew->Row[$key] = $val;
	    }
	    return $objNew;
	} else {
	    //echo 'RowCopy(): No data to copy in class '.$strClass;
	    return NULL;
	}
    }
}
// alias -- a sort of default dataset type
class clsDataSet_bare extends clsRecs_key_single {
}
/*
  PURPOSE: clsDataSet with overloaded field access methods
  DEPRECATED -- This has turned out to be more problematic than useful.
    Retained only for compatibility with existing code; hope to eliminate eventually.
*/
class clsDataSet extends clsRecs_key_single {
  // -- accessing individual fields
    public function __set($iName, $iValue) {
	$sClass = get_class($this);
	throw new exception("Internal - Attempted to set undeclared member [$sClass]->[$iName].");
	$this->Row[$iName] = $iValue;
    }
    public function __get($iName) {
	$sClass = get_class($this);
	throw new exception("Internal - Attempted to access undeclared member [$sClass]->[$iName].");
	if (isset($this->Row[$iName])) {
	    return $this->Row[$iName];
	} else {
	    return NULL;
	}
    }
}
