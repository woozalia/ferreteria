<?php
/*
  PURPOSE: classes for handling data tables
  HISTORY:
    2013-12-19 split off from data.php
    2015-09-07 Table classes now have InitVars(). I'm surprised that it didn't already.
*/
/*%%%%
  NAME: clsTable_abstract
  PURPOSE: objects for operating on particular tables
    Does not attempt to deal with keys.
*/
abstract class clsTable_abstract {
    protected $objDB;
    protected $vTblName;
    protected $vSngClass;	// name of singular class
    public $sqlExec;		// last SQL executed on this table

    // ++ STATIC ++ //

    /*----
      HISTORY:
	2010-11-20 Created
	2013-07-14 Adapted from clsTable_key_single to static method in clsTable_abstract
    */
    static public function _SQL_forUpdate($iTblName, array $iSet,$iWhere) {
	$sqlSet = '';
	foreach($iSet as $key=>$val) {
	    if ($sqlSet != '') {
		$sqlSet .= ',';
	    }
	    $sqlSet .= ' `'.$key.'`='.$val;
	}

	return "UPDATE `$iTblName` SET$sqlSet WHERE $iWhere";
    }

    // -- STATIC -- //
    // ++ DYNAMIC ++ //

    public function __construct(clsDatabase_abstract $iDB) {
	$this->objDB = $iDB;
	$this->InitVars();
    }
    protected function DefaultSingularClass() {
	return 'clsRecs_generic';
    }
    protected function InitVars() {
	$this->ClassSng($this->DefaultSingularClass());	// default
    }
    public function DB() {	// DEPRECATED - use Engine()
	return $this->objDB;
    }
    public function Engine() {
	return $this->objDB;
    }
    public function Name($iName=NULL) {
	if (!is_null($iName)) {
	    $this->vTblName = $iName;
	}
	return $this->vTblName;
    }
    public function NameSQL() {
	if (!is_string($this->vTblName)) {
	    echo 'TABLE NAME:<pre>'.print_r($this->vTblName).'</pre>';
	    throw new exception('Table name is not a string.');
	}
	return '`'.$this->vTblName.'`';
    }
    public function ClassSng($iName=NULL) {
	if (!is_null($iName)) {
	    $this->vSngClass = $iName;
	}
	return $this->vSngClass;
    }
    /*----
      ACTION: Make sure the item is ready to be released in the wild
    */
    protected function ReleaseItem(clsRecs_abstract $iItem) {
	$iItem->Table = $this;
	$iItem->objDB = $this->objDB;
	$iItem->sqlMake = $this->sqlExec;
    }
    /*----
      ACTION: creates a new uninitialized singular (recordset) object but sets the Table pointer back to self
      RETURNS: created recordset object
    */
    public function SpawnItem($sClass=NULL) {
	if (is_null($sClass)) {
	    $sClass = $this->ClassSng();
	}
	if (class_exists($sClass)) {
	    $rs = new $sClass();
	    // NOTE: If you get an error like "Missing argument 1 for <$sClass>" here,
	    //	you're probably requesting a non-recordset class. Check what was passed to ClassSng().
	    $this->ReleaseItem($rs);
	    return $rs;
	} else {
	    throw new exception('Class "'.$sClass.'" is not defined.');
	}
    }
    /*----
      RETURNS: dataset defined by the given SQL, wrapped in an object of the current class
      USAGE: primarily for joins where you want only records where there is no matching record
	in the joined table. (If other examples come up, maybe a DataNoJoin() method would
	be appropriate.)
      HISTORY:
	2013-12-28 Added sClass parameter - useful when joining a keyed table to a crosslink table,
	  so we can get objects of the keyed table as the result.
    */
    public function DataSQL($iSQL,$sClass=NULL) {
	if (is_null($sClass)) {
	    $sClass = $this->ClassSng();
	}
	$obj = $this->Engine()->DataSet($iSQL,$sClass);
	$this->sqlExec = $iSQL;
	$this->ReleaseItem($obj);
	return $obj;
    }
    /*----
      RETURNS: dataset containing all fields from the current table,
	with additional options (everything after the table name) being
	defined by $iSQL, wrapped in the current object class.
      HISTORY:
	2013-12-27 removed iClass parameter because the code ignores it.
	2014-05-26 I'm now wondering who even uses this, given the existence of GetData().
	2016-02-09 This is useful for select-from-self when you want to do more than just filter and sort
	  (e.g. LIMIT). It needs a better name, however.
    */
    public function DataSet($iSQL=NULL) {
	global $sql;	// for debugging

	$sql = 'SELECT * FROM '.$this->NameSQL();
	if (!is_null($iSQL)) {
	    $sql .= ' '.$iSQL;
	}
	return $this->DataSQL($sql);
/*
	$strCls = $this->vSngClass;
	$obj = $this->objDB->DataSet($sql,$strCls);
	$obj->Table = $this;
	return $obj;
*/
    }
    public function AllRecords() {
	return $this->GetRecords();
    }
    /*----
      NOTE: This replaces GetData()
    */
    public function GetRecords($sqlWhere=NULL,$sqlSort=NULL,$sClass=NULL) {
	global $sql; 	// for debugging

	$sql = 'SELECT * FROM '.$this->NameSQL();
	if (!is_null($sqlWhere)) {
	    $sql .= ' WHERE '.$sqlWhere;
	}
	if (!is_null($sqlSort)) {
	    $sql .= ' ORDER BY '.$sqlSort;
	}

	// TODO: should Query() really be a method of the Recordset-type classes?
	$rs = $this->SpawnItem($sClass);
	$rs->Query($sql);

	$this->sqlExec = $sql;
	if (!is_null($rs)) {
	    $rs->sqlMake = $sql;
	}
	return $rs;
    }
    /*----
      PURPOSE: Same as GetRecord, but
	(a) throws an exception if more than one record is found
	(b) advances to the first (only) record
	(c) returns NULL if no records found
      TODO: this could be optimized to skip object creation if no records found
    */
    public function GetRecord($sqlWhere,$sClass=NULL) {
	$rc = $this->GetRecords($sqlWhere,NULL,$sClass);
	$qr = $rc->RowCount();
	if ($qr == 0) {
	    return NULL;
	} elseif ($qr == 1) {
	    $rc->NextRow();
	    return $rc;
	} else {
	    $sMsg = $qr.' rows found in GetRecord(); should be 1 or 0.';
	    throw new exception($sMsg);
	}
    }
    /*----
      TODO: Deprecate this function and replace it with GetRecords()
    */
    public function GetData($iWhere=NULL,$iClass=NULL,$iSort=NULL) {
	global $sql; 	// for debugging

	$sql = 'SELECT * FROM '.$this->NameSQL();
	if (!is_null($iWhere)) {
	    $sql .= ' WHERE '.$iWhere;
	}
	if (!is_null($iSort)) {
	    $sql .= ' ORDER BY '.$iSort;
	}

	//$obj = $this->objDB->DataSet($sql,$strCls);
	//$res = $this->DB()->Exec($sql);
	$obj = $this->SpawnItem($iClass);
	assert('is_object($obj->Table);');
	$obj->Query($sql);

	$this->sqlExec = $sql;
	if (!is_null($obj)) {
//	    $obj->Table = $this;	// 2011-01-20 this should be redundant now
	    $obj->sqlMake = $sql;
	}
	return $obj;
    }
    /*----
      RETURNS: SQL for creating a new record for the given data
      HISTORY:
	2010-11-20 Created.
    */
    public function SQL_forInsert(array $iData) {
	$sqlNames = '';
	$sqlVals = '';
	foreach($iData as $key=>$val) {
	    if ($sqlNames != '') {
		$sqlNames .= ',';
		$sqlVals .= ',';
	    }
	    if (!(is_string($val) || is_numeric($val))) {
		$sType = gettype($val);
		throw new exception("Internal Error: The INSERT value of [$key] is of type $sType.");
	    }
	    $sqlNames .= $key;
	    $sqlVals .= $val;
	}
	return 'INSERT INTO `'.$this->Name().'` ('.$sqlNames.') VALUES('.$sqlVals.');';
    }
    /*----
      HISTORY:
	2010-11-16 Added "array" requirement for iData
	2010-11-20 Calculation now takes place in SQL_forInsert()
    */
    public function Insert(array $iData) {
	global $sql;

	$sql = $this->SQL_forInsert($iData);
	$this->sqlExec = $sql;
	$ok = $this->Engine()->Exec($sql);
	if ($ok) {
	    return $this->Engine()->NewID();
	} else {
	    return $ok;
	}
    }
    /*----
      ACTION: Same as Insert, but returns an object if successful
      HISTORY:
	2013-10-07 created, because it seemed like a good idea
    */
    public function Insert_andGet(array $iData) {
	$id = $this->Insert($iData);
	if ($id === FALSE) {
	    return NULL;
	} else {
	    $rc = $this->GetItem($id);
	    return $rc;
	}
    }
    /*----
      HISTORY:
	2011-02-02 created for deleting topic-title pairs
    */
    public function Delete($iFilt) {
	$sql = 'DELETE FROM `'.$this->Name().'` WHERE '.$iFilt;
	$this->sqlExec = $sql;
	return $this->Engine()->Exec($sql);
    }
    /*----
      HISTORY:
	2010-11-20 Created
	2013-07-14 Simplified to use static method
    */
    public function SQL_forUpdate(array $iSet,$iWhere) {
	return self::_SQL_forUpdate($this->Name(),$iSet,$iWhere);
/*
	$sqlSet = '';
	foreach($iSet as $key=>$val) {
	    if ($sqlSet != '') {
		$sqlSet .= ',';
	    }
	    $sqlSet .= ' `'.$key.'`='.$val;
	}

	return 'UPDATE `'.$this->Name().'` SET'.$sqlSet.' WHERE '.$iWhere;
*/
    }
    /*----
      HISTORY:
	2010-10-05 Commented out code which updated the row[] array from iSet's values.
	  * It doesn't work if the input is a string instead of an array.
	  * Also, it seems like a better idea to actually re-read the data if
	    we really need to update the object.
	2010-11-16 Added "array" requirement for iSet; removed code for handling
	  iSet as a string. If we want to support single-field updates, make a
	  new method: UpdateField($iField,$iVal,$iWhere). This makes it easier
	  to support automatic updates of certain fields in descendent classes
	  (e.g. updating a WhenEdited timestamp).
	2010-11-20 Calculation now takes place in SQL_forUpdate()
	2013-07-14 Moved from clsTable_key_single to clsTable_abstract
    */
    public function Update(array $iSet,$iWhere) {
	global $sql;

	$sql = $this->SQL_forUpdate($iSet,$iWhere);
	$this->sqlExec = $sql;
	$ok = $this->objDB->Exec($sql);

	return $ok;
    }
}
/*=============
  NAME: clsTable_keyed_abstract
  PURPOSE: adds abstract methods for dealing with keys
*/
abstract class clsTable_keyed_abstract extends clsTable_abstract {

