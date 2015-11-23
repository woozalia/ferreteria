<?php
/* =============================
  CLASS LIBRARY: Cache Management
  DOCUMENTATION: http://htyp.org/User:Woozle/cache.php
  HISTORY:
    2009-04-12 significant rewrite:
      - not using clsLibMgr
      - using rewritten data.php
    2009-09-07 more rewriting to use new DataSet construction standard
    2009-10-14 using KFP_LIB instead of constructed path
    2010-11-09 another significant rewrite to use queries instead of procedures
    2010-11-10 ...which turned out not to be a good idea. Change back later, or adapt to use either one.
    2010-11-14 finally renamed file from datamgr.php to cache.php
    2011-03-03 clsCacheFile
*/

/*
if (defined( '__DIR__' )) {
  $fpThis = __DIR__;
} else {
  $fpThis = dirname(__FILE__);
}
*/
/*
if (!defined('LIBMGR')) {
    require('libmgr.php');
}
clsLibMgr::Add('data',KFP_LIB.'/data.php',__FILE__,__LINE__);
clsLibMgr::Load('data',__FILE__,__LINE__);
*/
/*=====
  PURPOSE: dead simple file-based cache for a small number of larger chunks of data
*/
class clsCacheFile {
    protected $fpFolder;

    public function __construct($iFolder) {
	$this->fpFolder = $iFolder;
    }
    protected function Spec($iName) {
	$fs = $this->fpFolder.'/'.$iName.'.txt';
	return $fs;
    }
    public function Exists($iName) {
	$ok = file_exists($this->Spec($iName));
	return $ok;
    }
    public function Read($iName) {
	$fs = $this->Spec($iName);
	$txt = file_get_contents($fs);
	return $txt;
    }
    public function Write($iName,$iText) {
	$fs = $this->Spec($iName);
	$cnt = file_put_contents($fs,$iText);
	return $cnt;
    }
}
/* ============ *\
    manager
\* ============ */
class clsCacheMgr {
// parent object
    protected $objDB;	// database where tables are located
// child objects
    public $Tables;	// table containing info about managed tables
    public $Procs;	// table containing list of stored procedures which do updates
    public $Flow;	// table showing how procedures update tables
    public $Log;	// table for logging table updates
// stats
    public $strMsgs;	// status messages to display

    public function __construct($iDB) {
	assert('is_object($iDB)');
	$this->objDB	= $iDB;
	$this->strMsgs	= '';
    }
    /*----
      NOTES:
	If we later want to extend the functionality of the classes created here,
	  we can create them in a method and then redefine the method
	  in a child class of clsCacheMgr.
	For now, we just assume we always want to use these classes
	  (but the table names can change).
    */
    public function SetTables($iTables,$iProcs,$iFlow,$iLog) {
	$this->Tables	= $this->NewTblTables($iTables);
	$this->Procs	= $this->NewTblProcs($iProcs);
	$this->Flow	= $this->NewTblFlows($iFlow);
	$this->Log	= $this->NewTblEvents($iLog);
    }
    protected function NewTblTables($iName) {
	assert('is_string($iName); /* '.print_r($iName,TRUE).' */');
	return new clsCacheTables($this,$iName);
    }
    protected function NewTblProcs($iName) {
	return new clsCacheProcs($this,$iName);
    }
    protected function NewTblFlows($iName) {
	return new clsCacheFlows($this,$iName);
    }
    protected function NewTblEvents($iName) {
	return new clsCacheEvents($this,$iName);
    }
    public function Engine() {
	return $this->objDB;
    }
    /*
      2010-11-10 Why does this possibly retrieve more than one row? Shouldn't it use GetMgrItem()?
    */
/*
    private function GetTableInfo($iFilt) {
	$objDest = $this->Tables->GetMgrData($iFilt);
	$objDest->NextRow();
	assert('is_object($objDest)');
	assert('is_object($objDest->Mgr())');
	return $objDest;
    }
*/

//    public function Update_byID($iTableID,$iCaller) {
    /*
	$objDest = $this->Tables->GetData('ID='.$iTableID);
	$objDest->Mgr($this);
    */
/*
	//$objDest = $this->GetTableInfo('ID='.$iTableID);
	$objDest = $this->Tables->GetItem($iTableID);
	$arOut = $this->Update_byObj($objDest,$iCaller);
	return $arOut;
    }
*/
    public function UpdateTime_byName($iTableName) {
	$tblUpd = $this->Tables->GetData('Name="'.$iTableName.'"');
	$tblUpd->NextRow();	// load first (and only) data row
	$arOut = $tblUpd->UpdateTime();
	return $arOut;
    }
    public function UpdateTime_byTable(clsTable_abstract $iTable) {
	$this->UpdateTime_byName($iTable->Name());
    }
    /*----
      USED BY: customer-side catalog display routines -- table classes don't know what their cache management IDs are,
	so we have to look them up by name.
      HISTORY:
	2010-11-12 Renamed from Update_byName to UpdateData_byName... and then commented out,
	  because catalog display routines shouldn't be doing this. They should be using
	  UpdateTime_byName().
    */
/*
    public function UpdateData_byName($iTableName,$iCaller) {
	$objDest = $this->Tables->GetData('Name="'.$iTableName.'"');
	assert('is_object($objDest)');
	$objDest->Mgr($this);
	$objDest->NextRow();
	$arOut = $objDest->UpdateData($iCaller);
	return $arOut;
    }
*/
    /*----
      ACTION: Given a list of Procs, execute them and return relevant information
      INPUT:
	iProcs = array
      RETURNS: array
	[msgs] = messages to show admin user
	[targ] = array of targets updated
	  [id] = $obj
      FUTURE: This should probably be a method of the Procs class, or something
    */
/*
    public function ExecProcs(array $iProcs,array $iTarg=NULL) {
	$cntDone = 0;
	$out = NULL;
	$arTarg = NULL;
	foreach ($iProcs AS $id=>$obj) {
	    $out .= '<li>Running <b>'.$obj->Name.'</b>: ';
	    $arExec = $obj->Execute();
	    $ok = $arExec['ok'];
	    $out .= $arExec['msgs'];

	    if ($ok) {
		$cntDone++;

		foreach ($arExec['targ'] as $id => $obj) {
		    $iTarg[$id] = $obj;
		}
	    }
	}
	$arOut['msgs'] = $out;
	$arOut['cnt'] = $cntDone;
	$arOut['targ'] = $iTarg;
	return $arOut;
    }
*/
    /*-----
      ACTION: Return a list of source tables for the given table ID. as Flow records
    */
    public function Sources($iTable) {
	// get flows that write to this table
	$objRows = $this->Flow->GetData('(ID_Table='.$iTable.') AND (doWrite)');
	return $objRows;
    }
    /*-----
      ACTION: Return a list of target tables for the given table ID, as Flow records
    */
    public function Targets($iTable) {
	// get flows that don't write to this table
	$objRows = $this->Flow->GetData('(ID_Table='.$iTable.') AND (NOT doWrite)');
	return $objRows;
    }
}
/* ============= *\
   DataMgr Table
\* ============= */
class clsMgrTable extends clsTable_indexed {
    protected $objMgr;

