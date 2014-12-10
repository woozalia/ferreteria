<?php
/*
  FILE: rtext.php -- richtext base classes
  HISTORY:
    2012-05-08 started
*/
/*
clsLibMgr::Add('lib.tree',	KFP_LIB.'/tree.php',__FILE__,__LINE__);
  clsLibMgr::AddClass('clsTreeNode','lib.tree');
*/
class clsRT_elem extends clsTreeNode {	// RichText element
    public function AddText($iText) {
	$this->vVal .= $iText;
    }
    public function Render() {
	$out = $this->RenderSelf();
	$out .= $this->RenderContents();
	return $out;
    }
    public function RenderSelf() {
	return $this->Value();	// default
    }
    protected function RenderContents() {
	$out = NULL;
	if ($this->HasNodes()) {
	    foreach ($this->Nodes() as $name => $node) {
		$out .= $node->Render();
	    }
	}
	return $out;
    }
}
abstract class clsRTDoc extends clsRT_elem {
    public abstract function NewSection($iTitle,$iType);
    public abstract function NewTable();
    public abstract function NewBox($iText);	// single-cell table
    public abstract function NewText($iText);
}
abstract class clsRTText extends clsRT_elem {	// arbitrary text block
}
abstract class clsRTTable extends clsRT_elem {
    public abstract function NewRow();
}
abstract class clsRTTable_Row extends clsRT_elem {
    public abstract function NewCell($iText);
}
class clsRTTable_Cell extends clsRT_elem {
    public function Row() {
	return $this->Parent();
    }
    public function Table() {
	return $this->Row()->Parent();
    }
}