<?php
/* ===========================
 *** DATA UTILITY CLASSES ***
  AUTHOR: Woozle (Nick) Staddon
  HISTORY:
    2007-05-20 (wzl) These classes have been designed to be db-engine agnostic, but I wasn't able
	  to test against anything other than MySQL nor was I able to implement the usage of
	  the dbx_ functions, as the system that I was using didn't have them installed.
    2007-08-30 (wzl) posting this version at http://htyp.org/User:Woozle/data.php
    2007-12-24 (wzl) Some changes seem to have been made as recently as 12/17, so posting updated version
    2008-02-06 (wzl) Modified to use either mysqli or (standard) mysql library depending on flag; the latter isn't working yet
    2009-03-10 (wzl) adding some static functions to gradually get rid of the need for object factories
    2009-03-18 (wzl) debug constants now have defaults
    2009-03-26 (wzl) clsDataSet.Query() no longer fetches first row; this will require some rewriting
      NextRow() now returns TRUE if data was fetched; use if (data->NextRow()) {..} to loop through data.
    2009-05-02 (wzl) undocumented changes -- looks like:
      assert-checks return ID of an insertion
      function ExecUpdate($iSet,$iWhere)
      function SQLValue($iVal)
    2009-05-03 (wzl) more undocumented changes -- looks like mainly $iWhere is now optional in GetData()
    2009-07-05 (wzl) DataSet->__get now returns NULL if no field found; DataSet->HasField()
    2009-07-18 (wzl) clsTable::ExecUpdate() -> Update(); clsTable::Insert()
    2009-08-02 (wzl) clsDatabase::RowsAffected()
    2009-10-07 (wzl) minor: $dbg global added to clsTable Update() and Insert() methods
    2009-10-27 (wzl) clsTableCache
    2009-11-23 (wzl) clsDatabase.LogSQL(); some format-tidying
    2009-12-29 (wzl) clsDataSet_bare
    2010-01-02 (wzl) clsTable::DataSet()
    2010-01-08 (wzl) ifEmpty()
    2010-01-09 (wzl) fixed bug in clsDataSet_bare::Reload()
    2010-02-07 (wzl) clsTable::LastID()
    2010-04-11 (wzl) clsTable::KeyValue()
    2010-05-28 (wzl) split() is now deprecated -- replacing it with preg_split()
    2010-06-14 (wzl) added $iClass=NULL parameter to clsTable::SpawnItem
    2010-06-16 (wzl) nzApp()
    2010-07-19 (wzl) clsDatabase::Make()
    2010-10-04 (wzl) clsTable::ActionKey()
    2010-10-05 (wzl) removed reloading code from clsDataSet::Update()
    2010-10-16 (wzl) added clsTable::NameSQL(), clsTable::DataSetGroup()
    2010-10-19 (wzl) clsTable::DataSQL()
    2010-11-01 (wzl) clsTable::GetItem iID=NULL now means create new/blank object, i.e. SpawnItem()
    2010-11-14 (wzl) clsDataSet_bare::SameAs()
    2010-11-21 (wzl) caching helper class
    2011-02-07 (wzl) SQLValue() now handles arrays too
    2011-09-24 (wzl) Data Scripting classes created
    2011-10-07 (wzl) Data Scripting extracted to data-script.php
    2011-10-17 (wzl) ValueNz() rewritten (now goes directly to Row array instead of calling Value())
    2012-01-22 (wzl) clsDatabase_abstract
    2012-01-28 (wzl) clsDataEngine classes
    2012-12-31 (wzl) improved error handling in clsDataEngine_MySQL.db_open()
    2013-01-24 (wzl) clsDatabase_abstract:: SelfClass() and Spawn()
    2015-07-15 resolving conflicts with other edited version
    2016-07-12
      * Have to get mysqli class working, because PHP 7 won't use old mysql calls.
      * Removed debugging functions. This will break some things, but just remove the calls.
	* Also removed debugging-flag constants.
*/

define('KS_DEFAULT_ENGINE','clsDataEngine_MySQLi');	// classname of default database engine

abstract class clsDatabase_abstract {
    protected $objRes;	// result object
    protected $cntOpen;

    // ++ SETUP ++ //

    public function InitBase() {
	$this->cntOpen = 0;
    }

    // -- SETUP -- //
    // ++ OBJECTS ++ //

