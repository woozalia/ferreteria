<?php
/*
  PURPOSE: table/record classes that handle caching
    clsObjectCache (root class)
    clsTableCache -- DEPRECATED (use clsCache_Table helper class)
    clsCache_Table (root class)
  HISTORY:
    2013-11-06 split off from data.php
*/
/*%%%%
  PURPOSE: for tracking whether a cached object has the expected data or not
  HISTORY:
    2011-03-30 written
*/
class clsObjectCache {
    private $vKey;
    private $vObj;

    public function __construct() {
	$this->vKey = NULL;
	$this->vObj = NULL;
    }
    public function IsCached($iKey) {
	if (is_object($this->vObj)) {
	    return ($this->vKey == $iKey);
	} else {
	    return FALSE;
	}
    }
    public function Object($iObj=NULL,$iKey=NULL) {
	if (!is_null($iObj)) {
	    $this->vKey = $iKey;
	    $this->vObj = $iObj;
	}
	return $this->vObj;
    }
    public function Clear() {
	$this->vObj = NULL;
    }
}
// DEPRECATED -- use clsCache_Table helper class
class clsTableCache extends clsTable {
    private $arCache;

    public function GetItem($id=NULL,$sClass=NULL) {
	if (!clsArray::Exists($this->arCache,$id)) {
	    $sKeyName = $this->vKeyName;
	    $sqlVal = $this->Engine()->SanitizeAndQuote($id);
	    $rc = $this->GetData("$sKeyName=$sqlVal",$sClass);
	    $rc->NextRow();
	    $this->arCache[$id] = $rc->RowCopy();
	}
	return $this->arCache[$id];
    }
}
/*====
  CLASS: cache for Tables
  ACTION: provides a cached GetItem()
  USAGE: clsTable descendants should NOT override GetItem() or GetData() to use this class,
    as the class needs those methods to load data into the cache.
  BOILERPLATE:
    protected $objCache;
    protected function Cache() {
	if (!isset($this->objCache)) {
	    $this->objCache = new clsCache_Table($this);
	}
	return $this->objCache;
    }
    public function GetItem_Cached($iID=NULL,$iClass=NULL) {
	return $this->Cache()->GetItem($iID,$iClass);
    }
    public function GetData_Cached($iWhere=NULL,$iClass=NULL,$iSort=NULL) {
	return $this->Cache()->GetItem($iWhere,$iClass,$iSort);
    }
*/
/*----
*/
class clsCache_Table {
    protected $objTbl;
    protected $arRows;	// arRows[id] = rows[]
    protected $arSets;	// caches entire datasets

    public function __construct(clsTable $iTable) {
	$this->objTbl = $iTable;
    }
    public function GetItem($iID=NULL,$iClass=NULL) {
	$objTbl = $this->objTbl;
	if (isset($this->arRows[$iID])) {
	    $objItem = $objTbl->SpawnItem($iClass);
	    $objItem->Row = $this->arCache[$iID];
	} else {
	    $objItem = $objTbl->GetItem($iID,$iClass);
	    $this->arCache[$iID] = $objItem->Row;
	}
	return $objItem;
    }
    /*----
      HISTORY:
	2011-02-11 Renamed GetData_Cached() to GetData()
	  This was probably a leftover from before multiple inheritance
	  Fixed some bugs. Renamed from GetData() to GetData_array()
	    because caching the resource blob didn't seem to work very well.
	  Now returns an array instead of an object.
      FUTURE: Possibly we should be reading all rows into memory, instead of just saving the Res.
	That way, Res could be protected again instead of public.
    */
    public function GetData_array($iWhere=NULL,$iClass=NULL,$iSort=NULL) {
	$objTbl = $this->objTbl;
	$strKeyFilt = "$iWhere\t$iSort";
	$isCached = FALSE;
	if (is_array($this->arSets)) {
	    if (array_key_exists($strKeyFilt,$this->arSets)) {
		$isCached = TRUE;
	    }
	}
	if ($isCached) {
	    //$objSet = $objTbl->SpawnItem($iClass);
	    //$objSet->Res = $this->arSets[$strKey];
	    //assert('is_resource($objSet->Res); /* KEY='.$strKey.'*/');

	    // 2011-02-11 this code has not been tested yet
//echo '<pre>'.print_r($this->arSets,TRUE).'</pre>';
	    foreach ($this->arSets[$strKeyFilt] as $key) {
		$arOut[$key] = $this->arRows[$key];
	    }
	} else {
	    $objSet = $objTbl->GetData($iWhere,$iClass,$iSort);
	    while ($objSet->NextRow()) {
		$strKeyRow = $objSet->KeyString();
		$arOut[$strKeyRow] = $objSet->Values();
		$this->arSets[$strKeyFilt][] = $strKeyRow;
	    }
	    if (is_array($this->arRows)) {
		$this->arRows = array_merge($this->arRows,$arOut);	// add to cached rows
	    } else {
		$this->arRows = $arOut;	// start row cache
	    }
	}
	return $arOut;
    }
}
