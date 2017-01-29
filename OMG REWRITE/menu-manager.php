<?php
/*
  PURPOSE: class for keeping track of menu helper objects (currently Mapper and Painter)
  HISTORY:
    2016-01-02 started
    2016-01-03 Mapper, Painter, Root
*/

class fcMenuMgr {
    
    public function __construct(fcMenuMap $oMap, fcMenuPainter $oPaint) {
	$this->Mapper($oMap);
	$this->Painter($oPaint);
    }
    
    private $oMenuMap;
    public function Mapper(fcMenuMap $o=NULL) {
	if (!is_null($o)) {
	    $this->oMenuMap = $o;
	    $o->Manager($this);	// point Map back to Manager
	}
	return $this->oMenuMap;
    }
    private $oMenuPaint;
    public function Painter(fcMenuPainter $o=NULL) {
	if (!is_null($o)) {
	    $this->oMenuPaint = $o;
	    $o->Manager($this);	// point Painter back to Manager
	}
	return $this->oMenuPaint;
    }
    private $oMenuRoot;
    public function Root(fcMenuRoot $o=NULL) {
	if (!is_null($o)) {
	    $this->oMenuRoot = $o;
	    $o->ManagerObject($this);	// point Root back to Manager
	}
	return $this->oMenuRoot;
    }
}