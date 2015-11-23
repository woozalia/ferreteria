<?php
/*
  PURPOSE: helper classes for managing table indexing
  HISTORY:
    2013-11-06 split off from data.php
*/
/*====
  CLASS: Table Indexer
*/
class clsIndexer_Table {
    private $objTbl;	// clsTable object

    public function __construct(clsTable_abstract $iObj) {
	$this->objTbl = $iObj;
    }
    /*----
      RETURNS: newly-created clsIndexer_Recs-descended object
	Override this method to change how indexing works
    */
    protected function NewRecsIdxer(clsRecs_indexed $iRecs) {
	return new clsIndexer_Recs($iRecs,$this);
    }
    public function TableObj() {
	return $this->objTbl;
    }

    public function InitRecs(clsRecs_indexed $iRecs) {
	$objIdx = $this->NewRecsIdxer($iRecs);
	$iRecs->Indexer($objIdx);
	return $objIdx;
    }
}
/*====
  HISTORY:
    2011-01-19 Started; not ready yet -- just saving bits of code I know I will need
*/
class clsIndexer_Table_single_key extends clsIndexer_Table {
    protected function NewRecsIdxer(clsRecs_indexed $iRecs) {
	return new clsIndexer_Recs_single_key($iRecs,$this);
    }
    public function KeyName($iName=NULL) {
	if (!is_null($iName)) {
	    $this->vKeyName = $iName;
	}
	return $this->vKeyName;
    }
    public function GetItem($id=NULL,$sClass=NULL) {
	if (is_null($id)) {
	    $rcItem = $this->SpawnItem($sClass);
	    $rcItem->KeyValue(NULL);
	} else {
	    $tbl = $this->TableObj();
	    $db = $tbl->Engine();
	    $rcItem = $tbl->GetData($this->vKeyName.'='.$db->SanitizeAndQuote($id),$sClass);
	    $rcItem->NextRow();
	}
	return $rcItem;
    }
}

class clsIndexer_Table_multi_key extends clsIndexer_Table {
    private $arKeys;

    /*----
      RETURNS: newly-created clsIndexer_Recs-descended object
	Override this method to change how indexing works
    */
    protected function NewRecsIdxer(clsRecs_indexed $iRecs) {
	return new clsIndexer_Recs_multi_key($iRecs,$this);
    }
    public function KeyNames(array $iNames=NULL) {
	if (!is_null($iNames)) {
	    $this->arKeys = $iNames;
	}
	return $this->arKeys;
    }
    /*----
      HISTORY:
	2011-01-08 written
    */
    /*----
      INPUT:
	$iVals can be an array of index values, a prefix-marked string, or NULL
	  NULL means "spawn a blank item".
      HISTORY:
	2012-03-04 now calling MakeFilt() instead of SQL_for_filter()
    */
    public function GetItem($iVals=NULL) {
	if (is_null($iVals)) {
	    $objItem = $this->TableObj()->SpawnItem();
	} else {
	    if (is_array($iVals)) {
		$arVals = $iVals;
	    } else {
		$x = new xtString($iVals);	// KLUGE to get strings.php to load >.<
		$arVals = Xplode($iVals);
	    }
	    $arKeys = $this->KeyNames();
	    $arVals = array_reverse($arVals);	// pop takes the last item; we want to start with the first
	    foreach ($arKeys as $key) {
		$arData[$key] = array_pop($arVals);	// get raw values to match
	    }
	    $sqlFilt = $this->MakeFilt($arData,TRUE);
	    $objItem = $this->TableObj()->GetData($sqlFilt);
	    $objItem->NextRow();
	}
	return $objItem;
    }
    /*----
      RETURNS: SQL to filter for the current record by key value(s)
	'(name=value) AND (name=value) AND...'
      INPUT:
	$iData: array of keynames and values
	  iData[name] = value
	$iSQLify: TRUE = massage with SQLValue() before using; FALSE = ready to use in SQL
      USED BY: GetItem()
      HISTORY:
	2011-01-08 written
	2011-01-19 replaced with boilerplate call to indexer in clsIndexer_Table
	2012-03-04 I... don't know what I was talking about on 2011-01-19. Reinstating this,
	  but renaming it from SQL_for_filter() to MakeFilt()
    */
    public function MakeFilt(array $iData,$iSQLify) {
	$arKeys = $this->KeyNames();
	$sql = NULL;
	foreach ($arKeys as $name) {
	    if (!array_key_exists($name,$iData)) {
		echo '<br>Key ['.$name.'] not found in passed data:<pre>'.print_r($iData,TRUE).'</pre>';
		throw new exception('Key ['.$name.'] not found in passed data.');
	    }
	    $val = $iData[$name];
	    if (!is_null($sql)) {
		$sql .= ' AND ';
	    }
	    if ($iSQLify) {
		$val = SQLValue($val);
	    }
	    $sql .= '(`'.$name.'`='.$val.')';
	}
	return $sql;
    }

