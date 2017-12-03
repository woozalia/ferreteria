<?php
/*
  PURPOSE: MySQL database Engine class using PHP MySQLi library
  RULES: Class names are the same for all MySQL Engines; external code should choose which file to require.
  PART OF: db* database library
  HISTORY:
    2015-03-13 created
*/

class fcDataConn_MySQL extends fcDataConn_CliSrv {

    // ++ SETUP ++ //

    private $oNative;
    protected function NativeObject(mysqli $oConn=NULL) {
	if (!is_null($oConn)) {
	    $this->oNative = $oConn;
	}
	if (is_object($this->oNative)) {
	    return $this->oNative;
	} else {
	    throw new exception('Trying to retrieve native db object, but it is not set.');
	}
    }

    // -- SETUP -- //
    // ++ INFORMATION ++ //

    public function IsOkay() {
	return ($this->ErrorNumber() == 0);
    }
    public function ErrorNumber() {
	return $this->NativeObject()->errno;
    }
    public function ErrorString() {
	return $this->NativeObject()->error;
    }
    public function SanitizeString($sSQL) {
	return $this->NativeObject()->escape_string($sSQL);
    }
    public function SanitizeValue($val) {
	if (is_null($val)) {
	    return 'NULL';
	} else {
	    if (is_numeric($val)) {
		return $val;
	    } elseif (is_bool($val)) {
		return $val?'TRUE':'FALSE';
	    } else {
		$s = $this->SanitizeString($val);
		return "'$s'";
	    }
	}
    }
    /*----
      ACTION: Sanitizes, and encloses in quotes if needed;
	returns 'NULL' if input is NULL.
	This is equivalent to the functionality of
	  mysql_real_escape_string().
      HISTORY:
	2017-02-11 Now handles NULL properly. Also, decided there's no
	  need to sanitize a numeric.
      TODO: Should probably be named something that implies general SQLification.
    */
    public function Sanitize_andQuote($sSQL) {
	return $this->SanitizeValue($sSQL);
    /*
	if (is_null($sSQL)) {
	    return 'NULL';
	} else {
	    if (is_numeric($sSQL)) {
		return $sSQL;
	    } else {
		$s = $this->SanitizeString($sSQL);
		return "'$s'";
	    }
	}
    */
    }

    // -- INFORMATION -- //
    // ++ ACTIONS ++ //

    public function Open() {
	$oNative = new mysqli(
	  $this->HostString(),
	  $this->Username(),
	  $this->Password(),
	  $this->SchemaString()
	  );	 // open the connection natively
	$this->NativeObject($oNative);	// save native object
    }
    public function Shut() {
	$this->NativeObject()->close();
    }

    // -- ACTIONS -- //
    // ++ DATA R/W ++ //

    public function ExecuteAction($sSQL) {
	$this->sql = $sSQL;
	return $this->NativeObject()->query($sSQL);
    }
    public function CountOfAffectedRows() {
	return $this->NativeObject()->affected_rows;
    }
    public function FetchRecordset($sSQL,fiRecords_forTable $tbl) {
	$poRes = $this->NativeObject()->query($sSQL);	// returns a mysqli_result if successful
	$this->sql = $sSQL;
	return $this->ProcessResultset($poRes,$tbl,$sSQL);	// 2017-12-03 this was commented out -- why?
	//return $tbl->ProcessRecordset($poRes,$sSQL);		// 2017-12-03 there is no function ProcessRecordset()
    }
    /*----
      INPUT:
	$poRes should be either a Recordset wrapper object or boolean
	$tbl is the Table wrapper object which should be used to instantiate the Recordset wrapper object.
      RETURNS: Recordset wrapper
	* If query successful, Recordset wrapper object will be linked to a Table wrapper object, and will include the query results.
	* If query failed, Recordset wrapper object will have 0 rows.
    */
    protected function ProcessResultset($poRes,fiRecords_forTable $tbl,$sql) {
	$rcNew = $tbl->SpawnRecordset();		// spawn a blank Recordset wrapper object
	if (is_object($poRes)) {
	    $rcNew->SetDriverBlob($poRes);		// store mysqli_result in Recordset object
	} else {
	    $sErr = $this->ErrorString();
	    $nErr = $this->ErrorNumber();
	    echo "<b>SQL</b>: $sql<br>";
	    echo "<b>DB Error</b>: $sErr<br>";
	    throw new exception("Ferreteria/mysqli error: database query failed with error $nErr.");
	    $rcNew->SetDriverBlob(NULL);		// no result to store
	}
	$rcNew->sql = $sql;	// for debugging
	return $rcNew;
    }

    // -- DATA R/W -- //
    // ++ RESULTS ++ //

    public function CreatedID() {
	return $this->NativeObject()->insert_id;
    }
    protected function RetrieveDriver(fcDataRecord $rs) {
	$o = $rs->GetDriverBlob();
	if (is_object($o)) {
	    return $o;
	} else {
	    echo '<b>Driver blob</b>: '.fcArray::Render($o);
	    throw new exception('Ferreteria/mysqli usage error: data operation was attempted on a record object whose driver blob is not an object.');
	}
    }
    public function Result_RowCount(fcDataRecord $rs) {
	$native = $rs->GetDriverBlob();
	if (is_null($native)) {
	    return 0;
	} else {
	    return $native->num_rows;
	}
    }
    public function Result_NextRow(fcDataRecord $rs) {
	return $this->RetrieveDriver($rs)->fetch_assoc();
    }
    public function Result_Rewind(fcDataRecord $rs) {
	return $this->RetrieveDriver($rs)->data_seek(0);
    }
    // for debugging
    public function TestDriver(fcDataRecord $rs) {
	$this->RetrieveDriver($rs);	// throws an error if not set
    }

    // -- RESULTS -- //
    // ++ TRANSACTIONS ++ //
    
    /*
      NOTE: mysqli supports named transactions and other types besides r/w, but I don't know if this is common.
	Not supporting it for now, but that might be something to add later (optional arguments) if it is common and useful.
    */
    
    public function TransactionOpen() {
	$o = $this->NativeObject();
	$o->autocommit(FALSE);	// turn off autocommit
	$o->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
    }
    public function TransactionSave() {
	$o = $this->NativeObject();
	$o->commit();		// commit the transaction
	$o->autocommit(TRUE);	// turn autocommit back on
    }
    public function TransactionKill() {
	$o = $this->NativeObject();
	$o->rollback();		// roll back the transaction
	$o->autocommit(TRUE);	// turn autocommit back on
    }

    // -- TRANSACTIONS -- //

}