<?php
/*
  PURPOSE: returns JavaScript code in $sJS to create various functions to make the status updater work
    Needs to be surrounded by <script> tags before being emitted.
  NOTES:
    * Must run status-updater-pass-ctrls.js first.
    * The file has a .js extension so that editors will render the JavaScript properly,
    even though overall it is technically PHP.
  HISTORY:
    2016-01-31 reorganizing JS files
*/
$jsOut = <<<__END__

ctStatus.innerHTML = "Loading JavaScript functions...";

// ++ GLOBALS ++ //

var arMenu = [];

// -- GLOBALS -- //
// ++ API FUNCTIONS ++ //

function RunOnClick(sid,argUrlStart,argUrlCheck,argFx='') {
    // apparently foMenuItem has to be declared before this function is allowed to mention it.
    oMenuItem = new foMenuItem(sid);
    oMenuItem.SetURLs(argUrlStart,argUrlCheck);
    if (argFx != '') {
	oMenuItem.StartProcess = argFx;
    };
    arMenu[sid] = oMenuItem;
};

// -- API FUNCTIONS -- //
// ++ CLASS CODE ++ //

// CONSTRUCTOR
var foMenuItem = function(sid) {
  this.sid = sid;
  
  ct = document.getElementById(sid);
  ct.addEventListener('click', evLinkClick);
  //ct.onclick = function(event) { return evLinkClick(ct,event); };
  //ct.addEventListener('click', function(event) { return evLinkClick(ct,event); } );
  this.ctrl = ct;
};
foMenuItem.prototype.Click = function() { 
  // TODO: possibly we should only reset the timer if it's not already going
  ResetTimer();
  this.StartProcess();
};
foMenuItem.prototype.SetURLs = function(argUrlStart,argUrlCheck) {
  this.urlStart = argUrlStart;
  this.urlCheck = argUrlCheck;

// TODO: if argUrlCheck is '', we need to (1) turn off the checkbox and (2) not start the timer.
  
};
foMenuItem.prototype.StartProcess = function() {
  console.log("Requesting URL [ "+this.urlStart+" ]...");
  req.open('get', this.urlStart, true);
  req.send();
  urlCheck = this.urlCheck;	// set global URL to check
};

// -- CLASS CODE -- //
// ++ EVENT HANDLERS ++ //

/*
 * EVENT: when a menu item is clicked
 * event.target is the object for the clicked HTML element
 * event.target.id is the DOM ID of the clicked link
 */
function evLinkClick(event) {
  console.log('CONTROL ID: '+event.target.id);
  sID = event.target.id;
  oMenuItem = arMenu[sID];
  chkRun.checked = true;	// make sure we see status updates
  oMenuItem.StartProcess();
  //sUrlStart = oMenuItem.urlStart;
  // TODO: actually set the URLs from wherever they're supposed to be set
  // return 
};

// EVENT: when the "run" checkbox is clicked
function evRunClicked(event) {
  if (chkRun.checked) {
    // if it was just turned on, restart the timer
    ResetTimer();
    console.log("Clearing output area.");
    ctMain.innerHTML = "";	// clear the output
  };
};

function evPageLoaded(event) {
  if (chkRun.checked) {
    ResetTimer();
  };
  ctStatus.innerHTML = 'packet complete';
  sNew = HandleUpdatePacket(req.responseText,ctMain.innerHTML);	// process the packet
  ctMain.innerHTML = sNew;
};
function evPageUpdate(event) {
  // only parse complete packets - do nothing yet
  ctStatus.innerHTML = 'packet updated';
};
function evPageFailed(event) {
  // only parse complete packets - do nothing yet
  ctStatus.innerHTML = 'Houston, we have a problem.';
};
function evPageKilled(event) {
  // only parse complete packets - do nothing yet
  ctStatus.innerHTML = 'Retrying...';
};

// -- EVENT HANDLERS -- //
// ++ PROCESSING ++ //

/*----
  INPUT:
    sJSON: received JSON packet to parse
    sBase: current display contents, to be modified by received packet
  RETURNS: new contents to display
*/
function HandleUpdatePacket(sJSON,sBase) {
  arIn = JSON.parse(sJSON);
  sType = arIn['type'];
  sText = arIn['text'];
  bDone = arIn['end'];
  if (bDone) {
      chkRun.checked = false;	// stop updating
  };
  switch (sType) {
    case "new":
      sOut = sText;
      break;
    case "same":
      sOut = sBase;
      break;
    case "after":
      sOut = sBase + sText;
      break;
    case "before":
      sOut = sText + sBase;
      break;
    default:
      sOut = "";
  }
  console.log("Received: ["+sType+"] text ["+sText+"]");
  if (sBase == sOut) {
      console.log('(no change)');
  } else {
      console.log('WAS: ['+sBase+']');
      console.log('NOW: ['+sOut+']');
  }
  return sOut;
};
function ResetTimer() {
  timeoutID = window.setTimeout(evCheck, 500);
  //console.log("Timer set...");
};

function evCheck() {
  //console.log("Timeout triggered...");
  req.open('get', urlCheck, true);
  req.send();
  req.addEventListener("load", evPageLoaded);
};

// -- PROCESSING -- //
// ++ UTILITY FX ++ //

function ResetDisplay() {
    ctStatus.innerHTML = '';
}

// -- UTILITY FX -- //
// ++ MAIN ENTRY ++ //

ctStatus.innerHTML = "Setting up event handlers...";

chkRun.addEventListener('click', evRunClicked);

var req = new XMLHttpRequest();

req.addEventListener("progress", evPageUpdate);
req.addEventListener("load", evPageLoaded);
req.addEventListener("error", evPageFailed);
req.addEventListener("abort", evPageKilled);

ctStatus.innerHTML = "Ready to start.";

// -- MAIN ENTRY -- //
__END__;
