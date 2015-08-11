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
  FUTURE:
    API FIXES:
      GetData() should not have an $iClass parameter, or it should be the last parameter.
*/
// Select which DB library to use --
//	exactly one of the following must be true:
/*

These have been replaced by KS_DEFAULT_ENGINE

define('KF_USE_MYSQL',TRUE);	// in progress
define('KF_USE_MYSQLI',FALSE);	// complete & tested
define('KF_USE_DBX',false);	// not completely written; stalled
*/

define('KS_DEFAULT_ENGINE','clsDataEngine_MySQL');	// classname of default database engine

if (!defined('KDO_DEBUG')) {		define('KDO_DEBUG',FALSE); }
if (!defined('KDO_DEBUG_STACK')) {	define('KDO_DEBUG_STACK',FALSE); }
if (!defined('KDO_DEBUG_IMMED')) {	define('KDO_DEBUG_IMMED',FALSE); }
if (!defined('KS_DEBUG_HTML')) {	define('KS_DEBUG_HTML',FALSE); }
if (!defined('KDO_DEBUG_DARK')) {	define('KDO_DEBUG_DARK',FALSE); }

abstract class clsDatabase_abstract {
    protected $objEng;	// engine object
    protected $objRes;	// result object
    protected $cntOpen;


    public function InitBase() {
	$this->cntOpen = 0;
    }
    protected function DefaultEngine() {
	$cls = KS_DEFAULT_ENGINE;
	return new $cls;
    }
    public function Engine(clsDataEngine $iEngine=NULL) {
	if (!is_null($iEngine)) {
	    $this->objEng = $iEngine;
	} else {
	    if (!isset($this->objEng)) {
		$this->objEng = $this->DefaultEngine();
	    }
	}
	return $this->objEng;
    }
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

    /*-----
      PURPOSE: generic table-creation function
      HISTORY:
	2010-12-01 Added iID parameter to get singular item
	2011-02-23 Changed from protected to public, to support class registration
	2012-01-23 Moved from clsDatabase to (new) clsDatabase_abstract
    */
    public function Make($sClass,$id=NULL) {
	if (!isset($this->$sClass)) {
	    if (class_exists($sClass)) {
		$this->$sClass = new $sClass($this);
	    } else {
		throw new exception('Unknown class "'.$sClass.'" requested.');
	    }
	}
	if (!is_null($id)) {
	    return $this->$sClass->GetItem($id);
	} else {
	    return $this->$sClass;
	}
    }
    abstract public function Exec($iSQL);
    abstract public function DataSet($iSQL=NULL,$iClass=NULL);	// TODO: deprecate this; use Query() instead
    public function Query($sql,$sClass=NULL) { return $this->DataSet($sql,$sClass); }

    // ENGINE WRAPPER FUNCTIONS
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
}
/*%%%%
  HISTORY:
    2013-01-25 InitSpec() only makes sense for _CliSrv, which is the first descendant that needs connection credentials.
*/
abstract class clsDataEngine {
    private $arSQL;

    //abstract public function InitSpec($iSpec);
    abstract public function db_open();
    abstract public function db_shut();
    abstract public function db_transaction_open();
    abstract public function db_transaction_save();
    abstract public function db_transaction_kill();

    /*----
      RETURNS: clsDataResult descendant
    */
    public function db_query($iSQL) {
	$this->LogSQL($iSQL);
	return $this->db_do_query($iSQL);
    }
    /*----
      RETURNS: clsDataResult descendant
    */
    abstract protected function db_do_query($iSQL);
    //abstract public function db_get_error();
    abstract public function db_get_new_id();
    abstract public function db_safe_param($iVal);
    //abstract public function db_query_ok(array $iBox);
    abstract public function db_get_error();
    abstract public function db_get_qty_rows_chgd();

    // LOGGING -- eventually split this off into handler class
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
}
/*%%%%
  PURPOSE: encapsulates the results of a query
*/
abstract class clsDataResult {
    protected $box;

