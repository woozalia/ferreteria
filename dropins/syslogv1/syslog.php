<?php
/*
  FILE: admin.events.php -- handling of MediaWiki-oriented event logging
    Originally written to work with FinanceFerret, but should be compatible with standard event tables.
    Any app-specific code should be moved out into descendant classes.
  HISTORY:
    2010-04-06 clsAdminTable, clsAdminData, clsAdminData_Logged, clsAdminEvents, clsAdminEvent written (in menu.php)
    2010-10-25 clsAdminEvents, clsAdminEvent, clsAdminData_Logged extracted from menu.php
    2013-12-07 rewriting as drop-in module
    2014-02-05 renaming *Syslog to *SysEvents
    2017-01-07 updating for db.v2 revisions
*/
/*::::
  CLASS: clsAdminSyslog
  PURPOSE: Admin interface to system logs
  NOTE that this descends from the event *table* class, not the event *helper* class.
*/
class fctEvents_admin extends fctEvents {
    use ftLinkableTable;
  
    // ++ SETUP ++ //

    // UNSTUB
    protected function InitVars() {
	$this->rsType = NULL;
	$this->MaxLines(100);			// TODO: make this configurable
    }
    // OVERRIDE
    protected function SingularName() {
	return 'VC_SysEvent';
    }
    // CEMENT
    public function GetActionKey() {
	return KS_ACTION_SYSTEM_LOG;
    }
    // NEW
    private $nMaxRows;
    public function MaxLines($nMax=NULL) {
	if (!is_null($nMax)) {
	    $this->nMaxRows = $nMax;
	}
	return $this->nMaxRows;
    }

    // -- SETUP -- //
    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec() {
	return $this->AdminPage();
    }

    // -- DROP-IN API -- //
    // ++ ADMIN UI ++ //

    /*----
      HISTORY:
	2017-01-15 adapted from db.v1; moved from fctEvents to fctEvents_admin
    */
    public function EventListing() {
	$rs = $this->EventRecords();
	$oHdr = new fcSectionHeader('System Events');
	return 
	  $oHdr->Render()
	  .$rs->AdminRows(TRUE)
	  ;
    }
    protected function AdminPage() {
	return $this->EventListing();	// 2017-02-06 see if that works
    
	// 2017-01-15 WAIT -- this may be a duplicate of EventListing() or AdminRows().
	$rsEv = $this->EventRecords();
	if ($rsEv->HasRows()) {
	    $out = $rsEv->AdminRows(TRUE);
	} else {
	    $out = 'No events found here.';
	}
	return $out;
    }

    // -- ADMIN UI -- //
}
class VC_SysEvent extends fcrEvent {
    use ftLinkableRecord;
    use ftShowableRecord;

    // ++ WEB ADMIN UI ++ //

