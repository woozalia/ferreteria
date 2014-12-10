<?php
/*
  FILE: events.php -- handling of generalized event logging
    Originally written to work with FinanceFerret, but should be compatible with standard event tables.
    Any app-specific code should be moved out into descendant classes.
  HISTORY:
    2010-10-25 clsLogger_DataSet extracted from menu.php
    2010-10-29 moved clsEvents and clsEvent from store.php
      Needs a few tweaks to be fully generalized
    2013-12-07 renaming classes as "syslog" and "sysEvent"
      to prevent confusion in some apps where other meanings of "event" apply
*/

// event argument names
define('KS_EVENT_ARG_DESCR_START'	,'descr');
define('KS_EVENT_ARG_DESCR_FINISH'	,'descrfin');
define('KS_EVENT_ARG_NOTES'		,'notes');
define('KS_EVENT_ARG_MOD_TYPE'		,'type');
define('KS_EVENT_ARG_MOD_INDEX'	,'id');
define('KS_EVENT_ARG_WHERE'		,'where');
define('KS_EVENT_ARG_CODE'		,'code');
define('KS_EVENT_ARG_PARAMS'		,'params');
define('KS_EVENT_ARG_IS_ERROR'		,'error');
define('KS_EVENT_ARG_IS_SEVERE'	,'severe');

abstract class clsSysEvents_abstract extends clsTable_key_single {
}
/*%%%%
  CLASS: clsSysEvents - EVENT LOGGING
  FUTURE: There should be some way to "register" event types at the start of the code, so that:
    1. this class doesn't have to be aware of every class which might want to record events
    2. a conflicting name will always generate an error when the code runs, even if no events are triggered,
      alerting the author that a different name is needed. (Perhaps names should be prefixed
      using the module name, e.g. "shop.cart", to minimize the chance of undetected conflicts
      due to classes not being used at the same time -- class A using name X is used for awhile,
      leaves some events of type "X" in the log, then is discontinued; later, class B uses name X
      again. Unless both A and B have very similar purposes, it will be confusing to see A's events
      showing up in B's log.)
  TO DO:
    Data_forType($iType) -- returns events for the given type only
    clsSysEvent::AdminList() -- displays table of events
*/
class clsSysEvents extends clsSysEvents_abstract {
    public function __construct(clsDatabase_abstract $iDB) {
	assert('is_object($iDB)');
	parent::__construct($iDB);
	  $this->Name('event_log');
	  $this->KeyName('ID');
	  $this->ClassSng('clsSysEvent');
    }

    // ++ STATIC ++ //

    const ARG_DESCR_START	= KS_EVENT_ARG_DESCR_START;
    const ARG_DESCR_FINISH	= KS_EVENT_ARG_DESCR_FINISH;
    const ARG_NOTES		= KS_EVENT_ARG_NOTES;
    const ARG_MOD_TYPE		= KS_EVENT_ARG_MOD_TYPE;
    const ARG_MOD_INDEX		= KS_EVENT_ARG_MOD_INDEX;
    const ARG_WHERE		= KS_EVENT_ARG_WHERE;
    const ARG_CODE		= KS_EVENT_ARG_CODE;
    const ARG_PARAMS		= KS_EVENT_ARG_PARAMS;
    const ARG_IS_ERROR		= KS_EVENT_ARG_IS_ERROR;
    const ARG_IS_SEVERE		= KS_EVENT_ARG_IS_SEVERE;

    private static $arArgFields = array(
	'descr'		=> 'Descr',
	'descrfin'	=> 'DescrFin',
	'notes'		=> 'Notes',
	'type'		=> 'ModType',
	'id'		=> 'ModIndex',
	'where'		=> 'EvWhere',
	'code'		=> 'Code',
	'params'	=> 'Params',
	'error'		=> 'isError',
	'severe'	=> 'isSevere',
      );

    // -- STATIC -- //

