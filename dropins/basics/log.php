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
    2017-02-07 adapting for eventPlex
    2017-03-28 more y2017 remediation
*/
/*::::
  CLASS: clsAdminSyslog
  PURPOSE: Admin interface to system logs
  NOTE that this descends from the event *table* class, not the event *helper* class.
*/
class fctAdminEventPlex extends fctEventPlex_standard implements fiEventAware, fiLinkableTable, fiEventTable {
    use ftLinkableTable;
    use ftExecutableTwig;
  
    // ++ SETUP ++ //

    // UNSTUB
    protected function InitVars() {
	parent::InitVars();
	$this->rsType = NULL;
	$this->MaxLines(100);			// TODO: make this configurable
    }
    // OVERRIDE
    protected function SingularName() {
	return 'fcrAdminEventPlect';
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
    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle('Ferreteria Event Log');
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    public function Render() {
	return $this->AdminPage();
	//return $this->EventListing();
    }
    /*----
      PURPOSE: execution method called by dropin menu
    */ /*
    public function MenuExec() {
	return $this->AdminPage();
    } */

    // -- EVENTS -- //
    // ++ RECORDS ++ //
    
    protected function SummaryRecord() {
	$sql = 'SELECT COUNT(ID) AS RowCount, MIN(WhenStart) as DateOldest, MAX(WhenStart) AS DateNewest FROM event GROUP BY ID>0';
	$rc = $this->FetchRecords($sql);
	$rc->NextRow();
	return $rc;
    }
    
    // -- RECORDS -- //
    // ++ ADMIN UI ++ //

    /*----
      HISTORY:
	2017-01-15 adapted from db.v1; moved from fctEvents to fctAdminEventPlex (was fctEvents_admin)
      REQUIRED BY fiEventTable
      TODO: This should probably limit to, say, the most recent 100 records by default.
    */
    public function EventListing() {
	$rs = $this->SelectRecords();
	return $rs->AdminRows();
    }
    protected function AdminPage() {
	$rc = $this->SummaryRecord();
	$nRows = $rc->GetFieldValue('RowCount');
	$htData = NULL;
	if ($nRows == 0) {
	    $sText = 'No events logged yet.';
	} elseif ($nRows < 100) {
	    $sText = $nRows.' event'.fcString::Pluralize($nRows,' has','s have').' been logged:';
	    $htData = $this->EventListing();
	} else {
	    $sText = $nRows.' event'.fcString::Pluralize($nRows)
	      .' logged from '.$rc->GetFieldValue('DateOldest')
	      .' to '.$rc->GetFieldValue('DateNewest')
	      ;	
	}
	$out = "\n<div class=content>\n$sText\n</div>"
	  .$htData
	  ;
	return $out;
__END__;
    }

    // -- ADMIN UI -- //
}
class fcrAdminEventPlect extends fcrEventPlect_standard implements fiLinkableRecord, fiEventAware, fiEditableRecord  {
    use ftLinkableRecord;
    use ftShowableRecord;
    use ftSaveableRecord;
    use ftExecutableTwig;

    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$sTitle = 'ev#'.$this->GetKeyValue();
	$htTitle = 'Ferreteria Event Record #'.$this->GetKeyValue();
	
	$oPage = fcApp::Me()->GetPageObject();
	//$oPage->SetPageTitle($sTitle);
	$oPage->SetBrowserTitle($sTitle);
	$oPage->SetContentTitle($htTitle);
    }
    public function Render() {
	return $this->AdminPage();
    }

    // -- EVENTS -- //
    // ++ FIELD CALCULATIONS ++ //
    
