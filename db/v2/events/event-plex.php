<?php
/*
  PURPOSE: The long-awaited Sensible Event Logging System
    Allows extended tables to store specialized data for certain event types.
    Assumes the standard user application framework, and logs session data.
  HISTORY
    2017-02-06 started
    2017-03-19 It looks like the least absurdly complicated way to handle all the sub-event types
      is for the Plex class to be aware of them all. Extendability can be handled by descending
      from the Plex class where additional sub-event types are needed.
    2017-03-20 I tried again to make the sub-event system transparently extendable, but 
      this resulted in ridiculous complexity (yet again) --
      so for now I'm just implementing _standard Plex table and recordset types, and descending from those
      for the admin dropin. I'll just have to revisit the question of extendability later on.
      
      That said, the SELECT statement will still be auto-generated, because that works well enough.
      
      SubEvent methods will have to be implemented by the Plex recordset class, however. This means we never
      actually use SubEvent recordset types, unfortunately.
      
      POSSIBLE SOLUTION: Is there a way to add methods to an object or class at runtime?
    2017-04-14 Renamed fctPlex_Table to fctPlex_EventTable to reduce confusion with fctSubEvents_InTable.
*/

// TODO: 2017-02-11 Need to document all the error codes somewhere

// - this gets logged whenever data is found that violates some rule:
define('KS_EVENT_FERRETERIA_DB_INTEGRITY_ERROR','ferreteria.error.db.integrity');

/*::::
  PURPOSE: Handles aggregation of base events and subevent types
*/
class fctEventPlex extends fcDataTable_array {
    use ftSource_forTable;
    use ftSingleKeyedTable;

    // ++ SETUP ++ //

    protected function GetKeyName() {
	return $this->BaseTable()->GetKeyName();
    }
    protected function SingularName() {
	return 'fcrEventPlect';
    }
    
    // -- SETUP -- //
    // ++ SUB-EVENTS ++ //
    
    private $arTypes=NULL;
    public function RegisterEventTable(fctPlex_EventTable $tSubType) {
	// add table wrapper object to types index
	$sType = $tSubType->GetTypeKey();
	$this->arTypes[$sType] = $tSubType;
    }
    /*----
      PURPOSE: Creates the table wrapper object and registers it
	Can be used instead of RegisterEventTable() if caller doesn't already have the object
    */
    public function RegisterEventClass($sClass) {
	$t = $this->GetConnection()->MakeTableWrapper($sClass);
	$this->RegisterEventTable($t);
    }
    protected function GetEventTypes() {
	return $this->arTypes;
    }
    protected function HasSubEvents() {
	return is_array($this->arTypes);
    }
    protected function GetWrapperFor($sType) {
	return $this->arTypes[$sType];
    }
    
    // -- SUB-EVENTS -- //
    // ++ CLASSES ++ //
    
    protected function BaseClass() {
	return 'fctPlex_BaseEvents';
    }
    
    // -- CLASSES -- //
    // ++ TABLES ++ //
    
    protected function BaseTable() {
	return $this->GetConnection()->MakeTableWrapper($this->BaseClass());
    }
    
    // -- TABLES -- //
    // ++ INTERNAL STATES ++ //
    
    private $idLast;
    protected function SetLastID($id) {
	$this->idLast = $id;
    }
    public function GetLastID() {
	return $this->idLast;
    }
    
    // -- INTERNAL STATES -- //
    // ++ WRITE DATA ++ //
    
    public function CreateBaseEvent($sCode,$sText,array $arData=NULL) {
	$id = $this->BaseTable()->CreateEvent($sCode,$sText,$arData);
	$this->SetLastID($id);
	return $id;
    }
    
    // -- WRITE DATA -- //
    // ++ READ DATA ++ //
    
