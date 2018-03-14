<?php namespace ferreteria\mw;
/*
  PURPOSE: Kiosk class for MediaWiki - MW strips out the base URL, so we don't need to track that
  HISTORY:
    2018-03-12 created
*/
class fcMenuKiosk_MW extends \fcMenuKiosk {
    use \ftMenuKiosk_admin;
    
    private $fpInfo;
    public function SetInputString($fp) {
	$this->fpInfo = $fp;
    }
    public function GetInputString() {
	return $this->fpInfo;
    }
}
