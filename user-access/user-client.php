<?php

class clsUserClients extends clsTable {
    const TableName='user_client';

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name(self::TableName);
	  $this->KeyName('ID');
	  $this->ClassSng('clsUserClient');
    }
}
class clsUserClient extends clsDataSet {

    // ++ SETUP ++ //

    public function InitNew() {
	$this->ID = NULL;
	$this->Address = $_SERVER["REMOTE_ADDR"];
	$this->Browser = $_SERVER["HTTP_USER_AGENT"];
	$this->Domain = gethostbyaddr($this->Address);
	$this->CRC = crc32($this->Address.' '.$this->Browser);
	$this->isNew = TRUE;
    }

    // -- SETUP -- //
    // ++ FIELD CALCULATIONS ++ //

    public function IsValidNow() {
	return (($this->Address == $_SERVER["REMOTE_ADDR"]) && ($this->Browser == $_SERVER["HTTP_USER_AGENT"]));
    }

    // -- FIELD CALCULATIONS -- //
    // ++ ACTIONS ++ //

    public function Stamp() {
	$this->Update(array('WhenFinal'=>'NOW()'));
    }
    public function Build() {
    // update existing record, if any, or create new one
	$sql = 'SELECT * FROM '.clsUserClients::TableName.' WHERE CRC="'.$this->CRC.'";';
	$this->Query($sql);
	if ($this->hasRows()) {
	    $this->NextRow();	// get data
	    $this->isNew = FALSE;
	} else {
	    $strDomain = $this->objDB->SafeParam($this->Domain);
	    $strBrowser = $this->objDB->SafeParam($this->Browser);
	    /*
	    $sql = 'INSERT INTO `'.clsShopClients::TableName.'` (CRC, Address, Domain, Browser, WhenFirst)'
	    .' VALUES("'.$this->CRC.'", "'.$this->Address.'", "'.$strDomain.'", "'.$strBrowser.'", NOW());';
	    $this->objDB->Exec($sql);
	    */
	    $ar = array(
	      'CRC'		=> SQLValue($this->CRC),
	      'Address'		=> SQLValue($this->Address),
	      'Domain'		=> '"'.$strDomain.'"',
	      'Browser'		=> '"'.$strBrowser.'"',
	      'WhenFirst'	=> 'NOW()'
	      );
	    $this->ID = $this->Table->Insert($ar);
	    //$this->ID = $this->objDB->NewID('client.make');
	}
    }

    // -- ACTIONS -- //

}
