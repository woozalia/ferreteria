<?php
/*
  FILE: app.php
  PURPOSE: generic/abstract application framework
  DEPENDS ON DB V1 -- Ferreteria badly needs to be split into separate branches in Git... or maybe the DB functions need to be a separate project...
  HISTORY:
    2015-09-24 extracted clsDatabase_UserAuth from app.php to here (app-user.php)
    2016-10-01 Attempting to convert to db.v2
*/

/*%%%%
  PURPOSE: Database Factory which includes tables for basic user authorization
*/
class fcDBOF_UserAuth extends fcDBOFactory {

//    private $objApp;
    public function App(clsApp $iApp=NULL) {
	throw new exception('db->App() is deprecated -- call {app class}::Me() instead.');
	if (!is_null($iApp)) {
	    $this->objApp = $iApp;
	}
	return $this->objApp;
    }
    public function Sessions($id=NULL) {
	return $this->Make(KS_CLASS_USER_SESSIONS,$id);
    }
    public function Clients($id=NULL) {
	return $this->Make('clsUserClients',$id);
    }
    public function EmailAuth() {
	return $this->Make('clsUserTokens');
    }
}
