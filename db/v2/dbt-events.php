<?php
/*
  PURPOSE: adds logging capabilities to any table/recordset pair
  HISTORY:
    2014-06-10 Extracting useful non-vbz-specific bits from vbz-data.php
    2015-07-12 resolving conflicts with other edited version
    2015-09-06 moving methods into traits
        ftLoggableRecordset
    2016-10-23 adapting from db.v1 (events.php) to db.v2
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

/*::::
  REQUIRES: object must implement EventTable()
    This is implemented in both ftLoggableTable and ftLoggableRecord.
*/
trait ftLoggableObject {

    public function EventListing() {
	return $this->EventTable()->EventListing();
    }
}
/*::::
  REQUIRES: nothing yet
*/
trait ftLoggableTable {
    use ftLoggableObject;

    // ++ ABSTRACT ++ //
    
    abstract public function GetActionKey();
    
    // -- ABSTRACT -- //
    
    /*----
      ACTION: Automatically adds in the Table's specs to the event data,
	then passes the request on to the event logger table wrapper.
      INPUT:
	$arArgs = array containing event data to be written
	$arEdits (optional) = list of changes being made to the data record's values
      RETURNS: Event record
    */
    public function CreateEvent(array $arArgs,$arEdits=NULL) {
	$arArgs[fcrEvent::KF_MOD_TYPE] = $this->GetActionKey();
	return $this->EventTable()->CreateEvent($arArgs,$arEdits);
    }
}

/*::::
  REQUIRES: record class's table wrapper must implement GetActionKey().
*/
trait ftLoggableRecord {
    use ftLoggableObject;
  
    public function CreateEvent(array $arArgs,$arEdits=NULL) {
	$arArgs[fcrEvent::KF_MOD_TYPE] = $this->GetTableWrapper()->GetActionKey();
	$arArgs[fcrEvent::KF_MOD_INDEX] = $this->GetKeyValue();
	return $this->EventTable()->CreateEvent($arArgs,$arEdits);
    }
}

abstract class fctEvents extends fcTable_keyed_single_standard {

    // ++ CEMENTING ++ //

    protected function SingularName() {
	return 'fcrEvent';
    }

    // -- CEMENTING -- //
    // ++ EXPECTED FX ++ //
    
    abstract protected function FieldNameArray();
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
      TODO: this should fetch the current *system* user when in CLI mode
      HISTORY:
	2016-10-31 This was using lack of App object to detect CLI mode, but actually there should still be an App object in CLI mode.
    */
    protected function UserString() {
	$oUser = vcApp::Me()->GetUserRecord();
	if (is_null($oUser)) {
	    $out = '(n/a)';
	} else {
	    $sUser = $oUser->UserName();
	    $nUser = $oUser->GetKeyValue();
	    $out = "$sUser (#$nUser)";
	}
	return $out;
    }

    // -- EXPECTED FX -- //

}
// PURPOSE: This type will always be the singular type for fctEvents, regardless of what may change inside it.
abstract class fcrEvent_abstract extends fcRecord_standard {

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
class fcrEvent extends fcrEvent_abstract {

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
}
// PURPOSE: This just provides some reasonable cementing of the abstract functions.
class fctEvents_standard extends fctEvents {

    // ++ CEMENTING ++ //

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
    
    // -- CEMENTING -- //

}