    public function __construct(clsCacheMgr $iMgr, clsIndexer_Table $iIndexer=NULL) {
	$this->objMgr = $iMgr;
	parent::__construct($iMgr->Engine(),$iIndexer);
    }
    /*----
      HISTORY:
	2010-11-18 Other classes need access to the manager
    */
    public function Mgr() {
      return $this->objMgr;
    }
    /*----
      HISTORY:
	2010-11-10 Removed "clsCacheMgr $iMgr" input parameter -- should be getting this from $this->objMgr
    */
    public function GetMgrData($iWhere,$iClass=NULL) {
	CallEnter($this,__LINE__,'clsMgrTable.GetMgrData(...,"'.$iWhere.'","'.$iClass.'")');
	$objOut = $this->GetData($iWhere,$iClass);
	$objOut->Mgr($this->objMgr);
	CallExit('clsMgrTable.GetMgrData()');
	return $objOut;
    }
    /*----
      HISTORY:
	2010-11-10 Removed "clsCacheMgr $iMgr" input parameter -- should be getting this from $this->objMgr
	  Actually... replaced with GetItem()
    */
/*
    public function GetMgrItem($iID,$iClass=NULL) {
	CallEnter($this,__LINE__,'clsMgrTable.GetMgrItem(...,'.$iID.',"'.$iClass.'")');
	$objOut = $this->GetItem($iID,$iClass);
	$objOut->Mgr($this->objMgr);
	CallExit('clsMgrTable.GetMgrItem()');
	return $objOut;
    }
*/
}
/*====
  HISTORY:
    2011-01-19 Created -- Flows classes were not working right
*/
class clsMgrTable_single_key extends clsMgrTable {
    /*
      FUTURE: Modify this to use clsIndexer_Table_single_key, once that is written
    */
    public function __construct(clsCacheMgr $iMgr) {
	$objIdx = new clsIndexer_Table_single_key($this);
	$objIdx->KeyName('ID');
	$this->objMgr = $iMgr;
	parent::__construct($iMgr,$objIdx);
    }
    /*----
      HISTORY:
	2010-11-10 Created from GetMgrItem()
	2011-01-19 Moved from clsMgrTable to clsMgrTable_single_key
    */
    public function GetItem($iID=NULL,$iClass=NULL)  {
	$objOut = $this->Indexer()->GetItem($iID);
	$objOut->Mgr($this->objMgr);
	return $objOut;
    }
}
class clsMgrData extends clsRecs_indexed {
    protected $objMgr;

