<?php
/* ****

PURPOSE: handlers for tables which are chained to a parent table -- that is,
  each record in the table corresponds with exactly one record in the parent.
  This way we can sort of implement class inheritance for tables by adding
  more fields only where needed.
HISTORY:
  2013-07-14 started

**** */
/*=============
  NAME: clsTable_chained
  PURPOSE: table singly-keyed to a parent table
*/
class clsTable_chained extends clsTable_abstract {
    private $arDeps;
    private $sKeyReq, $sKeyRet;
    protected $oIdx;

    // %%%% INITIALIZATION %%%% //
    public function __construct(clsDatabase $iDB, clsIndexer_Table $iIndexer=NULL) {
	parent::__construct($iDB);
	$oIdxer = is_null($iIndexer)?(new clsIndexer_Table_chained($this)):$iIndexer;
	$this->Indexer($oIdxer);
    }

    /*----
      INPUT: arDeps - a hierarchy of dependencies
	arDeps[table_name]
	  [parent] = name of parent table; NULL if none
	  [key] = name of key (if parent exists, this should be keyed to parent)
	  [fields] = list of updateable fields
	    [field_name]
	    [field_name]
	    ...
    */
    /*----
      NOTE: so far, we've only needed the READ function
	but it does need to be public, so the recordset class
	can do stuff with the chain (see Update()).
    */
    public function Dependencies(array $arDeps=NULL) {
	if (!is_null($arDeps)) {
	    $this->arDeps = $arDeps;
	}
	return $this->arDeps;
    }
    /*----
      USAGE: always call *after* constructing parent, so array will be in order (starting with the root parent)
      INPUT:
	arData: everything to go under this table's name in the dependency array
	  [parent] = name of parent table; NULL if none
	  [key] = name of key (if parent exists, this should be keyed to parent)
	  [fields] = list of updateable fields
	    [field_name]
	    [field_name]
	    ...
    */
    public function AddDep(array $arData) {
	$this->arDeps[$this->Name()] = $arData;
	if (is_null($arData['parent'])) {
	    // make a note of the master key
	    $this->sKeyReq = $this->Name().'.'.$arData['key'];		// field requested
	    $this->sKeyRet = $arData['key'];				// field name returned
	}
    }

    // %%%% BOILERPLATE %%%% //

