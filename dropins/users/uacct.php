<?php
/*
  PURPOSE: user access control classes: user accounts
  HISTORY:
    2013-12-19 started (for VbzCart)
    2015-06-09 renamed to disentangle from VbzCart
      VCM_UserAccts -> scUserAccts
      VC_UserAcct -> scUserAcct
      scUserAccts descends from: was clsVbzUserTable, now clsUserAccts
      scUserAcct descends from: was clsVbzUserRec, now clsUserAcct

  FAMILY:
    * scUserAccts <- clsUserAccts (user-access)
    * scUserAcct <- clsUserAcct (user-access)
*/
class acUserAccts extends clsUserAccts {

    // ++ SETUP ++ //

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->ClassSng(KS_CLASS_ADMIN_USER_ACCOUNT);
	  $this->ActionKey(KS_ACTION_USER_ACCOUNT);
    }

    // -- SETUP -- //
    // ++ CLASS NAMES ++ //

    protected function ClassName_Perms() {
	return KS_CLASS_ADMIN_USER_PERMISSIONS;
    }
    protected function ClassName_Perm() {
	return KS_CLASS_ADMIN_USER_PERMISSION;
    }

    // -- CLASS NAMES -- //
    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
      NOTE: App()->User() may return the non-dropin class (TODO: FIX), so
	we have to upgrade it here.
    */
    public function MenuExec(array $arArgs=NULL) {
	$this->arArgs = $arArgs;
	$rcUser_base = $this->App()->User();
	$rcUser = $this->GetItem($rcUser_base->KeyValue());
	if ($rcUser->CanDo(KS_PERM_SEC_USER_VIEW)) {
	    $out = $this->AdminListing();
	} else {
	    $out = $rcUser->MenuExec();
	}
	return $out;
    }
    /*----
      RETURNS: TRUE iff user has permission to access this class
    */
    /* may not be needed
    public function UserCanAccess() {
	return $this->Engine()->App()->User()->CanDo(KS_PERM_SEC_PERM_VIEW);
    }*/

    // -- DROP-IN API -- //
    // ++ APP FRAMEWORK ++ //

    protected function App() {
	return $this->Engine()->App();
    }

    // -- APP FRAMEWORK -- //
    // ++ WEB UI ++ //

    protected function AdminListing() {
	$rs = $this->GetData();
	$arCols = array(
	  'ID'		=> 'ID',
	  'UserName'	=> 'Login',
	  'FullName'	=> 'Full Name',
	  'EmailAddr'	=> 'Email',
	  'WhenCreated'	=> 'Created',
	  '!grps'	=> 'Groups',
	  );
	$out = $rs->AdminRows($arCols);
/*
	$out = "\n<table class=listing>\n".VC_UserAcct::AdminLine_header();
	while ($rs->NextRow()) {
	    $out .= $rs->AdminLine();
	}
	$out .= "\n</table>";
*/
	return $out;
    }

    // -- WEB UI -- //
}
class acUserAcct extends clsUserAcct {

    // ++ CLASS NAMES ++ //

    protected function XGroupClass() {
	return KS_CLASS_ADMIN_UACCT_X_UGROUP;
    }

    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec(array $arArgs=NULL) {
	$oApp = $this->Engine()->App();
	$rcUser = $oApp->User();
	if ($rcUser->CanDo(KS_PERM_SEC_USER_EDIT) || $this->IsLoggedIn()) {
	    $out = $this->AdminPage();
	} else {
	    $out = NULL;	// user doesn't have permission; don't tell them how close they are to hacking us
	}
	return $out;
    }

    // -- DROP-IN API -- //
    // ++ DATA TABLE ACCESS ++ //

//    protected function CustomerTable($id=NULL) {
//	return $this->Engine()->Make(KS_CLASS_ADMIN_CUSTOMERS,$id);
//    }

    // -- DATA TABLE ACCESS -- //
    // ++ ADMIN INTERFACE ++ //