    public function Mgr(clsCacheMgr $iMgr=NULL) {
	if (!is_null($iMgr)) {
	    $this->objMgr = $iMgr;
	}
	return $this->objMgr;
    }
    /*----
      HISTORY:
	2010-11-10 Created
	2011-01-23 disabled; if RowCopy is needed, override to Release() should be written
    */
/*
    public function RowCopy() {
	$obj = parent::RowCopy();
	$obj->Mgr($this->objMgr);
	return $obj;
    }
*/
}
class clsMgrData_single_key extends clsMgrData {
    public function KeyValue() {
	return $this->objIdx->KeyValue('ID');	// TO DO: generalize this
    }
}
/* ============ *\
   DataTables
\* ============ */
class clsCacheTables extends clsMgrTable_single_key {
    public function __construct(clsCacheMgr $iMgr, $iName) {
	parent::__construct($iMgr);
	  $this->Name($iName);
	  //$this->KeyName('ID');
	  $this->ClassSng('clsCacheTable');
    }
}
class clsCacheTable extends clsMgrData_single_key {

    // INFO methods

    /*
      HISTORY:
	2011-12-22 created for command-line utility
    */
    public function Name() {
	return $this->Value('Name');
    }
    public function IsActive() {
	return $this->Value('isActive');
    }
    public function WhenUpdated() {
	return $this->Value('WhenUpdated');
    }
    /*----
      RETURNS: array of Procs that update this table
      HISTORY:
	2011-12-22 created for command-line utility
	2011-12-24 this isn't what we wanted; deprecating and adapting to TargProcs()
    */
/*
    public function SrceProcs() {
	$tbl = $this->Table()->Mgr()->Flow;
	return $tbl->SrceProcs_forTable($this->KeyValue());
    }
*/
    /*----
      RETURNS: array of Procs that source from this table
      HISTORY:
	2011-12-22 created for command-line utility
	2011-12-24 adapted from SrceProcs() (not needed) to TargProcs()
    */
    public function TargProcs() {
	$tbl = $this->Table()->Mgr()->Flow;
	return $tbl->TargProcs_forTable($this->KeyValue());
    }

    // ACTION methods

