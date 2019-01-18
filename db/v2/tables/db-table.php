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
	  class fcTable_wName_wSource_wRecords (alias fcDataTable)
	  class fcTable_wSource
	  class fcTable_wRecords
	  
	TODO: replace fcDataSource with fcTable_wRecords
	TODO: replace fcConnectedData with fcTable_wSource_wRecords
	TODO: fcDataTable should be an alias (at least for now) for fcTable_wName_wSource_wRecords
    2017-04-09
      * replaced fcDataSource with fcTable_wRecords (fcTableBase + ftRecords_forTable)
      * replaced fcConnectedData with fcTable_wSource_wRecords (fcTable_wRecords + ftSource_forTable)
      * replaced fcDataTable with fcTable_wName_wSource_wRecords (fcTable_wSource_wRecords + ftName_forTable + ftStoredTable)
      * created ftStoredTable with contents of fcTable_wName_wSource_wRecords
    2017-06-06 added SourceString_forSelect()
    2018-01-24 ftSelectable_Table, ftReadableTable, ftWriteableTable
    2018-01-26 fcUsableTable (should be the minimum needed to retrieve recordsets)
*/
define('KFMT_RDATA_NATIVE',TRUE);
define('KFMT_RDATA_SQL',FALSE);

interface fiTable_wRecords {
    function SpawnRecordset();
    function ProcessResultset($poRes,$sql);
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
      TODO: Change name back to GetDatabase()
      HISTORY:
	2018-04-01 A note said: "To access a table in some other database, create a descendant type
	which gets its connection differently." This is a problem, because we need to be able to set
	the database object explicitly on an object-by-object basis (mainly because this is what makes
	sense intuitively)... so I am changing this class to require explicit setting of the db object,
	which will be done by the db->MakeTableWrapper() function.
    */
    private $db;
    public function SetConnection(fcDataConn $db) {
	$this->db = $db;
    }
    public function GetConnection() {
	if (is_null($this->db)) {
	    $sClass = get_class($this);
	    throw new exception("Tried to retrieve db object before it has been set in class '$sClass'.");
	}
	return $this->db;
    }

    // -- CONFIGURATION -- //
    // ++ READ DATA ++ //

    public function FetchRecords($sql) {
	$this->sql = $sql;	// for debugging purposes
	$db = $this->GetConnection();
	return $db->FetchRecordset($sql,$this);
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
    /*----
      INPUT:
	$poRes should be either a Recordset wrapper object or boolean
	$tbl is the Table wrapper object which should be used to instantiate the Recordset wrapper object.
      RETURNS: Recordset wrapper
	* If query successful, Recordset wrapper object will be linked to a Table wrapper object, and will include the query results.
	* If query failed, Recordset wrapper object will have 0 rows.
      HISTORY:
	2017-11-05 moved from fcDataConn_MySQL to ftRecords_forTable
    */
    public function ProcessResultset($poRes,$sql) {
	$rcNew = $this->SpawnRecordset();		// spawn a blank Recordset wrapper object
	if (is_object($poRes)) {
	    $rcNew->SetDriverBlob($poRes);		// store mysqli_result in Recordset object
	} else {
	    $db = $this->GetConnection();
	    $sErr = $db->ErrorString();
	    $nErr = $db->ErrorNumber();
	    echo "<b>SQL</b>: $sql<br>";
	    echo "<b>DB Error</b>: $sErr<br>";
	    throw new exception("Ferreteria/mysqli error: database query failed with error $nErr.");
	    $rcNew->SetDriverBlob(NULL);		// no result to store
	}
	$rcNew->sql = $sql;	// for debugging
	return $rcNew;
    }
}
/*----
  PURPOSE: Table-like object where we can't just give it a table-name to work on but we can still read stuff from it
    Basically, a query.
*/
trait ftSelectable_Table {

    // ++ CONFIGURATION ++ //

    protected function FieldsString_forSelect() {
	return '*';
    }
    abstract protected function SourceString_forSelect();

    // -- CONFIGURATION -- //
    // ++ SQL: DATA READ ++ //
    
    protected function FigureSelectSQL($sqlWhere=NULL,$sqlSort=NULL,$sqlOther=NULL) {
	$sqlFields = $this->FieldsString_forSelect();
	$sqlSource = $this->SourceString_forSelect();
	$sql = "SELECT $sqlFields FROM $sqlSource";
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

    // -- SQL: DATA READ -- //
}
/*::::
  PURPOSE: table functions which require the table to know its own name
*/
trait ftName_forTable {
    use ftSelectable_Table;

    // ++ CONFIGURATION ++ //
    