    protected function Indexer(clsIndexer_Table $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->oIdx = $iObj;
	}
	return $this->oIdx;
    }
    /*----
      INPUT:
	$iVals can be an array of index values, a prefix-marked string, or NULL
	  NULL means "spawn a blank item".
    */
    public function GetItem($iVals=NULL) {
	return $this->Indexer()->GetItem($iVals);
    }
    protected function MakeFilt(array $iData) {
	return $this->Indexer()->MakeFilt($iData,TRUE);
    }
    protected function MakeFilt_direct(array $iData) {
	return $this->Indexer()->MakeFilt($iData,FALSE);
    }

    // %%%% KEY USAGE %%%% //

    public function KeyNameReq($sKey=NULL) {
	if (!is_null($sKey)) {
	    $this->sKeyReq = $sKey;
	}
	return $this->sKeyReq;
    }
    public function KeyNameRet($sKey=NULL) {
	if (!is_null($sKey)) {
	    $this->sKeyRet = $sKey;
	} else {
	    if (is_null($this->sKeyRet)) {
		$this->sKeyRet = $this->sKeyReq;
	    }
	}
	return $this->sKeyRet;
    }

    // %%%% DATA MANIPULATION %%%% //

    /*----
      RETURNS: master ID of new record
    */
    public function Insert(array $iData) {
	$arSQL = $this->SQL_forInsert($iData);
	$this->objDB->Exec('START TRANSACTION');
	$id = NULL;
	foreach ($arSQL as $sql) {
	    $this->sqlExec .= $sql."\n<br>";
	    $this->objDB->Exec($sql);
	    if (is_null($id)) {
		$id = $this->Engine()->NewID();
	    }
	}
	$this->Engine()->Exec('COMMIT');
	return $id;
    }
    public function Update(array $iSet,$iWhere=NULL) {
	global $sql;

	if (!is_null($iWhere)) {
	    throw new exception('Update with filter not yet supported in this class.');
	}

	$ok = TRUE;
	$arSQL = $this->SQL_forUpdateMe($iSet);
//	$this->objDB->Exec('START TRANSACTION');
	foreach ($arSQL as $sql) {
	    $this->sqlExec .= $sql."\n<br>";
echo 'SQL = '.$sql.'<br>';
//	    $okTbl = $this->objDB->Exec($sql);
//	    if (!$okTbl) {
//		$ok = FALSE;
//	    }
	}
die();
	if ($ok) {
	    $sqlDone = 'COMMIT';
	} else {
	    $sqlDone = 'ROLLBACK';
	}
//	$this->Engine()->Exec($sqlDone);
	return $ok;
    }

    // %%%% SQL GENERATION %%%% //

    /*----
      ACTION: Generates SQL to retrieve the given fields, doing whatever JOINs are necessary
      INPUT:
	arFields: fields which come directly from one of the chained tables; NULL=all of them
	sFieldsExtra: additional fields to include but not to look up in the chain (possibly calculated)
    */
    public function SQL_forSelect(array $arFields=NULL, $sFieldsExtra=NULL) {
	if (!is_null($arFields)) {
	    $arFlds = array_flip($arFields);	// turn into a string-keyed list
	}
	$sTbls = NULL;
	$sKeyParent = NULL;
	$sTblParent = NULL;
	$sFlds = $this->sKeyReq.' AS '.$this->sKeyRet;	// always include the master key
	$doAll = is_null($arFields);
	foreach ($this->arDeps as $sTbl => $arSpec) {
	    foreach($arSpec['fields'] as $sFld) {
		if ($doAll) {
		    $doUse = TRUE;
		} else {
		    $doUse = array_key_exists($sFld,$arFlds);
		}
		if ($doUse) {
		    if (!is_null($sFlds)) {
			$sFlds .= ',';
		    }
/* NOTE
 It seems to be unpredictable as to whether the table name will be included in the returned field names, even though they
  are explicitly specified. On top of that, we already have to assume that requested field names are unique across the
  entire table chain. So even though this --
		    //$sFlds .= $sTbl.'.'.$sFld;
  -- may be good form, it isn't necessary and causes problems. Asking for just the field names seems to return an error.
  So now we explicitly rename the colums without the table names.
  If there's ever a problem with that, we could do something like "$sTbl.$sFld AS $sTbl_$sFld".
*/
		    $sFlds .= "$sTbl.$sFld AS $sFld";
		    if (!$doAll) {
			unset($arFlds[$sFld]);	// remove from unsatisfied list
		    }
		}
	    }
	    if (!is_null($sTbls)) {
		$sTbls = '('.$sTbls.' LEFT JOIN '.$sTbl.' ON '.$sTbl.'.'.$arSpec['key'].'='.$sTblParent.'.'.$sKeyParent.')';
	    } else {
		$sTbls = $sTbl;
	    }
	    $sKeyParent = $arSpec['key'];
	    $sTblParent = $sTbl;
	}
	if (!is_null($sFieldsExtra)) {
	    $sFlds .= ','.$sFieldsExtra;
	}
	if (!$doAll) {
	    $qFlds = count($arFlds);
	    if ($qFlds > 0) {
		$sErr = 'Could not find tables for '.$qFlds.' field'.Pluralize($qFlds).':';
		foreach ($arFlds as $sFld => $idx) {
		    $sErr .= ' '.$sFld;
		}
		$sErr .= '<pre>'.print_r($this->arDeps,TRUE).'</pre>';
		throw new exception($sErr);
	    }
	}
	return "SELECT $sFlds FROM $sTbls";
    }
    /*----
      RETURNS: SQL for creating a new record for the given data
      HISTORY:
	2010-11-20 Created.
	2011-01-08 adapted from clsTable::Insert() for clsIndexer_Table_multi_key
	2013-07-17 adapted from clsIndexer_Table_multi_key for clsTable_chained
      OUTPUT: array of SQL strings to execute
    */
    public function SQL_forInsert(array $iData) {
	$arData = $iData;
	// build catalog of chained tables to be included in the update
	foreach ($this->arDeps as $sTbl => $arSpec) {
	    if (is_null($arSpec['parent'])) {
		$sFlds = NULL;
		$sVals = NULL;
	    } else {
		$sFlds = $arSpec['key'];	// always include key-to-parent
		$sVals = 'LAST_INSERT_ID()';
	    }
	    foreach($arSpec['fields'] as $sFld) {
		$doUse = array_key_exists($sFld,$arData);
		if ($doUse) {
		    if (!is_null($sFlds)) {
			$sFlds .= ',';
		    }
		    if (!is_null($sVals)) {
			$sVals .= ',';
		    }
		    $sFlds .= $sFld;
		    $sVal = $arData[$sFld];
		    $sVals .= $sVal;
		    unset($arData[$sFld]);	// remove from unsatisfied list
		} else {
		// just for debugging
		}
	    }
	    if (!is_null($sFlds)) {
		$sql = "INSERT INTO `$sTbl` ($sFlds) VALUES($sVals);";
		$arSQL[$sTbl] = $sql;
	    }
	}

	// check for fields which could not be found
	$this->CheckFieldRequests($arData,$iData);
/*
	$qFlds = count($arData);
	if ($qFlds > 0) {
	    $sErr = 'Could not find tables for '.$qFlds.' field'.Pluralize($qFlds).':';
	    $sOut = NULL;
	    foreach ($iData as $sFld => $sVal) {
		if (array_key_exists($sFld,$arData)) {
		    $sOut .= " <font color=red>$sFld</font>";
		    $sErr .= ' '.$sFld;
		} else {
		    $sOut .= " (<s>$sFld</s>&radic;)";
		}
	    }
	    echo '<b>Fields</b>:'.$sOut.'<br>';
	    $sErr .= '<pre>'.print_r($this->arDeps,TRUE).'</pre>';
	    throw new exception($sErr);
	}
*/

	return $arSQL;
    }
    /*----
      INPUT:
	arRem: array of remaining (unsatisfied) field requests
	arReq: array of all fields requested
      NOTE: has to be public so recordset class can do chained things
    */
    static public function CheckFieldRequests(array $arRem, array $arReq) {
	// check for fields which could not be found
	$qFlds = count($arRem);
	if ($qFlds > 0) {
	    $sErr = 'Could not find tables for '.$qFlds.' field'.Pluralize($qFlds).':';
	    $sOut = NULL;
	    // show all requests and their status
	    foreach ($arReq as $sFld => $sVal) {
		if (array_key_exists($sFld,$arRem)) {
		    $sOut .= " <font color=red>$sFld</font>";
		    $sErr .= ' '.$sFld;
		} else {
		    $sOut .= " (<s>$sFld</s>&radic;)";
		}
	    }
	    echo '<b>Fields</b>:'.$sOut.'<br>';
	    $sErr .= '<pre>'.print_r($this->arDeps,TRUE).'</pre>';
	    throw new exception($sErr);
	}
    }
}

