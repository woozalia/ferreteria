<?php
/*
  PURPOSE: user access control classes: available security permissions
  HISTORY:
    2013-12-29 started
*/
class acUserPerms extends clsUserPerms {
    use ftLinkableTable;
    private $arData;

    // ++ SETUP ++ //

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->ClassSng(KS_CLASS_ADMIN_USER_PERMISSION);
	  $this->ActionKey(KS_ACTION_USER_PERMISSION);
	$this->arData = NULL;
    }

    // -- SETUP -- //
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
	  new clsActionLink_option($arPage,KS_NEW_REC,'id')
	  );
	$oPage->PageHeaderWidgets($arActs);

	if ($rs->HasRows()) {
	    $out = "\n<table class=listing>\n".$rs->AdminLine_header();
	    while ($rs->NextRow()) {
		$out .= $rs->AdminLine();
	    }
	    $out .= "\n</table>";
	} else {
	    $out = '<i>(none defined)</i>';
	}
	return $out;
    }

    // -- ADMIN INTERFACE -- //
}
class acUserPerm extends clsUserPerm {

    // ++ STATIC ++ //

    const BTN_SAVE = 'btnSavePerm';

    static public function AdminLine_header() {
	return <<<__END__
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Description</th>
    <th>Created</th>
  </tr>
__END__;
    }

    // -- STATIC -- //
    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec(array $arArgs=NULL) {
	return $this->AdminPage();
    }

    // -- DROP-IN API -- //
    // ++ RENDER UI COMPONENTS ++ //

    /*----
      INPUT: if sName is not NULL, a checkbox will be included with each row.
	The checkbox will be named $sName[ID].
    */
    protected function AdminLine_edit($sName=NULL,$bSel=NULL) {
	$htID = $this->AdminLink();
	if (!is_null($sName)) {
	    $id = $this->KeyValue();
	    $htID .= clsHTML::CheckBox($sName,$bSel,$id);
	}

	$htName = fcString::EncodeForHTML($this->ValueNz('Name'));
	$htDescr = fcString::EncodeForHTML($this->ValueNz('Descr'));
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
    /* 2014-03-01 currently not used
    public function RenderInlineList($sSep=', ') {
	$out = NULL;
	while ($this->NextRow()) {
	    if (!is_null($out)) {
		$out .= $sSep;
	    }
	    $ht = $this->AdminLink($this->Name(),$this->Descr());
	    $out .= $ht;
	}
	return $out;
    }
    */
    /*----
      ACTION: Show active assignments in table form
    */
    public function RenderAssigns() {
	if ($this->HasRows()) {
	    $ht = "\n<table class=listing>"
	      .static::AdminLine_header();
	    while ($this->NextRow()) {
		$ht .= $this->AdminLine();
	    }
	    $ht .= "\n</table>";
	} else {
	    $ht = '<i>(no permissions assigned)</i>';
	}
	return $ht;
    }
    /*----
      PURPOSE: edit which Permissions are assigned to a Group
      USAGE: called within the admin page for a specific Group, to edit that Group's Permissions
      ACTIONS: Renders a form showing *all* permissions (including those *not* assigned)
	with checkboxes to indicate which should be assigned after saving.
      NOTE: This is very similar in structure to UGroup->RenderEditableList(),
	but it is not the same function. (They could both probably be abstracted as an HTML
	control class, but let's save that for later.)
    */
    public function RenderEditableList($sName=NULL) {
	$tbl = $this->Table;
	if (is_null($sName)) {
	    $sName = KS_ACTION_USER_PERMISSION;
	}

	// build arrays
	$arCur = $this->AsArray();
	$arAll = $tbl->AsArray();
	if (count($arAll) > 0) {

	    $ht = "\n<form method=post>"
	      ."\n<table class=listing>"
	      .static::AdminLine_header();
	    foreach ($arAll as $id => $row) {
		$isActive = array_key_exists($id,$arCur);
		$this->Values($row);
		$ht .= $this->AdminLine($sName,$isActive);
	    }
	    $sqBtnName = '"'.self::BTN_SAVE.'"';
	    $ht .= "\n</table>"
	      ."\n<input type=submit name=$sqBtnName value='Save'>"
	      ."\n</form>"
	      ;
	} else {
	    $ht = '<i>(no permissions defined)</i>';
	}
	return $ht;
    }

    // -- RENDER UI COMPONENTS -- //
    // ++ ADMIN INTERFACE ++ //

    public function AdminLine($sName=NULL,$bSel=NULL) {
	$htID = $this->SelfLink();
	if (!is_null($sName)) {
	    $id = $this->KeyValue();
	    $htID .= clsHTML::CheckBox($sName,$bSel,$id);
	}
	$htName = fcString::EncodeForHTML($this->ValueNz('Name'));
	$htDescr = fcString::EncodeForHTML($this->ValueNz('Descr'));
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

	$doSave = $oPage->ReqArgBool(self::BTN_SAVE);
	if ($doSave) {
	    $this->AdminPageSave();
	}

	// set up header action-links
	$arPage = $oPage->PathArgs();
	$arActs = array(
	  // (array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL,$iDescr=NULL)
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
	  $arCtrls['ID'] = $this->SelfLink();

	// render the form
	$oTplt->VariableValues($arCtrls);
	$htForm = $oTplt->Render();

	if ($doEdit) {
	    $sqBtnName = '"'.self::BTN_SAVE.'"';
	    $htFormHdr = "\n<form method=post id='uperm.AdminPage'>";
	    $htFormFtr = "\n<input type=submit value=Save name=$sqBtnName>"
	      ."\n</form>";
	} else {
	    $htFormHdr = NULL;
	    $htFormFtr = NULL;
	}

	$out = $htFormHdr
	  .$htForm
	  .$htFormFtr
//	  .$this->EventListing()	TODO
	  ;
	return $out;
    }
    private function AdminPageSave() {
	$oForm = $this->PageForm();
	//$oForm->ClearValues();
	$oForm->Save();
	$sMsg = $oForm->MessagesString();
	$this->SelfRedirect(array('id'=>FALSE),$sMsg);	// clear the form data out of the page reload
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
    private $frmPage;
    protected function PageForm() {
	if (empty($this->frmPage)) {
	    $oForm = new fcForm_DB($this);

//	      if (!$this->IsNew()) {
//		  $oField = new fcFormField_Num($oForm,'ID');
//		    $oCtrl = new fcFormControl_HTML_Hidden($oForm,$oField,array());
//	      }

	      $oField = new fcFormField_Text($oForm,'Name');
		$oCtrl = new fcFormControl_HTML($oField,array('size'=>20));

	      $oField = new fcFormField_Text($oForm,'Descr');
		$oCtrl = new fcFormControl_HTML($oField,array('size'=>60));

	      $oField = new fcFormField_Time($oForm,'WhenCreated');
		//$oCtrl = new fcFormControl_HTML($oField,array('size'=>10));
		$oField->ControlObject()->Editable(FALSE);
		$oField->SetDefault(time());

	    $this->frmPage = $oForm;
	}
	return $this->frmPage;
    }
}