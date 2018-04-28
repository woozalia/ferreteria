<?php
/*
  PURPOSE: database connection classes
  PART OF: db* database library
  RULES:
    Connections are requested from the Factory.
    Full db URL format: <type>:<user>:<password>@<host>/<schema>

  HISTORY:
    2015-03-12 rewrite of data*.php started
    2017-11-05 removed Sanitize_andQuote as a requirement in fcDataConn so MW classes wouldn't need to implement
      TODO: replace all uses of it throughout Ferreteria and its apps with SanitizeValue()
  */


/*::::
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
    
    /*----
      INPUT: non-NULL string value
      OUTPUT: string value with quotes escaped, but NOT quoted
      TODO: this is still a bad name for the action. I was originally thinking this, but now not sure:
	(current) SanitizeString() -> NormalizeString(): sanitize string data but don't quote
	(new) SanitizeString() = sanitize and always quote, because input is a string
	
	"Normalize" = make sure the value is safe to use as a value in SQL, but don't quote it
	"Sanitize" = make sure the value can be used as-is in SQL without additional quoting
    */
    abstract public function SanitizeString($s);
    /*----
      INPUT: any scalar value
      OUTPUT: non-blank SQL-compatible string that equates to the input value
	quoted if necessary
    */
    abstract public function SanitizeValue($v);
    
    // DEPRECATED
    public function Sanitize_andQuote($s) {
	throw new exception('Call SanitizeValue() instead.');
    }
    
    // -- DATA PREPROCESSING -- //
    // ++ DATA READ/WRITE ++ //

    abstract public function MakeTableWrapper($sTableClass,$id=NULL);
    abstract public function FetchRecordset($sql,fiTable_wRecords $tbl);
    abstract public function ExecuteAction($sSQL);
    abstract public function CountOfAffectedRows();
    abstract public function CreatedID();

    // -- DATA READ/WRITE -- //
    // ++ TRANSACTIONS ++ //
    
    abstract public function TransactionOpen();
    abstract public function TransactionSave();
    abstract public function TransactionKill();
    
    // -- TRANSACTIONS -- //
    // ++ UTILITY ++ //
    
    public function Sanitize_andQuote_ValueArray(array $arVals) {
	throw new exception('Call SanitizeValueArray() instead.');
    }
    public function SanitizeValueArray(array $arVals) {
	$arOut = NULL;
	foreach ($arVals as $key => $val) {
	    $arOut[$key] = $this->SanitizeValue($val);
	}
	return $arOut;
    }

    // -- UTILITY -- //
}

/*::::
  PURPOSE: database Engine that has a client-server architecture
    This type will always need host and schema names, username, and password.
*/
abstract class fcDataConn_CliSrv extends fcDataConn {
    private $sHost,$sUser,$sPass;

    // ++ SETUP ++ //
    static private $nInst=0;
    public function __construct() {
	self::$nInst++;
    }
    public function InstanceCount() {
	return self::$nInst;
    }

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
	$this->SetSchemaString($sSchema);
    }

    // -- SETUP -- //
    // ++ SETUP FIELDS ++ //

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
    
    private $sSchema;
    protected function SetSchemaString($sVal) {
	$this->sSchema = $sVal;
    }
    // PUBLIC for debugging
    public function GetSchemaString() {
	return $this->sSchema;
    }

    // -- SETUP FIELDS -- //
    // ++ DATA OPERATIONS ++ //

    abstract public function Result_RowCount(fcDataRecord $rs);
    abstract public function Result_NextRow(fcDataRecord $rs);

    // -- DATA OPERATIONS -- //
    // ++ DATA OBJECTS ++ //
    
    /*----
      NOTE: Adapted from Make() in db.v1
      TODO: see notes on SetMissingPermit() - sometimes we want to upgrade an object rather than making a new one
      ASSUMES:
	* Table will connect itself to this database. (TODO: fix this assumption)
	* If $id is not NULL, then $sTableClass must be a *single-keyed* table class and we will return a recordset object.
	  The table will be told to load the recordset whose row ID is $id (table->GetRecord_forKey($id)).
	  ...unless $id is KS_NEW_REC, in which case we will return a blank recordset.
      HISTORY:
	2017-08-28 Added KS_NEW_REC functionality -- but surely this must exist somewhere already.
	2018-04-01 added $t->SetConnection($this)
    */
    private $arTables = array();
    public function MakeTableWrapper($sTableClass,$id=NULL) {
	//if (empty($this->arTables)) { $this->arTables = array(); }
	if (array_key_exists($sTableClass,$this->arTables)) {
	    // a Table of that class has already been created
	    $t = $this->arTables[$sTableClass];
	} else {
	    // that class of Table has not yet been created
	    if (class_exists($sTableClass)) {
		// attempt to create & cache it
		$ok = FALSE;
		$ksRequiredParent = 'fcTableBase';
		if (is_subclass_of($sTableClass,$ksRequiredParent)) {
		    $t = new $sTableClass();
		    $t->SetConnection($this);
		    $this->arTables[$sTableClass] = $t;
		    $ok = TRUE;
		} else {
		    $sErr = "Requested class '$sTableClass' is not a descendant of $ksRequiredParent.";
		}
		if (!$ok) {
		    $arParents = class_parents($sTableClass);
		    echo "Parentage of <b>$sTableClass</b>:".fcArray::RenderList($arParents,' &larr; ');
		    throw new exception($sErr);
		}
	    } else {
		$tst = new $sTableClass($this);	// debugging
		// no code found for that class
		throw new exception("Trying to wrap table with unknown class '$sTableClass'. (Maybe the appropriate library module has not been requested?)");
	    }
	}
	if (is_null($id)) {
	    // table-wrapper result wanted
	    return $t;
	} elseif ($id == KS_NEW_REC) {
	    return $t->SpawnRecordset();
	} else {
	    // recordset-wrapper result wanted
	    return $t->GetRecord_forKey($id);
	}
    }
    
    // -- DATA OBJECTS -- //
}

