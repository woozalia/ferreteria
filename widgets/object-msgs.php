<?php
/*
  PURPOSE: trait to allow objects to pass messages back to callers
    This is mainly for utility objects that don't talk to the app framework.
    Those that do should go through the App object, which uses this trait.
  HISTORY:
    2016-04-03 extracted code from fcForm (forms.php)
*/

trait ftVerbalObject {
    private $arMsgs;	// list of messages (error etc.) to display

    // ++ API ++ //
    
    public function AddMessage($sText) {
	$this->arMsgs[] = $sText;
    }
    public function MessagesString($sLinePfx='<br>',$sLineSfx=NULL) {
	$out = NULL;
	if (empty($this->arMsgs)) {
	    $this->arMsgs = NULL;
	} elseif (is_array($this->arMsgs)) {
	    foreach ($this->arMsgs as $sText) {
		$out .= $sLinePfx.$sText.$sLineSfx;
	    }
	}
	return $out;
    }
    
    // -- API -- //
    
}