<?php
/*
  PURPOSE: database connection classes
  PART OF: db* database library
  RULES:
    Connections are requested from the Factory.
  HISTORY:
    2015-03-12 rewrite of data*.php started
  */
  

/*%%%%
  PURPOSE: defines the basic interface for a database connection
*/
abstract class fcDataConn {
    
    // ++ CONNECTING ++ //

    abstract public function Open();
    abstract public function Shut();
    
    // -- CONNECTING -- //
    // ++ INFORMATION ++ //
    
    abstract public function Sanitize($sSQL);
    abstract public function Sanitize_andQuote($sSQL);
    
    // -- INFORMATION -- //
    // ++ DATA ACCESS ++ //
    
    abstract public function Recordset($sSQL);
    //abstract public function Select($sTable,$arFields
    
    // -- DATA ACCESS -- //
    //
}

/*%%%%%
  PURPOSE: database Engine that has a client-server architecture
    This type will always need host and schema names, username, and password.
*/
abstract class fcDataConn_CliSrv extends fcDataConn {
    private $sHost,$sUser,$sPass,$sSchema;

    // ++ SETUP ++ //
    
    /*----
      RULES: spec includes everything after the "<type>:"
      IMPLEMENTATION: "<user>:<password>@<host>/<schema>"
      TODO: <schema> should be optional, but this is not yet coded.
    */
    public function Setup_spec($sSpec) {
	$ar = preg_split('/@/',$iSpec);
	if (count($ar) == 2) {
	    list($sPart1,$sPart2) = $ar;
	} else {
	    throw new exception("Connection string [$sSpec] is lacking an @host section");
	}
	// get user, password
	list($sUser,$sPass) = explode(':',$sPart1);
	// get host, schema
	list($sHost,$sName) = explode('/',$sPart2);
	
	// initialize it with these params
	$this->HostString($sHost);
	$this->Username($sUser);
	$this->Password($sPass);
	$this->SchemaString($sSchema);
	return $oConn;
    }

    // -- SETUP -- //
    // ++ CONFIGURATION FIELDS ++ //
    
    protected function HostString($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sHost = $sVal;
	}
	return $this->sHost;
    }
    protected function Username(fcDataConn $oConn, $sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sUser = $sVal;
	}
	return $this->sUser;
    }
    protected function Password(fcDataConn $oConn, $sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sPass = $sVal;
	}
	return $this->sPass;
    }
    protected function SchemaString(fcDataConn $oConn, $sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sSchema = $sVal;
	}
	return $this->sSchema;
    }
    // RETURNS: name of Recordset class to return from data queries
    private $sClassRecs;
    public function RecordsClassName($sName=NULL) {
	if (is_null($sName)) {
	    if (is_null($this->sClassRecs)) {
		$this->sClassRecs = $this->DefaultRecordsClassName();
	    }
	} else {
	    $this->sClassRecs = $sName;
	}
	return $this->sClassRecs;
    }
    protected function DefaultRecordsClassName() {
	return 'fcDataRecord';
    }
    
    // -- CONFIGURATION FIELDS -- //
    // ++ DATA OPERATIONS ++ //
    
    abstract public function Result_RowCount(fcDataRecord $rs);
    abstract public function Result_NextRow(fcDataRecord $rs);
    
    // -- DATA OPERATIONS -- //
}

