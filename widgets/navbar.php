<?php
/*
  LIBRARY: classes for managing collections of navigation links
  HISTORY:
    2013-10-08 clsNavBar, clsNavItem, clsNavLink
    2013-10-10 clsNavbar_flat, clsNavbar_tree, clsNavItemText
    2013-11-29 renamed from menu.php to navbar.php
      because proper menus look like they'll need another set of classes
*/

abstract class clsNavNode extends clsTreeNode {
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

abstract class clsNavbar extends clsNavNode {
    /*----
      USAGE: Should only be called by child nodes
    */
    abstract public function _Add(clsNavItem $iNode);
    abstract public function Render();
}

class clsNavbar_flat extends clsNavbar {
    private $sLinkBef,$sLinkAft,$sLinkInt;	// before, after, and in between

    public function _Add(clsNavItem $iNode) {
	$sName = $iNode->Name();
	$this->arSubs[$sName] = $iNode;
	$iNode->Parent($this);
    }
    public function AddAfter($iName,clsNavItem $iNode) {
	// there's got to be a better way to insert a keyed array element
	foreach ($this->arSubs as $name => $node) {
	    $arOut[$name] = $node;
	    if ($name == $iName) {
		$arOut[$iNode->Name()] = $iNode;
	    }
	}
	$this->arSubs = $arOut;
    }
    /*----
      ACTION: Sets the states for all nodes before and after the one with the given name
    */
    public function States($iName,$iStateBefore,$iStateAfter) {
	$isAfter = FALSE;
	$ar = $this->Nodes();
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
	$ar = $this->Nodes();
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
/*%%%%
  NOTE: this class has not been tested, and is probably
    not yet fully implemented.
*/
class clsNavbar_tree extends clsNavbar_flat {
    private $arAll;

    public function _Add(clsNavItem $iNode) {
	parent::_Add($iNode);
	$this->Root()->arAll[$iNode->Name()] = $iNode;
    }
}

abstract class clsNavItem extends clsNavNode {
    private $sName;		// unique ID
    private $sText;		// text to display
    //private $isActive;		// TRUE = highlighted somehow
    //private $isEnabled;		// FALSE = can't click (greyed out)

    public function __construct(clsNavbar $iParent,$iName,$iText) {
	$this->Name($iName);
	$this->Text($iText);
	$iParent->_Add($this);
	$this->Init();
    }
    protected function Init() {}
    //abstract public function State($iState=NULL);
    abstract public function Render();
    public function Text($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->sText = $iVal;
	}
	return $this->sText;
    }
}
/*%%%%
  PURPOSE: nav item which is just text (passive navigation)
*/
class clsNavText extends clsNavItem {
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
	$sClass = $this->Parent()->CSSClass($iClass,$nState);
	return $sClass;
    }
    public function Render() {
	$sClass = $this->CSSClass();
	if (is_null($sClass)) {
	    return $this->Text();
	} else {
	    $htStyle = is_null($sClass)?NULL:(' class='.$sClass);
	    $out = '<span'.$htStyle.'>'.$this->Text().'</span>';
	    return $out;
	}
    }
}
/*%%%%
  PURPOSE: nav item which can be a link (active navigation)
*/
class clsNavLink extends clsNavText {
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
/*%%%%
  PURPOSE: Shows the key as a label for the value
    This would probably work better with some support
    from the navbar class (formatting for the label,
    string to go between label and value) but it will
    do for starters.
*/
class clsNav_LabeledLink extends clsNavLink {
    public function Render() {
	return $this->Name().parent::Render();
    }
}