    protected function EngineClass() {
	$cls = KS_DEFAULT_ENGINE;
	return new $cls;
    }
    private $oEng;	// engine object
    public function Engine(clsDataEngine $iEngine=NULL) {
	if (!is_null($iEngine)) {
	    $this->oEng = $iEngine;
	} else {
	    if (!isset($this->oEng)) {
		$this->oEng = $this->EngineClass();
	    }
	}
	return $this->oEng;
    }
    /*-----
      ACTION: Create a table object of the requested class
      HISTORY:
	2010-12-01 Added iID parameter to get singular item
	2011-02-23 Changed from protected to public, to support class registration
	2012-01-23 Moved from clsDatabase to (new) clsDatabase_abstract
	2015-09-07
	  TODO: This *really* ought to be modified just slightly to keep the table objects in an array.
	  Do it when everything else is working.
	  TODO: Rename from Make() to MakeTableObject() OSLT.

    */
    public function Make($sClass,$id=NULL) {
	if (!isset($this->$sClass)) {
	    if (class_exists($sClass)) {
		$this->$sClass = new $sClass($this);
	    } else {
		throw new exception('Unknown class "'.$sClass.'" requested.');
	    }
	}
	if (is_null($id)) {
	    // table-type
	    return $this->$sClass;
	} else {
	    // recordset-type
	    return $this->$sClass->GetItem($id);
	}
    }

    // -- OBJECTS -- //
    // ++ CONNECTION ++ //

    public function Open() {
	if ($this->cntOpen == 0) {
	    $this->Engine()->db_open();
	}
	if (!$this->isOk()) {
	    $this->Engine()->db_get_error();
	}
	$this->cntOpen++;
    }
    public function Shut() {
	$this->cntOpen--;
	if ($this->cntOpen == 0) {
	    $this->Engine()->db_shut();
	}
    }

    // -- CONNECTION -- //
    // ++ DATA ACTIONS ++ //

    abstract public function Exec($iSQL);
    abstract public function DataSet($iSQL=NULL,$iClass=NULL);	// TODO: deprecate this; use Query() instead
    public function Query($sql,$sClass=NULL) { return $this->DataSet($sql,$sClass); }

    // -- DATA ACTIONS -- //
    // ++ ENGINE WRAPPER FUNCTIONS ++ //

    public function engine_db_query($iSQL) {
	return $this->Engine()->db_query($iSQL);
    }
    public function engine_db_query_ok() {
	return $this->objRes->is_okay();
    }
    public function engine_db_get_new_id() {
	return $this->Engine()->db_get_new_id();
    }
    public function engine_db_rows_affected() {
	return $this->Engine()->db_get_qty_rows_chgd();
    }
    public function engine_db_safe_param($val) {
	return $this->Engine()->db_safe_param($val);
    }
    public function Sanitize($val) {
	return $this->engine_db_safe_param($val);
    }
    public function SanitizeAndQuote($val) {
	$sql = $this->Sanitize($val);
	if (!is_numeric($val)) {
	    $sql = '"'.$sql.'"';
	}
	return $sql;
    }
/*
    public function engine_row_rewind() {
	return $this->objRes->do_rewind();
    }
    public function engine_row_get_next() { throw new exception('how did we get here?');
	return $this->objRes->get_next();
    }
    public function engine_row_get_count() {
	return $this->objRes->get_count();
    }
    public function engine_row_was_filled() {
	return $this->objRes->is_filled();
    }
*/

    // -- ENGINE WRAPPER FUNCTIONS -- //
}


/*====
  TODO: this is actually specific to a particular library for MySQL, so it should probably be renamed
    to reflect that.
*/
class clsDatabase extends clsDatabase_abstract {
    private $strType;	// type of db (MySQL etc.)
    private $strUser;	// database user
    private $strPass;	// password
    private $strHost;	// host (database server domain-name or IP address)
    private $strName;	// database (schema) name

    private $Conn;	// connection object
  // status
    private $strErr;	// latest error message
    public $sql;	// last SQL executed (or attempted)
    public $arSQL;	// array of all SQL statements attempted
    public $doAllowWrite;	// FALSE = don't execute UPDATE or INSERT commands, just log them

    public function __construct($iConn) {
	$this->Init($iConn);
	$this->doAllowWrite = TRUE;	// default
    }
    /*=====
      INPUT:
	$iConn: type:user:pass@server/dbname
      TO DO:
	Init() -> InitSpec()
	InitBase() -> Init()
    */
    public function Init($iConn) {
	$this->InitBase();
	$this->Engine()->InitSpec($iConn);
    }

