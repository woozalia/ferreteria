<?php
/*
  PURPOSE: database connection classes
  PART OF: db* database library
  RULES:
    Connections are requested from the Factory.
    Full db URL format: <type>:<user>:<password>@<host>/<schema>

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
    // ++ STATUS ++ //

    abstract public function IsOkay();
    abstract public function ErrorNumber();
    abstract public function ErrorString();

    // -- STATUS -- //
    // ++ DATA PREPROCESSING ++ //
    
    abstract public function Sanitize($sSQL);
    abstract public function Sanitize_andQuote($sSQL);
    
    // -- DATA PREPROCESSING -- //
    // ++ DATA READ/WRITE ++ //

    abstract public function MakeTableWrapper($sTableClass,$id=NULL);
    abstract public function FetchRecordset($sSQL,fcDataSource $tbl);
    abstract public function ExecuteAction($sSQL);
    abstract public function CreatedID();

    // -- DATA READ/WRITE -- //
    // ++ TRANSACTIONS ++ //
    
    abstract public function TransactionOpen();
    abstract public function TransactionSave();
    abstract public function TransactionKill();
    
    // -- TRANSACTIONS -- //
    // ++ UTILITY ++ //
    
    public function Sanitize_andQuote_ValueArray(array $arVals) {
	$arOut = NULL;
	foreach ($arVals as $key => $val) {
	    $arOut[$key] = $this->Sanitize_andQuote($val);
	}
	return $arOut;
    }

    // -- UTILITY -- //
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
      RETURNS: nothing
    */
    public function Setup_spec($sSpec) {
	$ar = preg_split('/@/',$sSpec);	// splits [<user>:<password>] from [<host>/<schema>]
	if (count($ar) == 2) {
	    list($sCreds,$sTarget) = $ar;
	} else {
	    throw new exception("Connection string [$sSpec] has no @host section");
	}
	// get user, password
	list($sUser,$sPass) = explode(':',$sCreds);
	// get host, schema
	list($sHost,$sSchema) = explode('/',$sTarget);

	// initialize it with these params
	$this->HostString($sHost);
	$this->Username($sUser);
	$this->Password($sPass);
	$this->SchemaString($sSchema);
    }

    // -- SETUP -- //
    // ++ CONFIGURATION FIELDS ++ //

    protected function HostString($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sHost = $sVal;
	}
	return $this->sHost;
    }
    protected function Username($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sUser = $sVal;
	}
	return $this->sUser;
    }
    protected function Password($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sPass = $sVal;
	}
	return $this->sPass;
    }
    protected function SchemaString($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sSchema = $sVal;
	}
	return $this->sSchema;
    }

    // -- CONFIGURATION FIELDS -- //
    // ++ DATA OPERATIONS ++ //

    abstract public function Result_RowCount(fcDataRecord $rs);
    abstract public function Result_NextRow(fcDataRecord $rs);

    // -- DATA OPERATIONS -- //
    // ++ DATA OBJECTS ++ //
    
    /*----
      NOTE: Adapted from Make() in db.v1
      ASSUMES: If $id is not NULL, then $sTableClass must be a *single-keyed* table class.
    */
    private $arTables;
    public function MakeTableWrapper($sTableClass,$id=NULL) {
	if (empty($this->arTables)) { $this->arTables = array(); }
	if (array_key_exists($sTableClass,$this->arTables)) {
	    // a Table of that class has already been created
	    $t = $this->arTables[$sTableClass];
	} else {
	    // that class of Table has not yet been created
	    if (class_exists($sTableClass)) {
		// create & cache it
		$t = new $sTableClass($this);
		if ($t instanceof fcDataSource) {
		    $this->arTables[$sTableClass] = $t;
		} else {
		    throw new exception('Requested class "'.$sTableClass.'" is not a descendant of fcDataSource.');
		}
	    } else {
		// no code found for that class
		throw new exception('Unknown table wrapper class "'.$sTableClass.'" requested.');
	    }
	}
	if (is_null($id)) {
	    // table-wrapper result wanted
	    return $t;
	} else {
	    // recordset-wrapper result wanted
	    return $t->GetRecord_forKey($id);
	}
    }
    
    // -- DATA OBJECTS -- //
}

