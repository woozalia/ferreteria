<?php
/*----
  PURPOSE: wrapper class for a data source
  HISTORY:
    2016-11-07 added fcDataSource so that query-table-types wouldn't need to return a table name
    2017-01-01 renamed fcDataSource to fcConnectedData, created new base class called fcDataSource that doesn't require a Connection
    2017-01-28
      Decided that having a Connection and being able to spawn Recordsets should both be traits.
	Most descendants want both, but some want Connection without Recordset, and some (e.g. array types) are the opposite.
	Implemented the following class structure:
	  trait ftSource_forTable
	  trait ftRecords_forTable
	  trait ftName_forTable (just for coding clarity)
	  class fcTable_wSource_wRecords (alias fcDataTable)
	  class fcTable_wSource
	  class fcTable_wRecords
	  
	TODO: replace fcDataSource with fcTable_wRecords
	TODO: replace fcConnectedData with fcTable_wSource_wRecords
	TODO: fcDataTable should be an alias (at least for now) for fcTable_wName_wSource_wRecords
*/
/*::::
  PURPOSE: base class to tie all the differently-derived Table classes together
    ...although it really doesn't do much itself.
*/
class fcTableBase {
    
    public function __construct() {
	$this->InitVars();
    }
    protected function InitVars() {}
    /*----
      PURPOSE: entirely to aid in API usage
	i.e. so Ferreteria can tell you if you're trying to use the wrong class
    */
}
/*::::
  PURPOSE: fcDataSource + this = table with db connection
  REQUIREMENT: class constructor should include same argument as TraitConstructor()
    and by default should call TraitConstructor(), unless you are doing something different.
*/
trait ftSource_forTable {
    
    public $sql;	// for debugging

    // ++ CONFIGURATION ++ //

    /*----
      PUBLIC so Recordset wrapper object can access it
      NOTE: To access a table in some other database, create a descendant type
	which gets its connection differently.
    */
    public function GetConnection() {
	return fcApp::Me()->GetDatabase();
    }

    // -- CONFIGURATION -- //
    // ++ READ DATA ++ //

    public function FetchRecords($sql) {
	$this->sql = $sql;	// for debugging purposes
	return $this->GetConnection()->FetchRecordset($sql,$this);
    }
    
    // -- READ DATA -- //
}
/*::::
  PURPOSE: fcDataSource + this = table which can pull up recordsets
*/
trait ftRecords_forTable {
   // name for singular class
    abstract protected function SingularName();
    // NOTE: 2016-11-06 Maybe this should be renamed SpawnRecordsWrapper().
    public function SpawnRecordset() {
	$sClass = $this->SingularName();
	return new $sClass($this);
    }
}
/*::::
  PURPOSE: table functions which require the table to know its own name
*/
trait ftName_forTable {

    // ++ CONFIGURATION ++ //
    
    // name of database table for which this class is a wrapper
    abstract protected function TableName();
    // PUBLIC because sometimes you have to build SQL queries from multiple tables
    public function TableName_Cooked() {	// db table name sanitized with backticks
	return '`'.$this->TableName().'`';
    }

    // -- CONFIGURATION -- //
    // ++ CALCULATIONS ++ //
    
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
class fcTable_wSource extends fcTableBase {
    use ftSource_forTable;
}
/*::::
  PURPOSE: data source wrapper class
*/
abstract class fcDataSource extends fcTableBase {
    use ftRecords_forTable;
}
/*::::
  PURPOSE: data source wrapper class whose data originates from a database connection
  ADDS:
    + database Connection
    + can do SQL
    + can fetch recordsets
*/
abstract class fcConnectedData extends fcDataSource {
    use ftSource_forTable;
}
/*----
  PURPOSE: wrapper class for a database table which can generate recordsets
  ADDS: table has a name
*/
abstract class fcDataTable extends fcConnectedData {
    use ftName_forTable;

    // ++ READ DATA ++ //

    // REQUIRES: table name fx and data source
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
}