    /*=====
      PURPOSE: For debugging, mainly
      RETURNS: TRUE if database connection is supposed to be open
    */
    public function isOpened() {
	return ($this->cntOpen > 0);
    }
    /*=====
      PURPOSE: Checking status of a db operation
      RETURNS: TRUE if last operation was successful
    */
    public function isOk() {
	if (empty($this->strErr)) {
	    return TRUE;
	} elseif ($this->Conn == FALSE) {
	    return FALSE;
	} else {
	    return FALSE;
	}
    }
    public function getError() {
      if (is_null($this->strErr)) {
      // avoid having an ok status overwrite an actual error
	  $this->strErr = $this->Engine()->db_get_error();
      }
      return $this->strErr;
    }
    public function ClearError() {
	$this->strErr = NULL;
    }
    protected function LogSQL($iSQL) {
	$this->sql = $iSQL;
	$this->arSQL[] = $iSQL;
    }
    public function ListSQL($iPfx=NULL) {
	$out = '';
	foreach ($this->arSQL as $sql) {
	    $out .= $iPfx.$sql;
	}
	return $out;
    }
    /*----
      HISTORY:
	2011-03-04 added DELETE to list of write commands; rewrote to be more robust
    */
    protected function OkToExecSQL($iSQL) {
	if ($this->doAllowWrite) {
	    return TRUE;
	} else {
	    // this is a bit of a kluge... need to strip out comments and whitespace
	    // but basically, if the SQL starts with UPDATE, INSERT, or DELETE, then it's a write command so forbid it
	    $sql = strtoupper(trim($iSQL));
	    $cmd = preg_split (' ',$sql,1);	// get just the first word
	    switch ($cmd) {
	      case 'UPDATE':
	      case 'INSERT':
	      case 'DELETE':
		return FALSE;
	      default:
		return TRUE;
	    }
	}
    }
    /*----
      HISTORY:
	2011-02-24 Now passing $this->Conn to mysql_query() because somehow the connection was getting set
	  to the wrong database.
    */
    public function Exec($iSQL) {
	$this->LogSQL($iSQL);
	$ok = TRUE;
	if ($this->OkToExecSQL($iSQL)) {
	    // db_query() RETURNS: clsDataResult descendant
	    $res = $this->Engine()->db_query($iSQL);
	    if (!$res->is_okay()) {
		//$this->getError();
		$ok = FALSE;
	    }
	}
	return $ok;
    }

    public function RowsAffected() {
	return $this->Engine()->db_get_qty_rows_chgd();
    }
    public function NewID($iDbg=NULL) {
	return $this->engine_db_get_new_id();
    }

    public function TransactionOpen() {
	return $this->Engine()->db_transaction_open();
    }
    public function TransactionSave() {
	return $this->Engine()->db_transaction_save();
    }
    public function TransactionKill() {
	return $this->Engine()->db_transaction_kill();
    }
    public function SafeParam($iVal) {
	if (is_object($iVal)) {
	    echo '<b>Internal error</b>: argument is an object of class '.get_class($iVal).', not a string.<br>';
	    throw new exception('Unexpected argument type.');
	}
	$out = $this->Engine()->db_safe_param($iVal);
	return $out;
    }
    public function ErrorText() {
	if ($this->strErr == '') {
	    $this->_api_getError();
	}
	return $this->strErr;
    }

/******
 SECTION: OBJECT FACTORY
*/
    public function DataSet($iSQL=NULL,$iClass=NULL) {
	if (is_string($iClass)) {
	    if (!class_exists($iClass,TRUE)) {
		throw new exception('Class "'.$iClass.'" does not seem to be defined anywhere.');
	    }

	    $rc = new $iClass($this);

	    if (!is_object($rc)) {
		throw new exception('Could not create object of class "'.$iClass.'".');
	    }
	    if (!($rc instanceof clsRecs_abstract)) {
		throw new exception('Class "'.$iClass.'" is not a recordset-type class.');
	    }
	} else {
	    $rc = new clsDataSet($this);
	}
	if (!is_null($iSQL)) {
	    if (is_object($rc)) {
		$rc->Query($iSQL);
	    }
	}
	return $rc;
    }
}
