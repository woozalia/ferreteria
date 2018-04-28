<?php
/*
  LIBRARY: classes for managing collections of navigation links
  HISTORY:
    2013-10-08 clsNavBar, clsNavItem, clsNavLink
    2013-10-10 clsNavbar_flat, clsNavbar_tree, clsNavItemText
    2013-11-29 renamed from menu.php to navbar.php
      because proper menus look like they'll need another set of classes
    2016-12-01 (note from @libs.php) replacing all these with page/page.navbar.php
    2018-03-14 The VbzCart checkout process still uses fcNavbar_flat, so I'm reinstating this file for now.
      It should probably be better integrated with page classes.
*/

abstract class fcNavNode extends fcTreeNode {
    private $htClass;	// default item CSS class array

    public function __construct() {
	$this->htClass = NULL;
    }

    public function CSSClass($iClass=NULL,$iState=NULL) {
	$nState = is_null($iState)?0:$iState;
	if (!is_null($iClass)) {
	    $this->htClass[$nState] = $iClass;
	}
	if (is_array($this->htClass)) {
	    if (!array_key_exists($nState,$this->htClass)) {
		if (is_null($this->htClass)) {
		    echo 'No CSS states are available.';
		} else {
		    echo 'CSS states available:<pre>'.print_r($this->htClass,TRUE).'</pre>';
		}
		throw new exception('Attempting to access unknown CSS state ['.$nState.'].');
	    }
	    $sClass = $this->htClass[$nState];
	} else {
	    $sClass = NULL;
	}
	return $sClass;
    }
}

abstract class fcNavbar extends fcNavNode {

    private $sDefaultNode;
    public function SetDefault($sName) {
	$this->sDefaultNode = $sName;
    }
    public function GetDefault() {
	return $this->sDefaultNode;
    }
    
    // ++ OVERRIDE ++ //
    
    public function GetNode($sName) {
	if (empty($sName)) {
	    $sName = $this->GetDefault();
	}
	return parent::GetNode($sName);
    }
    /*
    public function Node($sName, fcTreeNode $oNode=NULL) {
	if (empty($sName)) {
	    $sName = $this->GetDefault();
	}
	return parent::Node($sName,$oNode);
    }*/

    // -- OVERRIDE -- //
    // ++ ABSTRACT ++ //

    /*----
      USAGE: Should only be called by child nodes
    */
    abstract public function AddNavNode(fcNavItem $iNode);
    abstract public function Render();

    // -- ABSTRACT -- //

}

class fcNavbar_flat extends fcNavbar {
    private $sLinkBef,$sLinkAft,$sLinkInt;	// before, after, and in between

    // ASSUMES: $oNode already has a name
    public function AddNavNode(fcNavItem $oNode) {
	$this->SetNode($oNode);
	/* 2016-11-13 SetNode() does this now
	$sName = $oNode->GetName();
	$this->arSubs[$sName] = $iNode;
	$iNode->SetParent($this);
	*/
    }
    /* 2016-11-13 unused and probably the wrong way to do it
    public function AddAfter($iName,fcNavItem $iNode) {
	// there's got to be a better way to insert a keyed array element
	foreach ($this->arSubs as $name => $node) {
	    $arOut[$name] = $node;
	    if ($name == $iName) {
		$arOut[$iNode->Name()] = $iNode;
	    }
	}
	$this->arSubs = $arOut;
    }*/
    /*----
      ACTION: Sets the states for all nodes before and after the one with the given name
    */
    public function States($iName,$iStateBefore,$iStateAfter) {
	$isAfter = FALSE;
	$ar = $this->GetNodes();
	foreach ($ar as $key => $node) {
	    if ($key == $iName) {
		$isAfter = TRUE;
	    } elseif ($isAfter) {
		$node->State($iStateAfter);
	    } else {
		$node->State($iStateBefore);
	    }
	}
    }
    /*----
      INPUT:
	sBefore: string to appear before each link
	sAfter: string to appear after each link
	sInter: string to appear in between links
    */
    public function Decorate($sBefore,$sAfter,$sInter) {
	$this->sLinkBef = $sBefore;
	$this->sLinkAft = $sAfter;
	$this->sLinkInt = $sInter;
    }
    public function Render() {
	$ar = $this->GetNodes();
	$ht = NULL;
	if (is_array($ar)) {
	    foreach ($ar as $key => $node) {
		if (!is_null($ht)) {
		    $ht .= $this->sLinkInt;
		}
		$ht .= $this->sLinkBef.$node->Render().$this->sLinkAft;
	    }
	}
	return $ht;
    }
}
/*::::
  NOTE: this class has not been tested, and is probably
    not yet fully implemented.
*/
class fcNavbar_tree extends fcNavbar_flat {
    private $arAll;

