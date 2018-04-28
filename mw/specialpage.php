<?php namespace ferreteria\mw;
/*
  PURPOSE: MediaWiki SpecialPage descendant that interfaces with Ferreteria
  HISTORY:
    2017-12-01 starting from scratch to replace SpecialPageApp, which doesn't make sense anymore
*/
// USAGE: create a SpecialPage descendant class, and apply this trait.
trait tSpecialPage {
    function execute( $subPage ) {
	$oApp = fcApp_MW::Make();
	$oKiosk = $oApp->GetKioskObject();
	$oKiosk->SetInputString($subPage);
	$mwTitle = $this->getTitle(FALSE);	// FALSE = base Title object (no subpage)
	$urlThis = $mwTitle->getLocalURL();
	$oKiosk->SetPagePath($urlThis);
	$this->Go();
    }
    abstract protected function Go();
}