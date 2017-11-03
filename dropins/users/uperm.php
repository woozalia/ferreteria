<?php
/*
  PURPOSE: user access control classes: available security permissions
  HISTORY:
    2013-12-29 started
    2017-03-28 y2017 remediation
*/
class fctAdminUserPermits extends fctUserPerms implements fiEventAware, fiLinkableTable {
    use ftLinkableTable;
    use ftExecutableTwig;

    // ++ SETUP ++ //

    // OVERRIDE
    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_PERMISSION;
    }
    // CEMENT
    public function GetActionKey() {
	return KS_ACTION_USER_PERMISSION;
    }

    // -- SETUP -- //
    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle('User Permits');
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    public function Render() {
	return $this->AdminListing();
    }
    /*----
      PURPOSE: execution method called by dropin menu
    */ /*
    public function MenuExec() {
	return $this->AdminListing();
    } */

    // -- EVENTS -- //
    // ++ ADMIN INTERFACE ++ //

    protected function AdminListing() {
	$rs = $this->SelectRecords();

	// set up header action-links
	
	$oMenu = fcApp::Me()->GetHeaderMenu();
	  // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	  $oMenu->SetNode($ol = new fcMenuOptionLink('id',KS_NEW_REC));
	    $ol->SetBasePath($this->SelfURL());
	/* 2017-02-05 v2 - obsolete
	$oPage = $this->Engine()->App()->Page();
	$arPage = $oPage->PathArgs();
	$arActs = array(
	  // (array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL,$iDescr=NULL)
	  new clsActionLink_option($arPage,KS_NEW_REC,'id')
	  );
	$oPage->PageHeaderWidgets($arActs);
	*/

	if ($rs->HasRows()) {
	    $out = "\n<table class=listing>\n".$rs->AdminLine_header();
	    while ($rs->NextRow()) {
		$out .= $rs->AdminLine();
	    }
	    $out .= "\n</table>";
	} else {
	    $out = '<i>(none defined)</i>';
	}
	
	// report any permits that need to be created
	
	if ($this->PermitsAreMissing()) {
	    $arNeed = $this->GetMissingPermits();
	    $nNeed = count($arNeed);
	    $out .= '<div class=content>'
	      .$nNeed
	      .' additional permit'
	      .fcString::Pluralize($nNeed,' is','s are')
	      .' needed:'
	      ;
	
	    foreach ($arNeed as $sName => $nCount) {
		$out .= " <b>$sName</b> ($nCount)";
	    }
	    $out .= '</div>';
	}
	
	return $out;
    }

    // -- ADMIN INTERFACE -- //
}
class fcrAdminUserPermit extends fcrUserPermit implements fiLinkableRecord, fiEditableRecord , fiEventAware {
    use ftLinkableRecord;
    use ftSaveableRecord;
    use ftExecutableTwig;

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
    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	$isNew = $this->IsNew();
	
	// set up header action-links
	
	if ($isNew) {
	    $sTitle = 'new Permit';
	    $htTitle = 'create new Security Permission';
	    $doEdit = TRUE;
	} else {
	    $sTitle = 'Permit #'.$this->GetKeyValue();
	    $htTitle = 'edit Security permit #'.$this->SelfLink();
	    $oMenu = fcApp::Me()->GetHeaderMenu();
	      // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	      $oMenu->SetNode($ol = new fcMenuOptionLink('edit'));
		$doEdit = $ol->GetIsSelected();
	}
	$this->SetDoEdit($doEdit);
	$oPage->SetBrowserTitle($sTitle);
	$oPage->SetContentTitle($htTitle);
	//$oPage->SetPageTitle('User Permits');	// TODO: should be singular
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    public function Render() {
	return $this->AdminPage();
    }
    /*----
      PURPOSE: execution method called by dropin menu
    */ /*
    public function MenuExec(array $arArgs=NULL) {
	return $this->AdminPage();
    } */

    // -- EVENTS -- //
    // ++ INTERNAL STATES ++ //
    
    private $doEdit;
    protected function SetDoEdit($b) {
	$this->doEdit = $b;
    }
    protected function GetDoEdit() {
	return $this->doEdit;
    }
  
    // ++ RENDER UI COMPONENTS ++ //

    /*----
      INPUT: if sName is not NULL, a checkbox will be included with each row.
	The checkbox will be named $sName[ID].
    */
    protected function AdminLine_edit($sName=NULL,$bSel=NULL) {
	$htID = $this->AdminLink();
	if (!is_null($sName)) {
	    $id = $this->GetKeyValue();
	    $htID .= fcHTML::CheckBox($sName,$bSel,$id);
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
	    $ht = '<div class=content><i>(no permissions assigned)</i></div>';
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
	$tbl = $this->GetTableWrapper();
	if (is_null($sName)) {
	    $sName = KS_ACTION_USER_PERMISSION;
	}

	// build arrays
	$arCur = $this->asKeyedArray();
	$arAll = $tbl->SelectRecords()->FetchRows_asKeyedArray();
	if (count($arAll) > 0) {

	    $ht = "\n<table class=content><tr><td>"
	      ."\n<form method=post>"
	      ."\n<table class=listing>"
	      .static::AdminLine_header();
	    foreach ($arAll as $id => $row) {
		$isActive = array_key_exists($id,$arCur);
		$this->SetFieldValues($row);
		$ht .= $this->AdminLine($sName,$isActive);
	    }
	    $sqBtnName = '"'.self::BTN_SAVE.'"';
	    $ht .= "\n</table>"
	      ."\n<input type=submit name=$sqBtnName value='Save'>"
	      ."\n</form>"
	      ."\n</td></tr></table>"
	      ;
	} else {
	    $ht = '<i>(no permissions defined)</i>';
	}
	return $ht;
    }

    // -- RENDER UI COMPONENTS -- //
    // ++ ADMIN INTERFACE ++ //

    private $isOdd;
    public function AdminLine($sName=NULL,$bSel=NULL) {
	$htID = $this->SelfLink();
	if (!is_null($sName)) {
	    $id = $this->GetKeyValue();
	    $htID .= fcHTML::CheckBox($sName,$bSel,$id);
	}
	$htName = fcString::EncodeForHTML($this->GetFieldValueNz('Name'));
	$htDescr = fcString::EncodeForHTML($this->GetFieldValueNz('Descr'));
	$htWhen = $this->GetFieldValueNz('WhenCreated');
	$this->isOdd = ($isOdd = empty($this->isOdd));
	$sCSS = $isOdd?'odd':'even';

	$out = <<<__END__
  <tr class=$sCSS>
    <td>$htID</td>
    <td>$htName</td>
    <td>$htDescr</td>
    <td>$htWhen</td>
  </tr>
__END__;

	return $out;
    }
    protected function AdminPage() {
	$oFormIn = fcHTTP::Request();
	$doSave = $oFormIn->GetBool(self::BTN_SAVE);
	if ($doSave) {
	    $this->AdminPageSave();
	}

	$doEdit = $this->GetDoEdit();
	echo "DO EDIT=[$doEdit]";
	
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
	$oTplt->SetVariableValues($arCtrls);
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