    // REQUIRED by fiEditableRecord
    public function GetKeyValue() {
	return (string)$this->GetFieldValue('ID');
    }
    // REQUIRED by fiEditableRecord
    public function SetKeyValue($id) {
	$this->SetFieldValue('ID',$id);
    }
    // REQUIRED by fiEditableRecord
    public function IsNew() {
	return is_null($this->GetKeyValue());
    }
    protected function SessionLink() {
	if ($this->HasSession()) {
	    $rc = $this->SessionRecord();
	    return $rc->SelfLink();
	} else {
	    return '-';
	}
    }
    protected function AccountLink() {
	if ($this->HasAccount()) {
	    $rc = $this->AccountRecord();
	    $out = $rc->SelfLink_name();
	} else {
	    $out = '-';
	}
	return $out;
    }
    static protected function StashString_toDetails($sStash) {
	$arStash = unserialize($sStash);
	if (is_array($arStash)) {
	    $nStash = count($arStash);
	    $sPlur = $nStash.' item'.fcString::Pluralize($nStash);
	    $sStash = fcArrayText::Render($arStash);
	    $htStash = fcHTML::FormatString_forTag($sStash);
	} else {
	    $htStash = 'no data';
	    $sPlur = '-';
	}
	$out = "<span title='$htStash'>$sPlur</span>";
	return $out;
    }
    protected function BaseStashDetails() {
	return self::StashString_toDetails($this->BaseStashString());
    }

    // -- FIELD CALCULATIONS -- //
    // ++ SUBEVENT FIELDS ++ //
    
    protected function DoneStashDetails() {
	return self::StashString_toDetails($this->DoneStashString());
    }
    
    // -- SUBEVENT FIELDS -- //
    // ++ CLASSES ++ //
    
    protected function SessionsClass() {
	return KS_CLASS_ADMIN_USER_SESSIONS;
    }
    protected function AccountsClass() {
	return KS_CLASS_ADMIN_USER_ACCOUNTS;
    }
    
    // -- CLASSES -- //
    // ++ TABLES ++ //
    
    protected function SessionTable($id=NULL) {
	return $this->GetConnection()->MakeTableWrapper($this->SessionsClass(),$id);
    }
    protected function AccountTable($id=NULL) {
	return $this->GetConnection()->MakeTableWrapper($this->AccountsClass(),$id);
    }
    
    // -- TABLES -- //
    // ++ RECORDS ++ //
    
    protected function SessionRecord() {
	return $this->SessionTable($this->SessionID());
    }
    protected function AccountRecord() {
	return $this->AccountTable($this->AccountID());
    }
    
    // -- RECORDS -- //
    // ++ WEB ADMIN UI ++ //

    // CALLBACK from ftShowableRecord
    protected function AdminRows_settings_columns() {
	return array(
	  'ID'		=>	'ID',
	  'e_WhenStart'	=>	'Start',
	  'e_ID_Session'	=>	'Session',
	  'e_ID_Account'	=>	'User',
	  'e_TypeCode'	=> 	'Code',
	  'e_Descrip'	=>	'Description',
	  'e_Stash'	=> 	'Data',
	  );
    }
    /*----
      HISTORY:
	2017-03-15 rewriting from scratch for syslogv2
    */
    private $sDateLast = NULL;
    private $isOdd = FALSE;
    protected function AdminRows_row() {
	$cssClass = $this->isOdd?'odd':'even';
	$this->isOdd = !$this->isOdd;

	$sWhenStart = $this->WhenStarted();
	$dtWhenStart = strtotime($sWhenStart);
	$sDateStart = date('Y-m-d',$dtWhenStart);
	$sTimeStart = date('H:i:s',$dtWhenStart);

	$htID = $this->SelfLink();
	
	$out = NULL;
	if ($sDateStart != $this->sDateLast) {
	    $this->sDateLast = $sDateStart;
	    $out .= <<<__END__
  <tr>
    <td colspan=5><span class=section-header-year>$sDateStart</span></td>
  </tr>
__END__;
	}
	
	$htSession = $this->SessionLink();
	$htAccount = $this->AccountLink();
	$sTypeCode = $this->TypeCode();
	$sDescrip = $this->BaseDescriptiveText();
	$htStash = $this->BaseStashDetails();

	// check for sub-event data
	
	if ($this->HasDoneFields()) {
	    $sTimeDone = $this->WhenFinished();
	    $sDoneCode = $this->DoneStateCode();
	    $sDoneDescr = $this->DoneDescription();
	    $sDoneStash = $this->DoneStashDetails();
	    $htDone = "<td align=right><b>Done</b></td><td>@$sTimeDone</td><td>$sDoneCode</td><td>$sDoneDescr</td><td>$sDoneStash</td>";
	} else {
	    $htDone = NULL;
	}
	
	if ($this->HasNoteField()) {
	    $sNotes = $this->NoteString();
	    $htNotes = "<td><b>Notes</b>: $sNotes</td>";
	} else {
	    $htNotes = NULL;
	}
	
	// render the row
	
	$out .= <<<__END__
  <tr class="$cssClass">
    <td>$htID</td>
    <td>$sTimeStart</td>
    <td>$htSession</td>
    <td>$htAccount</td>
    <td>$sTypeCode</td>
    <td>$sDescrip</td>
    <td>$htStash</td>
    $htDone
    $htNotes
  </tr>
__END__;
	return $out;
    }