    public function AddNavNode(fcNavItem $iNode) {
	parent::AddNavNode($iNode);
	$this->Root()->arAll[$iNode->Name()] = $iNode;
    }
}

abstract class fcNavItem extends fcNavNode {
    //private $isActive;		// TRUE = highlighted somehow
    //private $isEnabled;		// FALSE = can't click (greyed out)

    public function __construct(fcNavbar $oParent,$sName,$sText) {
	$this->SetName($sName);
	$this->SetValue($sText);
	$oParent->AddNavNode($this);
	//$this->Init();
    }
    /* 2016-11-13 I think these are redundant now
    protected function Init() {}
    //abstract public function State($iState=NULL);
    abstract public function Render();
    public function Text($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->sText = $iVal;
	}
	return $this->sText;
    } */
}
/*%%%%
  PURPOSE: nav item which is just text (passive navigation)
*/
class fcNavText extends fcNavItem {
    private $nState;

    public function State($iState=NULL) {
	if (!is_null($iState)) {
	    if (!is_numeric($iState)) {
		throw new exception('State should be numeric, but ['.$iState.'] was received.');
	    }
	    $this->nState = $iState;
	}
	return $this->nState;
    }
    /*----
      RETURNS: The class name for the given state, as defined in the parent object.
    */
    public function CSSClass($iClass=NULL,$iState=NULL) {
	$nState = is_null($iState)?($this->nState):$iState;
	$sClass = $this->GetParent()->CSSClass($iClass,$nState);
	return $sClass;
    }
    public function Render() {
	$sClass = $this->CSSClass();
	if (is_null($sClass)) {
	    return $this->Text();
	} else {
	    $htStyle = is_null($sClass)?NULL:(' class='.$sClass);
	    $out = '<span'.$htStyle.'>'.$this->GetValue().'</span>';
	    return $out;
	}
    }
}
/*::::
  PURPOSE: nav item which can be a link (active navigation)
*/
/* 2018-03-14 duplicate name, hopefully duplicate functionality too
class fcNavLink extends fcNavText {
    private $sURL;	// where this item links when it's active
    private $sPop;	// popup text displayed when mouse hovers

    const KI_DEFAULT = 0;
    const KI_CURRENT = 1;

    protected function Init() {
	$this->sURL = NULL;
	$this->sPop = NULL;
    }
    public function URL($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->sURL = $iVal;
	}
	return $this->sURL;
    }
    public function Popup($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->sPop = $iVal;
	}
	return $this->sPop;
    }
    public function Render() {
	$url = $this->URL();
	if (is_null($url) || ($this->State() == self::KI_CURRENT)) {
	    $out = parent::Render();	// active links show as plain text
	} else {
	    $sPopup = $this->Popup();
	    $htPopup = is_null($sPopup)?'':(' title="'.fcString::EncodeForHTML($sPopup).'"');
	    $out = '<a href="'.$url.'"'.$htPopup.'>'.parent::Render().'</a>';
	}
	return $out;
    }
}
*/
/*%%%%
  PURPOSE: Shows the key as a label for the value
    This would probably work better with some support
    from the navbar class (formatting for the label,
    string to go between label and value) but it will
    do for starters.
*/
/* 2018-03-14 This does appear to be still unused.
class fcNav_LabeledLink extends fcNavLink {
    public function Render() {
	return $this->Name().parent::Render();
    }
} */