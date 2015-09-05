<?php
/*
  HISTORY:
    2014-09-18 Created in order to allow administering of client records.
*/

class actUserClients extends clsUserClients {
    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->ClassSng(KS_CLASS_ADMIN_USER_CLIENT);
    }
}
class acrUserClient extends clsUserClient {

    // ++ BOILERPLATE ++ //

    public function AdminLink($iText=NULL,$iPopup=NULL,array $iarArgs=NULL) {
	return clsMenuData_helper::_AdminLink($this,$iText,$iPopup,$iarArgs);
    }

    // -- BOILERPLATE -- //
    // ++ FIELD ACCESS ++ //

    public function BrowserString() {
	return $this->Value('Browser');
    }
    public function DomainString() {
	return $this->Value('Domain');
    }
    public function AddressString() {
	return $this->Value('Address');
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