    // -- rows
    // ++ page

    // NOTE: For now, at least, we're not going to support editing of events. History should not be editable.
    public function AdminPage() {
	$frmEdit = $this->GetPageForm_forMain();
	$frmEdit->LoadRecord();

	$oTplt = $frmEdit->GetTemplate();
	$arCtrls = $frmEdit->RenderControls(FALSE);
	$arCtrls['ID'] = $this->SelfLink();
	$arCtrls['ID_Session'] = $this->SessionLink();
	$arCtrls['ID_Acct'] = $this->AccountLink();
	$arCtrls['Stash'] = $this->BaseStashCompact();
	$arCtrls['section:done'] = $this->Admin_DoneRecord();
	$arCtrls['section:table'] = $this->Admin_TableRecord();
	$arCtrls['section:note'] = $this->Admin_NoteRecord();
 	$oTplt->SetVariableValues($arCtrls);
	$out = $oTplt->Render();
	return $out;
    }
    
    // ++ subtables
    
    protected function Admin_DoneRecord() {
	if ($this->HasDoneFields()) {
	    $frmEdit = $this->GetForm_forDoneRecord();
	    $frmEdit->LoadRecord();

	    $oTplt = $frmEdit->GetTemplate();
	    $arCtrls = $frmEdit->RenderControls(FALSE);
	    $oTplt->SetVariableValues($arCtrls);
	    $out = $oTplt->Render();
	    return $out;
	} else {
	    return NULL;
	}
    }
    protected function Admin_TableRecord() {
	if ($this->HasTableFields()) {
	    $frmEdit = $this->GetForm_forTableRecord();
	    $frmEdit->LoadRecord();

	    $oTplt = $frmEdit->GetTemplate();
	    $arCtrls = $frmEdit->RenderControls(FALSE);
	    $oTplt->SetVariableValues($arCtrls);
	    $out = $oTplt->Render();
	    return $out;
	} else {
	    return NULL;
	}
    }
    protected function Admin_NoteRecord() {
	if ($this->HasNoteField()) {
	    $frmEdit = $this->GetForm_forNoteRecord();
	    $frmEdit->LoadRecord();
	    
	    $oTplt = $frmEdit->GetTemplate();
	    $arCtrls = $frmEdit->RenderControls(FALSE);
	    $oTplt->SetVariableValues($arCtrls);
	    $out = $oTplt->Render();
	    return $out;
	} else {
	    return NULL;
	}
    }

    // ++ create form objects
    
    private $frmPage=NULL;
    private function GetPageForm_forMain() {
	if (is_null($this->frmPage)) {
	    $oForm = new fcForm_EventPlex($this);
	    $oForm->BuildControls();
	    $this->frmPage = $oForm;
	}
	return $this->frmPage;
    }
    private $frmForDone=NULL;
    protected function GetForm_forDoneRecord() {
	if (is_null($this->frmForDone)) {
	    $oForm = new fcForm_EventPlex_Done($this);
	    $oForm->BuildControls();
	    $this->frmForDone = $oForm;
	}
	return $this->frmForDone;
    }
    private $frmForTable=NULL;
    protected function GetForm_forTableRecord() {
	if (is_null($this->frmForTable)) {
	    $oForm = new fcForm_EventPlex_Table($this);
	    $oForm->BuildControls();
	    $this->frmForTable = $oForm;
	}
	return $this->frmForTable;
    }
    private $frmForNote=NULL;
    protected function GetForm_forNoteRecord() {
	if (is_null($this->frmForNote)) {
	    $oForm = new fcForm_EventPlex_Note($this);
	    $oForm->BuildControls();
	    $this->frmForNote = $oForm;
	}
	return $this->frmForNote;
    }
}
