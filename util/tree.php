<?php
/*
  FILE: tree.php -- general tree-handling classes
  HISTORY:
    2010-10-13 Extricated from shop.php
    2011-12-18 added access counter ($intRefs)
    2011-12-25 adding strName property, moving name-tracking out of parent
    2012-01-11 changed NodeAdd() from protected to public (presumably it
      had to be an internal routine before nodes tracked their own names)
    2012-01-13 commented out __constructor and moved most of the code to Nodes()
    2013-12-14 clsTreeNode::FindNode()
    2015-09-20 added overwrite checking to NodeAdd()
*/
class clsTreeNode {
    public $vVal;	// data value
    protected $arSubs;	// sub-nodes
    protected $objParent;
    protected $strName;
    protected $intRefs;

    // ++ SETUP ++ //

    public function Spawn() {
	$sClass = get_class($this);
	//$obj = new clsTreeNode();
	$obj = new $sClass();
	return $obj;
    }

    // -- SETUP -- //
    // ++ DATA FIELDS ++ //

    public function Name($iName=NULL) {
	if (!is_null($iName)) {
	    $this->strName = $iName;
	}
	return $this->strName;
    }
    public function Value($iVal=NULL) {
	if (!is_null($iVal)) {
	    if (!is_string($iVal)) {
		echo 'Trying to set node value using a <b>'.get_class($iVal).'</b> object instead of a string.<br>';
		throw new exception('Internal error');
	    }
	    $this->vVal = $iVal;
	}
	return $this->vVal;
    }

    // -- DATA FIELDS -- //
    // ++ PARENT NODE ++ //

    // TODO: deprecate this; use GetParent()/SetParent()
    public function Parent(clsTreeNode $iNode=NULL) {
	if (!is_null($iNode)) {
	    $this->SetParent($iNode);
	}
	return $this->GetParent();
    }
    public function GetParent() {
	return $this->objParent;
    }
    protected function SetParent(clsTreeNode $iNode) {
	$this->objParent = $iNode;
    }
    public function HasParent() {
	return (!is_null($this->objParent));
    }
    /*----
      2012-04-17 created for debugging purposes
    */
    public function Root() {
	if ($this->HasParent()) {
	    return $this->Parent()->Root();
	} else {
	    return $this;
	}
    }

    // -- PARENT NODE -- //
    // ++ SUBNODES -- LOOKUP/ADDING ++ //

    public function HasNodes() {
	if (isset($this->arSubs)) {
	    return (count($this->arSubs) > 0);
	} else {
	    return FALSE;
	}
    }
    /*----
      ACTION: Retrieve the first sub-node.
      PURPOSE: Mainly useful when there is exactly one
	sub-node which we expect to exist.
    */
    public function FirstNode() {
	$oNode = reset($this->arSubs);	// get the first node
	return $oNode;			// return it
    }
    // TODO: possibly this should be renamed HasNode(), for consistency
    public function Exists($sName) {
	if (is_array($this->arSubs)) {
	    return array_key_exists($sName,$this->arSubs);
	} else {
	    return FALSE;
	}
    /*
	if (isset($this->arSubs[$iName])) {
	    return isset($this->arSubs[$iName]);
	} else {
	    return FALSE;
	}*/
    }
    public function Loaded($iName=NULL) {
	if (is_null($iName)) {
	    return isset($this->vVal);
	} else {
	    if ($this->Exists($iName)) {
		return $this->Node($iName)->Loaded();
	    } else {
		return FALSE;
	    }
	}
    }
    /*----
      RETURNS: TRUE if there's a value by that name that is loaded.
      NOTE: The value may exist, but if it's not loaded this will still be FALSE.
	Why do we do it that way? DOCUMENT!! Might not be the way to do it.
    */
    public function Filled($iName=NULL) {
	if (is_null($iName)) {
	    if (!$this->Loaded()) {
		$this->vVal = $this->Value();	// is this in danger of being recursive?
	    }
	    return !empty($this->vVal);
	} else {
	    if ($this->Loaded($iName)) {
		return $this->Node($iName)->Filled();
	    } else {
		return FALSE;
	    }
	}
    }
    // TODO: the names of Node() and NodeAdd() should be swapped
    //	Maybe as an interim step, rename Node() to AddNode()
    public function Node($iName, clsTreeNode $iNode=NULL) {
	if (!is_null($iNode)) {
	    $iNode->Name($iName);
	    $this->NodeAdd($iNode);
	}
	if ($this->Exists($iName)) {
	    return $this->arSubs[$iName];
	} else {
	    return NULL;
	}
    }
    /*----
      INPUT:
	$doReplace:
	  if TRUE, overwrite any existing node that has the same name
	  if FALSE, ignore the new node and use the existing one
    */
    public function NodeAdd(clsTreeNode $iNode,$doReplace=FALSE) {
	$sName = $iNode->Name();
	if (is_null($sName)) {
	    $this->arSubs[] = $iNode;
	} else {
	    if ($this->Exists($sName)) {
		if ($doReplace) {
		    $this->arSubs[$sName] = $iNode;
		}
	    } else {
		$this->arSubs[$sName] = $iNode;
	    }
	}
	$iNode->SetParent($this);
    }
    public function Nodes(array $iNodes=NULL) {
	if (is_array($iNodes)) {
	    $this->arSubs = $iNodes;

	    foreach ($iNodes as $name => $node) {
		$node->Parent($this);
	    }
	}
	return $this->arSubs;
    }
    /*----
      ACTION: searches from the current node downward to find
	a node by the given name.
      RETURNS: Node object, or NULL if not found.
    */
    public function FindNode($sName) {
	if (!is_string($sName)) {
	    throw new exception("Internal Error: Searching for node name that is type '".gettype($sName)."' instead of a string.");
	}
	if ($sName == $this->Name()) {
	    return $this;
	} elseif (!is_array($this->arSubs)) {
	    return NULL;
	} elseif (array_key_exists($sName,$this->arSubs)) {
	    return $this->arSubs[$sName];
	} elseif (is_array($this->arSubs)) {
	    foreach ($this->arSubs as $key => $node) {
		$oFnd = $node->FindNode($sName);
		if (is_object($oFnd)) {
		    return $oFnd;
		}
	    }
	}
	return NULL;
    }