    public function __construct(array $iBox=NULL) {
	$this->box = $iBox;
    }
    /*----
      PURPOSE: The "Box" is an array containing information which this class needs but which
	the calling class has to be responsible for. The caller doesn't need to know what's
	in the box, it just needs to keep it safe.
    */
    public function Box(array $iBox=NULL) {
	if (!is_null($iBox)) {
	    $this->box = $iBox;
	}
	return $this->box;
    }
    public function Row(array $iRow=NULL) {
	if (!is_null($iRow)) {
	    $this->box['row'] = $iRow;
	    return $iRow;
	}
	if ($this->HasRow()) {
	    return $this->box['row'];
	} else {
	    return NULL;
	}
    }
    /*----
      USAGE: used internally when row retrieval comes back FALSE
    */
    protected function RowClear() {
	$this->box['row'] = NULL;
    }
    public function Val($iKey,$iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->box['row'][$iKey] = $iVal;
	    return $iVal;
	} else {
	    if (!array_key_exists('row',$this->box)) {
		throw new exception('Row data not loaded yet.');
	    }
	    return $this->box['row'][$iKey];
	}
    }
    public function HasRow() {
	if (array_key_exists('row',$this->box)) {
	    return (!is_null($this->box['row']));
	} else {
	    return FALSE;
	}
    }
/* might be useful, but not actually needed now
    public function HasVal($iKey) {
	$row = $this->Row();
	return array_key_exists($iKey,$this->box['row']);
    }
*/
    abstract public function is_okay();
    /*----
      ACTION: set the record pointer so the first row in the set will be read next
    */
    abstract public function do_rewind();
    /*----
      ACTION: Fetch the first/next row of data from a result set
    */
    abstract public function get_next();
    /*----
      ACTION: Return the number of rows in the result set
    */
    abstract public function get_count();
    /*----
      ACTION: Return whether row currently has data.
    */
    abstract public function is_filled();
}

/*%%%%%
  PURPOSE: clsDataEngine that is specific to client-server databases
    This type will always need host and schema names, username, and password.
*/
abstract class clsDataEngine_CliSrv extends clsDataEngine {
    protected $strType, $strHost, $strUser, $strPass;

    public function InitSpec($iSpec) {
	$ar = preg_split('/@/',$iSpec);
	if (array_key_exists(1,$ar)) {
	    list($part1,$part2) = preg_split('/@/',$iSpec);
	} else {
	    throw new exception('Connection string not formatted right: ['.$iSpec.']');
	}
	list($this->strType,$this->strUser,$this->strPass) = preg_split('/:/',$part1);
	list($this->strHost,$this->strName) = explode('/',$part2);
	$this->strType = strtolower($this->strType);	// make sure it is lowercased, for comparison
	$this->strErr = NULL;
    }
    public function Host() {
	return $this->strHost;
    }
    public function User() {
	return $this->strUser;
    }
}

class clsDataResult_MySQL extends clsDataResult {

    /*----
      HISTORY:
	2012-09-06 This needs to be public so descendant helper classes can transfer the resource.
    */
    public function Resource($iRes=NULL) {
	if (!is_null($iRes)) {
	    $this->box['res'] = $iRes;
	}
	return $this->box['res'];
    }
    /*----
      NOTES:
	* For queries returning a resultset, mysql_query() returns a resource on success, or FALSE on error.
	* For other SQL statements, INSERT, UPDATE, DELETE, DROP, etc, mysql_query() returns TRUE on success or FALSE on error.
      HISTORY:
	2012-02-04 revised to use box['ok']
    */
    public function do_query($iConn,$iSQL) {
	$res = mysql_query($iSQL,$iConn);
	if (is_resource($res)) {
	    $this->Resource($res);
	    $this->box['ok'] = TRUE;
	} else {
	    $this->Resource(NULL);
	    $this->box['ok'] = $res;	// TRUE if successful, false otherwise
	}
    }
    /*----
      USAGE: call after do_query()
      FUTURE: should probably reflect status of other operations besides do_query()
      HISTORY:
	2012-02-04 revised to use box['ok']
    */
    public function is_okay() {
	return $this->box['ok'];
    }
    public function do_rewind() {
	$res = $this->Resource();
	mysql_data_seek($res, 0);
    }
    public function get_next() {
	$res = $this->Resource();
	if (is_resource($res)) {
	    $row = mysql_fetch_assoc($res);
	    if ($row === FALSE) {
		$this->RowClear();
		$row = NULL;	// return value must also be null
	    } else {
		$this->Row($row);
	    }
	    return $row;
	} else {
	    return NULL;
	}
    }
    /*=====
      ACTION: Return the number of rows in the result set
    */
    public function get_count() {
	$res = $this->Resource();
	if ($res === FALSE) {
	    return NULL;
	} else {
	    if (is_resource($res)) {
		$arRow = mysql_num_rows($res);
		return $arRow;
	    } else {
		return NULL;
	    }
	}
    }
    public function is_filled() {
	return $this->HasRow();
    }
}

