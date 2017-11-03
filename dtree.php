<?php
/*
  FILE: dtree.php -- PHP API for using dtree.js
  HISTORY:
    2010-10-13 Started
    2011-01-12 Somewhat working
    2012-02-12 Fixed bug with non-numeric IDs; all node fields printed in JS are now properly escaped (in theory)
*/
class fcDTreeAPI {
    protected $wpFiles;
    private $objRoot;
    private $fnCSS;

    /*----
      INPUT:
	iPath: web path to folder containing dtree.js and dtree.css
    */
    public function __construct($iPath) {
	$this->wpFiles = $iPath;
	$this->fnCSS = 'dtree.css';	// default
    }
    public function RootNode() {
	if (is_null($this->objRoot)) {
	    $this->objRoot = new fcDTreeNode(-1,$this,NULL,'root');
	}
	return $this->objRoot;
    }
    public function FileForCSS($iName=NULL) {
	if (!is_null($iName)) {
	    $this->fnCSS = $iName;
	}
	return $this->fnCSS;
    }
    public function RenderPageHdr() {
	$fnCSS = $this->fnCSS;
	$fp = $this->wpFiles;
	if (!empty($fnCSS)) {
	    if (!empty($fp)) { $fp .= '/'; }
	    $out = "\n".'<link rel="StyleSheet" href="'.$fp.$fnCSS.'" type="text/css" />';
	} else {
	    $out = NULL;
	}
	$out .= "\n".'<script type="text/javascript" src="'.$fp.'dtree.js"></script>';
	return $out;
    }
    /*----
      FUTURE: "/img/" should not be hard-coded
    */
    public function RenderTreeHdr() {
	$fp = $this->wpFiles;
	$out = <<<__END__
<script type="text/javascript"><!--
d = new dTree('d');
d.config.inOrder = false;
d.config.fpImgBase = '$fp/img/';
__END__;
	return $out;
    }
    /*----
      NOTE: It looks like this does the actual output
    */
    public function RenderTreeFtr() {
	$out = <<<__END__
document.write(d);
//-->
</script>
__END__;
	return $out;
    }
/* Where would this ever be used?
    public function RenderTree() {
	$out = $this->RenderTreeHdr();
// add content
	$objRoot = $this->RootNode();
	$out .= $objRoot->RenderTree();
	$out .= $this->RenderTreeFtr();
	return $out;
    }
*/
}

class fcDTreeNode extends fcTreeNode {
    public $ID, $Parent, $TextTwig, $URL, $TextPopup, $NameTarget, $IconSpecOpen, $IconSpecShut;
    protected $objMgr;

    public function __construct(
      $iID,
      fcDTreeAPI $iMgr,
      fcDTreeNode $iParent=NULL,
      $iTextTwig,
      $iURL=NULL,
      $iTextPopup=NULL,
      $iNameTarget=NULL,
      $iIconSpecOpen=NULL,
      $iIconSpecShut=NULL) {
	$this->ID = $iID;
	$this->objMgr = $iMgr;
	$this->Parent = $iParent;
	$this->TextTwig = $iTextTwig;
	$this->URL = $iURL;
	$this->TextPopup = $iTextPopup;
	$this->NameTarget = $iNameTarget;
	$this->IconSpecOpen = $iIconSpecOpen;
	$this->IconSpecShut = $iIconSpecShut;
    }
    public function IsRoot() {	
	return ($this->ID == -1);
    }
    public function TextShow($iText=NULL) {
	if (!is_null($iText)) {
	    $this->TextTwig = $iText;
	}
	return $this->TextTwig;
    }
    public function CanShow() {
	return !empty($this->TextTwig);
    }
    public function Mgr() {
	return $this->objMgr;
    }
    public function Add(
      $idNew,
      $iTextTwig,
      $iURL=NULL,
      $iTextPopup=NULL,
      $iNameTarget=NULL,
      $iIconSpecOpen=NULL,
      $iIconSpecShut=NULL) {
	$oNodeNew = new fcDTreeNode($idNew,$this->Mgr(),$this,$iTextTwig,$iURL,$iTextPopup,$iNameTarget,$iIconSpecOpen,$iIconSpecShut);
	$this->SetNode($oNodeNew,$idNew);	// add new node to sub-array
	return $oNodeNew;
    }
    public function Render() {
	return "\nd.add("
	  .JSEscape($this->ID).','
	  .JSEscape($this->Parent->ID).','
	  .JSEscape($this->TextTwig).','
	  .JSEscape($this->URL).','
	  .JSEscape($this->TextPopup).','
	  .JSEscape($this->NameTarget).','
	  .JSEscape($this->IconSpecOpen).','
	  .JSEscape($this->IconSpecShut).');';
    }
    public function RenderTree() {
	$out = '';
	$isRoot = $this->IsRoot();
	if ($isRoot) {
	    $out .= $this->Mgr()->RenderTreeHdr();
	    if ($this->CanShow()) {
		$txtShow = $this->TextShow();
		//$out .= "\nd.add(1,0,'$txtShow','');";
	    }
	}
	if ($this->HasNodes()) {
	    foreach ($this->Nodes() as $obj) {
		$out .= $obj->Render();
		$out .= $obj->RenderTree();
	    }
	} else {
	    $out = NULL;
	}
	if ($isRoot) {
	    $out .= $this->Mgr()->RenderTreeFtr();
	}
	return $out;
    }
}

function JSEscape($iVal) {
    if (is_numeric($iVal)) {
	return $iVal;
    } else {
	$out = str_replace("'","\'",$iVal);
	return "'$out'";
    }
}