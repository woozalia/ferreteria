<?php
/*
  PURPOSE: adds logging capabilities to any table/recordset pair
  HISTORY:
    2014-06-10 Extracting useful non-vbz-specific bits from vbz-data.php
    2015-07-12 resolving conflicts with other edited version
    2015-09-06 moving methods into traits
        ftLoggableRecord
    2016-10-23 adapting from db.v1 (events.php) to db.v2
    2017-01-15 some class renaming
*/

// event argument names
/* use class constants instead -- let's not have to maintain two sets of constants
define('KS_EVENT_ARG_DESCR_START'	,'descr');
define('KS_EVENT_ARG_DESCR_FINISH'	,'descrfin');
define('KS_EVENT_ARG_NOTES'		,'notes');
define('KS_EVENT_ARG_MOD_TYPE'		,'type');
define('KS_EVENT_ARG_MOD_INDEX'		,'id');
define('KS_EVENT_ARG_WHERE'		,'where');
define('KS_EVENT_ARG_CODE'		,'code');
define('KS_EVENT_ARG_PARAMS'		,'params');
define('KS_EVENT_ARG_IS_ERROR'		,'error');
define('KS_EVENT_ARG_IS_SEVERE'		,'severe');
*/

interface fiEventTable {
    function EventListing();
}
/*
interface fiEventTable_forTable extends fiEventTable {
    function SelectRecords_forTable($sKey,$idRow=NULL)
}*/
/*::::
  NOTE: For EventTable(), use ftFrameworkAccess
*/
trait ftLoggableObject {

    // ++ CLASS ++ //
    
    abstract protected function GetEventsClass();
    
    // -- CLASS -- //
    // ++ TABLE ++ //

    protected function EventTable() {
	return $this->GetConnection()->MakeTableWrapper($this->GetEventsClass());
    }
    
    // -- TABLE -- //
    // ++ DB WRITE ++ //

    protected function CreateEvent_Notes($idEvent,$sNotes) {
	$tBase = $this->EventTable();
	$tSub = $tBase->TableWrapper_forNotes();
	$tSub->CreateRecord($idEvent,$sNotes);
    }

    // -- DB WRITE -- //
    // ++ WEB OUTPUT ++ //
    
    protected function EventListing_SectionHeader($sSuffix) {
	$oHdr = new fcSectionHeader('Ferreteria Events'.$sSuffix);
	return $oHdr;
    }
    // TODO: this needs to be different for recordsets and tables
    protected function EventListing() {
	$tEv = $this->EventTable();
	//if (method_exists($tEv,'EventListing')) {
	    return $tEv->EventListing();
	//} else {
	//    throw new exception('Ferreteria usage error: you need to be using the admin UI (dropin) descendant of the event table class //which defines EventListing(). The event table class received from EventTable() was '.get_class($tEv).'.');
	//}
    }
    
    // -- WEB OUTPUT -- //
}
/*::::
  REQUIRES: nothing yet
*/
trait ftLoggableTable {
    use ftLoggableObject;

    // ++ SETUP ++ //
    
    abstract public function GetActionKey();
    
    // -- SETUP -- //
    // ++ CLASSES ++ //
    
    protected function GetEventsClass() {
	return 'fctEventPlex_standard';
    }
    
    // -- CLASSES -- //
    // ++ WRITE DATA ++ //
    
    /*----
      ACTION: Automatically adds in the Table's specs to the event data,
	then passes the request on to the event logger table wrapper.
      INPUT:
	$arArgs = array containing event data to be written
	$arEdits (optional) = list of changes being made to the data record's values
      RETURNS: Event record
    */
    public function CreateEvent($sCode,$sText,array $arData=NULL) {
	$tBase = $this->EventTable();
	$id = $tBase->CreateBaseEvent($sCode,$sText,$arData);
	// create sub-event for current record
	$tSub = $tBase->TableWrapper_forInTable();
	$tSub->CreateRecord($id,$this->GetActionKey());
	return $id;
    }
    
    // -- WRITE DATA -- //
    // ++ READ DATA ++ //