class clsDataEngine_MySQL extends clsDataEngine_CliSrv {
    private $objConn;	// connection object

    public function db_open() {
	$this->objConn = @mysql_connect( $this->strHost, $this->strUser, $this->strPass, false );
	if ($this->objConn === FALSE) {
	    $arErr = error_get_last();
	    throw new exception('MySQL could not connect: '.$arErr['message']);
	} else {
	    $ok = mysql_select_db($this->strName, $this->objConn);
	    if (!$ok) {
		throw new exception('MySQL could not select database "'.$this->strName.'": '.mysql_error());
	    }
	}
    }
    public function db_shut() {
	mysql_close($this->objConn);
    }
    public function db_transaction_open() {
	$oRes = $this->db_do_query('START TRANSACTION');
    }
    public function db_transaction_save() {
	$oRes = $this->db_do_query('COMMIT');
    }
    public function db_transaction_kill() {
	$this->db_do_query(' ROLLBACK');
    }

    protected function Spawn_ResultObject() {
	return new clsDataResult_MySQL();
    }
    /*----
      RETURNS: clsDataResult descendant
    */
    protected function db_do_query($iSQL) {
	if (is_resource($this->objConn)) {
	    $obj = $this->Spawn_ResultObject();
	    $obj->do_query($this->objConn,$iSQL);
	    return $obj;
	} else {
	    throw new Exception('Database Connection object is a '.gettype($this->objConn).', not a resource');
	}
    }
    public function db_get_new_id() {
	$id = mysql_insert_id($this->objConn);
	return $id;
    }
    public function db_get_error() {
	return mysql_error();
    }
    public function db_safe_param($iVal) {
	if (is_resource($this->objConn)) {
	    $out = mysql_real_escape_string($iVal,$this->objConn);
	} else {
	    throw new exception(get_class($this).'.SafeParam("'.$iString.'") has no connection.');
	}
	return $out;
    }
    public function db_get_qty_rows_chgd() {
	return mysql_affected_rows($this->objConn);
    }
}

/*
  These interfaces marked "abstract" have not been completed or tested.
    They're mainly here as a place to stick the partial code I wrote for them
    back when I first started writing the data.php library.
*/
abstract class clsDataEngine_MySQLi extends clsDataEngine_CliSrv {
    private $objConn;	// connection object

    public function db_open() {
	$this->objConn = new mysqli($this->strHost,$this->strUser,$this->strPass,$this->strName);
    }
    public function db_shut() {
	$this->objConn->close();
    }
    public function db_get_error() {
	return $this->objConn->error;
    }
    public function db_safe_param($iVal) {
	return $this->objConn->escape_string($iVal);
    }
    protected function db_do_query($iSQL) {
	$this->objConn->real_query($iSQL);
	return $this->objConn->store_result();
    }
    public function db_get_new_id() {
	$id = $this->objConn->insert_id;
	return $id;
    }
    public function row_do_rewind(array $iBox) {
    }
    public function row_get_next(array $iBox) {
	return $iRes->fetch_assoc();
    }
    public function row_get_count(array $iBox) {
	return $iRes->num_rows;
    }
    public function row_was_filled(array $iBox) {
	return ($this->objData !== FALSE) ;
    }
}
abstract class clsDataEngine_DBX extends clsDataEngine_CliSrv {
    private $objConn;	// connection object

    public function db_open() {
	$this->objConn = dbx_connect($this->strType,$this->strHost,$this->strName,$this->strUser,$this->strPass);
    }
    public function db_shut() {
	dbx_close($this->Conn);
    }

    protected function db_do_query($iSQL) {
	return dbx_query($this->objConn,$iSQL,DBX_RESULT_ASSOC);
    }
    public function db_get_new_id() {
    }
    public function row_do_rewind(array $iBox) {
    }
    public function row_get_next(array $iBox) {
    }
    public function row_get_count(array $iBox) {
    }
    public function row_was_filled(array $iBox) {
    }
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

// HELPER CLASSES

class clsSQLFilt {
    private $arFilt;
    private $strConj;
    private $sVerb;	// NOT YET IMPLEMENTED
    private $sSort;
    private $nMax;

