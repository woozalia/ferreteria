<?php
/*
  PURPOSE: The long-awaited Sensible Event Logging System
    Allows extended tables to store specialized data for certain event types.
    Assumes the standard user application framework, and logs session data.
  HISTORY
    2017-02-06 started
*/

// TODO: 2017-02-11 Need to document all the error codes somewhere

// - this gets logged whenever data is found that violates some rule:
define('KS_EVENT_FERRETERIA_DB_INTEGRITY_ERROR','ferreteria.error.db.integrity');

class fctEventPlex extends fcTable_keyed_single_standard {

    // ++ SETUP ++ //
    
    protected function SingularName() {
	return 'fcrEventPlex';
    }
    protected function TableName() {
	return 'event';
    }

    // ++ STATES ++ //
    
    private $idEvent;
    protected function SetLastID($id) {
	$this->idEvent = $id;
	if (is_null($this->idEvent)) {
	    throw new exception('Ferreteria internal error: event ID being set to NULL.');
	}
    }
    public function GetLastID() {
	if (is_null($this->idEvent)) {
	    throw new exception('Ferreteria usage error: trying to retrieve event ID before event has been created.');
	}
	return $this->idEvent;
    }

    
    public function CreateEvent($sCode,$sText,array $arData=NULL) {
	$oApp = fcApp::Me();
	$db = $oApp->GetDatabase();
	$sqlUser = $oApp->UserIsLoggedIn() ? $oApp->GetUserID() : 'NULL' ;
	$sqlData = is_null($arData)? 'NULL' : ($db->Sanitize_andQuote(serialize($arData))) ;
	$ar = array(
	  'WhenStart'	=> 'NOW()',
	  'ID_Session'	=> $oApp->GetSessionRecord()->GetKeyValue(),
	  'ID_Acct'	=> $sqlUser,
	  'TypeCode'	=> $db->Sanitize_andQuote($sCode),
	  'Descrip'	=> $db->Sanitize_andQuote($sText),
	  'Stash'	=> $sqlData
	  );
	$id = $this->Insert($ar);
	if ($id === FALSE) {
	    throw new exception('Could not log event!');
	}
	// create an event record to be used for adding subsidiary data:
	/*
	$rcEvent = $this->SpawnRecordset();
	$rcEvent->SetKeyValue($id);
	return $rcEvent;
	*/
	$this->SetLastID($id);
	return $id;
    }
}
// NOTE: class currently not actually used.
class fcrEventPlex extends fcRecord_keyed_single_integer {
}
// ABSTRACT: n/i = SingularName(), TableName()
abstract class fctSubEvents extends fcTable_keyed_single_standard {
}

class fctSubEvents_done extends fctSubEvents {

    // ++ SETUP ++ //

    protected function SingularName() {
	return 'fcrEventPlex';
    }
    protected function TableName() {
	return 'event_done';
    }

    // -- SETUP -- //
    // ++ WRITE DATA ++ //
    
    public function CreateRecord($idEvent,$sState,$sText=NULL) {
	$db = $this->GetConnection();
	$ar = array(
	  'ID_Event'	=> $idEvent,
	  'WhenFinish'	=> 'NOW()',
	  'State'	=> $db->Sanitize_andQuote($sState),
	  'Descrip'	=> $db->Sanitize_andQuote($sText)
	  );
	$id = $this->Insert($ar);
	return $id;
    }

    // ++ WRITE DATA ++ //
}
class fcrSubEvent_done extends fcRecord_keyed_single_integer {
}