    /*----
      RETURNS: SQL for creating a new record for the given data
      NOTE: does anything actually call this?
      HISTORY:
	2010-11-20 Created.
	2011-01-08 adapted from clsTable::Insert()
    */
    public function SQL_forInsert(array $iData) {
	$sqlNames = '';
	$sqlVals = '';
	foreach($iData as $key=>$val) {
	    if ($sqlNames != '') {
		$sqlNames .= ',';
		$sqlVals .= ',';
	    }
	    $sqlNames .= '`'.$key.'`';
	    $sqlVals .= $val;
	}
	return 'INSERT INTO `'.$this->Name().'` ('.$sqlNames.') VALUES('.$sqlVals.');';
    }

}
/*====
  CLASS: clsIndexer_Recs -- record set indexer
  PURPOSE: Handles record sets for tables with multiple keys
  HISTORY:
    2010-?? written for clsCacheFlows/clsCacheFlow in cache.php
    2011-01-08 renamed, revised and clarified
*/
abstract class clsIndexer_Recs {
    private $objData;
    private $objTbl;	// table indexer

    // ABSTRACT functions
    abstract public function IndexIsSet();
    abstract public function KeyString();
    abstract public function SQL_forWhere();

    /*----
      INPUT:
	iObj = DataSet
	iKeys = array of field names
    */
    public function __construct(clsRecs_keyed_abstract $iData,clsIndexer_Table $iTable) {
	$this->objData = $iData;
	$this->objTbl = $iTable;
    }
    public function DataObj() {
	return $this->objData;
    }
    public function TblIdxObj() {
	assert('is_a($this->objTbl,"clsIndexer_Table"); /* CLASS='.get_class($this->objTbl).' */');
	return $this->objTbl;
    }
    public function Engine() {
	return $this->DataObj()->Engine();
    }
    public function TableObj() {
	return $this->TblIdxObj()->TableObj();
    }
    public function TableName() {
	return $this->TableObj()->Name();
    }
    /*----
      INPUT:
	iSet: array specifying fields to update and the values to update them to
	  iSet[field name] = value
      HISTORY:
	2010-11-20 Created
	2011-01-09 Adapted from clsDataSet_bare
    */
    public function SQL_forUpdate(array $iSet) {
	$sqlSet = '';
	foreach($iSet as $key=>$val) {
	    if ($sqlSet != '') {
		$sqlSet .= ',';
	    }
	    $sqlSet .= ' `'.$key.'`='.$val;
	}
	$sqlWhere = $this->SQL_forWhere();

	return 'UPDATE `'.$this->TableName().'` SET'.$sqlSet.' WHERE '.$sqlWhere;
    }
    /*----
      HISTORY:
	2010-11-16 Added "array" requirement for iData
	2010-11-20 Calculation now takes place in SQL_forInsert()
	2011-01-08 adapted from clsTable::Insert()
    */
}
class clsIndexer_Recs_single_key extends clsIndexer_Recs {
    private $vKeyName;