    /*----
      METHOD: UpdateData($iCaller)
      ACTION: Update this table's data from its source tables
	Check to see if the given table is up-to-date; update it if not.
      INPUT:
	iCaller = "who's askin'", i.e. descriptive/identifying string
	  relating to where in the code the request came from
	  and maybe the conditions which generated the request
      RETURNS: array
	array['msgs']: descriptive text to display to administrative user
	array['cnt']; number of procs executed
	array['proc']: array - one object for each proc executed (no index)
	array['targ']: array of targets updated
	  [id] => object -- object is target table updated
      NO LONGER RETURNED:
	array['ok']: TRUE if no errors were bumped into
    */
    public function UpdateData($iCaller) {
	$objDest = $this;

	if ($objDest->HasRows()) {
	    $arOut['proc'] = NULL;
	    $arOut['targ'] = NULL;
	    $arProcs = NULL;

	    $out = '<br>Updating table [<b>'.$objDest->Value('Name').'</b>]';
	    $dtDest = $objDest->Value('WhenUpdated');
	    $htDest = is_null($dtDest)?'never updated before':('last updated '.$dtDest);
	    $out .= ', '.$htDest.'... ';

	// get list of all updates for this table, with clearing functions first:
	    assert($objDest->KeyValue());
	    $idDest = $objDest->KeyValue();
	    $objFlow = $this->Mgr()->Flow->GetData_forDest($idDest);
	    assert('is_object($objFlow)');
	    if ($objFlow->hasRows()) {
	  // check for more recent source tables:
		$wasCleared = false;
		$didUpdate = false;

		$tblTables = $this->Mgr()->Tables;

	  // build lists of procs to run:
		$out .= '<ul>';
		while ($objFlow->NextRow()) {
		    //$objSrce = $objFlow->TableObj();
		    $objProc = $objFlow->ProcObj();
		    $rsSrces = $objProc->Sources();	// get flows showing sources used by this proc

		    $idProc = $objProc->KeyValue();
		    $out .= '<li>Checking [<b>'.$objProc->Value('Name').'</b>](ID '.$objFlow->Value('ID_Proc').') - ';

		    if ($rsSrces->HasRows()) {
			$out .= '<ul>';
			while ($rsSrces->NextRow()) {
			    $rsTable = $tblTables->GetItem($rsSrces->Value('ID_Table'));
			    $dtSrce = $rsTable->Value('WhenUpdated');
			    $out .= '<li>source <b>'.$rsTable->Value('Name').'</b> ';
			    $out .= is_null($dtSrce)?'never updated!':('updated '.$dtSrce.': ');
			    if ($wasCleared || (is_null($dtDest)) || ($dtSrce > $dtDest)) {
		      // source table is more recent
				if ($wasCleared) {
				    $out .= 'target was cleared -- must run all updates.';
				} elseif (is_null($dtDest)) {
				    $out .= 'target has never been updated -- run all updates.';
				} elseif ($dtSrce > $dtDest) {
				    $out .= 'source more recent -- run this update.';
				}
				assert('is_object($objProc->Mgr())');
				$arProcs[] = $objProc;
			    } else {
				$txtSrceUpd = is_null($dtSrce)?'never updated':('updated '.$dtSrce);
				$out .= 'target more current than source -- no update needed';
			    }
			}
			$out .= '</ul>';
		    } else {
			$out .= 'no sources found!';
		    }
		}
		$out .= '</ul>';

	  // run the procs, starting with the ones which clear the destination:

		$cntDone = 0;
		$arTarg = array();
		$cntProcs = count($arProcs);
		$out .= 'Running '.$cntProcs.' procedure'.Pluralize($cntProcs).'...';
		$out .= '<ul>';

		//$arExec = $this->Mgr()->ExecProcs($lstRunFirst);
		//$out .= $arExec['msgs'];
		//$cntDone += $arExec['cnt'];
		//$arExec = $this->Mgr()->ExecProcs($lstRunAfter,$arExec['targ']);
		//$out .= $arExec['msgs'];
		//$cntDone += $arExec['cnt'];
		//$arOut['targ'] = $arExec['targ'];

		$cntProcOk = $cntProcErr = 0;
		$arTarg = NULL;
		if (is_array($arProcs)) {
		    foreach ($arProcs as $objProc) {
			$out .= '<li>Running <b>'.$objProc->Value('Name').'</b>: ';
			$arExec = $objProc->Execute(TRUE);
			if ($arExec['ok']) {
			    $cntProcOk++;
			} else {
			    $cntProcErr++;
			}
			$out .= $arExec['msgs'];

			if ($cntProcOk > 0) {
			    foreach ($arExec['targ'] as $id => $row) {
				$arTarg[$id] = $row;
			    }
			}
		    }
		}

		$out .= '</ul>';

		$cntTarg = count($arTarg);
		if (is_array($arTarg)) {
		    $out .= 'Updating timestamps on '.$cntTarg.' target table'.Pluralize($cntTarg).':';
		    $out .= '<ul>';
		    $objTarg = $this->objMgr->Tables->SpawnItem();
		    foreach ($arTarg as $id => $row) {
			$objTarg->Values($row);
			$out .= '<li> '.$row['Name'].' (was: '.$row['WhenUpdated'].': ';
			$ok = $objTarg->UpdateTime($iCaller);
			if ($ok) {
			    $out .= 'ok';
			} else {
			    $out .= 'Error: '.$this->Engine()->getError();
			    $this->Engine()->ClearError();
			}
		    }
		    $out .= '</ul>';
		} else {
		    $out .= '<br>No targets updated.';
		}
	    } else {
		global $sql;
		$out .= '<br>No update paths found - SQL=['.$sql.']';
	    }

	    $arOut['msgs'] = $out;
	    $arOut['proc'] = $arProcs;
	    $arOut['proc.cnt.ok'] = $cntProcOk;
	    $arOut['proc.cnt.err'] = $cntProcErr;
	    $arOut['targ'] = $arTarg;
	} else {
	    $arOut['msgs'] = 'No data found.';
	    $arOut['proc'] = NULL;
	    $arOut['proc.cnt.ok'] = NULL;
	    $arOut['proc.cnt.err'] = NULL;
	    $arOut['targ'] = NULL;
	}
	return $arOut;
    }
    /*----
      ACTION: Update this table's timestamp
      RETURNS: Same as SetTime()
    */
    public function UpdateTime() {
	$arOut = $this->SetTime('NOW()');
	return $arOut;
    }
    /*----
      ACTION: Clears the table's timestamp (sets it to NULL)
	Same as UpdateTime, except 'NULL' instead of 'NOW()'.
      RETURNS: Same as SetTime()
    */
    public function ClearTime() {
	$arOut = $this->SetTime('NULL');
	return $arOut;
    }
    /*----
      PURPOSE: Internal function for setting the timestamp and logging the change
      RETURNS: array
	array[was]: timestamp before update
	array[sql]: SQL executed
      TO DO: Event logging needs to have a place to log what is actually being done.
    */
    protected function SetTime($iTime) {
	$objMgr = $this->Table->Mgr();
	$objLog = $objMgr->Log;
	$objEvent = $objLog->Start($this->KeyValue());

	$arOut['was'] = $this->Value('WhenUpdated');

	$arUpd = array(
	  'WhenUpdated' => $iTime,
	  );
	$this->Update($arUpd);

	global $sql;
	$arOut['sql'] = $sql;

	$objEvent->Finish();

	return $arOut;
    }
    /*----
      ACTION: Update the table's timestamp
    */
/*
    public function MarkUpdate() {
	$strName = $this->Name;
	assert('$strName != ""');
	assert('$this->ID');

	//$sql = 'UPDATE '.$strName.' SET WhenUpdated=NOW() WHERE ID='.$this->ID;;
	$arUpd = array(
	  'WhenUpdated'	=> 'NOW()'
	  );
	$this->Update($arUpd);
	$this->objDB->Exec($sql);
    }
*/
}
/* ============ *\
  Cache Procedures
\* ============ */
class clsCacheProcs extends clsMgrTable_single_key {
    public function __construct(clsCacheMgr $iMgr,$iName) {
	assert('is_string($iName); /* '.print_r($iName,TRUE).' */');
	parent::__construct($iMgr);
	  $this->Name($iName);
	  //$this->KeyName('ID');
	  $this->ClassSng('clsCacheProc');
    }
    public function DropDown($iName=NULL, $iDefault=NULL, array $iExclude=NULL) {
	$rsRows = $this->GetData();
	return $rsRows->DropDown($iName,$iDefault,$iExclude);
    }
}
class clsCacheProc extends clsMgrData_single_key {
// status
    public $sql;	// last SQL executed (or attempted)

// INFO methods

