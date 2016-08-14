<?php
/*
  PURPOSE: PHP classes for emitting JavaScript
    This page emits the JS that handles the FRONT end of AJAX transactions - initiating processes
      and receiving status updates in AJAX format.
  HISTORY:
    2015-12-17 adapting from prototype code in vbz/dropins/cat-local/maint.php
*/

class fcJavaScript {
    /*----
      INPUT: 
	$sidShow	= DOM ID of element to display output from the process
	$sidStatus	= DOM ID of element to display comm status
	$sidChk		= DOM ID of checkbox to turn updating on/off
      RETURNS: calculated JavaScript to emit
      RULES: 
	Before setting controls, we have to emit the HTML for those controls.
	We have to load all the functions before we can pass any arguments to them.
	Menu-item JavaScript must be emitted *after* the menu-item HTML *and* the JS functions.
    */
    static public function StatusUpdater_Controls($sidShow,$sidStatus,$sidChk) {
    
	// ACTION: set the controls for the status updater to use
	// INPUTS: $sidShow, $sidStatus, $sidChk
	// OUTPUT: $jsOut
	include 'status-updater-pass-ctrls.js';
	return $jsOut;
    }
    static public function StatusUpdater_Functions() {
	// ACTION: load JavaScript functions
	// OUTPUT: $jsOut
  	include 'status-updater-fx.js';
  	return $jsOut;
    }
    /*----
      PURPOSE: links a menu item DOM ID with URLs to run and check a particular process
      INPUTS:
	$sidClick	= DOM ID of element that initiates process if clicked
	$urlStart	= URL to start the process
	$urlCheck	= URL to check on the process
      RETURNS: calculated JavaScript to emit
    */
    static public function StatusUpdater_MenuItem($sidClick,$urlStart,$urlCheck) {
    
	// INPUTS: $sidClick, $urlStart, $urlCheck
	// OUTPUT: $jsOut
	// calls RunOnClick()
  	include 'status-updater-pass-menu-item.js';
  	$out = $jsOut;
  	
	return $out;
    }
}