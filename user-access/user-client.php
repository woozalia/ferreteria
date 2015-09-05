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
	$nCRC = crc32($sAddr.' '.$sAgent);
	$sCRC = sprintf('%u',$nCRC);	// make sure is unsigned
	$arInit = array(
	  'ID'		=> NULL,
	  'Address'	=> $sAddr,
	  'Browser'	=> $sAgent,
	  'Domain'	=> gethostbyaddr($sAddr),
	  'CRC'		=> $sCRC
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
	    $sCRC = sprintf('%u',$this->CRC());
	    $ar = array(
	      'CRC'		=> SQLValue($sCRC),
	      'Address'		=> SQLValue($this->AddressString()),
	      'Domain'		=> SQLValue($sDomain),
	      'Browser'		=> SQLValue($sBrowser),
	      'WhenFirst'	=> 'NOW()'
	      );
	    $idNew = $this->Table()->Insert($ar);
	    if ($idNew === FALSE) {
		echo 'CLIENT RECORD TO ADD:'.clsArray::Render($ar);
		echo 'SQL: '.$this->Table()->sqlExec.'<br>';
		throw new exception('Could not insert new client record.');
	    }
	    $this->KeyValue($idNew);
	}
    }

    // -- ACTIONS -- //

}