    public function SelectRecords($sqlFilt=NULL,$sqlSort='WhenStart DESC') {
	$tBase = $this->BaseTable();
	$sqlBase = $tBase->TableName_cooked();
	$sKeyBase = $tBase->GetTypeKey();
	$sqlJoin = $sqlBase.' AS '.$sKeyBase;
	$sqlFields = $tBase->FieldsSQL();

	if ($this->HasSubEvents()) {
	    $arTypes = $this->GetEventTypes();
	    foreach ($arTypes as $sKey => $tSub) {
		$sqlTable = $tSub->TableName_cooked();
		$sqlJoin = "($sqlJoin) LEFT JOIN $sqlTable AS $sKey ON $sKey.ID_Event=e.ID";
		$sqlHas = "($sKey.ID_Event IS NOT NULL) AS has_$sKey";
		$sqlFields .= ', '.$sqlHas.', '.$tSub->FieldsSQL();
	    }
	}
	
	$sqlWhere = is_null($sqlFilt)?'':(' WHERE '.$sqlFilt);
	$sql = "SELECT e.ID, $sqlFields FROM $sqlJoin$sqlWhere ORDER BY $sqlSort";
	//echo 'SPAWN CLASS: ['.$this->SingularName().']<br>';
	$rs = $this->FetchRecords($sql);
	return $rs;
    }
    public function SelectRecords_forTable($sKey,$idRow=NULL) {
	$sqlFilt = "TableKey='$sKey'";
	if (!is_null($idRow)) {
	    $sqlFilt = "($sqlFilt) AND (TableRow=$idRow)";
	}
	$rs = $this->SelectRecords($sqlFilt);
	return $rs;
    }
    // -- READ DATA -- //
}
class fcrEventPlect extends fcDataRecord {
    
    const kEVENT_TYPE_KEY_DONE = 'done';
    const kEVENT_TYPE_KEY_INTABLE = 'tbl';
    const kEVENT_TYPE_KEY_NOTE = 'note';

    // ++ FIELD VALUES ++ //	- from the base event type

    protected function WhenStarted() {
	return $this->GetFieldValue('e_WhenStart');
    }
    protected function SessionID() {
	return $this->GetFieldValue('e_ID_Session');
    }
    protected function AccountID() {
	return $this->GetFieldValue('e_ID_Acct');
    }
    protected function TypeCode() {
	return $this->GetFieldValue('e_TypeCode');
    }
    protected function BaseDescriptiveText() {
	return $this->GetFieldValue('e_Descrip');
    }
    protected function BaseStashString() {
	return $this->GetFieldValue('e_Stash');
    }

    // -- FIELD VALUES -- //
    // ++ FIELD CALCULATIONS ++ //	- from the base event type

    protected function HasSession() {
	return !is_null($this->SessionID());
    }
    protected function HasAccount() {
	return !is_null($this->AccountID());
    }
    static protected function StashString_to_ShortText($sStash) {
	$arStash = unserialize($sStash);
	$nStash = count($arStash);
	return $nStash.' item'.fcString::Pluralize($nStash);
    }
    protected function BaseStashCompact() {
	return self::StashString_to_ShortText($this->BaseStashString());
    }

    // -- FIELD CALCULATIONS -- //
    // ++ SUBEVENT FIELDS ++ //

    protected function HasDoneFields() {
	return $this->GetFieldValue('has_'.self::kEVENT_TYPE_KEY_DONE);
    }
    protected function WhenFinished() {
	return $this->GetFieldValue(self::kEVENT_TYPE_KEY_DONE.'_WhenFinish');
    }
    protected function DoneStateCode() {
	return $this->GetFieldValue(self::kEVENT_TYPE_KEY_DONE.'_StateCode');
    }
    protected function DoneDescription() {
	return $this->GetFieldValue(self::kEVENT_TYPE_KEY_DONE.'_Descrip');
    }
    protected function DoneStashString() {
	return $this->GetFieldValue(self::kEVENT_TYPE_KEY_DONE.'_Stash');
    }
    protected function DoneStashCompact() {
	return self::StashString_to_ShortText($this->DoneStashString());
    }
    
