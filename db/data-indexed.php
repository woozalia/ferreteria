<?php
/*
  PURPOSE: table/record classes that handle indexing
  HISTORY:
    2013-11-06 split off from data.php
*/
/*=============
  NAME: clsTable_indexed
  PURPOSE: handles indexes via a helper object
*/
class clsTable_indexed extends clsTable_keyed_abstract {
    protected $objIdx;

    /*----
      NOTE: In practice, how would you ever have the Indexer object created before the Table object,
	since the Indexer object requires a Table object in its constructor? Possibly descendent classes
	can create the Indexer in their constructors and then pass it back to the parent constructor,
	which lets you have a default Indexer that you can override if you need, but how useful is this?
    */
    public function __construct(clsDatabase $iDB, clsIndexer_Table $iIndexer=NULL) {
	parent::__construct($iDB);
	$this->Indexer($iIndexer);
    }
    // BOILERPLATE BEGINS
    protected function Indexer(clsIndexer_Table $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->objIdx = $iObj;
	}
	return $this->objIdx;
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
    // BOILERPLATE ENDS
    // OVERRIDES
    /*----
      ADDS: spawns an indexer and attaches it to the item
    */
    protected function ReleaseItem(clsRecs_abstract $iItem) {
	parent::ReleaseItem($iItem);
	$this->Indexer()->InitRecs($iItem);
    }
    /*----
      ADDS: spawns an indexer and attaches it to the item
    */
/*
    public function SpawnItem($iClass=NULL) {
	$obj = parent::SpawnItem($iClass);
	return $obj;
    }
*/
}
/*=============
  NAME: clsRecs_indexed
*/
class clsRecs_indexed extends clsRecs_keyed_abstract {
    protected $objIdx;

/* This is never used
    public function __construct(clsIndexer_Recs $iIndexer=NULL) {
	$this->Indexer($iIndexer);
    }
*/
    // BOILERPLATE BEGINS
    public function Indexer(clsIndexer_Recs $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->objIdx = $iObj;
	}
	assert('is_object($this->objIdx);');
	return $this->objIdx;
    }
    public function IsNew() {
	return !$this->Indexer()->IndexIsSet();
    }
    /*----
      USED BY: Administrative UI classes which need a string for referring to a particular record
    */
    public function KeyString() {
	return $this->Indexer()->KeyString();
    }
    // for compatibility with menu-helper.php
    public function KeyValue() {
	return $this->KeyString();
    }
    public function SelfFilter() {
	return $this->Indexer()->SQL_forWhere();
    }
    public function SQL_forUpdate(array $iSet,$iWhere) {
	return $this->Indexer()->SQL_forUpdate($iSet,$iWhere);
    }
    public function SQL_forUpdateMe(array $iSet) {
	return $this->Indexer()->SQL_forUpdate($iSet);
    }
    // BOILERPLATE ENDS
    public function SQL_forMake(array $iarSet) { die('Not yet written.'); }
}
