<?php

abstract class fcDataTable {

    // ++ SETUP ++ //

    public function __construct(fcDataConn $oConn) {
	$this->InitMe();
	$this->ConnObject($oConn);
    }
    protected function InitMe() {
	$this->TableName($this->DefaultTableName());
	$this->SingularName($this->DefaultSingularName());
    }
    
    // -- SETUP -- //
    // ++ CONFIGURATION ++ //
    
      // connection object
    private $oConn;
    protected function ConnObject(fcDataConn $oConn=NULL) {
	if (!is_null($oConn)) {
	    $this->oConn = $oConn;
	}
	return $this->oConn;
    }
      // table name
    private $sTName;
    protected function TableName($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sTName = $sName;
	}
	return $this->sTName;
    }
    abstract protected function DefaultTableName();
      // singular class
    private $sCSing;
    protected function SingularName($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sCSing = $sName;
	}
	return $this->sCSing;
    }
    abstract protected function DefaultSingularName();
    
    // -- CONFIGURATION -- //
}