    protected function HasTableFields() {
	return $this->GetFieldValue('has_'.self::kEVENT_TYPE_KEY_INTABLE);
    }
    
    protected function HasNoteField() {
	return $this->GetFieldValue('has_'.self::kEVENT_TYPE_KEY_NOTE);
    }
    protected function NoteString() {
	return $this->GetFieldValue(self::kEVENT_TYPE_KEY_NOTE.'_Notes');
    }

    // -- SUBEVENT FIELDS -- //
}

class fctEventPlex_standard extends fctEventPlex {

    // ++ SETUP ++ //

    protected function InitVars() {
	parent::InitVars();
	$this->RegisterEventClass('fctSubEvents_Done');
	$this->RegisterEventClass('fctSubEvents_InTable');
	$this->RegisterEventClass('fctSubEvents_Note');
    }
    
    // -- SETUP -- //
    // ++ TABLES ++ //

    public function TableWrapper_forDone() {
	return $this->GetWrapperFor(fcrEventPlect::kEVENT_TYPE_KEY_DONE);
    }
    public function TableWrapper_forInTable() {
	return $this->GetWrapperFor(fcrEventPlect::kEVENT_TYPE_KEY_INTABLE);
    }
    public function TableWrapper_forNotes() {
	return $this->GetWrapperFor(fcrEventPlect::kEVENT_TYPE_KEY_NOTE);
    }
    
    // -- TABLES -- //
}
// TODO: field access methods for subevents
abstract class fcrEventPlect_standard extends fcrEventPlect {
}

// NOTE: not to be confused with fctSubEvents_InTable
abstract class fctPlex_EventTable extends fcTable_keyed_single_standard {
    /*----
      RETURNS: key for type of event -- like an ActionKey, but only unique within Event sub-types
	This is used in the SQL SELECT statement as an alias for each sub-event table.
    */
    abstract public function GetTypeKey();
    /*----
      RETURNS: array of field-names to be included in the JOIN statement (not including ID_Event)
    */
    abstract protected function FieldArray();
    public function FieldsSQL() {
	$sql = NULL;
	$arFields = $this->FieldArray();
	$sKey = $this->GetTypeKey();
	foreach ($arFields as $sField) {
	    if (!is_null($sql)) {
		$sql .= ', ';
	    }
	    $sql .= "$sKey.$sField AS ".$sKey.'_'.$sField;
	}
	return $sql;
    }
}
class fctPlex_BaseEvents extends fctPlex_EventTable {

    // ++ SETUP ++ //
    
    protected function SingularName() {
	return 'fcrPlex_BaseEvent';
    }
    protected function TableName() {
	return 'event';
    }
    public function GetTypeKey() {
	return 'e';
    }
    protected function FieldArray() {
	return array('WhenStart','ID_Session','ID_Acct','TypeCode','Descrip','Stash');
    }

    // -- SETUP -- //
    // ++ STATES ++ //
    
    public function CreateEvent($sCode,$sText,array $arData=NULL) {
	$oApp = fcApp::Me();
	$db = $oApp->GetDatabase();
	$sqlUser = $oApp->UserIsLoggedIn() ? $oApp->GetUserID() : 'NULL' ;
	$sqlData = is_null($arData)? 'NULL' : ($db->SanitizeValue(serialize($arData)));
	$ar = array(
	  'WhenStart'	=> 'NOW()',
	  'ID_Session'	=> $oApp->GetSessionRecord()->GetKeyValue(),
	  'ID_Acct'	=> $sqlUser,
	  'TypeCode'	=> $db->SanitizeValue($sCode),
	  'Descrip'	=> $db->SanitizeValue($sText),
	  'Stash'	=> $sqlData
	  );
	$id = $this->Insert($ar);
	if ($id === FALSE) {
	    throw new exception('Could not log event!');
	}
	return $id;
    }
}
/*----
  HISTORY:
    2017-03-15 I had written a note that "class currently not actually used.", but actually it is.
      Adding field methods for displaying log.
    2018-02-22 So... where are those field methods again?
*/
class fcrPlex_BaseEvent extends fcRecord_keyed_single_integer {
}