    /*-----
      RETURNS: event arguments translated into field names for use in Insert()
      NOTE: This is the method which ultimately determines what the list of values is.
      INPUT: Array containing zero or more elements whose keys match the keys of self::arArgFields,
	which descendant classes must define.
      FUTURE: This could probably be a static function.
    */
    static public function CalcSQL(array $iArgs) {
	$arIns = NULL;
	foreach ($iArgs as $key=>$val) {
	    if (array_key_exists($key,self::$arArgFields)) {
		$sqlKey = self::$arArgFields[$key];
		$sqlVal = SQLValue($val);
	    } else {
		throw new exception('Unrecognized event argument "'.$key.'".');
	    }
	    $arIns[$sqlKey] = $sqlVal;
	}
	return $arIns;
    }
    protected function UserString() {
	$oUser = $this->Engine()->App()->User();
	if (is_null($oUser)) {
	    $out = '(n/a)';
	} else {
	    $sUser = $oUser->UserName();
	    $nUser = $oUser->KeyValue();
	    $out = "$sUser (vc:$nUser)";
	}
	return $out;
    }
    /*----
      USAGE: This version is for logging general events with no stop/start; they are treated as "finished" only
    */
    public function LogEvent($iWhere,$iParams,$iDescr,$iCode,$iIsError,$iIsSevere) {
	global $sql;

	throw new exception('Who uses this? It may be okay...');

	$sUser = $this->UserString();

	if ($iIsSevere) {
	    $txtSubj = KS_SITE_NAME.' internal error: '.$iCode;
	    $txtBody = $iWhere.' generated an internal error:'
	      ."\n* What: $iDescr"
	      ."\n* Who: $sUser"
	      ."\n* Address: ".$_SERVER['REMOTE_ADDR']
	      ."\n* Params: $iParams";
	    $okMail = mail(KS_EMAIL_ADMIN,$txtSubj,$txtBody);
	}

	$sql = 'INSERT INTO `'.$this->Name().'` (WhenFinished,EvWhere,Params,Descr,Code,WhoAdmin,WhoSystem,WhoNetwork,isError,isSevere)'.
	  'VALUES(NOW(),"'.$iWhere.'","'.$iParams.'","'.$iDescr.'",'.SQLValue($iCode).','.SQLValue($sUser).
	  ',NULL,"'.
	  $_SERVER['REMOTE_ADDR'].'",'.
	  ($iIsError?'TRUE':'FALSE').','.
	  ($iIsSevere?'TRUE':'FALSE').');';
	$this->Engine()->Exec($sql);
	if ($iIsSevere) {
	    throw new exception($txtBody);	// this should send a second, more detailed email
	}
    }
    public function CreateEvent(array $arArgs) {
	$sUser = $this->UserString();

	$arIns = static::CalcSQL($arArgs);
	if (empty($arIns)) {
	    return NULL;
	} else {
	    $arIns['WhenStarted'] = 'NOW()';
	    $arIns['WhoNetwork'] = SQLValue($_SERVER['REMOTE_ADDR']);
	    $arIns['WhoAdmin'] = SQLValue($sUser);
	    $idNew = $this->Insert($arIns);
	    if ($idNew) {
		return $this->GetItem($idNew);
	    } else {
		return NULL;
	    }
	}
    }
    /*-----
      ACTION: Logs an event from specs in an array
	Possible specs are determined by CalcSQL()
    */
    public function StartEvent(array $iArgs) {
	throw new exception('StartEvent() is deprecated; use CreateEvent().');
	if (empty($iArgs)) { throw new exception('No arguments given for event.'); }

	$sUser = $this->UserString();

	$arIns = $this->CalcSQL($iArgs);
	if (empty($arIns)) {
	    return NULL;
	} else {
	    $arIns['WhenStarted'] = 'NOW()';
	    $arIns['WhoNetwork'] = SQLValue($_SERVER['REMOTE_ADDR']);
	    $arIns['WhoAdmin'] = SQLValue($sUser);
	    $ok = $this->Insert($arIns);
	    if ($ok) {
		return $this->Engine()->NewID(__METHOD__);
	    } else {
		return NULL;
	    }
	}
    }
    public function FinishEvent($iEvent,array $iArgs=NULL) {
	if (is_array($iArgs)) {
	    $arUpd = $this->CalcSQL($iArgs);
	    //$arUpd = array_merge($arUpd,$iArgs);
	}
	$arUpd['WhenFinished'] = 'NOW()';
	$this->Update($arUpd,'ID='.$iEvent);
    }

