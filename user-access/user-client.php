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
	$sAddr = clsHTTP::ClientAddress_string();
	$sAgent = clsHTTP::ClientBrowser_string();
	$arInit = array(
	  'ID'		=> NULL,
	  'Address'	=> $sAddr,
	  'Browser'	=> $sAgent,
	  'Domain'	=> gethostbyaddr($sAddr),
	  'CRC'		=> crc32($sAddr.' '.$sAgent)
	  );
	$this->Values($arInit);
	//$this->isNew = TRUE;	// 2015-02-17 not used in this file; is it used elsewhere? Replace with method.
    }

    // -- SETUP -- //
    // ++ FIELD VALUES ++ //

    protected function AddressString() {
	return $this->Value('Address');
    }
    protected function BrowserString() {
	return $this->Value('Browser');
    }
    protected function DomainString() {
	return $this->Value('Domain');
    }
    protected function CRC() {
	return $this->Value('CRC');
    }

    // ++ FIELD VALUES ++ //
    // ++ FIELD CALCULATIONS ++ //

    public function IsValidNow() {
	return (
	  ($this->AddressString() == $_SERVER["REMOTE_ADDR"]) &&
	  ($this->BrowserString() == $_SERVER["HTTP_USER_AGENT"])
	  );
    }

    // -- FIELD CALCULATIONS -- //
    // ++ ACTIONS ++ //

    public function Stamp() {
	$this->Update(array('WhenFinal'=>'NOW()'));
    }
    public function Build() {
    // update existing record, if any, or create new one
	$sql = 'SELECT * FROM '.$this->Table()->NameSQL().' WHERE CRC="'.$this->CRC().'";';
	$this->Query($sql);
	if ($this->hasRows()) {
	    $this->NextRow();	// get data
	    //$this->isNew = FALSE;
	} else {
	    $sDomain = $this->Engine()->SafeParam($this->DomainString());
	    $sBrowser = $this->Engine()->SafeParam($this->BrowserString());
	    /*
	    $sql = 'INSERT INTO `'.clsShopClients::TableName.'` (CRC, Address, Domain, Browser, WhenFirst)'
	    .' VALUES("'.$this->CRC.'", "'.$this->Address.'", "'.$strDomain.'", "'.$strBrowser.'", NOW());';
	    $this->objDB->Exec($sql);
	    */
	    $ar = array(
	      'CRC'		=> SQLValue($this->CRC()),
	      'Address'		=> SQLValue($this->AddressString()),
	      'Domain'		=> SQLValue($sDomain),
	      'Browser'		=> SQLValue($sBrowser),
	      'WhenFirst'	=> 'NOW()'
	      );
	    $this->KeyValue($this->Table()->Insert($ar));
	}
    }

    // -- ACTIONS -- //

}
