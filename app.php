<?php
/*
  FILE: app.php
  PURPOSE: generic/abstract application framework
  HISTORY:
    2012-05-08 split off from store.php
    2012-05-13 removed clsPageOutput (renamed clsPageOutput_WHO_USES_THIS some time ago)
    2013-09-16 clsVbzSkin_Standard renamed clsVbzPage_Standard
    2013-09-17 clsVbzPage_Standard renamed clsVbzPage_Browse
    2013-10-23 stripped for use with ATC app (renamed as app.php)
    2013-11-11 re-adapted for general library
*/

/*%%%%
  CLASS: clsApp
  PURPOSE: base class -- container for the application
*/
abstract class clsApp {
    static protected $me;
    static public function Me(clsApp $oApp=NULL) {
	if (!is_null($oApp)) {
	    self::$me = $oApp;
	}
	return self::$me;
    }
    public function __construct() {
	self::Me($this);			// there can be only one
    }
    abstract public function Go();
    abstract public function Session();
    abstract public function Skin();
    abstract public function Page(clsPage $iObj=NULL);
    abstract public function Data(clsDatabase $iObj=NULL);
    abstract public function User();
}
abstract class cAppStandard extends clsApp {
    private $oPage;
    private $oSkin;
    private $oData;

    // ++ MAIN ++ //

    public function Go() {
	$oData = $this->Data();
	$oData->Open();
	if ($oData->isOk()) {
	    $this->Page()->DoPage();
	    $oData->Shut();
	} else {
	    throw new exception('Could not open the database.');
	}
    }

    // -- MAIN -- //
    // ++ CLASS NAMES ++ //

    protected function SessionsClass() {
	return 'clsUserSessions';
    }

    // -- CLASS NAMES -- //
    // ++ DATA TABLE ACCESS ++ //

    protected function SessionTable() {
	return $this->Data()->Make($this->SessionsClass());
    }

    // -- DATA TABLE ACCESS -- //
    // ++ FRAMEWORK OBJECTS ++ //

    public function Page(clsPage $obj=NULL) {
	if (!is_null($obj)) {
	    $this->oPage = $obj;
	    $obj->App($this);
	    //$obj->Doc($oDoc);
	}
	return $this->oPage;
    }
    public function Skin(clsSkin $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->oSkin = $iObj;
	}
	return $this->oSkin;
    }
    public function Data(clsDatabase $iObj=NULL) {
	if (!is_null($iObj)) {
	    $this->oData = $iObj;
	}
	return $this->oData;
    }
    public function Session() {
	$tSess = $this->Data()->Sessions();
	$oSess = $tSess->GetCurrent();
	return $oSess;
    }
    public function Users($id=NULL) {
	return $this->Make($this->UsersClass(),$id);
    }
    public function User() {
	return $this->Session()->UserRecord();
    }
    /*----
      RETURNS: TRUE iff the user is logged in
    */
    public function UserKnown() {
	return (!is_null($this->Session()->UserRecord()));
    }
    /*----
      RETURNS: User login string, or NULL if user not logged in
      HISTORY:
	2014-07-27 Written because this seems to be where it belongs.
	  May duplicate functionality in Page object. Why is that there?
    */
    public function UserName() {
	if ($this->UserKnown()) {
	    return $this->User()->UserName();
	} else {
	    return NULL;
	}
    }
    public function Events($id=NULL) {
	return $this->Data()->Make($this->EventsClass(),$id);
    }

    // -- FRAMEWORK OBJECTS -- //
    // ++ FRAMEWORK CLASSES ++ //

    /*----
      USAGE: Override this if a different (descendant) User class is needed.
    */
    public function UsersClass() {
	return 'clsUserAccts';
    }
    public function EventsClass() {
	return 'clsSysEvents';
    }

    // -- FRAMEWORK CLASSES -- //

    protected function EmailAddr_FROM($sTag) {
	$ar = array('tag'=>$sTag);
	$tplt = new clsStringTemplate_array(KS_TPLT_OPEN,KS_TPLT_SHUT,$ar);

	return $tplt->Replace(KS_TPLT_EMAIL_ADDR_ADMIN);
    }
    /*----
      ACTION: send an automatic email (i.e. not a message from an actual person)
      USAGE: called from clsEmailAuth::SendPassReset_forAddr()
    */
    public function DoEmail_Auto($sToAddr,$sToName,$sSubj,$sMsg) {
	if (empty($sToName)) {
	    $sAddrToFull = $sToAddr;
	} else {
	    $sAddrToFull = $sToName.' <'.$sToAddr.'>';
	}

	$sHdr = 'From: '.$this->EmailAddr_FROM(date('Y'));
	$ok = mail($sAddrToFull,$sSubj,$sMsg,$sHdr);
	return $ok;
    }
}

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
	return $this->Make('clsUserSessions',$id);
    }
    public function Clients($id=NULL) {
	return $this->Make('clsUserClients',$id);
    }
    public function EmailAuth() {
	return $this->Make('clsUserTokens');
    }
}