    //abstract public function GetItem_byArray();
    abstract protected function MakeFilt(array $iData);
    abstract protected function MakeFilt_direct(array $iData);
    /*----
      PURPOSE: method for setting a key which uniquely refers to this table
	Useful for logging, menus, and other text-driven contexts.
      HISTORY:
	2015-09-03 Added exception trap for undefined ActionKey.
    */
    public function ActionKey($iName=NULL) {
	if (!is_null($iName)) {
	    $this->ActionKey = $iName;
	}
	if (empty($this->ActionKey)) {
	    $sName = $this->Name();
	    $sClass = get_class($this);
	    $sMsg = "Internal Error: trying to access ActionKey before it has been set in table '$sName', class '$sClass'.";
	    throw new exception($sMsg);
	}
	return $this->ActionKey;
    }
    /*----
      INPUT:
	$iData: array of data necessary to create a new record
	  or update an existing one, if found
	$iFilt: SQL defining what constitutes an existing record
	  If NULL, MakeFilt() will be called to build this from $iData.
      ASSUMES: iData has already been massaged for direct SQL use
      HISTORY:
	2011-02-22 created
	2011-03-23 added madeNew and dataOld fields
	  Nothing is actually using these yet, but that will probably change.
	  For example, we might want to log when an existing record gets modified.
	2011-03-31 why is this protected? Leaving it that way for now, but consider making it public.
	2012-02-21 Needs to be public; making it so.
	  Also changed $this->Values() (which couldn't possibly have worked) to $rs->Values()
    */
    public $madeNew,$dataOld;	// additional status output
    /*----
      NOTE: This seems kind of inefficient and probably needs to be rethought.
	Perhaps an ID parameter would help.
    */
    public function Make(array $iData,$iFilt=NULL) {
	if (is_null($iFilt)) {
	    $sqlFilt = $this->MakeFilt_direct($iData);
	} else {
	    $sqlFilt = $iFilt;
	}
	$rs = $this->GetData($sqlFilt);
	if ($rs->HasRows()) {
	    assert('$rs->RowCount() == 1');
	    $rs->NextRow();

	    $this->madeNew = FALSE;
	    $this->dataOld = $rs->Values();

	    $rs->Update($iData);
	    $id = $rs->KeyString();
	    $this->sqlExec = $rs->sqlExec;
	} else {
	    $this->Insert($iData);
	    $id = $this->Engine()->NewID();
	    $this->madeNew = TRUE;
	}
	return $id;
    }
}
/*=============
  NAME: clsTable_key_single
  PURPOSE: table with a single key field
*/
class clsTable_key_single extends clsTable_keyed_abstract {
    protected $vKeyName;

