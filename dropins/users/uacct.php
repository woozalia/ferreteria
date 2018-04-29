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
    2017-03-28 more y2017 remediation

  FAMILY:
    * fctUserAccts_admin <- fctUserAccts (user-access)
    * fcrUserAcct_admin <- fcrUserAcct (user-access)
*/
class fctUserAccts_admin extends fctUserAccts implements fiEventAware, fiLinkableTable {
    use ftLinkableTable;
    use ftExecutableTwig;

    // ++ SETUP ++ //

    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_ACCOUNT;
    }
    public function GetActionKey() {
	return KS_ACTION_USER_ACCOUNT;
    }

    // -- SETUP -- //
    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle('User Accounts');
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    /*----
      NOTE: App()->GetUserRecord() may return the non-dropin class (TODO: FIX), so
	we have to upgrade it here.
    */
    public function Render() {
	$rcUser_base = fcApp::Me()->GetUserRecord();
	$rcUser = $this->GetRecord_forKey($rcUser_base->GetKeyValue());	// TODO: copy fields instead of reloading
	if ($rcUser->CanDo(KS_PERM_SEC_USER_VIEW)) {
	    $out = $this->AdminListing();
	} else {
	    $rcUser->SelfRedirect();	// redirect to user's record
	}
	return $out;
     }
    /*----
      PURPOSE: execution method called by dropin menu
      NOTE: App()->User() may return the non-dropin class (TODO: FIX), so
	we have to upgrade it here.
    */ /*
    public function MenuExec() {
	$rcUser_base = fcApp::Me()->GetUserRecord();
	$rcUser = $this->GetRecord_forKey($rcUser_base->GetKeyValue());
	if ($rcUser->CanDo(KS_PERM_SEC_USER_VIEW)) {
	    $out = $this->AdminListing();
	} else {
	    $rcUser->SelfRedirect();	// redirect to user's record
	}
	return $out;
    } */

    // -- DROP-IN API -- //
    // ++ CLASSES ++ //

    protected function ClassName_Perms() {
	return KS_CLASS_ADMIN_USER_PERMISSIONS;
    }
    protected function ClassName_Perm() {
	return KS_CLASS_ADMIN_USER_PERMISSION;
    }

    // -- CLASSES -- //
    // ++ WEB UI ++ //

    protected function AdminListing() {
	$rs = $this->SelectRecords();
	$out = $rs->AdminRows();
	return $out;
    }

    // -- WEB UI -- //
}
class fcrUserAcct_admin extends fcrUserAcct implements fiLinkableRecord, fiEditableRecord, fiEventAware {
    use ftShowableRecord;
    use ftSaveableRecord;
    use ftLinkableRecord;
    use ftExecutableTwig;

    // ++ SETUP ++ //

    protected function AdminRows_settings_columns() {
	$arCols = array(
	  'ID'		=> 'ID',
	  'UserName'	=> 'Login',
	  'FullName'	=> 'Full Name',
	  'EmailAddr'	=> 'Email',
	  'WhenCreated'	=> 'Created',
	  '!grps'	=> 'Groups',
	  );
	return $arCols;
    }
    
    // -- SETUP -- //
    // ++ EVENTS ++ //

    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$id = $this->GetKeyValue();
	$sTitle = 'User Account #'.$id;
    
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle($sTitle);
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    public function Render() {
	$rcUser = fcApp::Me()->GetUserRecord();
	if ($rcUser->CanDo(KS_USER_SEC_ACCT_EDIT) || $this->IsLoggedIn()) {
	    $out = $this->AdminPage();
	} else {
	    $out = NULL;	// user doesn't have permission (TODO: tell them they don't have permission)
	}
	return $out;
    }

    // -- EVENTS -- //
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
    // ++ FIELD CALCULATIONS ++ //
    
    // TAGS: ADMIN, TRAIT HELPER, CEMENT
    public function SelfLink_name() {
	$sName = $this->LoginName();
	return $this->SelfLink($sName);
    }

    // -- FIELD CALCULATIONS -- //
    // ++ CALCULATIONS ++ //
    
    protected function IsCurrentUser() {
	$idUser = fcApp::Me()->GetUserID();
	return ($idUser == $this->GetKeyValue());
    }
    
    // -- CALCULATIONS -- //
    // ++ ADMIN UI ++ //

    protected function AdminRows_start() {
	return "\n<table class=listing>";
    }
    protected function AdminField($sField) {
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
	    // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
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
	  $htID = $this->SelfLink();
	  if ($this->IsCurrentUser()) {
	      $htID .= $this->Admin_UserControls();
	  }
	  $arCtrls['ID'] = $htID;
	  $arCtrls['!Groups'] = $this->AdminGroups();

	// render the form
	$oTplt->SetVariableValues($arCtrls);
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
		    
	return $htFormHdr.$htForm.$htFormBtn.$htFormFtr;
    }
    /*----
      RETURNS: controls that the current user should be able to use
	Should include: logout, change password
    */
    protected function Admin_UserControls() {
    }
    // TODO: move to calculations section
    protected function CanAdminThisUser() {
	if ($this->IsCurrentUser()) {
	    return TRUE;	// current user can always admin own account
	} else {
	    $rcCurr = fcApp::Me()->GetCurrentUser();
	    return $rcCurr->CanDo(KS_USER_SEC_ACCT_EDIT);
	}
    }
    protected function AdminPage_SaveGroups() {
	$oFormInput = fcHTTP::Request();
	$arGrps = $oFormInput->GetArray(KS_ACTION_USER_GROUP);
	// $arGrps is formatted like $arGrps[ID] = 'on' for each checked box
	// we could actually use the non-admin version here, but why not reuse a class we're already using:
	$tbl = $this->XGroupTable();
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
	    $oMenu = new fcHeaderMenu();
	      // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	      $oMenu->SetNode(new fcMenuOptionLink('edit.grp',TRUE,'edit'));
	      
	    if ($rs->HasRows()) {
		$out = $rs->RenderInlineList();
	    } else {
		$out = '<i>(none)</i>';
	    }
	    $out .= ' : ['.$oMenu->Render().']';
	}
	return $out;
    }

    // -- ADMIN UI -- //
}