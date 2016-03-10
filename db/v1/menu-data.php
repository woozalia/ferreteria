<?php
/*
  PURPOSE: menu-aware data base class
  HISTORY:
    2014-06-10 Extracting useful non-vbz-specific bits from vbz-data.php
    2015-07-12 resolving conflicts with other edited version
    2015-09-06 moving methods into traits
        ftLoggableRecordset
*/

/*%%%%
  ASSUMES: object has an Engine() method
*/
trait ftLoggableObject {
    //private $oLogger;
    private $rcLastEvent;
    public function StartEvent(array $arArgs) {
	return $this->Log()->StartEvent($arArgs);
    }
    public function FinishEvent(array $arArgs=NULL) {
	return $this->Log()->FinishEvent($arArgs);
    }
    public function CreateEvent(array $arArgs) {
	$rcEv = $this->Log()->CreateEvent($arArgs);
	$this->SetEvent($rcEv);	// is this a kluge?
	return $rcEv;
    }
    public function EventListing() {
	return $this->Log()->EventListing();
    }
    protected function SetEvent(clsSysEvent $rcEvent) {
	$this->rcLastEvent = $rcEvent;
    }
    protected function GetEvent() {
	return $this->rcLastEvent;
    }
}
trait ftLoggableRecord {
    use ftLoggableObject;
  
    protected function Log() {
        static $oLogger = NULL;

	if (is_null($oLogger)) {
	    $tLog = $this->Engine()->App()->Events();
	    $oLogger = new clsLogger_DataSet($this,$tLog);
	}
	return $oLogger;
    }
}
trait ftLoggableTable {
    use ftLoggableObject;
    
    protected function Log() {
        static $oLogger = NULL;

	if (is_null($oLogger)) {
	    $tLog = $this->Engine()->App()->Events();
	    $oLogger = new clsLogger_Table($this,$tLog);
	}
	return $oLogger;
    }
}

class clsDataTable_Menu extends clsTable {
    use ftLinkableTable;
    /*----
      NOTE: Not sure if this is really necessary, but leaving it for now.
	It may be helpful during development. Perhaps the base method should
	be modified. (It includes everything except the default to the
	singular class-name.)
    */
    /* 2015-10-06 with the addition of ftLinkableTable, this is now redundant.
    public function ActionKey($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->ActionKey = $iVal;
	}
	if (isset($this->ActionKey)) {
	    return $this->ActionKey;
	} else {
	    return $this->ClassSng();
	}
    } */
}
/*%%%%
  PURPOSE: intermediate recordset class that doesn't assume a standalone application implemented with Ferreteria
*/
class clsDataRecord_admin extends clsDataSet {
    use ftLinkableRecord;
    use ftLoggableRecord;
    use ftShowableRecord;

    // ++ TRAIT CALLBACKS ++ //

    public function IdentityValues() {
	$ar = array(
	  'page'	=> $this->Table()->ActionKey(),
	  'id'		=> $this->KeyValue(),
	  );
	return $ar;
    }
    public function BaseURL_rel() {
	return $this->Engine()->App()->Page()->BaseURL_rel();
    }

    // -- TRAIT CALLBACKS -- //
    // ++ ADMIN UI ++ //

    // -- ADMIN UI -- //
}
/*%%%%
  PURPOSE: not sure. The implementation of BaseURL_rel() only works within Ferreteria's app framework,
    but surely there's a more general way of handling this.
*/
class clsDataRecord_Menu extends clsDataRecord_admin {

    // ++ HELPER CALLBACKS ++ //

    public function BaseURL_rel() {
	return $this->Engine()->App()->Page()->BaseURL_rel();
    }

    // -- HELPER CALLBACKS -- //
}