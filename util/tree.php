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
    2016-11-08 renamed clsTreeNode -> fcTreeNode
*/
trait ftAutomakeable {
    abstract function ClassForSubs();
    public function SetSubValue($sName,$sValue) {
	$o = $this->MakeNode($sName,$this->ClassForSubs());
	$o->SetValue($sValue);
    }
}
class fcTreeNode {
    private $arSubs;	// sub-nodes
    //protected $intRefs;	// commented out 2016-11-11

    // ++ SELF ++ //

    protected $sName;
    public function SetName($sName) {
	$this->sName = $sName;
    }
    public function GetName() {
	return $this->sName;
    }
	
    private $sVal;	// data value
    public function SetValue($s) {
	$this->sVal = $s;
    }
    public function GetValue() {
	return $this->sVal;
    }
    public function AddText($s) {
	$this->sVal .= $s;
    }

    // -- SELF -- //
    // ++ PARENT ++ //

    private $oParent;
    public function GetParent() {
	return $this->oParent;
    }
    protected function SetParent(fcTreeNode $iNode) {
	$this->oParent = $iNode;
    }
    public function HasParent() {
	return (!is_null($this->oParent));
    }
    /*----
      2012-04-17 created for debugging purposes
    */
    public function Root() {
	if ($this->HasParent()) {
	    return $this->GetParent()->Root();
	} else {
	    return $this;
	}
    }

    // -- PARENT -- //
    // ++ CHILDREN ++ //

      //++creating++//
    
    /*----
      HISTORY:
	2016-11-13 making these 3 fx protected until need for public can be documented
	  ...but really, Spawn() and SpawnNode() should be internal fx because they
	  don't hook the node into the tree.
	  Also, SpawnNode() seems to be unnecessary because of MakeNode(); commenting it out.
    */
    protected function Spawn($sClass=NULL) {
	if (is_null($sClass)) {
	throw new exception('Who is spawning itself?');
	    // default to same class as spawner
	    $sClass = get_class($this);
	}
	$oNode = new $sClass();
	return $oNode;
    }
    /*----
      ACTION: If the named node exists, return that; otherwise create it.
      HISTORY:
	2016-11-20 Making $sClass mandatory.
	2017-01-25 Making it *really* mandatory (throws error if it equals NULL).
	  If we desperately want to make it automatically spawn self's class,
	  document the usage case and define a separate MakeSameNode() method.
    */
    protected function MakeNode($sName,$sClass) {
	if (is_null($sClass)) {
	    throw new exception("Ferreteria usage error: when calling MakeNode(), class for node '$sName' must be specified.");
	}
	if ($this->HasNode($sName)) {
	    $oNode = $this->GetNode($sName);
	} else {
	    $oNode = $this->CreateNamedNode($sName,$sClass);
	}
	return $oNode;
    }
    /*----
      HISTORY:
	2016-12-01 Renamed CreateNode()->CreateNamedNode()
    */
    protected function CreateNamedNode($sName,$sClass=NULL) {
	$oNode = $this->Spawn($sClass);
	$this->SetNode($oNode,$sName);
	return $oNode;
    }
    /*----
      ACTION: Create a new enumerated node.
    */
    protected function CreateAnonymousNode($sClass=NULL) {
	$oNode = $this->Spawn($sClass);
	$this->SetNode($oNode);
	return $oNode;
    }

      //--creating--//
      //++adopting++//
      
