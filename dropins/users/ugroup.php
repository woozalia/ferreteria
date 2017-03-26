<?php
/*
  PURPOSE: user access control classes: security groups
  HISTORY:
    2013-12-19 started
    2017-01-04 This is going to need some updating to work with the Ferreteria revisions.
*/
class fctAdminUserGroups extends fctUserGroups implements fiLinkableTable {
    use ftLinkableTable;

    // OVERRIDE
    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_GROUP;
    }
    // CEMENT
    public function GetActionKey() {
	return KS_ACTION_USER_GROUP;
    }

    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec() {
	return $this->AdminListing();
    }

    // -- DROP-IN API -- //
    // ++ ADMIN INTERFACE ++ //

    protected function AdminListing() {
	$rs = $this->SelectRecords();

	// set up header action-links
	
	$oMenu = fcApp::Me()->GetHeaderMenu();
	  // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	  //$oMenu->SetNode($ol = new fcMenuOptionLink('do','add'));
	  $oMenu->SetNode($ol = new fcMenuOptionLink('id',KS_NEW_REC));
	    //$ol->SetBasePath($this->SelfURL());
	/* v2
	$oPage = $this->Engine()->App()->Page();
	$arPage = $oPage->PathArgs();
	$arActs = array(
	  // (array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL,$iDescr=NULL)
	  new clsActionLink_option($arPage,'add','id')
	  );
	$oPage->PageHeaderWidgets($arActs);
	*/

	if ($rs->HasRows()) {
	    $arCols = array(
	      'ID'	=> 'ID',
	      'Name'	=> 'Name',
	      'Descr'	=> 'Description',
	      'WhenCreated'	=> 'Created',
	      );
	    $out = $rs->AdminRows($arCols);
	} else {
	    $out = '<i>(none defined)</i>';
	}
	return $out;
    }

    // -- ADMIN INTERFACE -- //
}
class fcrAdminUserGroup extends fcrUserGroup implements fiLinkableRecord, fiEditableRecord {
    use ftLinkableRecord;
    use ftShowableRecord;
    use ftSaveableRecord;

    // ++ DATA TABLE ACCESS ++ //

    protected function XPermitsClass() {
	return KS_CLASS_ADMIN_UPERMITS_FOR_UGROUP;
    }

    // -- DATA TABLE ACCESS -- //
    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec(array $arArgs=NULL) {
	return $this->AdminPage();
    }

    // -- DROP-IN API -- //
    // ++ RENDER UI COMPONENTS ++ //

    protected function AdminRows_start(array $arOptions = NULL) {
	return "\n<table class=listing>";
    }
    protected function AdminField($sField,array $arOptions = NULL) {
	if ($sField == 'ID') {
	    $val = $this->SelfLink();
	} else {
	    $val = $this->GetFieldValue($sField);
	}
	return "<td>$val</td>";
    }

    /*----
      ACTION: Show active assignments in table form
    */
/*
    public function RenderAssigns() {
	if ($this->HasRows()) {
	    $ht = "\n<table class=listing>"
	      .static::AdminLine_header();
	    while ($this->NextRow()) {
		$ht .= $this->AdminLine_static();
	    }
	    $ht .= "\n</table>";
	} else {
	    $ht = '<i>(no permissions )</i>';
	}
    }*/
    /*----
      ACTION: Show active assignments as an inline list
      HISTORY:
	2014-03-01 commented out because "currently unused, and anyway this belongs in perm.php"
	  Why perm.php? What does this have to do with permissions? This generates a list of groups.
	2014-03-16 uncommented because VC_UserAcct:: AdminLine() needs it
    */
    public function RenderInlineList($sSep=', ') {
	$out = NULL;
	while ($this->NextRow()) {
	    if (!is_null($out)) {
		$out .= $sSep;
	    }
	    $ht = $this->SelfLink($this->NameString(),$this->UsageText());
	    if ($this->GetKeyValue() == ID_GROUP_USERS) {
		$ht = "($ht)";
	    }
	    $out .= $ht;
	}
	return $out;
    }
    protected static function AdminLine_header() {
	return '';	// for now
    }
    /*----
      USAGE: Create a recordset of all the groups that are currently assigned.
      ACTIONS: Renders a form showing *all* groups (including those *not* assigned)
	with checkboxes to indicate which should be assigned after saving.
      NOTE: This is very similar in structure to UPerm->RenderEditableList(),
	but it is not the same function. (It could probably be abstracted as an HTML
	control class, but let's save that for later.)
    */
    public function RenderEditableList($sName=NULL) {
	$tbl = $this->GetTableWrapper();
	if (is_null($sName)) {
	    $sName = KS_ACTION_USER_GROUP;
	}

	// build arrays
	$rsAll = $tbl->SelectRecords();	// get ALL records
	$arCur = $this->asKeyedArray();
	$arAll = $rsAll->asKeyedArray();
	if (count($arAll) > 0) {

	    $ht = "\n<table class=listing>"
	      .static::AdminLine_header();
	    foreach ($arAll as $id => $row) {
		$isActive = array_key_exists($id,$arCur);
		$this->SetFieldValues($row);
		$ht .= $this->AdminLine_edit($sName,$isActive);
	    }
	    $ht .= "\n</table>"
	      ."\n<input type=submit name=btnSaveGrps value='Save'>";
	} else {
	    $ht = '<i>(no groups defined)</i>';
	}
	return $ht;
    }

