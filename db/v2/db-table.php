<?php
/*----
  PURPOSE: wrapper class for a data source
  HISTORY:
    2016-11-07 added fcDataSource so that query-table-types wouldn't need to return a table name
*/

// PURPOSE: data source wrapper class - has a Connection and a singular type and can do SQL, but is not named and can't do much else
abstract class fcDataSource {

    // ++ SETUP ++ //

    public function __construct(fcDataConn $oConn) {
	$this->InitVars();
	$this->SetConnection($oConn);
    }
    protected function InitVars() {}

    // name for singular class
    abstract protected function SingularName();

    // -- SETUP -- //
    // ++ CONFIGURATION ++ //

      // connection object
    private $oConn;
    protected function SetConnection(fcDataConn $oConn=NULL) {
	$this->oConn = $oConn;
    }
    // PUBLIC so Recordset wrapper object can access it
    public function GetConnection() {
	return $this->oConn;
    }

    // -- CONFIGURATION -- //
    // ++ FACTORY ++ //
    
    // NOTE: 2016-11-06 Maybe this should be renamed SpawnRecordsWrapper().
    public function SpawnRecordset() {
	$sClass = $this->SingularName();
	return new $sClass($this);
    }
    
    // -- FACTORY -- //
    // ++ READ DATA ++ //

    public function FetchRecords($sql) {
	$this->sql = $sql;	// for debugging purposes
	return $this->GetConnection()->FetchRecordset($sql,$this);
    }
    
    // -- READ DATA -- //

}
/*----
  PURPOSE: wrapper class for a database table
*/
abstract class fcDataTable extends fcDataSource {
    public $sql;	// for debugging

    // -- CONFIGURATION -- //
    
    // name of database table for which this class is a wrapper
    abstract protected function TableName();
    // PUBLIC because sometimes you have to build SQL queries from multiple tables
    public function TableName_Cooked() {	// db table name sanitized with backticks
	return '`'.$this->TableName().'`';
    }

    // -- CONFIGURATION -- //
    // ++ READ DATA ++ //
    
    protected function FigureSelectSQL($sqlWhere=NULL,$sqlSort=NULL,$sqlOther=NULL) {
	$sql = 'SELECT * FROM '.$this->TableName_Cooked();
	if (!is_null($sqlWhere)) {
	    $sql .= ' WHERE '.$sqlWhere;
	}
	if (!is_null($sqlSort)) {
	    $sql .= ' ORDER BY '.$sqlSort;
	}
	if (!is_null($sqlOther)) {
	    $sql .= ' '.$sqlOther;
	}
	return $sql;
    }
    public function SelectRecords($sqlWhere=NULL,$sqlSort=NULL,$sqlOther=NULL) {
	$sql = $this->FigureSelectSQL($sqlWhere,$sqlSort,$sqlOther);
	return $this->FetchRecords($sql);
    }
    
    // -- READ DATA -- //
    // ++ WRITE DATA ++ //
    
    /*----
      RETURNS: ID of new record, or FALSE if it could not be created
      HISTORY:
	2010-11-16 Added "array" requirement for iData
	2010-11-20 Calculation now takes place in SQL_forInsert()
    */
    public function Insert(array $arData) {
	$sql = $this->FigureSQL_forInsert($arData);
	$this->sql = $sql;
	$db = $this->GetConnection();
	$ok = $db->ExecuteAction($sql);
	if ($ok) {
	    return $db->CreatedID();
	} else {
	    return FALSE;
	}
    }
    public function Update(array $arChg,$sqlWhere) {
	$sql = $this->FigureSQL_forUpdate($arChg,$sqlWhere);
	$this->sql = $sql;
	$db = $this->GetConnection();
	$ok = $db->ExecuteAction($sql);
	return $ok;
    }

    // -- WRITE DATA -- //
    // ++ CALCULATIONS ++ //
    
    /*----
      RETURNS: SQL for creating a new record for the given data
      HISTORY:
	2010-11-20 Created.
	2016-10-27 Adapted from db.v1 to db.v2.
    */
    protected function FigureSQL_forInsert(array $arData) {
	$sqlNames = '';
	$sqlVals = '';
	foreach($arData as $key=>$val) {
	    if ($sqlNames != '') {
		$sqlNames .= ',';
		$sqlVals .= ',';
	    }
	    if (!(is_string($val) || is_numeric($val))) {
		$sType = gettype($val);
		throw new exception("Internal Error: The INSERT value of [$key] is of type $sType.");
	    }
	    $sqlNames .= "`$key`";
	    $sqlVals .= $val;
	}
	$sqlTable = $this->TableName_Cooked();
	return "INSERT INTO $sqlTable ($sqlNames) VALUES($sqlVals);";
    }
    /*----
      HISTORY:
	2010-11-20 Created
	2013-07-14 Adapted from clsTable_key_single to static method in clsTable_abstract
	2016-10-30 tweaked very slightly for db.v2
    */
    public function FigureSQL_forUpdate(array $arChg,$sqlWhere) {
	$sqlSet = '';
	foreach($arChg as $key=>$val) {
	    if (is_scalar($val)) {
		if ($sqlSet != '') {
		    $sqlSet .= ',';
		}
		$sqlSet .= ' `'.$key.'`='.$val;
	    } else {
		throw new exception('Ferreteria parameter error: value for "'
		  .$key
		  .'" is "'
		  .gettype($val)
		  .'", which is not a scalar type.'
		  );
	    }
	}
	$sqlName = $this->TableName_Cooked();
	return "UPDATE $sqlName SET$sqlSet WHERE $sqlWhere";
    }

    // ++ CALCULATIONS ++ //

}