    public function __construct(clsDatabase_abstract $iDB) {
	parent::__construct($iDB);
    }
    public function KeyName($iName=NULL) {
	if (!is_null($iName)) {
	    $this->vKeyName = $iName;
	}
	return $this->vKeyName;
    }
    /*----
      HISTORY:
	2010-11-01 iID=NULL now means create new/blank object, i.e. SpawnItem()
	2011-11-15 tweak for clarity
	2012-03-04 getting rid of optional $iClass param
    */
    public function GetItem($id=NULL) {
	if (!is_numeric($id) && !is_string($id)) {
	    throw new exception('"$id" should be an integer or string, but received '.gettype($id));
	}
	if (is_null($id)) {
	    $objItem = $this->SpawnItem();
	    $objItem->KeyValue(NULL);
	} else {
	    $sqlFilt = $this->vKeyName.'='.$this->Engine()->engine_db_safe_param($id);
	    $objItem = $this->GetData($sqlFilt);
	    $objItem->NextRow();
	}
	return $objItem;
    }
    /*----
      INPUT:
	iFields: array of source fields and their output names - specified as iFields[output]=input, because you can
	  have a single input used for multiple outputs, but not vice-versa. Yes, this is confusing but that's how
	  arrays are indexed.
      HISTORY:
	2010-10-16 Created for VbzAdminCartLog::AdminPage()
    */
    public function DataSetGroup(array $iFields, $iGroupBy, $iSort=NULL) {
	global $sql;	// for debugging

	foreach ($iFields AS $fDest => $fSrce) {
	    if(isset($sqlFlds)) {
		$sqlFlds .= ', ';
	    } else {
		$sqlFlds = '';
	    }
	    $sqlFlds .= $fSrce.' AS '.$fDest;
	}
	$sql = 'SELECT '.$sqlFlds.' FROM '.$this->NameSQL().' GROUP BY '.$iGroupBy;
	if (!is_null($iSort)) {
	    $sql .= ' ORDER BY '.$iSort;
	}
	$obj = $this->objDB->DataSet($sql);
	return $obj;
    }
    public function LastID() {
	$sKey = $this->KeyName();
	$sqlTbl = $this->NameSQL();
	$sql = "SELECT $sKey FROM $sqlTbl ORDER BY $sKey DESC LIMIT 1;";

	$rc = $this->Engine()->DataSet($sql);

	if ($rc->HasRows()) {
	    $rc->NextRow();
	    $id = $rc->Value($sKey);
	    return $id;
	} else {
	    return 0;
	}
    }
    public function NextID() {
	return $this->LastID()+1;
    }
    /*----
      HISTORY:
	2011-02-22 created
	2012-02-21 this couldn't possibly have worked before, since it used $this->KeyValue(),
	  which is a Record function, not a Table function.
	  Replaced that with $iData[$strName].
      ASSUMES: $iData[$this->KeyName()] is set, but may need to be SQL-formatted
      IMPLEMENTATION:
	KeyName must equal KeyValue
    */
    protected function MakeFilt(array $iData) {
	$strName = $this->KeyName();
	$val = $iData[$strName];
	if ($iSQLify) {
	    $val = SQLValue($val);
	}
	return $strName.'='.$val;
    }
    /*----
      PURPOSE: same as MakeFilt(), but does no escaping of SQL data
      HISTORY:
	2012-03-04 created to replace $iSQLify option
      ASSUMES: $iData[$this->KeyName()] is set, and already SQL-formatted (i.e. quoted if necessary)
    */
    protected function MakeFilt_direct(array $iData) {
	$strName = $this->KeyName();
	$val = $iData[$strName];
	return $strName.'='.$val;
    }
    /*----
      ACTION: Update the record identified by the key $id
    */
    public function Update_Keyed(array $arUpd,$id) {
	$sName = $this->KeyName();
	$sFilt = $sName.'='.$id;
	$this->Update($arUpd,$sFilt);
    }
}
// alias -- sort of a default table type
class clsTable extends clsTable_key_single {
}