    protected function EventListing() {
	$tEv = $this->EventTable();
	$sTbl = $this->GetActionKey();
	$sFor = ' for table '.$sTbl;
	// TODO: define an interface for this
	$rs = $tEv->SelectRecords_forTable($sTbl);
	
	$out = $this->EventListing_SectionHeader($sFor)->Render()
	  .$rs->AdminRows()
	  .'<div class=content><b>SQL</b>: '.$tEv->sql.'</div>'
	  ;
	return $out;
    }
    
    // -- READ DATA -- //
}

/*::::
  REQUIRES: record class's table wrapper must implement GetActionKey().
*/
trait ftLoggableRecord {
    use ftLoggableObject;
    
    // ++ DB WRITE ++ //

    public function CreateEvent($sCode,$sText,array $arData=NULL) {
	$tBase = $this->EventTable();
	$id = $tBase->CreateBaseEvent($sCode,$sText,$arData);
	// create sub-event for current record
	$tSub = $tBase->TableWrapper_forInTable();
	$tSub->CreateRecord($id,$this->GetTableWrapper()->GetActionKey(),$this->GetKeyValue());
	return $id;
    }
    
    // -- DB WRITE -- //
    // ++ DB READ / WEB UI ++ //

    protected function EventListing() {
	$tEv = $this->EventTable();
	$sTbl = $this->GetActionKey();
	$id = $this->GetKeyValue();
	$sFor = " for $sTbl $id";
	$rs = $tEv->SelectRecords_forTable($sTbl,$id);
	return $this->EventListing_SectionHeader($sFor)->Render()
	  .$rs->AdminRows()
	  .'<div class=content><span class=line-stats><b>Events SQL</b>: '.$tEv->sql.'</span></div>'
	  ;
    }
    
    // -- DB READ / WEB UI -- //
}
/*----
  PURPOSE: Records every update and insert in the Ferreteria event log, laying in the basis for eventual "undo" capability.
  USES: fx() in ftLoggableRecord - maybe should be required? Can traits require an interface?
*/
define('KS_EVENT_FERRETERIA_AUTOLOG_INSERT','fe.ins');
define('KS_EVENT_FERRETERIA_AUTOLOG_UPDATE','fe.upd');
define('KS_FERRETERIA_STASH_AREA_BEFORE','before');	// stashed record values before the edit
define('KS_FERRETERIA_STASH_AREA_AFTER','after');	// stashed record values after the edit
define('KS_FERRETERIA_STASH_AREA_CHANGE','change');	// stashed values of changes to field values
define('KS_FERRETERIA_STASH_AREA_NEW','new');	// stashed values of new record
define('KS_FERRETERIA_FIELD_EDIT_NOTES','EvNotes');	// field for editor's notes about the edit

// REQUIRES: CreateEvent() (defined in ftLoggableTable, ftLoggableRecord)
trait ftLoggedRecord {
    protected function Insert(array $arRow) {
	$arData = array(
	  KS_FERRETERIA_STASH_AREA_NEW	=> $arRow
	  );
	//$idEv = fcApp::Me()->CreateEvent(KS_EVENT_FERRETERIA_AUTOLOG_INSERT,'insert',$arData);
	$idEv = $this->CreateEvent(KS_EVENT_FERRETERIA_AUTOLOG_INSERT,'insert',$arData);
	$idNew = parent::Insert($arRow,$isNativeData);
	$this->LogEventCompletion($idEv,array('id'=>$idNew));
	return $idNew;
    }
    public function Update(array $arChg,$isNativeData = false) {
	$arOld = $this->GetFieldValues();
	$arData = array(
	  KS_FERRETERIA_STASH_AREA_BEFORE	=> $arOld,
	  KS_FERRETERIA_STASH_AREA_CHANGE	=> $arChg
	  );
	//$idEv = fcApp::Me()->CreateEvent(KS_EVENT_FERRETERIA_AUTOLOG_UPDATE,'update',$arData);
	$idEv = $this->CreateEvent(KS_EVENT_FERRETERIA_AUTOLOG_UPDATE,'update',$arData);
	parent::Update($arChg,$isNativeData);
	$this->LogEventCompletion($idEv);
    }
    protected function LogEventCompletion($idEvent,array $arData=NULL) {
	$db = $this->GetConnection();
	$tBase = $this->EventTable();

	// This is done in ftLoggableRecord::CreateEvent()
	//$tSub = $tBase->TableWrapper_forInTable();
	//$tSub->CreateRecord($idEvent,$this->GetTableWrapper()->GetActionKey(),$this->GetKeyValue());
	
	$tSub = $tBase->TableWrapper_forDone();
	if ($db->IsOkay()) {
	    $tSub->CreateRecord($idEvent,KS_EVENT_SUCCESS,NULL,$arData);
	} else {
	    $tSub->CreateRecord($idEvent,KS_EVENT_FAILED,NULL,$arData);
	}
	
	// log edit notes, if any
	$oFormIn = fcHTTP::Request();
	$sNotes = $oFormIn->GetString(KS_FERRETERIA_FIELD_EDIT_NOTES);
	if (!is_null($sNotes)) {
	    $tSub = $tBase->TableWrapper_forNotes();
	    $tSub->CreateRecord($idEvent,$sNotes);
	}
    }
}
abstract class fctEvents_base extends fcTable_keyed_single_standard {