class clsRecordset_chained extends clsRecs_key_single {
    private $sKey;
    /*----
      PURPOSE: Chained recordsets sometimes need to have different keys for different queries.
    */
    public function KeyName($sKey=NULL) {
	if (!is_null($sKey)) {
	    $this->sKey = $sKey;
	}
	if (isset($this->sKey)) {
	    return $this->sKey;
	} else {
	    return $this->Table()->KeyNameRet($sKey);
	}
    }
    public function KeyValue($iVal=NULL) {
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
    public function Make(array $iarSet) {
	$out = parent::Make($iarSet);
//	echo 'NEW ID: '.$this->objDB->NewID().'<br>';
//	echo 'KEY VALUE: '.$this->KeyValue();
//	die('<pre>'.print_r($this->Values(),TRUE).'</pre>');
	return $out;
    }

    public function Update(array $iSet,$iWhere=NULL) {
	global $sql;

	if (!is_null($iWhere)) {
	    throw new exception('Update with filter not yet supported in this class.');
	}

	$ok = TRUE;
	$arSQL = $this->SQL_forUpdateMe($iSet);
	$this->objDB->Exec('START TRANSACTION');
	foreach ($arSQL as $sql) {
	    $this->sqlExec .= $sql."\n<br>";
	    $okTbl = $this->objDB->Exec($sql);
	    if (!$okTbl) {
		$ok = FALSE;
	    }
	}
	if ($ok) {
	    $sqlDone = 'COMMIT';
	} else {
	    $sqlDone = 'ROLLBACK';
	}
	$this->Engine()->Exec($sqlDone);
	return $ok;
    }
    /*----
      NOTE: Trying to replicate the function of $iWhere in chained tables is too complicated for now,
	and I don't think I ever use it anyway. Maybe later the self-updating version should be UpdateMe(),
	and we could have a simpler Update() which only does the current table (no chaining) with a specified
	filter. UpdateMe() should be introduced in the keyed table types.
    */
    public function SQL_forUpdateMe(array $iSet) {
	$arSet = $iSet;
	$sqlID = SQLValue($this->KeyValue());
	// search through each table's fields looking for the ones in the request
	foreach ($this->Table()->Dependencies() as $sTbl => $arSpec) {
	    $arSetTbl = NULL;
	    foreach($arSpec['fields'] as $sFld) {
		if (array_key_exists($sFld,$arSet)) {
		    $arSetTbl[$sFld] = $arSet[$sFld];	// copy to set list for this table
		    unset($arSet[$sFld]);		// remove it from the unsatisfied list
		}
	    }
	    $sKeyName = $arSpec['key'];
	    $sqlWhere = "$sKeyName=$sqlID";
	    if (!is_null($arSetTbl)) {	// if any fields being updated in the current table...
		// get and save the SQL for updating this table
		$sql = clsTable_abstract::_SQL_forUpdate($sTbl,$arSetTbl,$sqlWhere);
		$arSQL[$sTbl] = $sql;
	    }
	}
	// check for fields which could not be found
	clsTable_chained::CheckFieldRequests($arSet,$iSet);

	return $arSQL;
    }
}

class clsIndexer_Table_chained extends clsIndexer_Table {
    public function GetItem($iID=NULL,$iClass=NULL) {
	if (is_null($iID)) {
	    $objItem = $this->TableObj()->SpawnItem($iClass);
	    $objItem->KeyValue(NULL);
	} else {
	    assert('!is_array($iID); /* TABLE='.$this->TableObj()->Name().' */');
	    $oTbl = $this->TableObj();
	    $sql = $oTbl->SQL_forSelect();
	    $sql .= ' WHERE '.$this->TableObj()->KeyNameRet().'='.$iID;
	    $objItem = $oTbl->DataSQL($sql);
	    $objItem->NextRow();
	}
	return $objItem;
    }
}