    public function __construct($iConj) {
	$this->strConj = $iConj;
	$this->arFilt = NULL;
	$this->sVerb = NULL;
	$this->sSort = NULL;
	$this->nMax = NULL;
    }
    public function Verb($sVerb) {	// NOT YET IMPLEMENTED
	$this->sVerb = $sVerb;
    }
    public function Order($sSort=NULL) {
	if (!empty($sSort)) {
	    $this->sSort = $sSort;
	}
	return $this->sSort;
    }
    protected function HasOrder() {
	return (!is_null($this->sSort));
    }
    protected function RenderOrder() {
	if ($this->HasOrder()) {
	    return ' ORDER BY '.$this->Order();
	} else {
	    return NULL;
	}
    }
    public function Limit($nMax=NULL) {
	if (!empty($nMax)) {
	    $this->nMax = $nMax;
	}
	return $this->nMax;
    }
    protected function HasLimit() {
	return (!is_null($this->nMax));
    }
    protected function RenderLimit() {
	if ($this->HasLimit()) {
	    return ' LIMIT '.$this->Limit();
	} else {
	    return NULL;
	}
    }
    /*-----
      ACTION: Add a condition
    */
    public function AddCond($iSQL) {
	$this->arFilt[] = $iSQL;
    }
    public function RenderFilter($sqlPrefix=NULL) {
	$out = NULL;
	if (is_array($this->arFilt)) {
	    foreach ($this->arFilt as $sql) {
		if ($out != '') {
		    $out .= ' '.$this->strConj.' ';
		}
		$out .= '('.$sql.')';
	    }
	}
	if (is_null($out)) {
	    return '';
	} else {
	    return $sqlPrefix.$out;
	}
    }
    // TODO: render the selection part as well
    public function RenderQuery() {
	$sql = $this->RenderFilter('WHERE ')
	  .$this->RenderOrder()
	  .$this->RenderLimit()
	  ;
	return $sql;
    }
}
/* ========================
 *** UTILITY FUNCTIONS ***
*/
/*----
  PURPOSE: This gets around PHP's apparent lack of built-in object type-conversion.
  ACTION: Copies all public fields from iSrce to iDest
*/
function CopyObj(object $iSrce, object $iDest) {
    foreach($iSrce as $key => $val) {
	$iDest->$key = $val;
    }
}
if (!function_exists('Pluralize')) {
    function Pluralize($iQty,$iSingular='',$iPlural='s') {
	  if ($iQty == 1) {
		  return $iSingular;
	  } else {
		  return $iPlural;
	  }
  }
}

function SQLValue($iVal) {
    if (is_array($iVal)) {
	foreach ($iVal as $key => $val) {
	    $arOut[$key] = SQLValue($val);
	}
	return $arOut;
    } else {
	if (is_null($iVal)) {
	    return 'NULL';
	} else if (is_bool($iVal)) {
	    return $iVal?'TRUE':'FALSE';
	} else if (is_string($iVal)) {
	    $oVal = '"'.mysql_real_escape_string($iVal).'"';
	    return $oVal;
	} else {
    // numeric can be raw
    // all others, we don't know how to handle, so return raw as well
	    return $iVal;
	}
    }
}
function SQL_for_filter(array $iVals) {
    $sql = NULL;
    foreach ($iVals as $name => $val) {
	if (!is_null($sql)) {
	    $sql .= ' AND ';
	}
	$sql .= '('.$name.'='.SQLValue($val).')';
    }
throw new exception('How did we get here?');
    return $sql;
}
function NoYes($iBool,$iNo='no',$iYes='yes') {
    if ($iBool) {
	return $iYes;
    } else {
	return $iNo;
    }
}

function nz(&$iVal,$default=NULL) {
    return empty($iVal)?$default:$iVal;
}
/*-----
  FUNCTION: nzAdd -- NZ Add
  RETURNS: ioVal += iAmt, but assumes ioVal is zero if not set (prevents runtime error)
  NOTE: iAmt is a reference so that we can pass variables which might not be set.
    Need to document why this is better than being able to pass constants.
*/
function nzAdd(&$ioVal,$nAmt=NULL) {
    $intAmt = is_null($nAmt)?0:$nAmt;
    if (empty($ioVal)) {
	$ioVal = $intAmt;
    } else {
	$ioVal += $intAmt;
    }
    return $ioVal;
}
/*-----
  FUNCTION: nzApp -- NZ Append
  PURPOSE: Like nzAdd(), but appends strings instead of adding numbers
*/
function nzApp(&$ioVal,$iTxt=NULL) {
    if (empty($ioVal)) {
	$ioVal = $iTxt;
    } else {
	$ioVal .= $iTxt;
    }
    return $ioVal;
}
/*----
  HISTORY:
    2012-03-11 iKey can now be an array, for multidimensional iArr
*/
function nzArray(array $iArr=NULL,$iKey,$iDefault=NULL) {
    $out = $iDefault;
    if (is_array($iArr)) {
	if (is_array($iKey)) {
	    $out = $iArr;
	    foreach ($iKey as $key) {
		if (array_key_exists($key,$out)) {
		    $out = $out[$key];
		} else {
		    return $iDefault;
		}
	    }
	} else {
	    if (array_key_exists($iKey,$iArr)) {
		$out = $iArr[$iKey];
	    }
	}
    }
    return $out;
}
function nzArray_debug(array $iArr=NULL,$iKey,$iDefault=NULL) {
    $out = $iDefault;
    if (is_array($iArr)) {
	if (is_array($iKey)) {
	    $out = $iArr;
	    foreach ($iKey as $key) {
		if (array_key_exists($key,$out)) {
		    $out = $out[$key];
		} else {
		    return $iDefault;
		}
	    }
	} else {
	    if (array_key_exists($iKey,$iArr)) {
		$out = $iArr[$iKey];
	    }
	}
    }
//echo '<br>IARR:<pre>'.print_r($iArr,TRUE).'</pre> KEY=['.$iKey.'] RETURNING <pre>'.print_r($out,TRUE).'</pre>';
    return $out;
}
/*----
  PURPOSE: combines the two arrays without changing any keys
    If entries in arAdd have the same keys as arStart, the result
      depends on the value of iReplace
    If entries in arAdd have keys that don't exist in arStart,
      the result depends on the value of iAppend
    This probably means that there are some equivalent ways of doing
      things by reversing order and changing flags, but I haven't
      worked through it yet.
  INPUT:
    arStart: the starting array - key-value pairs
    arAdd: additional key-value pairs to add to arStart
    iReplace:
      TRUE = arAdd values replace same keys in arStart
  RETURNS: the combined array
  NOTE: You'd think one of the native array functions could do this...
    array_merge(): "Values in the input array with numeric keys will be
      renumbered with incrementing keys starting from zero in the result array."
      (This is a problem if the keys are significant, e.g. record ID numbers.)
  HISTORY:
    2011-12-22 written because I keep needing it for cache mapping functions
    2012-03-05 added iReplace, iAppend
*/
function ArrayJoin(array $arStart=NULL, array $arAdd=NULL, $iReplace, $iAppend) {
    if (is_null($arStart)) {
	$arOut = $arAdd;
    } elseif (is_null($arAdd)) {
	$arOut = $arStart;
    } else {
	$arOut = $arStart;
	foreach ($arAdd as $key => $val) {
	    if (array_key_exists($key,$arOut)) {
        	if ($iReplace) {
		    $arOut[$key] = $val;
    		}
	    } else {
    		if ($iAppend) {
		    $arOut[$key] = $val;
    		}
	    }
	}
    }
    return $arOut;
}
/*----
  RETURNS: an array consisting only of keys from $arKeys
    plus the associated values from $arData
*/
function ArrayFilter_byKeys(array $arData, array $arKeys) {
    foreach ($arKeys as $key) {
	if (array_key_exists($key,$arData)) {
	    $arOut[$key] = $arData[$key];
	} else {
	    echo 'KEY ['.$key,'] not found.';
	    echo ' Array contents:<pre>'.print_r($arData,TRUE).'</pre>';
	    throw new exception('Expected key not found.');
	}
    }
    return $arOut;
}
function ifEmpty(&$iVal,$iDefault) {
    if (empty($iVal)) {
	return $iDefault;
    } else {
	return $iVal;
    }
}
function FirstNonEmpty(array $iList) {
    foreach ($iList as $val) {
	if (!empty($val)) {
	    return $val;
	}
    }
}
/*----
  ACTION: Takes a two-dimensional array and returns it flipped diagonally,
    i.e. each element out[x][y] is element in[y][x].
  EXAMPLE:
    INPUT      OUTPUT
    +---+---+  +---+---+---+
    | A | 1 |  | A | B | C |
    +---+---+  +---+---+---+
    | B | 2 |  | 1 | 2 | 3 |
    +---+---+  +---+---+---+
    | C | 3 |
    +---+---+
*/
function ArrayPivot($iArray) {
    foreach ($iArray as $row => $col) {
	if (is_array($col)) {
	    foreach ($col as $key => $val) {
		$arOut[$key][$row] = $val;
	    }
	}
    }
    return $arOut;
}
/*----
  ACTION: convert an array to SQL for filtering
  INPUT: iarFilt = array of filter terms; key is ignored
*/
function Array_toFilter($iarFilt) {
    $out = NULL;
    if (is_array($iarFilt)) {
	foreach ($iarFilt as $key => $cond) {
	    if (!is_null($out)) {
		$out .= ' AND ';
	    }
	    $out .= '('.$cond.')';
	}
    }
    return $out;
}
/* ========================
 *** DEBUGGING FUNCTIONS ***
*/

// these could later be expanded to create a call-path for errors, etc.

function CallEnter($iObj,$iLine,$iName) {
  global $intCallDepth, $debug;
  if (KDO_DEBUG_STACK) {
    $strDescr =  ' line '.$iLine.' ('.get_class($iObj).')'.$iName;
    _debugLine('enter','&gt;',$strDescr);
    $intCallDepth++;
    _debugDump();
  }
}
function CallExit($iName) {
  global $intCallDepth, $debug;
  if (KDO_DEBUG_STACK) {
    $intCallDepth--;
    _debugLine('exit','&lt;',$iName);
    _debugDump();
  }
}
function CallStep($iDescr) {
  global $intCallDepth, $debug;
  if (KDO_DEBUG_STACK) {
    _debugLine('step',':',$iDescr);
    _debugDump();
  }
}
function LogError($iDescr) {
  global $intCallDepth, $debug;

  if (KDO_DEBUG_STACK) {
    _debugLine('error',':',$iDescr);
    _debugDump();
  }
}
function _debugLine($iType,$iSfx,$iText) {
    global $intCallDepth, $debug;

    if (KDO_DEBUG_HTML) {
      $debug .= '<span class="debug-'.$iType.'"><b>'.str_repeat('&mdash;',$intCallDepth).$iSfx.'</b> '.$iText.'</span><br>';
    } else {
      $debug .= str_repeat('*',$intCallDepth).'++ '.$iText."\n";
    }
}
function _debugDump() {
    global $debug;

    if (KDO_DEBUG_IMMED) {
	DoDebugStyle();
	echo $debug;
	$debug = '';
    }
}
function DumpArray($iArr) {
  global $intCallDepth, $debug;

  if (KDO_DEBUG) {
    while (list($key, $val) = each($iArr)) {
      if (KS_DEBUG_HTML) {
        $debug .= '<br><span class="debug-dump"><b>'.str_repeat('-- ',$intCallDepth+1).'</b>';
        $debug .= " $key => $val";
        $debug .= '</span>';
      } else {
        $debug .= "/ $key => $val /";
      }
      if (KDO_DEBUG_IMMED) {
        DoDebugStyle();
        echo $debug;
        $debug = '';
      }
    }
  }
}
function DumpValue($iName,$iVal) {
  global $intCallDepth, $debug;

  if (KDO_DEBUG) {
    if (KS_DEBUG_HTML) {
      $debug .= '<br><span class="debug-dump"><b>'.str_repeat('-- ',$intCallDepth+1);
      $debug .= " $iName</b>: [$iVal]";
      $debug .= '</span>';
    } else {
      $debug .= "/ $iName => $iVal /";
    }
    if (KDO_DEBUG_IMMED) {
      DoDebugStyle();
      echo $debug;
      $debug = '';
    }
  }
}
function DoDebugStyle() {
  static $isStyleDone = false;

  if (!$isStyleDone) {
    echo '<style type="text/css"><!--';
    if (KDO_DEBUG_DARK) {
      echo '.debug-enter { background: #666600; }';	// dark highlight
    } else {
      echo '.debug-enter { background: #ffff00; }';	// regular yellow highlight
    }
    echo '--></style>';
    $isStyleDone = true;
  }
}