    // ++ CLASSES ++ //

    // CEMENT
    protected function SingularName() {
	return 'fcrEvent';
    }

    // -- CLASSES -- //
    // ++ CONFIG ++ //

    /*----
      PURPOSE: defines the set of event-field aliases used by the current event-logging table
      RETURNS: array[alias] => table fieldname
      NEW
    */
    abstract protected function FieldNameArray();
    
    // -- CONFIG -- //
    // ++ CALCULATIONS ++ //

    /*-----
      RETURNS: event arguments translated into field names for use in Insert()
      PUBLIC so records-type can use it to Finish events
      NOTE: This is the method which ultimately determines what the list of values is.
      INPUT: Array containing zero or more elements whose keys match the keys of self::arArgFields,
	which descendant classes must define.
      HISTORY:
	2016-03-13 'params' argument is now automatically serialized if it isn't a string
	2016-10-23 adapting from db.v1 (CalcSQL())
	2016-10-24 changed event name definition from static array to function
    */
    public function FigureSQL_forArgs(array $arArgs) {
	$arIns = NULL;
	$db = $this->GetConnection();
	$arFNames = $this->FieldNameArray();
	foreach ($arArgs as $key=>$val) {
	    if (array_key_exists($key,$arFNames)) {
		$sqlKey = $arFNames[$key];
		if ($key == 'params') {
		    if (!is_string($val)) {
			$val = serialize($val);
		    }
		}
		$sqlVal = $db->Sanitize_andQuote($val);
	    } else {
		throw new exception('Unrecognized event argument "'.$key.'".');
	    }
	    $arIns[$sqlKey] = $sqlVal;
	}
	return $arIns;
    }
    /*----
      PURPOSE: builds a string briefly describing the current user
      TODO: this should fetch the current *system* user when in CLI mode
      HISTORY:
	2016-10-31 This was using lack of App object to detect CLI mode, but actually there should still be an App object in CLI mode.
	  The App object itself should probably have a flag for that.
    */
    protected function UserString() {
	$rcUser = vcApp::Me()->GetUserRecord();
	if (is_null($rcUser)) {
	    $out = '(n/a)';
	} else {
	    $sUser = $rcUser->LoginName();
	    $nUser = $rcUser->GetKeyValue();
	    $out = "$sUser (#$nUser)";
	}
	return $out;
    }

    // -- CALCULATIONS -- //
    // ++ RECORDS ++ //

    /*----
      RETURNS: dataset consisting of events related to the specific DatSet object given
      USED BY: Local Catalog Item event listing, at least
      HISTORY:
	2017-01-15 adapting from db.v1
    */
    public function EventRecords($sTableKey=NULL,$idTableRow=NULL,$bDebug=FALSE) {
	$arFilt = NULL;
	if (!is_null($sTableKey)) {
	    $arFilt[] = 'ModType="'.$sTableKey.'"';
	}
	if (!is_null($idTableRow)) {
	    $arFilt[] = 'ModIndex='.$idTableRow;
	}
	if (!$bDebug) {
	    $arFilt[] = 'NOT isDebug';
	}
	$of = new fcSQLt_Filt('AND',$arFilt);
	$sqlFilt = $of->RenderValue();	// don't include the 'WHERE'
	
	return $this->SelectRecords($sqlFilt);
    }