    public function KeyName() {
	return $this->TblIdxObj()->KeyName();
    }
    public function KeyValue() {
	return $this->DataObj()->Value($this->KeyName());
    }
    public function KeyString() {
	return (string)$this->KeyValue();
    }
    public function IndexIsSet() {
	return !is_null($this->KeyValue());
    }

    public function SQL_forWhere() {
	$db = $this->TableObj()->Engine();
	$sql = $this->KeyName().'='.$db->SanitizeAndQuote($this->KeyValue());
	return $sql;
    }
}
class clsIndexer_Recs_multi_key extends clsIndexer_Recs {
    /*----
      RETURNS: Array of values which constitute this row's key
	array[key name] = key value
    */
    public function KeyArray() {
	$arKeys = $this->TblIdxObj()->KeyNames();
	$arRow = $this->DataObj()->Values();
	foreach ($arKeys as $key) {
	    if (array_key_exists($key,$arRow)) {
		$arOut[$key] = $arRow[$key];
	    } else {
		echo "\nTrying to access nonexistent key [$key]. Available keys:";
		echo '<pre>'.print_r($arRow,TRUE).'</pre>';
		throw new exception('Nonexistent key requested.');
	    }
	}
	return $arOut;
    }
    /*----
      NOTE: The definition of "new" is a little more ambiguous with multikey tables;
	for now, I'm saying that all keys must be NULL, because NULL keys are sometimes
	valid in multikey contexts.
    */
    public function IsNew() {
	$arData = $this->KeyArray();
	foreach ($arData as $key => $val) {
	    if (!is_null($val)) {
		return FALSE;
	    }
	}
	return TRUE;
    }
    /*----
      ASSUMES: keys will always be returned in the same order
	If this changes, add field names.
      POTENTIAL BUG: Non-numeric keys might contain the separator character
	that we are currently using ('.'). Some characters may not be appropriate
	for some contexts. The caller should be able to specify what separator it wants.
    */
    public function KeyString() {
	$arKeys = $this->KeyArray();
	$out = NULL;
	foreach ($arKeys as $name=>$val) {
	    $out .= '.'.$val;
	}
	return $out;
    }
   /*----
      RETURNS: TRUE if any index fields are NULL
      ASSUMES: An index may not contain any NULL fields. Perhaps this is untrue, and it should
	only return TRUE if *all* index fields are NULL.
    */
    public function IndexIsSet() {
	$arKeys = $this->KeyArray();
	$isset = TRUE;
	foreach ($arKeys as $key=>$val) {
	    if (is_null($val)) { $isset = FALSE; }
	}
	return $isset;
    }
    /*----
      RETURNS: SQL to filter for the current record by key value(s)
      HISTORY:
	2011-01-08 written for Insert()
	2011-01-19 moved from clsIndexer_Recs to clsIndexer_Recs_multi_key
	2012-03-04 This couldn't have been working; it was calling SQL_for_filter() on a list of keys,
	  not keys-and-values. Fixed.
    */
    public function SQL_forWhere() {
	//$arKeys = $this->TblIdxObj()->KeyNames();
	//return SQL_for_filter($arVals);

	$rcData = $this->DataObj();
	$arVals = $rcData->Values();
	if (is_null($arVals)) {
	    $tData = $rcData->Table();
	    echo '<b>SQL</b>: '.$tData->sqlExec;
	    throw new exception('Row for table '.$tData->Name().' has no values. How is this happening?');
	    $sql = NULL;
	} else {
	    $sql = $this->TblIdxObj()->MakeFilt($arVals,TRUE);
	}
	return $sql;
    }
    /*----
      NOTE: This is slightly different from the single-keyed Make() in that it assumes there are no autonumber keys.
	All keys must be specified in the initial data.
    */
    public function Make(array $iarSet) {
	if ($this->IsNew()) {
	    $ok = $this->Table->Insert($iarSet);
	    //$this->KeyValue($this->objDB->NewID());
	    $this->DataObj()->Values($iarSet);	// do we need to preserve any existing values? for now, assuming not.
	    return $ok;
	} else {
	    return $this->DataObj()->Update($iarSet);
	}
    }
}