    /*----
      RETURNS: $oNode, just as a convenience for setup code
      PUBLIC because sometimes we...
	* have subnodes whose classes are best initialized in the
	constructor -- so we can't just ask the parent to automake them
	(via SetSubValue()).
	* usually have multiple sets of subnodes that need to be added,
	with order-of-execution not being guaranteed -- so we can't just
	create a child-class to	add the specific nodes internally.
      HISTORY:
	2016-11-13
	  * this should be an internal function - making PROTECTED
	  * reversing argument order so $sName can be optional
	2016-11-20 Merged StashNode() functionality: when there is no name to use, add anonymously
	  This is useful when we want to add multiple nodes without having to explicitly name each one.
	2016-12-11 It turns out that sometimes it just doesn't make sense to restrict this (see NOTE).
	  Making it PUBLIC.
    */
    public function SetNode(fcTreeNode $oNode,$sName=NULL) {
	$oNode->SetParent($this);
	if (is_null($sName)) {
	    $sName = $oNode->GetName();
	} else {
	    $oNode->SetName($sName);
	}
	if (is_null($sName)) {
	    // if there just isn't a name to use, then add anonymously
	    $this->arSubs[] = $oNode;		// add the received node to the node array
	    // node's name needs to match its key in the node array (mainly for deleting), so:
	    end($this->arSubs);			// move native pointer to end of node array
	    $sName = key($this->arSubs);	// get key value for last element (the one just added)
	    $oNode->SetName($sName);		// set the node's name to that key value
	} else {
	    $this->arSubs[$sName] = $oNode;
	}
	return $oNode;
    }

      //--adopting--//
      //++individual access++//

    public function Exists($sName) {
	throw new exception('2016-11-11 Exists() is deprecated; call HasNode() instead.');
    }
    public function HasNode($sName) {
	return $this->HasNodes()?array_key_exists($sName,$this->GetNodes()):FALSE;
    }
    public function GetNode($sName) {
	if (array_key_exists($sName,$this->arSubs)) {
	    return $this->arSubs[$sName];
	} else {
	    throw new fcDebugException("Ferreteria Usage Error: requested node '$sName' does not exist.");
	}
    }
    /*----
      ACTION: Retrieve the first subnode.
      PURPOSE: Mainly useful when we expect there to be exactly one subnode.
      TODO: Figure out if maybe what is needed is a function named something like OnlyNode()
	that will throw an exception if there is more than one subnode.
    */
    public function FirstNode() {
	$oNode = reset($this->arSubs);	// get the first node
	return $oNode;			// return it
    }

      //--individual access--//
      //++collective access++//

    public function SetNodes(array $arNodes) {
	$this->arSubs = $arNodes;
    }
    public function GetNodes() {
	return $this->arSubs;
    }
    public function HasNodes() {
	if (isset($this->arSubs)) {
	    return ($this->NodeCount() > 0);
	} else {
	    return FALSE;
	}
    }
    public function NodeCount() {
	return count($this->arSubs);
    }
    public function GetValues() {
	if ($this->HasNodes()) {
	    $arNodes = $this->GetNodes();
	    foreach ($arNodes as $key => $node) {
		$arVals[$key] = $node->GetValue();
	    }
	} else {
	    $arVals = array();
	}
	return $arVals;
    }
    
      //--collective access--//
      //++removal++//

    // ACTION: Remove self from parent's collection.
    public function DeleteMe() {
	$sName = $this->GetName();
	if ($this->HasParent()) {
	    $this->GetParent()->DeleteNode($sName);
	} else {
	    echo 'Trying to delete node "'.$sName.'":';
	    throw new exception('Cannot delete root node this way.');
	}
    }
    public function DeleteNode($sName) {
	if (isset($this->arSubs[$sName])) {
	    unset($this->arSubs[$sName]);
	}
    }
    // TODO: Split this into DeleteMe() and DeleteNode($sName)
    public function Delete($sName=NULL) {
	throw new exception('Delete() is deprecated; call DeleteMe() or DeleteNode().');
    }
    
      //--removal--//

    // -- CHILDREN -- //
    // ++ TREE ++ //