    // ++ DATA RECORDS ACCESS ++ //

    /*----
      RETURNS: dataset consisting of events related to the specific DatSet object given
      USED BY: Local Catalog Item event listing, at least
    */
    public function EventData($sTableKey=NULL,$idTableRow=NULL,$iDebug=FALSE) {
	$rs = $this;
	$oSQL = new clsSQLFilt('AND');
	if (!is_null($sTableKey)) {
	    $oSQL->AddCond('ModType="'.$sTableKey.'"');
	}
	if (!is_null($idTableRow)) {
	    $oSQL->AddCond('ModIndex='.$idTableRow);
	}
	if (!$iDebug) {
	    $oSQL->AddCond('NOT isDebug');
	}
	$sql = $oSQL->RenderFilter('WHERE ').' ORDER BY WhenStarted DESC, ID DESC';
	$rc = $this->DataSet($sql);
	return $rc;
    }
    /*
    public function EventListing($sTableKey=NULL,$idTableRow=NULL,$iDebug=FALSE) {
	$rsEv = $this->EventData($sTableKey,$idTableRow,$iDebug);
	echo 'SQL2: '.$rsEv->sqlMake;
	if ($rsEv->HasRows()) {
	    $out = $rsEv->AdminRows();
	} else {
	    $out = 'No events found here.';
	}
	return $out;
    }*/
}
class clsSysEvent extends clsDataSet {
    public function Finish(array $iArgs=NULL) {
	if (is_array($iArgs)) {
	    $arUpd = $this->Table()->CalcSQL($iArgs);
	}
	$arUpd['WhenFinished'] = 'NOW()';
	$this->Update($arUpd);
    }
}
/*%%%%
  CLASS: clsLogger
  PURPOSE: abstract event-logging interface
  HISTORY:
    2013-02-18 removing $iEdits parameter -- nothing seems to use it; may have been left over from
      the work that ultimately resulted in clsFxEvents
*/
abstract class clsLogger {
    abstract public function StartEvent(array $iarArgs);
    abstract public function FinishEvent(array $iarArgs=NULL);
    abstract public function EventListing();
}
/*%%%%
  PURPOSE: This is a helper class for clsDataSet and clsSysEvents.
    It is initialized with a DataSet object, and provides event logging.
    This is essentially multiple inheritance the hard way.
    This replaces clsAdminData_Logged.
  HISTORY:
    2011-10-09 changed 1st constructor parameter from clsDataSet (deprecated) to clsRecs_key_single
    2013-02-18 removing $iEdits parameter -- nothing seems to use it; may have been left over from
      the work that ultimately resulted in clsFxEvents
    2013-07-17 igov-mw-special.php IG_Question_admin::StartEvent() uses it, so restoring it.
      This caused a problem: clsLogger_DataSet::StartEvent() must be compatible with clsLogger::StartEvent(),
      so I made iEdits an optional parameter. THIS IS A KLUGE.
*/
class clsLogger_DataSet extends clsLogger {
    private $rs;
    private $tEv;
    protected $rcEvent;

