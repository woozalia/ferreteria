<?php
/*
  PURPOSE: user access control classes: security groups
  HISTORY:
    2013-12-19 started
*/
class acUserGroups extends clsUserGroups {
    use ftLinkableTable;

    private $arData;

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->ClassSng(KS_CLASS_ADMIN_USER_GROUP);
	  $this->ActionKey(KS_ACTION_USER_GROUP);
	$this->arData = NULL;
    }

    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec(array $arArgs=NULL) {
	$this->arArgs = $arArgs;
	$out = $this->AdminListing();
	return $out;
    }

    // -- DROP-IN API -- //
    // ++ ADMIN INTERFACE ++ //

    protected function AdminListing() {
	$rs = $this->GetData();

	// set up header action-links
	$oPage = $this->Engine()->App()->Page();
	$arPage = $oPage->PathArgs();
	$arActs = array(
	  // (array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL,$iDescr=NULL)
	  new clsActionLink_option($arPage,'add','id')
	  );
	$oPage->PageHeaderWidgets($arActs);

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
class acUserGroup extends clsUserGroup {

    // ++ DATA TABLE ACCESS ++ //

    protected function XPermTable() {
	$tbl = parent::XPermTable();
	$tbl->ClassName_Perms(KS_CLASS_ADMIN_USER_PERMISSIONS);
	$tbl->ClassName_Perm(KS_CLASS_ADMIN_USER_PERMISSION);
	return $tbl;
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
	    $val = $this->Value($sField);
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
	    $ht = '<i>(no permissions assigned)</i>';
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
	    $ht = $this->SelfLink($this->Name(),$this->Descr());
	    if ($this->KeyValue() == ID_GROUP_USERS) {
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
	$tbl = $this->Table;
	if (is_null($sName)) {
	    $sName = KS_ACTION_USER_GROUP;
	}

	// build arrays
	$arCur = $this->AsArray();
	$arAll = $tbl->AsArray();
	if (count($arAll) > 0) {

	    $ht = "\n<table class=listing>"
	      .static::AdminLine_header();
	    foreach ($arAll as $id => $row) {
		$isActive = array_key_exists($id,$arCur);
		$this->Values($row);
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

/*
    protected function AdminLine_static() {
	$htID = $this->AdminLink();
	$out = $this->AdminLine_core($htID);
	return $out;
    }
    */
    /*----
      RENDERS: edit control for the current Group
      INPUT: if sName is not NULL, a checkbox will be included with each row.
	The checkbox will be named $sName[ID].
    */
    protected function AdminLine_edit($sName=NULL,$bSel=NULL) {
	$htID = $this->SelfLink();
	if (!is_null($sName)) {
	    $id = $this->KeyValue();
	    $htID .= clsHTML::CheckBox($sName,$bSel,$id);
	}
	$out = $this->AdminLine_core($htID);

	$htName = htmlspecialchars($this->ValueNz('Name'));
	$htDescr = htmlspecialchars($this->ValueNz('Descr'));
	$htWhen = $this->ValueNz('WhenCreated');

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
	$htName = htmlspecialchars($this->ValueNz('Name'));
	$htDescr = htmlspecialchars($this->ValueNz('Descr'));
	$htWhen = $this->ValueNz('WhenCreated');

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
	$oPage = $this->Engine()->App()->Page();
	$out = NULL;

	// saving edits to the record?
	$doSave = $oPage->ReqArgBool('btnSave');
	if ($doSave) {
	    $out = $this->AdminSave();
	}

	// saving changes to the permission assignments?
	$doSave = $oPage->ReqArgBool(acUserPerm::BTN_SAVE);
	if ($doSave) {
	    $this->AdminPage_SavePerms();
	    // return to the list form after saving
	    $urlReturn = $oPage->SelfURL(array('edit.prm'=>FALSE),TRUE);
	    clsHTTP::Redirect($urlReturn);
	}

	// set up header action-links
	$arPage = $oPage->PathArgs();
	$arActs = array(
	  new clsActionLink_option($arPage,'edit')
	  );
	$oPage->PageHeaderWidgets($arActs);

	$doEdit = $oPage->PathArg('edit') || $this->IsNew();

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
	  $arCtrls['ID'] = $this->AdminLink();
	  if ($this->IsNew()) {
	      $arCtrls['ID'] = 'n/a';
	      $arCtrls['WhenCreated'] = '<i>not yet</i>';
	  } else {
	      $arCtrls['ID'] = $this->AdminLink();
	  }

	// render the form
	$oTplt->VariableValues($arCtrls);
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
	$oPage = $this->Engine()->App()->Page();
	$arPrms = $oPage->ReqArgArray(KS_ACTION_USER_PERMISSION);
	// $arGrps is formatted like $arGrps[ID] = 'on' for each checked box
	$tbl = $this->Engine()->Make(KS_CLASS_UGROUP_X_UPERM);
	$tbl->SetUPerms($this->KeyValue(),$arPrms);
    }
    private $frmPage;
    protected function PageForm() {
	if (empty($this->frmPage)) {
	    $oForm = new fcForm_DB($this->Table()->ActionKey(),$this);

	      $oField = new fcFormField_Num($oForm,'ID');
		$oCtrl = new fcFormControl_HTML_Hidden($oForm,$oField,array());
		  $oCtrl->Editable(FALSE);

	      $oField = new fcFormField_Text($oForm,'Name');
		$oCtrl = new fcFormControl_HTML($oForm,$oField,array('size'=>20));

	      $oField = new fcFormField_Text($oForm,'Descr');
		$oCtrl = new fcFormControl_HTML($oForm,$oField,array('size'=>60));

	      $oField = new fcFormField_Time($oForm,'WhenCreated');
		$oCtrl = new fcFormControl_HTML($oForm,$oField,array('size'=>10));
		  $oCtrl->Editable(FALSE);

	    $oForm->NewValue('WhenCreated',time());

//	    $frmPage->AddField(new clsFieldTime('WhenCreated'),	new clsCtrlHTML_ReadOnly());
//	    $frmPage->NewVals(array('WhenCreated'=>'NOW()'));

	    $this->frmPage = $oForm;
	}
	return $this->frmPage;
    }
    private $tpPage;
    protected function PageTemplate() {
	if (empty($this->tpPage)) {
	    $sTplt = <<<__END__
<table>
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
	$oPage = $this->Engine()->App()->Page();

	// set up header action-links
	$arPath = $oPage->PathArgs();
	$arActs = array(
	  // (array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL,$iDescr=NULL)
	  new clsActionLink_option($arPath,'edit.prm',NULL,'edit')
	  );
	$out = $oPage->ActionHeader('Permissions',$arActs);

	$doEdit = $oPage->PathArg('edit.prm');

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