<?php
/*
  PURPOSE: user access control classes: user accounts
  HISTORY:
    2013-12-19 started (for VbzCart)
    2015-06-09 renamed to disentangle from VbzCart
      VCM_UserAccts -> scUserAccts
      VC_UserAcct -> scUserAcct
      fctUserAccts_admin (then scUserAccts) now descends from ftcUserAccts (was clsVbzUserTable) 
      fcrUserAcct_admin (then scUserAcct) now descends from frcUserAcct (was clsVbzUserRec)
    2017-01-27 adjustments to work with Ferreteria revisions

  FAMILY:
    * fctUserAccts_admin <- fctUserAccts (user-access)
    * fcrUserAcct_admin <- fcrUserAcct (user-access)
*/
class fctUserAccts_admin extends fctUserAccts {
    use ftLinkableTable;

    // ++ SETUP ++ //

    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_ACCOUNT;
    }
    public function GetActionKey() {
	return KS_ACTION_USER_ACCOUNT;
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
	$rcUser_base = fcApp::Me()->GetUserRecord();
	$rcUser = $this->GetRecord_forKey($rcUser_base->GetKeyValue());
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
/* 2016-10-31 this should be replaced by the app framework trait
    protected function App() {
	return $this->Engine()->App();
    }
*/

    // -- APP FRAMEWORK -- //
    // ++ WEB UI ++ //

    protected function AdminListing() {
	$rs = $this->SelectRecords();
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
class fcrUserAcct_admin extends fcrUserAcct {
    use ftShowableRecord;
    use ftSaveableRecord;

    // ++ CLASSES ++ //

    protected function XGroupClass() {
	return KS_CLASS_ADMIN_UGROUPS_FOR_UACCT;
    }

    // -- CLASSES -- //
    // ++ TABLES ++ //

    protected function XGroupTable() {
    	return $this->GetConnection()->MakeTableWrapper($this->XGroupClass());
    }

    // -- TABLES -- //
    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec() {
	$rcUser = fcApp::Me()->GetUserRecord();
	if ($rcUser->CanDo(KS_PERM_SEC_USER_EDIT) || $this->IsLoggedIn()) {
	    $out = $this->AdminPage();
	} else {
	    $out = NULL;	// user doesn't have permission (TODO: tell them they don't have permission)
	}
	return $out;
    }

    // -- DROP-IN API -- //
    // ++ ADMIN INTERFACE ++ //

    protected function AdminRows_start(array $arOptions = NULL) {
	return "\n<table class=listing>";
    }
    protected function AdminField($sField, array $arOptions = NULL) {
	switch ($sField) {
	  case 'ID':
	    $val = $this->SelfLink();
	    break;
	  case '!grps':
	    $val = $this->GroupList();
	    break;
	  default:
	    $val = $this->GetFieldValue($sField);
	}
	return "<td>$val</td>";
    }
    protected function GroupList() {
	$rsGrps = $this->UGroupRecords();
	if ($rsGrps->HasRows()) {
	    $out = $rsGrps->RenderInlineList();
	} else {
	    $out = '<i>(none)</i>';
	}
	return $out;
    }
    public function AdminLine() {
	$htID = $this->AdminLink();
	$htLogin = fcString::EncodeForHTML($this->GetFieldValue('UserName'));
	$htName = fcString::EncodeForHTML($this->GetFieldValue('FullName'));
	$htWhen = $this->GetFieldValue('WhenCreated');
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
	$oPathInput = fcApp::Me()->GetKioskObject()->GetInputObject();
	$oFormInput = fcHTTP::Request();

	// saving changes to the user record?
	$doSave = $oFormInput->GetBool('btnSave');
	if ($doSave) {
	    $this->PageForm()->Save();
	    $this->SelfRedirect();
	}

	// saving changes to the group assignments?
	$doSave = $oFormInput->GetBool('btnSaveGrps');
	if ($doSave) {
	    $out = $this->AdminPage_SaveGroups();
	    $this->SelfRedirect(NULL,$out);
	}

	// set up header action-links
	// v3
	$oMenu = fcApp::Me()->GetHeaderMenu();
	  $oMenu->SetNode(new fcMenuOptionLink('edit'));
	
	/* v2
	$arActs = array(
	  new clsActionLink_option(array(),'edit')
	  );
	$oPage->PageHeaderWidgets($arActs); */

	$doEdit = $oPathInput->GetBool('edit');

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
	  $arCtrls['!Groups'] = $this->AdminGroups();

	// render the form
	$oTplt->VariableValues($arCtrls);
	$htForm = $oTplt->Render();
	
	if ($doEdit) {
	    $htFormHdr = "\n<form method=post id='uacct.AdminPage'>";
	    $htFormBtn = "\n<input type=submit name=btnSave value='Save'>";
	    $htFormFtr = "\n</form>";
	} else {
	    $htFormHdr = NULL;
	    $htFormBtn = NULL;
	    $htFormFtr = NULL;
	}

	$out = $htFormHdr.$htForm.$htFormBtn.$htFormFtr;
	return $out;
    }
    protected function AdminPage_SaveGroups() {
	$oFormInput = fcHTTP::Request();
	$arGrps = $oFormInput->GetArray(KS_ACTION_USER_GROUP);
	// $arGrps is formatted like $arGrps[ID] = 'on' for each checked box
	// we could actually use the non-admin version here, but why not reuse a class we're already using:
	$tbl = $this->XUGroupsTable();
	$out = $tbl->SetUGroups($this->GetKeyValue(),$arGrps);
	return $out;
    }

    private $frmPage;
    protected function PageForm() {
	if (empty($this->frmPage)) {
	    $oForm = new fcForm_DB($this);

	      $oField = new fcFormField_Num($oForm,'ID');
		$oField->StorageObject()->Writable(FALSE);	// never update this field in the db
		$oCtrl = new fcFormControl_HTML_Hidden($oField,array());

	      $oField = new fcFormField_Text($oForm,'UserName');
		$oCtrl = new fcFormControl_HTML($oField,array('size'=>20));

	      $oField = new fcFormField_Text($oForm,'FullName');
		$oCtrl = new fcFormControl_HTML($oField,array('size'=>40));

	      $oField = new fcFormField_Time($oForm,'WhenCreated');
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
<table class=listing>
  <tr class=odd><td align=right><b>ID</b>:</td><td>{{ID}}</td></tr>
  <tr class=even><td align=right><b>Login</b>:</td><td>{{UserName}}</td></tr>
  <tr class=odd><td align=right><b>Full Name</b>:</td><td>{{FullName}}</td></tr>
  <tr class=even><td align=right><b>Groups</b>:</td><td>{{!Groups}}</td></tr>
  <tr class=odd><td align=right><b>Created</b>:</td><td>{{WhenCreated}}</td></tr>
</table>
__END__;
	    $this->tpPage = new fcTemplate_array('{{','}}',$sTplt);
	}
	return $this->tpPage;
    }
    protected function AdminGroups() {
	$oApp = fcApp::Me();
	$oPage = $oApp->GetPageObject();
	
	if ($this->IsNew()) {
	    // don't display or edit groups if user is new -- there can't be any yet
	    $oApp->AddContentString('<i>(new user - no groups)</i>');
	    $oPage->SetPageTitle('New User');
	} else {
	    // page title
	    $sName = $this->UserName();
	    $id = $this->GetKeyValue();
	    $oPage->SetPageTitle("User &ldquo;$sName&rdquo; (ID=$id)");
	    
	    /* 2017-01-14 actually, we don't need a section-header, but this code currently works:
	    // section-header
	    $oMenu = $oPage->GetElement_HeaderMenu();
	      // (sLinkKey,sGroupKey=NULL,sDispOff=NULL,sDispOn=NULL,sPopup=NULL)
	      $oMenu->SetNode(new fcMenuOptionLink('edit.grp',NULL,'edit'));
	    $oHdr = new fcSectionHeader('Groups',$oMenu);
	    $oPage->AddImmediateRender($oHdr);	// render the section header
	    
	    // What we *do* want is an edit link for the groups -- and we can use the same menu link objects for that, below.
	    
	    */
	    	    
	    //$out = $oPage->ActionHeader('Groups',$arActs);
	    
	    $oInput = $oApp->GetKioskObject()->GetInputObject();
	    $doEdit = $oInput->GetBool('edit.grp');
	    $rs = $this->UGroupRecords();
	    if ($doEdit) {
		$out =
		  "\n<form method=post>"
		  .$rs->RenderEditableList()
		  ."\n</form>"
		  ;
	    } else {
		// 2017-01-14 What we *do* want is an edit link for the groups.
		//$oMenu = $oPage->GetElement_HeaderMenu();	// this puts it in the title header
		$oMenu = new fcHeaderMenu();
		  // (sLinkKey,sGroupKey=NULL,sDispOff=NULL,sDispOn=NULL,sPopup=NULL)
		  $oMenu->SetNode(new fcMenuOptionLink('edit.grp',NULL,'edit'));
		  
		if ($rs->HasRows()) {
		    $out = $rs->RenderInlineList();
		} else {
		    $out = '<i>(none)</i>';
		}
		$out .= ' : ['.$oMenu->Render().']';
	    }
	    return $out;
	}
    }

    // -- ADMIN INTERFACE -- //
}