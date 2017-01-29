<?php
/*
  PURPOSE: an exception type for silently logging/emailing errors instead of giving hackers a stack dump
  HISTORY:
    2016-11-14 created
*/
abstract class fcExceptionBase extends exception {

    // ++ SETUP ++ //

    public function __construct ($sMsg = "") {
	parent::__construct($sMsg);
    }
    /* 2016-11-20 Not sure if this is worth finishing.
    static public function WrapNativeException(exception $e) {
	$sClass = get_class($this);
	$ef = new $sClass($e->getMessage());
	$ef->
    }*/
    
    // -- SETUP -- //
    // ++ ABSTRACT ++ //
    
    // Ferreteria will call this when an exception is caught:
    abstract public function React();
    
    // -- ABSTRACT -- //
    // ++ REACTIONS ++ //

    protected function DoShowException() {
	$arErr = $this->GetSpecArray();
	echo $this->Exception_Message_toShow($sMsgErr);		// display something for the guest
	die();	// or just throw it again, but that gives us less control
    }
    protected function DoEmailException() {
	$arErr = $this->GetSpecArray();
	$sMsg = $this->Exception_Message_toEmail($arErr);	// generate the message to email
	$sSubj = $this->Exception_Subject_toEmail($arErr);	// generate subject for email
	
	// this still needs adapting
	$this->App()->DoEmail_Auto(
	  KS_TEXT_EMAIL_ADDR_ERROR,
	  KS_TEXT_EMAIL_NAME_ERROR,
	  $sSubj,$sMsg);

    }
    protected function DoLogException() {
    }

    // -- REACTIONS -- //
    // ++ CALCULATION ++ //

    protected function GetSpecArray() {
	$arErr = array(
	  'descr'	=> $this->getMessage(),
	  'stack'	=> $this->getTraceAsString(),
	  'guest.addr'	=> $_SERVER['REMOTE_ADDR'],
	  'guest.agent'	=> $_SERVER['HTTP_USER_AGENT'],
	  'guest.ref'	=> fcArray::Nz($_SERVER,'HTTP_REFERER'),
	  'guest.page'	=> $_SERVER['REQUEST_URI'],
	  'guest.ckie'	=> fcArray::Nz($_SERVER,'HTTP_COOKIE'),
	  );
	return $arErr;
    }

    // -- CALCULATION -- //
}
class fcDebugException extends exception {
    public function React() {
	$this->DoShowException();
	$this->DoEmailException();
	$this->DoLogException();
    }
}
class fcSilentException extends exception {
    public function React() {
	$this->DoEmailException();
	$this->DoLogException();
    }
}