    /*----
      RETURNS: proc's name
      HISTORY:
	2011-12-22 added for command-line utility
    */
    public function Name() {
	return $this->Value('Name');
    }
    /*----
      RETURNS: proc's name and ID
      HISTORY:
	2011-12-24 added for command-line utility
    */
    public function NameFull() {
	return '<'.$this->KeyValue().'> '.$this->Name();
    }
    /*----
      RETURNS: TRUE if this proc entry is active;
	If FALSE, never execute it or calculate dependencies with it.
      HISTORY:
	2011-12-24 added for command-line utility
    */
    public function IsActive() {
	return $this->Value('isActive');
    }
    /*----
      NOTE: Although at present there aren't any procs which update more than one table, this *could* happen.
	So we return the destination table(s) as a dataset.
    */
    public function Flows($iFilt=NULL) {
	$sqlFilt = 'ID_Proc='.$this->KeyValue();
	if (!is_null($iFilt)) {
	    $sqlFilt = '('.$sqlFilt.') AND ('.$iFilt.')';
	}
	return $this->objMgr->Flow->GetData($sqlFilt);
    }
    /*----
      RETURNS: Recordset consisting only of Flows that show *sources* used by this procedure.
    */
    public function Sources() {
	$rsOut = $this->Flows('NOT doWrite');
	return $rsOut;
    }
    /*----
      RETURNS: Recordset consisting only of Flows for Tables that are *targets* of this Proc
      HISTORY:
	2011-12-24 created so self::Execute() can update target timestamps
    */
    public function Targets() {
	$rsOut = $this->Flows('doWrite');
	return $rsOut;
    }

// ACTION methods

    /*----
      INPUT:
	iWrite: if FALSE, does not actually write any data (or events)
      RETURNS: array
	array['ok']: TRUE if no errors were bumped into
	array['html']: descriptive text to display to administrative user, in HTML format (was ['msgs'])
	array['text']: same as ['html'] but in plaintext suitable for text-mode display
	array['targ']: array of target table(s) updated -- they need to have their timestamps updated too
	  [id] => object -- object is target table object
      HISTORY:
	2010-11-12 Fixed $arOut['targ']
	2011-12-22 added $iWrite parameter
    */
    public function Execute($iWrite) {
	$sql = 'CALL '.$this->Value('Name').'();';
	//$this->sql = 'CALL '.$sql.'();';
// create log entry and note start-time

	assert('$this->KeyValue()');
	$objMgr = $this->Mgr();
	assert('is_object($objMgr)');
	$objLog = $objMgr->Log;
	assert('is_object($objLog)');

	$htOut = $txOut = '';

	if ($iWrite) {
	    $objEvent = $objLog->Start($this->KeyValue(),__CLASS__.'.'.__METHOD__);
	    $db = $this->objDB;
	    $ok = $db->Exec($sql);
	    $cntRows = $db->RowsAffected();
	    $htOut = $txOut = ($cntRows.' row'.Pluralize($cntRows).' written');
	} else {
	    $ok = TRUE;
	    $htOut = $txOut = 'Debug mode - no data written';
	}

	if ($ok) {
	    $tblT = $this->objMgr->Tables;
	    // get list of all target tables
	    // -- if they've been written, update their timestamps too
	    $rsF = $this->Targets();
	    if ($rsF->HasRows()) {
		while ($rsF->NextRow()) {
		    $rcT = $rsF->TableObj();
		    if ($iWrite) {
			$rcT->UpdateTime(__METHOD__);
		    }
		    $arOut['targ'][$rcT->KeyValue()] = $rcT->Values();
		}
		if ($iWrite) {
		    $objEvent->Finish();
		}
	    } else {
		// if a Proc has no targets, it shouldn't ever get executed.
		$htOut .= '<br><b>Internal error</b>: no targets to stamp!';
		$txOut .= "\nINTERNAL ERROR: no targets to stamp!";
	    }
	} else {
	    $txtError = $this->objDB->ErrorText();
	    $htOut .= '<br>Error: '.$txtError;
	    $txOut .= "\nError: $txtError";
// TO DO: should log an error here, offscreen
	    $htOut .= '<br> - FAILED SQL: '.$sql;
	    $txOut .= "\n - FAILED SQL: $sql";
	}
	$arOut['html'] = $htOut;
	$arOut['text'] = $txOut;
	$arOut['ok'] = $ok;

	return $arOut;
    }
    public function DropDown($iName=NULL, $iDefault=NULL, array $iExclude=NULL) {
	$strName = is_null($iName)?($this->Table->ActionKey()):$iName;	// control name defaults to action key

	if ($this->HasRows()) {
	    $out = "\n<SELECT NAME=$strName>";
	    while ($this->NextRow()) {
		$id = $this->KeyValue();
		    $htShow = htmlspecialchars($this->Value('Name'));
		    $htSel = ($id == $iDefault)?' selected':'';
		    if (isset($iExclude[$id])) {
			$htSel .= ' disabled';
		    }
		    $out .= "\n<option value=$id$htSel>$htShow</option>";
	    }
	    $out .= "\n</select>";
	    return $out;
	} else {
	    return NULL;
	}
    }
}/* ============ *\
 CACHE FLOW classes
\* ============ */
class clsCacheFlows extends clsMgrTable {
    protected $objIndex;	// indexing helper object