    // name of database table for which this class is a wrapper
    abstract protected function TableName();
    // This exists only for debugging:
    public function DebugTableName() { return $this->TableName(); }
    // PUBLIC because sometimes you have to build SQL queries from multiple tables
    public function TableName_Cooked() {	// db table name sanitized with backticks
	return '`'.$this->TableName().'`';
    }
    // PURPOSE: provides field list for SELECT queries
    protected function FieldsString_forSelect() {
	return '*';
    }
    // PURPOSE: provides name of table for SELECT queries; can also return a JOIN
    protected function SourceString_forSelect() {
	return $this->TableName_Cooked();	// default; override for joins
    }

    // -- CONFIGURATION -- //
    // ++ SQL: DATA WRITE ++ //
    
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
      INPUT:
	$isNativeData: if TRUE, values in $arChg should be treated as values rather than SQL elements
	  i.e. non-numerics should be quoted, NULLs should be converted to 'NULL'.
      HISTORY:
	2010-11-20 Created
	2013-07-14 Adapted from clsTable_key_single to static method in clsTable_abstract
	2016-10-30 tweaked very slightly for db.v2
	2017-07-29 added optional $isNativeData parameter
    */
    public function FigureSQL_forUpdate(array $arChg,$sqlWhere,$isNativeData=self::KFMT_NATIVE) {
	$sqlSet = '';
	if ($isNativeData) {
	    $db = $this->GetConnection();
	}
	foreach($arChg as $key=>$val) {
	    if (is_scalar($val)) {
		if ($sqlSet != '') {
		    $sqlSet .= ',';
		}
		if ($isNativeData) {
		    $sqlVal = $db->SanitizeValue($val);
		} else {
		    $sqlVal = $val;
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

    // -- SQL: DATA WRITE -- //

}
/*----
  REQUIRES:
    * SelectRecords() needs FigureSelectSQL() (in ftSelectable_Table)
    * SelectRecords() needs FetchRecords() (in ftSource_forTable)
*/
trait ftReadableTable {

    // ++ READ DATA ++ //

    // REQUIRES: table name fx and data source
    public function SelectRecords($sqlWhere=NULL,$sqlSort=NULL,$sqlOther=NULL) {
	$sql = $this->FigureSelectSQL($sqlWhere,$sqlSort,$sqlOther);
	return $this->FetchRecords($sql);
    }
    
    // -- READ DATA -- //
}
/*----
  REQUIRES:
    * Insert() needs FigureSQL_forInsert() (in ftName_forTable)
    * Update() needs FigureSQL_forUpdate() (in ftName_forTable)
    * Insert() and Update() need GetConnection() (in ftSource_forTable)
*/
trait ftWriteableTable {
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
    /*----
      PURPOSE: Insert only values, not SQL
      ACTION: Sanitizes values before passing them to $this->Insert()
      USED BY: was going to be used by array tables, but ended up unneeded (2017-03-25)
    */ /*
    protected function InsertValues(array $arData) {
	$db = $this->GetConnection();
	foreach ($arData as $key => $val) {
	    $sqlVal = $db->SanitizeValue($val);
	    $arSQL[$key] = $sqlVal;
	}
	return $this->Insert($arSQL);
    } */
    public function Update(array $arChg,$sqlWhere,$isNativeData=FALSE) {
	$sql = $this->FigureSQL_forUpdate($arChg,$sqlWhere,$isNativeData);
	$this->sql = $sql;
	$db = $this->GetConnection();
	$ok = $db->ExecuteAction($sql);
	return $ok;
    }

    // -- WRITE DATA -- //
}

// PURPOSE: table that is readable and writable - kept for backward compatibility
trait ftStoredTable {
    use ftReadableTable, ftWriteableTable;
}
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
class fcTable_wSource extends fcTableBase {
    use ftSource_forTable;
}
/*::::
  PURPOSE: data source wrapper class
*/
abstract class fcTable_wRecords extends fcTableBase implements fiTable_wRecords {
    use ftRecords_forTable;
}
/*::::
  PURPOSE: data source wrapper class whose data originates from a database connection
  ADDS:
    + database Connection
    + can do SQL
    + can fetch recordsets
*/
abstract class fcTable_wSource_wRecords extends fcTable_wRecords {
    use ftSource_forTable;
}
// PURPOSE: minimum implementation necessary to retrieve records
class fcUsableTable extends fcTable_wSource_wRecords {
    // CEMENT
    protected function SingularName() {
	return 'fcUsableRecordset';
    }
}
/*----
  PURPOSE: wrapper class for a database table which can generate recordsets
  ADDS: table has a name
*/
abstract class fcTable_wName_wSource_wRecords extends fcTable_wSource_wRecords {
    use ftName_forTable, ftStoredTable;
}