    // -- RENDER UI COMPONENTS -- //
    // ++ ADMIN INTERFACE ++ //

    /*----
      RENDERS: edit control for the current Group
      INPUT: if sName is not NULL, a checkbox will be included with each row.
	The checkbox will be named $sName[ID].
    */
    protected function AdminLine_edit($sName=NULL,$bSel=NULL) {
	$htID = $this->SelfLink();
	if (!is_null($sName)) {
	    $id = $this->GetKeyValue();
	    $htID .= fcHTML::CheckBox($sName,$bSel,$id);
	}
	$out = $this->AdminLine_core($htID);

	$htName = fcString::EncodeForHTML($this->GetFieldValueNz('Name'));
	$htDescr = fcString::EncodeForHTML($this->GetFieldValueNz('Descr'));
	$htWhen = $this->GetFieldValueNz('WhenCreated');

	$out = <<<__END__
  <tr>
    <td>$htID</td>
    <td>$htName</td>
    <td>$htDescr</td>
    <td>$htWhen</td>
  </tr>
__END__;

	return $out;
    }
    protected function AdminLine_core($htID) {
	$htName = fcString::EncodeForHTML($this->GetFieldValueNz('Name'));
	$htDescr = fcString::EncodeForHTML($this->GetFieldValueNz('Descr'));
	$htWhen = $this->GetFieldValueNz('WhenCreated');

	$out = <<<__END__
  <tr>
    <td>$htID</td>
    <td>$htName</td>
    <td>$htDescr</td>
    <td>$htWhen</td>
  </tr>
__END__;

	return $out;
    }
    protected function AdminPage() {
	$oPathInput = fcApp::Me()->GetKioskObject()->GetInputObject();
	$oFormInput = fcHTTP::Request();

	$out = NULL;

	// saving edits to the record?
	$doSave = $oFormInput->GetBool('btnSave');
	if ($doSave) {
	    $out = $this->AdminSave();
	}

	// saving changes to the permission assignments?
	$doSave = $oFormInput->GetBool(fcrAdminUserPermit::BTN_SAVE);
	if ($doSave) {
	    $this->AdminPage_SavePerms();
	    // return to the list form after saving
	    $this->SelfRedirect();
	    /* 2017-01-26 old code -- new code *should* automatically remove edit.prm...
	    $urlReturn = $this->SelfURL(array('edit.prm'=>FALSE),TRUE);
	    clsHTTP::Redirect($urlReturn); */
	}

	// set up header action-links
	$oMenu = fcApp::Me()->GetHeaderMenu();
	  // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	  $oMenu->SetNode($ol = new fcMenuOptionLink('do','edit',NULL,'cancel','edit this supplier'));
	    $doEditReq = $ol->GetIsSelected();
	
	/* 2017-01-26 old
	$arActs = array(
	  new clsActionLink_option($arPage,'edit')
	  );
	$oPage->PageHeaderWidgets($arActs);*/

	$doEdit = $doEditReq || $this->IsNew();

	// prepare the form

	$frmEdit = $this->PageForm();
	if ($this->IsNew()) {
	    $frmEdit->ClearValues();
	} else {
	    $frmEdit->LoadRecord();
	}
	$oTplt = $this->PageTemplate();
	$arCtrls = $frmEdit->RenderControls($doEdit);
	  // custom vars
	  if ($this->IsNew()) {
	      $arCtrls['ID'] = 'n/a';
	      $arCtrls['WhenCreated'] = '<i>not yet</i>';
	  } else {
	      $arCtrls['ID'] = $this->SelfLink();
	  }

	// render the form
	$oTplt->SetVariableValues($arCtrls);
	$htForm = $oTplt->Render();

	if ($doEdit) {
	    $out .= "\n<form method=post id='ugroup.AdminPage'>";
	}
	$out .= $htForm.$this->AdminPerms();
	if ($doEdit) {
	    $out .= "\n<input type=submit name=btnSave value='Save'>"
	      .'</form>';
	}
	return $out;
    }
    private function AdminSave() {
	$oForm = $this->PageForm();
	$oForm->ClearValues();
	$out = $oForm->Save();
	return $out;
    }
    protected function AdminPage_SavePerms() {
	$oFormInput = fcApp::Me()->GetKioskObject()->GetInputObject()->GetArray(KS_ACTION_USER_PERMISSION);
	// $arGrps is formatted like $arGrps[ID] = 'on' for each checked box
	$tbl = $this->GetConnection()->MakeTableWrapper(KS_CLASS_UPERMITS_FOR_UGROUP);
	$tbl->SetUPerms($this->GetKeyValue(),$arPrms);
    }
    private $frmPage;
    protected function PageForm() {
	if (empty($this->frmPage)) {
	    $oForm = new fcForm_DB($this);

	      $oField = new fcFormField_Num($oForm,'ID');
		$oCtrl = new fcFormControl_HTML_Hidden($oField,array());
		  $oCtrl->Editable(FALSE);

	      $oField = new fcFormField_Text($oForm,'Name');
		$oCtrl = new fcFormControl_HTML($oField,array('size'=>20));

	      $oField = new fcFormField_Text($oForm,'Descr');
		$oCtrl = new fcFormControl_HTML($oField,array('size'=>60));

	      $oField = new fcFormField_Time($oForm,'WhenCreated');
		$oField->SetDefault(time());
		$oCtrl = new fcFormControl_HTML_Timestamp($oField,array('size'=>10));
		  $oCtrl->Editable(FALSE);

	    $this->frmPage = $oForm;
	}
	return $this->frmPage;
    }
    private $tpPage;
    protected function PageTemplate() {
	if (empty($this->tpPage)) {
	    $sTplt = <<<__END__
<table class=content>
  <tr><td align=right><b>ID</b>:</td><td>{{ID}}</td></tr>
  <tr><td align=right><b>Name</b>:</td><td>{{Name}}</td></tr>
  <tr><td align=right><b>Description</b>:</td><td>{{Descr}}</td></tr>
  <tr><td align=right><b>Created</b>:</td><td>{{WhenCreated}}</td></tr>
</table>
__END__;
	    $this->tpPage = new fcTemplate_array('{{','}}',$sTplt);
	}
	return $this->tpPage;
    }
    protected function AdminPerms() {
	if ($this->IsNew()) {
	    return NULL;	// new record - no permissions yet
	}
	//$oPage = $this->Engine()->App()->Page();

	// set up header action-links
	$sName = $this->NameString();
	//$oMenu = fcApp::Me()->GetHeaderMenu();
	$oMenu = new fcHeaderMenu();
	  // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	  $oMenu->SetNode($ol = new fcMenuOptionLink(
	    'do',				// $sGroupKey=NULL
	    'edit.prm',				// $sLinkKey
	    'edit',				// $sDispOff=NULL
	    NULL,				// $sDispOn=NULL
	    "edit permissions for $sName"	// $sPopup=NULL
	    ));
	    //$ol->SetBasePath($this->SelfURL());
	    $doEdit = $ol->GetIsSelected();

	// set up section and menu
	$oHdr = new fcSectionHeader('Group Permissions',$oMenu);
	$out = $oHdr->Render();
	    
	/* 2017-01-27 old
	// set up header action-links
	$arPath = $oPage->PathArgs();
	$arActs = array(
	  // (array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL,$iDescr=NULL)
	  new clsActionLink_option($arPath,'edit.prm',NULL,'edit')
	  );
	$out = $oPage->ActionHeader('Permissions',$arActs);

	$doEdit = $oPage->PathArg('edit.prm');
	*/

	$rs = $this->UPermRecords();
	if ($doEdit) {
	    $out .= $rs->RenderEditableList();
	} else {
	    $out .= $rs->RenderAssigns();
	}
	return $out;
    }

    // -- ADMIN INTERFACE -- //
}