    /*----
      RETURNS: Path from root current object
    */
    public function FigurePath($sSep=':') {
	if ($this->HasParent()) {
	    return $this->GetParent()->GetName().$sSep.$this->GetName();
	} else {
	    return is_null($this->GetName()) ? '[root]' : $this->GetName();
	}
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

    // -- TREE -- //
    // ++ DEBUGGING ++ //

    public function DumpHTML() {
	$out = '<li>'.$this->DumpHTML_self().'</li>';
	if ($this->HasNodes()) {
	    $out .= '<ul>'
	      .$this->DumpHTML_subs()
	      . '</ul>'
	      ;
	}
	return $out;
    }
    protected function DumpHTML_self() {
	$sClass = get_class($this);
	$sName = $this->GetName();
	$val = $this->GetValue();
	if (is_null($sName)) {
	    $htName = '(no name)';
	} else {
	    $htName = '"<b>'.$sName.'</b>"';
	}
	if (is_null($val)) {
	    $htValue = ' = NULL';
	} elseif (is_scalar($val)) {
	    $htValue = " = '$val'";
	} else {
	    $htValue = 'value is CLASS ['.get_class($val).']';
	}
	return "[::$sClass::] $htName $htValue";
    }
    // NOTE: 2016-11-20 I'm not sure how the isAlias functionality is intended to be used; leaving it in for now.
    protected function DumpHTML_subs() {
	$out = NULL;
	foreach ($this->GetNodes() as $name => $node) {
	    if ($node->HasParent()) {
		$isAlias = ($node->GetParent() != $this);
	    } else {
		$out .= '<b>INTERNAL ERROR</b>: parent of the following subnode is not set.<br>';
		$isAlias = FALSE;
	    }
	    if ($isAlias) {
		$out .= '<i>';
	    }
	    $out .= $node->DumpHTML();
	    if ($isAlias) {
		$out .= '</i>';
	    }
	}
	return $out;
    }

    // -- DEBUGGING -- //
    // ++ DEPRECATED ++ //
    
    public function Name($sName=NULL) {
	throw new exception('2016-11-13 Name() is deprecated; call SetName() or GetName().');
	if (!is_null($sName)) {
	    $this->sName = $sName;
	}
	return $this->sName;
    }
    public function Value($iVal=NULL) {
	throw new exception('2016-11-20 Value() is deprecated; call SetValue() or GetValue().');
	if (!is_null($iVal)) {
	    if (!is_string($iVal)) {
		echo 'Trying to set node value using a <b>'.get_class($iVal).'</b> object instead of a string.<br>';
		throw new exception('Internal error');
	    }
	    $this->sVal = $iVal;
	}
	return $this->sVal;
    }
    public function Parent(fcTreeNode $iNode=NULL) {
	throw new exception('2016-11-13 Parent() is deprecated; call GetParent() or SetParent().');
	if (!is_null($iNode)) {
	    $this->SetParent($iNode);
	}
	return $this->GetParent();
    }
    /*----
      INPUT:
	$doReplace:
	  if TRUE, overwrite any existing node that has the same name
	  if FALSE, ignore the new node and use the existing one
    */
    public function NodeAdd(fcTreeNode $iNode,$doReplace=FALSE) {
	throw new exception('2016-11-11 NodeAdd() is deprecated; call SetNode() instead.');
    }
    public function Loaded($iName=NULL) {
	throw new exception('2016-11-11 Who calls this, and why is it needed?');
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
	throw new exception('2016-11-11 Who calls this, and why is it needed?');
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
    public function Node($sName, fcTreeNode $oNode=NULL) {
	throw new exception('2016-11-11 Node() is deprecated; call SetNode() or GetNode().');
	if (!is_null($oNode)) {
	    $iNode->Name($sName);
	    $this->NodeAdd($oNode);
	}
	if ($this->Exists($sName)) {
	    return $this->arSubs[$sName];
	} else {
	    return NULL;
	}
    }
    public function Nodes(array $iNodes=NULL) {
	throw new exception('2016-11-11 Nodes() is deprecated; call GetNodes() or SetNodes().');
	if (is_array($iNodes)) {
	    $this->arSubs = $iNodes;

	    foreach ($iNodes as $name => $node) {
		$node->Parent($this);
	    }
	}
	return $this->arSubs;
    }

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
	throw new exception('2016-11-11 What is this even for?');
	return $this->intRefs;
    }
    public function IncCount() {
	throw new exception('2016-11-11 What is this even for?');
	$this->intRefs++;
    }
    public function ResetCount() {
	throw new exception('2016-11-11 What is this even for?');
	$this->intRefs = 0;
	if ($this->HasNodes()) {
	    foreach ($this->Nodes() as $name => $node) {
		$node->ResetCount();
	    }
	}
    }

    // -- DEPRECATED -- //

}