    public function __construct(clsCacheMgr $iMgr,$iName) {
	assert('is_string($iName); /* '.print_r($iName,TRUE).' */');
	$objIdx = new clsIndexer_Table_multi_key($this);
	$objIdx->KeyNames(array('ID_Proc','ID_Table'));
	parent::__construct($iMgr,$objIdx);
	  $this->Name($iName);
	  $this->ClassSng('clsCacheFlow');
	  //$this->KeyName(array('ID_Proc','ID_Table',));
    }

// PARENTAL OVERRIDES

    public function GetData($iWhere=NULL,$iClass=NULL,$iSort=NULL) {
	$rc = parent::GetData($iWhere,$iClass,$iSort);
	$rc->Mgr($this->objMgr);
	return $rc;
    }

// INFORMATIONAL

    /*----
      RETURNS: array of procs that target the given table
      INPUT:
	iTable: ID of table
      HISTORY:
	2011-12-22 created for command-line utility
	2011-12-24 adapting from SrceProcs_forTable() (not needed) to TargProcs_forTable()
    */
    public function TargProcs_forTable($iTable) {
	$sqlFilt = '(NOT doWrite) AND (ID_Table='.$iTable.')';
	$rcF = $this->GetData($sqlFilt);
	if ($rcF->HasRows()) {
	    while ($rcF->NextRow()) {
		$rcP = $rcF->ProcObj();
		$ar[$rcP->KeyValue()] = $rcP;
	    }
	} else {
	    $ar = NULL;
	}
	return $ar;
    }
    /*----
      NOTE: this should probably be renamed
    */
    public function Data_forProc($iProc) {
	$recs = $this->GetData('ID_Proc='.$iProc);
	return $recs;
    }
    /*----
      RETURNS: dataset of clsCacheTable objects needed as sources for the given destination table
    */
    public function GetData_forDest($iDest) {
	$objOut = $this->DataSQL('SELECT * FROM qryCache_Flow_Procs WHERE (ID_Table='.$iDest.') AND doWrite');
	$objOut->Mgr($this->objMgr);
	return $objOut;
    }
    public function Add($iProc,$iTable,$iWrite,$iNotes) {
	$arChg = array(
	  'doWrite'	=> SQLValue($iWrite),
	  'Notes'	=> SQLValue($iNotes)
	  );

	$recs = $this->GetData('(ID_Proc='.$iProc.') AND (ID_Table='.$iTable.')');
	if ($recs->HasRows()) {
	    $recs->Update($arChg);
	} else {
	    $arChg['ID_Proc'] = $iProc;
	    $arChg['ID_Table'] = $iTable;
	    $this->Insert($arChg);
	}
    }
}
class clsCacheFlow extends clsMgrData {
// object cache
    protected $objProc;		// stored procedure which updates destination from source
    protected $objTable;	// relevant table
    protected $objIndex;	// indexing helper object
/*
    protected $objSrce;	// source table
    protected $objDest;	// destination table (contains at least some cached data)
*/

// INDEXING - BOILERPLATE FUNCTIONS
/*    public function Indexer() {
	if (!is_object($this->objIndex)) {
	    $this->objIndex = new clsIndexer_Recs($this,$this->Table->Indexer());
	}
	return $this->objIndex;
    }
*/
/*
WHAT USES THIS?
    public function KeyValue($iVal=NULL) {
	return $this->Indexer()->KeyValue($iVal);
    }
*/
// FIELDS
    public function ProcObj() {
	$idProc = $this->Value('ID_Proc');
	if (is_object($this->objProc)) {
	    $doLoad = ($this->objProc != $idProc);
	} else {
	    $doLoad = TRUE;
	}
	if ($doLoad) {
	    assert('is_object($this->objMgr)');
	    $this->objProc = $this->objMgr->Procs->GetItem($idProc);
	}
	return $this->objProc;
    }
    public function TableObj() {
	$idTbl = $this->Value('ID_Table');
	if (is_object($this->objTable)) {
	    $doLoad = ($this->objTable != $idTbl);
	} else {
	    $doLoad = TRUE;
	}
	if ($doLoad) {
	    assert('is_object($this->objMgr)');
	    $this->objTable = $this->objMgr->Tables->GetItem($idTbl);
	}
	return $this->objTable;
    }

