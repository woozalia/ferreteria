<?php
/*
  PURPOSE: Trait to enable Pages to pass arguments in the path part of a URL (instead of using ?queries)
  HISTORY:
    2016-11-20 started
    2016-12-31 this is so almost-redundant now (just one fx())
      TODO: either find an excuse to expand it, or ditch it
    2017-01-03 This can be ditched now.
*/

trait ftPathFragument_NOT_USED {

    // ++ INPUT CALCULATIONS ++ //
    
    // RETURNS: the path fragument (the fragment of the path which contains any argument(s) being passed)
    protected function GetPathFragument() {
    /*
	$wp = fcURL::PathRelativeTo(
	  fcApp::Me()->GetKioskObject()->GetBasePath()
	  );
	return $wp; */
	return fcApp::Me()->GetKioskObject()->GetInputString();
    }
    
    // -- INPUT CALCULATIONS -- //
}