// === SUB EVENTS === //

// ABSTRACT: n/i = { SingularName(), TableName() }
abstract class fctSubEvents extends fctPlex_EventTable {
}

class fctSubEvents_Done extends fctSubEvents {

    // ++ SETUP ++ //

    // CEMENT
    protected function SingularName() {
	return 'fcrEventPlex';
    }
    // CEMENT
    protected function TableName() {
	return 'event_done';
    }
    // CEMENT
    public function GetTypeKey() {
	return fcrEventPlect::kEVENT_TYPE_KEY_DONE;
    }
    // CEMENT
    protected function FieldArray() {
	return array('WhenFinish','StateCode','Descrip','Stash');
    }

    // -- SETUP -- //
    // ++ WRITE DATA ++ //
    
    public function CreateRecord($idEvent,$sState,$sText=NULL,array $arData=NULL) {
	$db = $this->GetConnection();
	$sqlData = is_null($arData)? 'NULL' : ($db->SanitizeValue(serialize($arData))) ;
	$ar = array(
	  'ID_Event'	=> $idEvent,
	  'WhenFinish'	=> 'NOW()',
	  'StateCode'	=> $db->SanitizeValue($sState),
	  'Descrip'	=> $db->SanitizeValue($sText),
	  'Stash'	=> $sqlData
	  );
	$id = $this->Insert($ar);
	return $id;
    }

    // -- WRITE DATA -- //
}
class fcrSubEvent_Done extends fcRecord_keyed_single_integer {
}
class fctSubEvents_InTable extends fctSubEvents {
    // CEMENT
    protected function SingularName() {
	return 'fcrSubEvent_inTable';
    }
    // CEMENT
    protected function TableName() {
	return 'event_in_table';
    }
    // CEMENT
    public function GetTypeKey() {
	return fcrEventPlect::kEVENT_TYPE_KEY_INTABLE;
    }
    // CEMENT
    protected function FieldArray() {
	return array('TableKey','TableRow');
    }
    
    // ++ WRITING ++ //
    
    public function CreateEvent($idEvent,$sTable,$sRow=NULL) {
	throw new exception('Oops! Call CreateRecord() instead.');
    }
    public function CreateRecord($idEvent,$sTable,$idRow=NULL) {
	$db = $this->GetConnection();
	$ar = array(
	  'ID_Event'	=> $idEvent,
	  'TableKey'	=> $db->SanitizeValue($sTable),
	  'TableRow'	=> $db->SanitizeValue($idRow)
	  );
	$id = $this->Insert($ar);
	return $id;
    }
    
    // -- WRITING -- //

}
class fcrSubEvent_InTable extends fcRecord_keyed_single_integer {
}
class fctSubEvents_Note extends fctSubEvents {

    // ++ SETUP ++ //

    // CEMENT
    protected function SingularName() {
	return 'fcrSubEvent_Note';
    }
    // CEMENT
    protected function TableName() {
	return 'event_notes';
    }
    // CEMENT
    public function GetTypeKey() {
	return fcrEventPlect::kEVENT_TYPE_KEY_NOTE;
    }
    // CEMENT
    protected function FieldArray() {
	return array('Notes');
    }

    // -- SETUP -- //
    // ++ DATA WRITE ++ //

    public function CreateRecord($idEvent,$sNotes) {
	$db = $this->GetConnection();
	$ar = array(
	  'ID_Event'	=> $idEvent,
	  'Notes'	=> $db->SanitizeValue($sNotes),
	  );
	$id = $this->Insert($ar);
	return $id;
    }

    // -- DATA WRITE -- //
}
class fcrSubEvent_Note extends fcRecord_keyed_single_integer {
}