    // -- RECORDS -- //
    // ++ ACTION ++ //
    
    /*----
      INPUT:
	$arArgs = Array containing any of several possible elements as defined by clsSysEvents
	$arEdits (optional) = changes being made to the data record's values
	  TODO: This currently is not used, and needs to be implemented.
    */
    public function CreateEvent(array $arArgs,$arEdits=NULL) {
	$arIns = $this->FigureSQL_forArgs($arArgs);
	if (empty($arIns)) {
	    return NULL;
	} else {
	    $arIns['WhenStarted'] = 'NOW()';
	    if (array_key_exists('REMOTE_ADDR',$_SERVER)) {
		// looks like an http connection, so log Ferreteria user and connection's IP address:
		$sUser = $this->UserString();
		$sAddr = $_SERVER['REMOTE_ADDR'];
	    } else {
		// looks like a command-line connection, so log system user and whatever SSH_CLIENT is
		$sUser = '(sys:'.$_SERVER['USER'].')';
		$sAddr = fcArray::Nz($_SERVER,'SSH_CLIENT','LAN');
	    }
	    $db = $this->GetConnection();
	    $arIns['WhoNetwork'] = $db->Sanitize_andQuote($sAddr);
	    $arIns['WhoAdmin'] = $db->Sanitize_andQuote($sUser);
	    $idNew = $this->Insert($arIns);
	    if ($idNew) {
		$rcEv = $this->GetRecord_forKey($idNew);
		return $rcEv;
	    } else {
		return NULL;
	    }
	}
    }
    
    // -- ACTION -- //

}
// PURPOSE: This type will always be the singular type for fctEvents, regardless of what may change inside it.
abstract class fcrEvent_base extends fcRecord_standard {

    // ++ CALLABLE API ++ //

    public function Finish(array $arArgs=NULL) {
	if (is_array($arArgs)) {
	    $arUpd = $this->GetTableWrapper()->FigureSQL_forArgs($arArgs);
	}
	$arUpd['WhenFinished'] = 'NOW()';
	$this->Update($arUpd);
    }
    
    // -- CALLABLE API -- //
}
class fcrEvent extends fcrEvent_base {

    // Fieldname Konstants:
    const KF_DESCR_START	= 'descr';
    const KF_DESCR_FINISH	= 'descrfin';
    const KF_NOTES		= 'notes';
    const KF_MOD_TYPE		= 'type';
    const KF_MOD_INDEX	= 'id';
    const KF_WHERE		= 'where';
    const KF_CODE		= 'code';
    const KF_PARAMS		= 'params';
    const KF_IS_ERROR		= 'error';
    const KF_IS_SEVERE	= 'severe';
    // 2018-02-21 the above all seem like they should be DEPRECATED; new Stash fields:
    const KF_SQL	= 'sql';
}
// PURPOSE: This just provides some reasonable cementing of the abstract functions.
class fctEvents extends fctEvents_base {

    // ++ CEMENT ++ //

    protected function TableName() {
	return 'event_log';
    }
    protected function FieldNameArray() {
	return array(
	  fcrEvent::KF_DESCR_START	=> 'Descr',
	  fcrEvent::KF_DESCR_FINISH	=> 'DescrFin',
	  fcrEvent::KF_NOTES		=> 'Notes',
	  fcrEvent::KF_MOD_TYPE		=> 'ModType',
	  fcrEvent::KF_MOD_INDEX	=> 'ModIndex',
	  fcrEvent::KF_WHERE		=> 'EvWhere',
	  fcrEvent::KF_CODE		=> 'Code',
	  fcrEvent::KF_PARAMS		=> 'Params',
	  fcrEvent::KF_IS_ERROR		=> 'isError',
	  fcrEvent::KF_IS_SEVERE	=> 'isSevere',
	  );
    }
    
    // -- CEMENT -- //

}
