<?php
/*
  LIBRARY: menu.php - a few classes for managing menus
  REQUIRES: richtext.php
  HISTORY:
    2009-08-09 Extracted from SpecialVbzAdmin.php
    2009-11-04 clsArgs
    2010-02-20 clsWikiSection::SectionAdd(), ::ToggleAdd()
    2010-04-06
      * General reconciliation with edited non-dev version
      * Done earlier but not logged here:
	clsWikiSection::ArgsToAdd() (ported from other copy of menu.php)
	clsAdminTable, clsAdminData, clsAdminData_Logged, clsAdminEvents, clsAdminEvent
    2010-06-17 StartEvent() / FinishEvent() now keep the event ID in $this->idEvent
    2010-10-06
      Reconciling with dev:
      * Minor improvements; split() is deprecated; small bugfixes
    2010-10-07 reconstructed clsDataSet::_AdminLink(), which apparently got lost during reconciliation
    2010-10-17 Expanded SpecialPageApp::Args() to allow returning a limited set of arguments
    2013-01-04 clsDataSet isn't in this file, so why is the note about clsDataSet::_AdminLink() here?
    2013-06-02 fixed Values_forLink() - toggles on as well as off now
    2013-06-06 rework of menu link classes complete -- working now
  BUGS:
    2010-09-13 When there are multiple toggles, they don't preserve each other's states
      http://wiki.vbz.net/Special:VbzAdmin/page:order/id:4263/receipt -- "email" cancels out "receipt"
    2012-05-27 lots of tweaks and fixes and some new classes to replace old ones; see code comments
    2012-07-08 explicit prepping of mw.menus module
    2015-07-12 resolving conflicts with other edited version
*/

if (!defined('KS_CHAR_URL_ASSIGN')) {
    define('KS_CHAR_URL_ASSIGN',':');
}

class clsMenu {
    public $Page;
    private $SubNodes;
    private $AllNodes;
    protected $Action;
    protected $Selected;
    protected $isSubActive;

    public function __construct($iWikiPage) {
	$this->Page = $iWikiPage;
	$this->isSubActive = FALSE;
    }
    public function Render($iAction) {
	$this->Action = $iAction;
	if (isset($this->AllNodes[$iAction])) {
	    $this->AllNodes[$iAction]->Activate();
	    $this->isSubActive = TRUE;
	}
	$out = $this->Render_SubMenu($iAction);
	return $out;
    }
    public function Render_SubMenu($iAction) {
	$out = NULL;
	foreach ($this->SubNodes as $objNode) {
	    if (!is_null($out)) {
		$out .= ' | ';
	    }
	    $out .= $objNode->Render($iAction);
	}
	return $out;
    }
    public function Add(clsMenuNode $iNode) {
	$this->SubNodes[$iNode->Name] = $iNode;
	$this->Root()->AllNodes[$iNode->Name] = $iNode;
	$iNode->Parent = $this;
    }
    protected function Root() {
	return $this;
    }
    protected function Activate() {    }	// do nothing
    public function Execute() {
	if (isset($this->Selected)) {
	    $out = $this->Selected->DoAction();
	} else {
	    $out = NULL;
	}
	return $out;
    }
    /*----
      RETURNS: TRUE if the current input has activated a sub-menu
    */
    public function IsSubMenuActive() {
	return 	$this->isSubActive;
    }
}

abstract class clsMenuNode extends clsMenu {
    public $Name;
    protected $Text;
    protected $DoSpec;
    public $Parent;
    protected $IsActive;

    public function __construct($iText, $iDo) {
	$this->Name = $iDo;
	$this->Text = $iText;
	$this->DoSpec = $iDo;
    }
    public function Root() {
	return $this->Parent->Root();
    }
    abstract public function DoAction();
    protected function Activate() {
	$this->IsActive = TRUE;
	$this->Parent->Activate();
    }
    /*----
      HISTORY:
	2016-03-29 This used to insert a '/' before 'page', but now the base URL includes that.
    */
    public function Render($iAction) {
	$wtSelf = $this->Root()->Page;
	//$wtItem = "[[$wtSelf/page".KS_CHAR_URL_ASSIGN."{$this->DoSpec}|{$this->Text}]]";
	$url = $wtSelf.'page'.KS_CHAR_URL_ASSIGN.$this->DoSpec;
	$sText = $this->Text;
	$ftItem = "<a href='$url'>$sText</a>";
//	if ($iAction == $this->DoSpec) {
	if ($this->IsActive) {
	    $out = "<b>$ftItem</b>";
	    $this->Root()->Selected = $this;
	} else {
	    $out = $ftItem;
	}
	return $out;
    }
}

class clsMenuRow extends clsMenuNode {
    public function DoAction() {
        $sText = $this->Text;
	$out = "<br><b>$sText</b>: ";
	$out .= $this->Render_SubMenu($this->Root()->Action);
	return $out;
    }
}

class clsMenuItem extends clsMenuNode {
    public function DoAction() {
// later: actually implement menu item action
    }
}

/*----
  RETURNS: the part of the path *after* the specialpage name
  USED BY: SelfURL(), which is used by clsWikiSection:ActionAdd()
*/
function SelfLink_Path(array $iData) {
    $fp = '';
    foreach ($iData AS $key => $val) {
	if ($val !== FALSE) {
	    if ($val === TRUE) {
		$fp .= '/'.$key;
	    } else {
		$fp .= '/'.$key.KS_CHAR_URL_ASSIGN.$val;
	    }
	}
    }
    $out = $fp;
    return $out;
}
function SelfLink_WT(array $iData,$iShow) {
    if (is_null($iShow)) {
	return NULL;
    } else {
	$strPath = SelfLink_Path($iData);
	return '[[{{FULLPAGENAME}}'.$strPath.'|'.$iShow.']]';
    }
}
/* 2010-10-01 NEED TO KNOW who uses this, so I can fix it
function SelfLink_HTML(array $iData,$iShow,$iPopup=NULL) {
    if (is_null($iShow)) {
	return NULL;
    } else {
	$strPath = SelfLink_Path($iData);
	$htPopup = is_null($iPopup)?'':(' title="'.$iPopup.'"');
	return '<a href="'.$strPath.'"'.$htPopup.'>'.$iShow.'</a>';
    }
}
*/

// TABBED CONTENTS
/*
  NOTES: There's probably a better way of doing this (e.g. using the same code used by the User Preferences page), but for now
    this uses the Tabber extension.
*/
function TabsOpen() {
    global $wgScriptPath,$wgOut;

    $path = $wgScriptPath . '/extensions/tabber/';
    $htmlHeader = '<script type="text/javascript" src="'.$path.'tabber.js"></script>'
	    . '<link rel="stylesheet" href="'.$path.'tabber.css" TYPE="text/css" MEDIA="screen">'
	    . '<div class="tabber">';
    $wgOut->addHTML($htmlHeader);
}
function TabsShut() {
    global $wgOut;

    $wgOut->addHTML('</div>');
}
function TabOpen($iName) {
    global $wgOut;

    $wgOut->addHTML('<div class="tabbertab" title="'.$iName.'"><p>');
}
function TabShut() {
    global $wgOut;

    $wgOut->addHTML('</p></div>');
}