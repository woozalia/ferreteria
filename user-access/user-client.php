<?php

class clsUserClients extends fcTable_keyed_single_standard {
//    const TableName='user_client';
/* 2016-10-27 reorganized
    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name(self::TableName);
	  $this->KeyName('ID');
	  $this->ClassSng('clsUserClient');
    }
*/    
    // ++ CEMENTING ++ //
    
    protected function TableName() {
	return 'user_client';
    }
    protected function SingularName() {
	return 'clsUserClient';
    }

    // -- CEMENTING -- //
    // ++ ENVIRONMENT ++ //

    static protected function ActiveCRC() {
	$sAddr = self::ActiveAddress();
	$sAgent = self::ActiveBrowser();
	$nCRC = crc32($sAddr.' '.$sAgent);
	$sCRC = sprintf('%u',$nCRC);	// make sure is unsigned
	return $sCRC;
    }
    static protected function ActiveAddress() {
	return fcHTTP::ClientAddress_string();
    }
    static protected function ActiveBrowser() {
	return fcHTTP::ClientBrowser_string();
    }
    static protected function ActiveDomain() {
	return gethostbyaddr(self::ActiveAddress());
    }

    // -- ENVIRONMENT -- //
    // ++ RECORDS ++ //
    
    public function MakeRecord_forCRC() {
	$sCRC = self::ActiveCRC();
	$rc = $this->SelectRecords("CRC='$sCRC'");
	if ($rc->HasRows()) {
	    // the current browser already has a session record
	    $rc->NextRow();	// get first row (should be the only one)
	} else {
	    // need to create a new session record
	    $db = $this->GetConnection();
	    $sqlAddress = $db->Sanitize_andQuote(self::ActiveAddress());
	    $sqlDomain = $db->Sanitize_andQuote(self::ActiveDomain());
	    $sqlBrowser = $db->Sanitize_andQuote(self::ActiveBrowser());
	    $ar = array(
	      'CRC'		=> $db->Sanitize_andQuote($sCRC),
	      'Address'		=> $sqlAddress,
	      'Domain'		=> $sqlDomain,
	      'Browser'		=> $sqlBrowser,
	      'WhenFirst'	=> 'NOW()'
	      );
	    $idNew = $this->Insert($ar);
	    if ($idNew === FALSE) {
		echo 'CLIENT RECORD TO ADD:'.fcArray::Render($ar);
		echo 'SQL: '.$this->sql.'<br>';
		throw new exception('Could not insert new client record.');
	    }
	    $rc = $this->GetRecord_forKey($idNew);
	}
	return $rc;
    }

    // -- RECORDS -- //

}
class clsUserClient extends fcRecord_standard {

    // ++ SETUP ++ //

    public function InitNew() {
	$sAddr = fcHTTP::ClientAddress_string();
	$sAgent = fcHTTP::ClientBrowser_string();
	$nCRC = crc32($sAddr.' '.$sAgent);
	$sCRC = sprintf('%u',$nCRC);	// make sure is unsigned
	$arInit = array(
	  'ID'		=> NULL,
	  'Address'	=> $sAddr,
	  'Browser'	=> $sAgent,
	  'Domain'	=> gethostbyaddr($sAddr),
	  'CRC'		=> $sCRC
	  );
	$this->ClearFields();
	$this->SetFieldValues($arInit);
    }

    // -- SETUP -- //
    // ++ FIELD VALUES ++ //

    protected function AddressString() {
	return $this->GetFieldValue('Address');
    }
    protected function BrowserString() {
	return $this->GetFieldValue('Browser');
    }
    protected function DomainString() {
	return $this->Value('Domain');
    }
    protected function CRC() {
	return $this->GetFieldValue('CRC');
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
    
    /* 2016-10-27 rewrote as a Table method
    public function Build() {
    throw new exception('Who calls this? Having a recordset wrapper query itself seems like bad form.');
    // update existing record, if any, or create new one
	$sql = 'SELECT * FROM '.$this->GetTableWrapper()->TableName_Cooked().' WHERE CRC="'.$this->CRC().'";';
	$this->Query($sql);
	if ($this->hasRows()) {
	    $this->NextRow();	// get data
	    //$this->isNew = FALSE;
	} else {
	    $sDomain = $this->GetConnection()->SafeParam($this->DomainString());
	    $sBrowser = $this->GetConnection()->SafeParam($this->BrowserString());
	    $sCRC = sprintf('%u',$this->CRC());
	    $db = $this->Table()->Engine();
	    $ar = array(
	      'CRC'		=> $db->SanitizeAndQuote($sCRC),
	      'Address'		=> $db->SanitizeAndQuote($this->AddressString()),
	      'Domain'		=> $db->SanitizeAndQuote($sDomain),
	      'Browser'		=> $db->SanitizeAndQuote($sBrowser),
	      'WhenFirst'	=> 'NOW()'
	      );
	    $idNew = $this->Table()->Insert($ar);
	    if ($idNew === FALSE) {
		echo 'CLIENT RECORD TO ADD:'.clsArray::Render($ar);
		echo 'SQL: '.$this->Table()->sqlExec.'<br>';
		throw new exception('Could not insert new client record.');
	    }
	    $this->GetKeyValue($idNew);
	}
    }*/

    // -- ACTIONS -- //

}