    /*----
      INPUT:
	$doGeneral: TRUE = show Mod and Index columns
    */
    protected static function AdminRowHeader($doGeneral) {
	$out = "\n<table class=listing><tr><th>ID</th>";
	if ($doGeneral) {
	    $out .= '<th>ModType</th><th>ModIndex</th>';
	}
	$out .= '<th>Start</th><th>Finish</th><th>Who/How</th><th>Where</th></tr>';
	return $out;
    }
    static $sUnknownField = '<span style="color: #888888;">?</span>';
    static $sDateLast;
    /*----
      ASSUMES: there are rows (caller should check this)
      INPUT:
	$doGeneral: TRUE = show Mod and Index columns
      HISTORY:
	2011-03-24 added call to UseHTML()
	  Data is apparently too long to show all at once now; something Clever is needed.
	2013-12-08 adapting from clsAdminEvent (MW event admin class)
    */
    public function AdminRows($doGeneral) {
	if ($this->HasRows()) {
	    //$htUnknown = '<span style="color: #888888;">?</span>';
	    $out = self::AdminRowHeader($doGeneral);

	    $isOdd = TRUE;
	    $strDateLast = NULL;
	    $nRows = 0;
	    $nMax = $this->GetTableWrapper()->MaxLines();
	    $isOver = FALSE;
	    self::$sDateLast = NULL;
	    while ($this->NextRow()) {
		$nRows++;
		if ($nRows > $nMax) {
		    $isOver = TRUE;
		    break;
		}

		$cssClass = $isOdd?'odd':'even';
		$isOdd = !$isOdd;

		$out .= $this->AdminRow($doGeneral,$cssClass);
	    }
	    $out .= "\n</table>";
	    if ($isOver) {
		$nTotal = $this->RowCount();
		$out .= "Showing only $nMax of $nTotal rows.<br>";
	    }
	} else {
	    $out = 'No events found. ';
	}
	$out .= '<br><span class="line-stats"><b>SQL</b>: '.$this->sql.'</span>';

	return $out;
    }
    protected function AdminRow($doGeneral,$cssClass) {
	$row = $this->GetFieldValues();

	$sSysUser	= $row['WhoAdmin'];	// aren't these two...
	$sAppUser	= $row['WhoSystem'];	// ...reversed?
	$sMachine	= $row['WhoNetwork'];
	$htSysUser	= is_null($sSysUser)?(self::$sUnknownField):$sSysUser;
	$htMachine	= is_null($sMachine)?(self::$sUnknownField):$sMachine;
	$htAppUser	= is_null($sAppUser)?(self::$sUnknownField):$sAppUser;

	$ftDescr	= $row['Descr'];
	$ftDescrFin	= $row['DescrFin'];
	$strNotes	= $row['Notes'];
	$id		= $this->SelfLink();
	$strWhenSt	= $row['WhenStarted'];
	$strWhenFi	= $row['WhenFinished'];
	$strWhere	= $row['EvWhere'];
	$htWho		= $htAppUser.'/'.$htSysUser.'@'.$htMachine;
	$strParams	= $row['Params'];

	$dtWhenSt	= strtotime($strWhenSt);
	$dtWhenFi	= strtotime($strWhenFi);
	$strDate	= date('Y-m-d',empty($dtWhenSt)?$dtWhenFi:$dtWhenSt);
	$strTimeSt = empty($dtWhenSt)?'':date('H:i',$dtWhenSt);
	$strTimeFi = empty($dtWhenFi)?'':date('H:i',$dtWhenFi);

	$out = NULL;

	if ($strDate != self::$sDateLast) {
	  // date header
	    self::$sDateLast = $strDate;
	    $out .= <<<__END__
  <tr class=date-header>
    <td colspan=5><b>$strDate</b></td>
  </tr>
__END__;
	}

	if ($doGeneral) {
	    // data-record columns
	    $sModType = $row['ModType'];
	    $sModIndex = $row['ModIndex'];
	    $htDataCols = <<<__END__
    <td>$sModType</td>
    <td>$sModIndex</td>
__END__;
	} else {
	    $htDataCols = NULL;
	}

	// first 2 columns
	$out .= <<<__END__
  <tr class="$cssClass">
    <td>$id</td>$htDataCols
    <td>$strTimeSt</td>
    <td>$strTimeFi</td>
    <td>$htWho</td>
    <td><small>$strWhere</small></td>
  </tr>
__END__;

	if (!empty($ftDescr)) {
	    $out .= <<<__END__
  <tr class="$cssClass">
    <td></td>
    <td colspan=5><b>Mission</b>: $ftDescr</td>
  </tr>
__END__;
	}

	if (!empty($ftDescrFin)) {
	    $out .= <<<__END__
  <tr class="$cssClass">
    <td></td>
    <td colspan=5><b>Results</b>: $ftDescrFin</td>
  </tr>
__END__;

	}

	if (!empty($strParams)) {
	    $out .= <<<__END__
  <tr class="$cssClass">
    <td></td>
    <td colspan=5><b>Params</b>: $strParams</td>
  </tr>
__END__;
	}

	if (!empty($strNotes)) {
	    $out .= <<<__END__
  <tr class="$cssClass">
    <td></td>
    <td colspan=5><b>Notes</b>: $strNotes</td>
  </tr>
__END__;
	}

	return $out;
    }
}
/*
class VC_Syslog_RecordSet_helper {
    private $tLog, $rsData;

    public function __construct(clsSysEvents $tLog, clsDataSet $rsData) {
	$this->tLog = $tLog;
	$this->rsData = $rsData;
    }

    // ++ BOILERPLATE API ++ //

    public function EventListing() {
	$rs = $this->EventRecords();
	return $rs->AdminList();
    }
    public function StartEvent(array $iArgs) {
    }
    public function FinishEvent(array $iArgs=NULL) {
    }

    // -- BOILERPLATE API -- //
    // ++ DATA OBJECT ACCESS ++ //

    protected function LogTable() {
	return $this->tLog;
    }
    protected function DataRecords() {
	return $this->rsData;
    }

    // -- DATA OBJECT ACCESS -- //
    // ++ BUSINESS LOGIC ++ //
    protected function EventRecords() {
	$sTblAct = $this->DataRecords()->Table->ActionKey();
	$idRecord = $this->DataRecords()->KeyValue();
	$sqlFilt = '(ModType="'.$sTblAct.'") AND (ModIndex='.$idRecord.')';
	$rs = $this->LogTable()->GetData($sqlFilt,'WhenStarted,WhenFinished');
	return $rs;
    }
}
*/