    /*----
      PURPOSE: Generates a simple map showing sources and targets for the flow(s)
	in the current recordset. Does not distinguish between flows/procs; for a more
	detailed and complete map, use FullMap().
      RETURNS: array-structure of table dependencies
	'srce'	= array of table objects that are sources for the flow(s) in this recordset
	'dest'	= array of table objects that are targets for the flow(s) in this recordset
      HISTORY:
	2011-12-21 separated out so it can be reused
    */
    public function PoolMap() {
	$tblTbls = $this->Mgr()->Tables;
	$arDone = array();
	if ($this->HasRows()) {
	    $arTarg = NULL;
	    $arSrce = NULL;
	    while ($this->NextRow()) {
		$idTbl = $this->Value('ID_Table');
		if (!array_key_exists($idTbl,$arDone)) {
		    $objTbl = $tblTbls->GetItem($idTbl);
		    $arDone[$idTbl] = $objTbl;
		    if ($this->Value('doWrite')) {
			// the proc writes to these tables
			$arTarg[$idTbl] = $objTbl;
		    } else {
			// the proc reads from these tables
			$arSrce[$idTbl] = $objTbl;
		    }
		}
	    }
	    $arOut['targ'] = $arTarg;
	    $arOut['srce'] = $arSrce;
	    return $arOut;
	} else {
	    return NULL;
	}
    }
    /*----
      PURPOSE: Generates a complete map showing sources and targets
	for each proc in the current flow recordset.
      RETURNS: array-structure
	'tbl' = array of table objects, each with the following arrays:
	  arPSrce = array of procs that write to this table
	  arTSrce = array of tables this table sources from
	  arPTarg = array of procs this table targets
	  arTTarg = array of tables this table targets
	'prc' = array of proc objects, each with the following arrays:
	  arTSrce = array of tables this proc sources from
	  arTTarg = array of tables this proc targets
	'run' = array of proc objects that need to be run
	  because at least one source is newer than the target
	  This is a subset of the 'prc' list (same format).
      HISTORY:
	2011-12-22 created for command-line cache update utility
    */
    public function FullMap() {
	$tblTbls = $this->Mgr()->Tables;
	$tblPrcs = $this->Mgr()->Procs;
	$arTbl = array();
	$arPrc = array();

	// 1. Build the table-proc map
//	WriteLn('Building arrays:');
	if ($this->HasRows()) {
	    while ($this->NextRow()) {

		// get data for the flow's table
		$idTbl = $this->Value('ID_Table');
		// update the table list
		if (!array_key_exists($idTbl,$arTbl)) {
		    $objTbl = $tblTbls->GetItem($idTbl);
		    $arTbl[$idTbl] = $objTbl;
		} else {
		    $objTbl = $arTbl[$idTbl];
		}

		if ($objTbl->IsActive()) {
		    $arTblActive[$idTbl] = $objTbl;

		    // update the proc list
		    $idPrc = $this->Value('ID_Proc');
		    if (!array_key_exists($idPrc,$arPrc)) {
			$objPrc = $tblPrcs->GetItem($idPrc);
			$arPrc[$idPrc] = $objPrc;
		    } else {
			$objPrc = $arPrc[$idPrc];
		    }

		    if ($objPrc->IsActive()) {
			$arPrcActive[$idPrc] = $objPrc;

			$doWrite = $this->Value('doWrite');
			if ($doWrite) {
			    // the proc writes to this table
			    $objTbl->arPSrce[$idPrc] = $objPrc;	// the proc is a source for this table
			    $objPrc->arTTarg[$idTbl] = $objTbl;	// the table is a target for this proc
			} else {
			    // the proc reads from this table
			    $objTbl->arPTarg[$idPrc] = $objPrc;	// the proc is a target for this table
			    $objPrc->arTSrce[$idTbl] = $objTbl;	// the table is a source for this proc
			}
			$txtArrow = $doWrite?'=>':'<=';
//			WriteLn('PROC ('.$idPrc.') '.$objPrc->Name().' '.$txtArrow.' TABLE ('.$idTbl.') '.$objTbl->Name());
		    }
		}
	    }

	    // replace complete lists with lists of only active objects
	    $arTbl = $arTblActive;
	    $arPrc = $arPrcActive;
	    // save memory -- not needed yet, but if lists ever get really large...
	    unset($arTblActive);
	    unset($arPrcActive);

	    // a little status output for the user

	    Write('TABLES:');
	    foreach ($arTbl as $id => $obj) {
		Write(' '.$obj->Name());
	    }
	    WriteLn();

	    Write('PROCS:');
	    foreach ($arPrc as $id => $obj) {
		Write(' '.$obj->Name());
	    }
	    WriteLn();
	}

	// 2. Build the table-table map
	//	Copy each proc's target array to each source for that proc, and vice-versa
	foreach ($arPrc as $idPrc => $objPrc) {
	    $arSrce = Nz($objPrc->arTSrce,array());
	    $arTarg = Nz($objPrc->arTTarg,array());
	    foreach ($arSrce as $idTbl => $objTbl) {
		$objTbl->TTarg = ArrayJoin(Nz($objTbl->TTarg,array()),$arTarg);
	    }
	    foreach ($arTarg as $idTbl => $objTbl) {
		$objTbl->TSrce = ArrayJoin(Nz($objTbl->TSrce,array()),$arSrce);
	    }
	}

	// 3. Figure out what procs need to be run due to date discrepancies
	$arToRun = NULL;
	foreach ($arPrc as $idPrc => $objPrc) {
	    $arSrce = $objPrc->arTSrce;
	    $arTarg = $objPrc->arTTarg;

	    if (!is_array($arSrce)) {
		WriteLn('NOTE: '.$objPrc->Name().' has no source array.');
	    }

	    $dtOldest = NULL;
	    if (is_array($arTarg)) {
		// get date of the oldest target
		foreach ($arTarg as $idTbl => $objTbl) {
		    $dtTarg = $objTbl->WhenUpdated();
		    if (is_null($dtOldest) || ($dtTarg < $dtOldest)) {
			$dtOldest = $dtTarg;
		    }
		}
	    } else {
		WriteLn('NOTE: '.$objPrc->Name().' has no target array.');
	    }

	    if (is_array($arSrce)) {
		foreach ($arSrce as $idTbl => $objSrce) {
		    $dtSrce = $objSrce->WhenUpdated();
		    if ($dtSrce > $dtOldest) {
			$arToRun[$idPrc] = $objPrc;
			// we could escape from the loop at this point... what's the command for that again?
		    }
		}
	    } else {
		WriteLn('NOTE: no sources found.');
	    }
	}

	$arOut['prc'] = $arPrc;
	$arOut['tbl'] = $arTbl;
	$arOut['run'] = $arToRun;
	return $arOut;
    }
    /*----
      RETURNS: array of tables that have no sources within the cache system
      INPUT:
	iTables: array of all active Table objects, with arTSrce arrays built
      NOTE: This was originally elsewhere, commented out; would need to be rewritten.
    */
/*
    protected function FindRoots(array $iTables) {
	if (is_null($iNode)) {
	    $arSrce = $this->arMap['srce'];
	    $arOut = $this->FindFlowSources($arSrce);
	} else {
	    $id = $iNode->KeyValue();
//	    Write('ID='.$id.': ');
	    if (array_key_exists($id,$this->arCkd)) {
//		WriteLn('duplicate');
		return NULL;	// possible recursion - this node has already been checked
	    } else {
//		Write('checking... ');
		$this->arCkd[$id] = $iNode->Row;
		$rcFlows = $this->Mgr()->Sources($id);
		if ($rcFlows->HasRows()) {
//		    WriteLn('sources:');
		    $arOut = array();
		    while ($rcFlows->NextRow()) {
			// get sources for this flow
			$arMapSub = $rcFlows->PoolMap();
			$arSrce = $arMapSub['srce'];
			if (is_null($arSrce)) {
//			    WriteLn(' - no sources');
			} else {
			    $ar = $this->FindFlowSources($arSrce);
			    // add to array for this level
			    foreach ($ar as $id => $row) {
//				Write(' - '.$row['Name']);
				if (array_key_exists($id,$arOut)) {
//				    WriteLn(' (duplicate)');
				} else {
				    if ($row['isActive']) {
					$arOut[$id] = $row;
//					WriteLn(' - added');
				    } else {
//					WriteLn(' - inactive');
				    }
				}
			    }
			}
		    }
		} else {
//		    WriteLn('ROOT');
		    // this is a root
		    $arOut[$id] = $iNode->Row;
		}
	    }
	}
	return $arOut;
    }
*/
}

