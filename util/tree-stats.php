<?php
/*
  PURPOSE: for managing statistics about trees
  HISTORY:
    2018-02-07 adapting from code in VbzCart which looks like it was still in use
      but I don't know how well it was actually working
*/
class fcTreeStatsMgr {
    private $arStat;
    private $sStatClass;

    public function __construct($sStatClass) {
	$this->arStat = array();
	$this->sStatClass = $sStatClass;
    }
    public function IndexExists($id) {
	return array_key_exists($id,$this->arStat);
    }
    public function StatFor($id) {
	if (!$this->IndexExists($id)) {
	    $obj = new $this->sStatClass;
	    $this->arStat[$id] = $obj;
	}
	return $this->arStat[$id];
    }
}
