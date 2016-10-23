<?php
/*
  PURPOSE: MySQL database Engine class using PHP MySQLi library
  RULES: Class names are the same for all MySQL Engines; external code should choose which file to require.
  PART OF: db* database library
  HISTORY:
    2015-03-13 created
*/

class fcDataConn_MySQL extends fcDataConn_CliSrv {

    // ++ CONFIGURATION FIELDS ++ //

    private $oNative;
    protected function NativeObject(mysqli $oConn=NULL) {
	if (!is_null($oConn)) {
	    $this->oNative = $oConn;
	}
	return $this->oNative;
    }

    // -- CONFIGURATION FIELDS -- //
    // ++ INFORMATION ++ //

    public function IsOkay() {
    throw new exception('Working HERE');
    }
    public function ErrorNumber() {
    }
    public function ErrorString() {
    }
    public function Sanitize($sSQL) {
	return $this->NativeObject()->escape_string($sSQL);
    }
    /*----
      ACTION: Sanitizes, and encloses in quotes if needed
	This is equivalent to the functionality of
	  mysql_real_escape_string().
    */
    public function Sanitize_andQuote($sSQL) {
	$s = $this->Sanitize($sSQL);
	if (is_numeric($s)) {
	    return $s;
	} else {
	    return "'$s'";
	}
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
    // ++ DATA RETRIEVAL ++ //

    public function Recordset($sSQL) {
	$poRes = $this->NativeObject()->query($sSQL);	// returns a mysqli_result if successful
	return $this->ProcessResultset($poRes);
    }
    /*----
      INPUT: $poRes should be either a mysqli_result or boolean
    */
    protected function ProcessResultset($poRes) {
	if (is_object($poRes)) {
	    // ASSUMED: if it's an object, it's a mysqli_result
	    $sClass = $this->RecordsClassName();	// get class to use for Recordset
	    $rcNew = new $sClass($this);		// create new Recordset object
	    $rcNew->BlobValue('native',$poRes);		// store mysqli_result in Recordset object
	    return $rcNew;
	} else {
	    return $poRes;	// TRUE for successful non-data-output query, FALSE if query failed
	}
    }

    // -- DATA RETRIEVAL -- //
    // ++ DATA OPERATIONS ++ //

    public function Result_RowCount(fcDataRecord $rs) {
	return $rs->BlobValue('native')->num_rows;
    }
    public function Result_NextRow(fcDataRecord $rs) {
    	return $rs->BlobValue('native')->fetch_assoc();
    }

    // -- DATA OPERATIONS -- //
}