/* =====
  CLASSES: cache event logging
*/
class clsCacheEvents extends clsMgrTable_single_key {
    public function __construct(clsCacheMgr $iMgr,$iName) {
	assert('is_string($iName); /* '.print_r($iName,TRUE).' */');
	parent::__construct($iMgr);
	  $this->Name($iName);
	  //$this->KeyName('ID');
	  $this->ClassSng('clsCacheEvent');
    }
    public function Start($iProc) {

	if (is_numeric($iProc)) {

	    $sAddr = clsArray::Nz($_SERVER,'REMOTE_ADDR','N/A');	// N/A = probably commandline
	    $db = $this->Engine();
	    
	    $sUser = clsApp::Me()->User()->UserName();

	    $arEv = array(
	      'WhenStarted'	=> 'NOW()',
	      'ID_Proc'		=> $iProc,
	      'Caller'		=> 'NULL',
	      'WhoAdmin'	=> $db->SanitizeAndQuote($sUser),
	      'WhoSystem'	=> 'NULL',
	      'WhoNetwork'	=> $db->SanitizeAndQuote($sAddr)
	      );
	    $ok = $this->Insert($arEv);
	    if (!$ok) {
		global $sql;
		echo 'SQL error recording event: '.$db->getError();
		echo '<br> - SQL:'.$sql;
	    }

	    $idLog = $db->NewID();
	    $rcEntry = $this->GetItem($idLog);
	    $rcEntry->Mgr($this->objMgr);
	} else {
	    LogError(__METHOD__.': no Proc given');
	    $rcEntry = NULL;
	}
	return $rcEntry;
    }
}
class clsCacheEvent extends clsMgrData_single_key {
    public function Finish() {
	$objTbls = $this->objMgr->Tables;
	assert('is_object($objTbls)');

	//return $objTbls->Update('WhenFinished=NOW()','ID='.$this->ID);
	$arEv = array(
	  'WhenFinished'	=> 'NOW()'
	  );
	$this->Update($arEv);
    }
}