    // -- SUBNODES -- LOOKUP/ADDING -- //
    // ++ SUBNODES -- OTHER ACTION ++ //

    public function Delete($sName=NULL) {
	if (is_null($sName)) {
	    // deleting self from parent
	    $sName = $this->Name();
	    if ($this->HasParent()) {
		$this->Parent()->Delete($sName);
	    } else {
		echo 'Trying to delete node "'.$sName.'":';
		throw new exception('Cannot delete root node this way.');
	    }
	} else {
	    // deleting named sub-item (this is where it actually happens)
	    if (isset($this->arSubs[$sName])) {
		unset($this->arSubs[$sName]);
	    }
	}
    }

    // -- SUBNODES -- OTHER ACTION -- //
    // ++ DEBUGGING ++ //

    public function DumpHTML() {
	$out = NULL;
	if ($this->intRefs > 0) {
	    $out = '[<b><font color=#aa6600>x'.$this->intRefs.'</font></b>]';
	}
	$this->intRefs++;
	if ($this->HasNodes()) {
	    $out = '<ul>';
	    foreach ($this->Nodes() as $name => $node) {
		$isAlias = ($node->Parent() != $this);
		if ($isAlias) {
		    $out .= '<i>';
		}
		$strCls = get_class($node);
		$val = $node->Value();
		if (is_string($val)) {
		    $strVal = $val;
		} else {
		    $strVal = 'CLASS ['.get_class($val).']';
		}
		$out .= '<li> ['.$strCls.'] <b>'.$name.'</b> = ['.$strVal.']';
		$out .= $node->DumpHTML();
		if ($isAlias) {
		    $out .= '</i>';
		}
		if (!$node->HasParent()) {
		    $out .= '<ul><li><b>INTERNAL ERROR</b>: parent not set</li></ul>';
		}
	    }
	    $out .= '</ul>';
	}
	return $out;
    }

    // -- DEBUGGING -- //
    // ++ DEPRECATED ++ //

    // ...until their use can be documented, at least.

    /*----
      FUTURE: For consistency, should probably be renamed NodeValue()
    */
/* 2011-12-25 who uses this?
    public function SubValue($iName,$iVal=NULL) {
	if (!is_null($iVal)) {
	    $obj = $this->Spawn();
	    $obj->Value($iVal);
	} else {
	    $obj = NULL;
	}
	return $this->Node($iName,$obj)->Value();
    }
*/
    public function AccessCount() {
	return $this->intRefs;
    }
    public function IncCount() {
	$this->intRefs++;
    }
    public function ResetCount() {
	$this->intRefs = 0;
	if ($this->HasNodes()) {
	    foreach ($this->Nodes() as $name => $node) {
		$node->ResetCount();
	    }
	}
    }

    // -- DEPRECATED -- //
}