    protected function AdminRows_start() {
	return "\n<table class=listing>";
    }
    protected function AdminField($sField) {
	switch ($sField) {
	  case 'ID':
	    $val = $this->AdminLink();
	    break;
	  case '!grps':
	    $val = $this->GroupList();
	    break;
	  default:
	    $val = $this->Value($sField);
	}
	return "<td>$val</td>";
    }
    protected function GroupList() {
	$rcGrps = $this->UGroupRecords();
	if ($rcGrps->HasRows()) {
	    $out = $rcGrps->RenderInlineList();
	} else {
	    $out = '<i>(none)</i>';
	}
	return $out;
    }
    public function AdminLine() {
	$htID = $this->AdminLink();
	$htLogin = htmlspecialchars($this->Value('UserName'));
	$htName = htmlspecialchars($this->Value('FullName'));
	$htWhen = $this->Value('WhenCreated');
	$htGrps = $this->GroupList();

	$out = <<<__END__

  <tr>
    <td>$htID</td>
    <td>$htLogin</td>
    <td>$htName</td>
    <td>$htWhen</td>
    <td>$htGrps</td>
  </tr>
__END__;

	return $out;
    }
    protected function AdminPage() {
	$oPage = $this->Engine()->App()->Page();

	// saving changes to the user record?
	$doSave = $oPage->ReqArgBool('btnSave');
	if ($doSave) {
	    $this->PageForm()->Save();
	    // return to the list form after saving
	    //$urlReturn = $oPage->SelfURL(array('id'=>FALSE));
	    //clsHTTP::Redirect($urlReturn);
	    $this->AdminRedirect();
	}

	// saving changes to the group assignments?
	$doSave = $oPage->ReqArgBool('btnSaveGrps');
	if ($doSave) {
	    $out = $this->AdminPage_SaveGroups();
	    // return to the list form after saving
	    //$urlReturn = $oPage->SelfURL(array('edit.grp'=>FALSE));
	    //clsHTTP::Redirect($urlReturn);
	    $this->AdminRedirect(NULL,$out);
	}

	// set up header action-links
	clsActionLink_option::UseRelativeURL_default(TRUE);	// use relative URLs
	$arActs = array(
	  new clsActionLink_option(array(),'edit')
	  );
	$oPage->PageHeaderWidgets($arActs);

	$doEdit = $oPage->PathArg('edit');

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

	// render the form
	$oTplt->VariableValues($arCtrls);
	$htForm = $oTplt->Render();

	$out = "\n<form method=post id='uacct.AdminPage'>"
	  .$htForm
	  .$this->AdminGroups()
	  ."\n</form>"
	  //.$this->RenderAddresses()	// TODO: render Stories etc.
	  ;
	return $out;
    }
    protected function AdminPage_SaveGroups() {
	$oPage = $this->Engine()->App()->Page();
	$arGrps = $oPage->ReqArgArray(KS_ACTION_USER_GROUP);
	// $arGrps is formatted like $arGrps[ID] = 'on' for each checked box
	$tbl = $this->Engine()->Make(KS_CLASS_UACCT_X_UGROUP);
	$out = $tbl->SetUGroups($this->KeyValue(),$arGrps);
	return $out;
    }

    private $frmPage;
    protected function PageForm() {
	if (empty($this->frmPage)) {
	    $oForm = new fcForm_DB($this->Table()->ActionKey(),$this);

	      $oField = new fcFormField_Num($oForm,'ID');
		$oCtrl = new fcFormControl_HTML_Hidden($oForm,$oField,array());

	      $oField = new fcFormField_Text($oForm,'UserName');
		$oCtrl = new fcFormControl_HTML($oForm,$oField,array('size'=>20));

	      $oField = new fcFormField_Text($oForm,'FullName');
		$oCtrl = new fcFormControl_HTML($oForm,$oField,array('size'=>40));

	      $oField = new fcFormField_Time($oForm,'WhenCreated');
		$oCtrl = new fcFormControl_HTML($oForm,$oField,array('size'=>10));
		  $oCtrl->Editable(FALSE);

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
  <tr><td align=right><b>Login</b>:</td><td>{{UserName}}</td></tr>
  <tr><td align=right><b>Full Name</b>:</td><td>{{FullName}}</td></tr>
  <tr><td align=right><b>Created</b>:</td><td>{{WhenCreated}}</td></tr>
</table>
__END__;
	    $this->tpPage = new fcTemplate_array('{{','}}',$sTplt);
	}
	return $this->tpPage;
    }
    protected function AdminGroups() {
	$oPage = $this->Engine()->App()->Page();

	// set up header action-links
	$arActs = array(
	  // (array $iarData,$iLinkKey,$iGroupKey=NULL,$iDispOff=NULL,$iDispOn=NULL,$iDescr=NULL)
	  new clsActionLink_option(array(),'edit.grp',NULL,'edit')
	  );
	$out = $oPage->ActionHeader('Groups',$arActs);

	$doEdit = $oPage->PathArg('edit.grp');

	$rs = $this->UGroupRecords();
	if ($doEdit) {
	    $out .= $rs->RenderEditableList();
	} else {
	    if ($rs->HasRows()) {
		$out .= $rs->RenderInlineList();
	    } else {
		$out .= '<i>(none)</i>';
	    }
	}
	return $out;
    }
    /*----
      ACTION: show customer profiles for the current user
    */
/*    protected function RenderAddresses() {
	$oApp = $this->Engine()->App();
	$oPage = $oApp->Page();
	$out = $oPage->ActionHeader('Addresses')
	  .'<center>'.$this->RenderCustomers().'</center>';
	$idLogin = $oApp->User()->KeyValue();
	$idView = $this->KeyValue();
	if ($idLogin == $idView) {
	    $oSkin = $oPage->Skin();
	    $out .= '<hr>'
	    // option to add more emails
	      .'If you have previously placed orders using other email addresses to which you currently have access, you may claim ownership of those orders here.<br>'
	      .$oSkin->RenderForm_Email_RequestAdd();
	}
	return $out;
    }
    */
    /*----
      ACTION: render HTML to show all customer profiles for this user
    */
/*    protected function RenderCustomers() {
	$id = $this->KeyValue();
	$tCust = $this->CustomerTable();
	$rc = $tCust->GetRecs_forUser($id);
	//$ht = $rc->Render_asTable();
	$arF = $rc->ColumnsArray();
	$ht = $rc->AdminRows($arF);
	return $ht;
    }
*/
    // -- ADMIN INTERFACE -- //
}