    public function __construct(clsRecs_key_single $rsD, clsSysEvents_abstract $tEv) {
	$this->rsD = $rsD;	// recordset being logged
	$this->tEv = $tEv;	// event table
    }
    public function EventTable() {
	return $this->tEv;
    }
    public function DataRecord() {
	return $this->rsD;
    }
    /*----
      INPUT:
	$arArgs = Array containing any of several possible elements as defined by clsSysEvents
	$arEdits (optional) = changes being made to the data record's values
    */
    public function CreateEvent(array $arArgs,$arEdits=NULL) {
	$rcData = $this->DataRecord();
	// add data record's identity info
	$arArgs['type'] = $rcData->Table()->ActionKey();
	$arArgs['id'] = $rcData->KeyValue();

	$this->rcEvent = $this->EventTable()->CreateEvent($arArgs,$rcData->Values(),$arEdits);
	if (!is_object($this->rcEvent)) {
	    throw new exception('Could not create event object.');
	}
	return $this->rcEvent;
    }
    /*----
      INPUT:
	$arArgs = Array containing any of several possible elements as defined by clsSysEvents
	$arEdits (optional) = changes being made to the data record's values
    */
    public function StartEvent(array $iarArgs, array $iEdits=NULL) {
/*
	$arArgs = $iarArgs;
	$rc = $this->DataRecord();

	$arArgs['type'] = $rc->Table()->ActionKey();
	$arArgs['id'] = $rc->KeyValue();

	$this->idEvent = $this->EventTable()->StartEvent($arArgs,$rc->Values(),$iEdits);
	return $this->idEvent;
*/
	return $this->CreateEvent($iarArgs,$iEdits);
    }
    /*----
      HISTORY:
	2014-02-18 changed first line of code
	  from $this->DataRecord()...
	  to $this->EventTable()...
	  The first version would have caused an infinite loop if the params had matched.
	2014-07-01 Now using (event)->Finish().
    */
    public function FinishEvent(array $iarArgs=NULL) {
/*
	$this->EventTable()->FinishEvent($this->idEvent,$iarArgs);
	unset($this->idEvent);
*/
	if (!is_object($this->rcEvent)) {
	    throw new exception('FinishEvent() called, but event was not Started.');
	}
	return $this->rcEvent->Finish($iarArgs);
    }
    /*----
      RETURNS: dataset consisting of events for the record pointed to by $this->DataRecord()
      CALLED BY:
	$this->EventListing()
    */
    public function EventData($iDebug=FALSE) {
	$rsD = $this->DataRecord();
	$sTableKey = $rsD->Table()->ActionKey();
	$idTableRow = $rsD->KeyValue();
	$rs = $this->EventTable()->EventData($sTableKey,$idTableRow,$iDebug);
	return $rs;
/*
	$sql = '(ModType="'.$rsD->Table()->ActionKey().'") AND (ModIndex='.$rsD->KeyValue().')';
	if (!$iDebug) {
	    $sql .= ' AND (NOT isDebug)';
	}
	return $this->EventTable()->GetData($sql,NULL,'WhenStarted DESC');
*/
    }
    public function EventListing() {
	$rs = $this->EventData();
	return $rs->AdminRows(TRUE);
    }
}

/*%%%%
  PURPOSE: more detailed event table class which keeps separate records for each field modified
  NOTE: This is an EVENT class, not a LOGGER class.
  HISTORY:
    2013-01-03 started
*/
class clsFxSyslog extends clsSysEvents_abstract {
    protected $tblFields;

    public function __construct($iDB) {
	$this->tblFields = NULL;
	parent::__construct($iDB);
	  $this->Name('event_log');
	  $this->KeyName('ID');
	  $this->ClassSng('clsFxEvent');
    }
    protected function TblFields() {
	if (is_null($this->tblFields)) {
	    $this->tblFields = $this->Engine()->EventFields();
	}
	return $this->tblFields;
    }
    /*-----
      ACTION: Logs an event from specs in an array
      RETURNS: ID of new event, or NULL
    */
    public function StartEvent(array $iArgs,array $iOldVals, array $iNewVals) {
	if (empty($iArgs)) { throw new exception('No arguments given for event.'); }

	$arIns['ModType'] = $iArgs['tbl'];
	$arIns['ModIndex'] = $iArgs['idx'];
	$arIns['ID_User'] = $iArgs['user'];
	$arIns['WhenStarted'] = 'NOW()';
	$ok = $this->Insert($arIns);
	if ($ok) {
	    $idEv = $this->objDB->NewID(__METHOD__);
	    $this->TblFields()->LogEdits($idEv,$iOldVals,$iNewVals);
	    return $idEv;
	} else {
	    return NULL;
	}
    }
    public function FinishEvent($iEvent,array $iArgs=NULL) {
    }
}
class clsFxEvents_Flds extends clsTable_key_single {
    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name('event_log_fields');
    }
    public function LogEdits($idEvent, array $iOldVals, array $iNewVals) {
	foreach ($iNewVals as $key => $val) {
	    $arIns = array(
	      'ID_Event'	=> $idEvent,
	      'ModField'	=> SQLValue($key),
	      'ValOld'		=> SQLValue($iOldVals[$key]),
	      'ValNew'		=> SQLValue($val),
	      );
	    $this->Insert($arIns);
	}
    }
}