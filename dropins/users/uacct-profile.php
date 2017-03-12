<?php
/*
  PURPOSE: User Profile Page, i.e. stuff about the current user that the current user
    can always see, presented in a more non-adminly kind of way
  HISTORY:
    2017-02-10 Started to create, then realized it's more than what I actually need.
*/
class fcrUserAcct_profile extends fcrUserAcct_admin {
    protected function AdminProfile() {
	$oMenu = new fcHeaderMenu();
	$urlBase = 
	$arContext = array('context'=>'profile');
	    // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	  $oMenu->SetNode($ol = new fcMenuOptionLink('edit'));
	    $ol->SetBasePath($this->SelfURL());
	    $ol->AddLinkArray($arContext);
	$oHdr = new fcSectionHeader('User Profile',$oMenu);
    
	$oLogin = fcApp::Me()->GetPageObject()->GetElement_LoginWidget();	// for now; render actual menu too, later
	$htLogout = $oLogin->Render_LogoutRequestControl();
	
	$oTplt = $this->ProfileTemplate();
	  // custom vars
	  $arCtrls['!logout'] = $htLogout;
	  $arCtrls['Name'] = $this->FullName(FALSE);

	// render the form
	$oTplt->VariableValues($arCtrls);
	$htForm = 
	  $oHdr->Render()
	  .$oTplt->Render()
	  ;
	return $htForm;
    }
    private $tpProfile;
    protected function ProfileTemplate() {
	if (empty($this->tpProfile)) {
	    $sTplt = <<<__END__
<table class=form-record>
  <tr><td><span class='field-label'>Actions</span>:</td><td class=field-value>[{{!logout}}]</td></tr>
  <tr><td><span class='field-label'>Full Name</span>:</td><td class=field-value>{{Name}}</td></tr>
</table>
__END__;
	    $this->tpProfile = new fcTemplate_array('{{','}}',$sTplt);
	}
	return $this->tpProfile;
    }
}