<?php
/*
  FILE: app.php
  PURPOSE: generic/abstract application framework
  DEPENDS ON DB V1 -- Ferreteria badly needs to be split into separate branches in Git... or maybe the DB functions need to be a separate project...
  HISTORY:
    2015-09-24 extracted clsDatabase_UserAuth from app.php to here (app-user.php)
*/

/*%%%%
  PURPOSE: Database which includes tables for basic user authorization
*/
class clsDatabase_UserAuth extends clsDatabase {
    private $objApp;

    public function App(clsApp $iApp=NULL) {
	if (!is_null($iApp)) {
	    $this->objApp = $iApp;
	}
	return $this->objApp;
    }
/* 2014-01-11 for now, this is deprecated; use clsApp
    public function Users($id=NULL) {
	return $this->Make('clsWebUsers',$id);
    }
*/
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
