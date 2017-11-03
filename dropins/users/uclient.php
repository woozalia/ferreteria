<?php
/*
  HISTORY:
    2014-09-18 Created in order to allow administering of client records.
*/

class fctUserClientsAdmin extends fctUserClients implements fiEventAware, fiLinkableTable {
    use ftLinkableTable;
    use ftExecutableTwig;

    // ++ SETUP ++ //

    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_CLIENT;
    }
    public function GetActionKey() {
	return KS_ACTION_USER_CLIENT;
    }
    
    // -- SETUP -- //
    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle('User Clients');
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    public function Render() {
	return $this->AdminListing();
    }
    /*
    public function MenuExec() {
	return $this->AdminListing();
    } */

    // -- EVENTS -- //
    // ++ WEB UI ++ //

    protected function AdminListing() {
	$rs = $this->SelectRecords();
	$out = $rs->AdminRows();
	return $out;
    }

    // -- WEB UI -- //
}
class fcrUserClientAdmin extends fcrUserClient {
    use ftShowableRecord;

    // ++ SETUP ++ //

    protected function AdminRows_settings_columns() {
	$arCols = array(
	  'ID'		=> 'ID',
	  'Address'	=> 'Address',
	  'Domain'	=> 'Domain',
	  'Browser'	=> 'Browser',
	  'WhenFirst'	=> 'First',
	  'WhenFinal'	=> 'Final',
	  );
	return $arCols;
    }

    // ++ BOILERPLATE ++ //

    public function AdminLink($iText=NULL,$iPopup=NULL,array $iarArgs=NULL) {
	return clsMenuData_helper::_AdminLink($this,$iText,$iPopup,$iarArgs);
    }

    // -- BOILERPLATE -- //
    // ++ FIELD ACCESS ++ //

    public function BrowserString() {
	return $this->GetFieldValue('Browser');
    }
    public function DomainString() {
	return $this->GetFieldValue('Domain');
    }
    public function AddressString() {
	return $this->GetFieldValue('Address');
    }
    public function SourceShort() {
	$sDom = $this->DomainString();
	if (is_null($sDom)) {
	    return $this->AddressString();
	} else {
	    return $sDom;
	}
    }

    // -- FIELD ACCESS -- //
}