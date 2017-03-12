<?php
/*
  HISTORY:
    2014-09-18 Created in order to allow administering of client records.
*/

class fctUserClientsAdmin extends fctUserClients {

    // ++ SETUP ++ //

    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_CLIENT;
    }
    
    // -- SETUP -- //
    // ++ CALLBACKS ++ //
    
    public function MenuExec() {
	return $this->AdminListing();
    }

    // -- CALLBACKS -- //
    // ++ WEB UI ++ //

    protected function AdminListing() {
	$rs = $this->SelectRecords();
	$arCols = array(
	  'ID'		=> 'ID',
	  'Address'	=> 'Address',
	  'Domain'	=> 'Domain',
	  'Browser'	=> 'Browser',
	  'WhenFirst'	=> 'First',
	  'WhenFinal'	=> 'Final',
	  );
	$out = $rs->AdminRows($arCols);
	return $out;
    }

    // -- WEB UI -- //
}
class fcrUserClientAdmin extends fcrUserClient {
    use ftShowableRecord;

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