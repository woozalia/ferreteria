<?php
/*
  FILE: rtext-html.php -- richtext HTML classes
  HISTORY:
    2012-05-08 started
*/
/*
clsLibMgr::Add('rtext',	KFP_LIB.'/rtext.php',__FILE__,__LINE__);
  clsLibMgr::AddClass('clsRTDoc','rtext');
*/
class clsElem_HTML {	// "behavior" class
    private $arAttrs;
    private $strClass;

    public function __construct() {
	$this->arAttrs = NULL;
	$this->strClass = NULL;
    }
    public function ClassName($iName) {
	$this->strClass = htmlspecialchars($iName);
    }
    public function RenderClass() {
	if (!is_null($this->strClass)) {
	    $out = ' class="'.$this->strClass.'"';
	    return $out;
	} else {
	    return NULL;
	}
    }
    public function SetAttrs(array $iList) {
	$this->arAttrs = ArrayJoin($this->arAttrs, $iList, TRUE, TRUE);
    }
    public function RenderAttrs() {
	$out = NULL;
	if (is_array($this->arAttrs)) {
	    foreach($this->arAttrs as $key => $val) {
		$out .= ' '.$key.'="'.$val.'"';
	    }
	}
	return $out;
    }
    public function RenderAllAttrs() {
	return $this->RenderClass().$this->RenderAttrs();
    }
}

class clsRTDoc_HTML extends clsRTDoc {
    private $objElem;

    public function __construct() {
	$this->objElem = NULL;
    }

    // BOILERPLATE
    protected function Elem() {
	if (is_null($this->objElem)) {
	    $this->objElem = new clsElem_HTML();
	}
	return $this->objElem;
    }
    // /boilerplate

    public function NewSection($iTitle,$iType) {
	$obj = new clsRTText_HTML();
	$this->NodeAdd($obj);
	if (is_numeric($iType)) {
	    $intLevel = (int)$iType;
	    $txt = "<h$intLevel>$iTitle</h$intLevel>";
	} else {
	    $txt = '<p class="'.$iType.'">'.$iTitle.'</p>';
	}
	$obj->AddText($txt);
	return $obj;
    }
    public function NewTable() {
	$obj = new clsRTTable_HTML();
	$this->NodeAdd($obj);
	return $obj;
    }
    public function NewBox($iText) {	// single-cell table
	$tbl = $this->NewTable();
	$row = $tbl->NewRow();
	$cell = $row->NewCell($iText);
	return $cell;
    }
    public function NewText($iText) {
	$obj = new clsRTText_HTML();
	$this->NodeAdd($obj);
	$obj->Value($iText);
	return $obj;
    }
    public function NewHLine() {
	$obj = new clsRTText_HTML();
	$this->NodeAdd($obj);
	$obj->Value('<hr>');
	return $obj;
    }
}
class clsRTText_HTML extends clsRT_elem {	// arbitrary text block - no special functions
    private $objElem;

    public function __construct() {
	$this->objElem = NULL;
    }

    // BOILERPLATE
    protected function Elem() {
	if (is_null($this->objElem)) {
	    $this->objElem = new clsElem_HTML();
	}
	return $this->objElem;
    }
    public function ClassName($iName) {
	$this->Elem()->ClassName($iName);
    }
    public function SetAttrs(array $iList) {
	$this->Elem()->SetAttrs($iList);
    }
    // /boilerplate

}
class clsRTTable_HTML extends clsRTTable {
    private $objElem;

    public function __construct() {
	$this->objElem = NULL;
    }

    // BOILERPLATE
    protected function Elem() {
	if (is_null($this->objElem)) {
	    $this->objElem = new clsElem_HTML();
	}
	return $this->objElem;
    }
    public function ClassName($iName) {
	$this->Elem()->ClassName($iName);
    }
    public function SetAttrs(array $iList) {
	$this->Elem()->SetAttrs($iList);
    }
    // /boilerplate

    public function Render() {
	// open the table

	$out = '<table'.$this->Elem()->RenderAllAttrs().'>';

	// render the rows
	$out .= $this->RenderContents();

	// close the table

	$out .= '</table>';

	return $out;
    }
    public function NewRow() {
	$obj = new clsRTTable_Row_HTML();
	$this->NodeAdd($obj);
	return $obj;
    }
    public function NewHeader() {
	$obj = new clsRTTable_Hdr_HTML();
	$this->NodeAdd($obj);
	return $obj;
    }
}
class clsRTTable_Row_HTML extends clsRTTable_Row {
    private $out;
    private $objElem;

    public function __construct() {
	$this->objElem = NULL;
    }

    // BOILERPLATE
    protected function Elem() {
	if (is_null($this->objElem)) {
	    $this->objElem = new clsElem_HTML();
	}
	return $this->objElem;
    }
    public function ClassName($iName) {
	$this->Elem()->ClassName($iName);
    }
    public function SetAttrs(array $iList) {
	$this->Elem()->SetAttrs($iList);
    }
    // /boilerplate

    public function NewCell($iText) {
	$obj = new clsRTTable_Cell_HTML();
	$this->NodeAdd($obj);
	$obj->Value($iText);
	return $obj;
    }
    public function Render() {
	$out = "\n<tr".$this->Elem()->RenderAllAttrs().'>'
	  .$this->RenderContents()
	  ."\n</tr>";
	return $out;
    }
}
class clsRTTable_Hdr_HTML extends clsRTTable_Row_HTML {
    public function NewCell($iText) {
	$obj = new clsRTTable_HdrCell_HTML();
	$this->NodeAdd($obj);
	$obj->Value($iText);
	return $obj;
    }
}
class clsRTTable_Cell_HTML extends clsRTTable_Cell {
    private $objElem;

    public function __construct() {
	$this->objElem = NULL;
    }

    // BOILERPLATE
    protected function Elem() {
	if (is_null($this->objElem)) {
	    $this->objElem = new clsElem_HTML();
	}
	return $this->objElem;
    }
    public function ClassName($iName) {
	$this->Elem()->ClassName($iName);
    }
    public function SetAttrs(array $iList) {
	$this->Elem()->SetAttrs($iList);
    }
    // /boilerplate

    public function Render() {
	$out = "\n  <td";
	$out .= $this->Elem()->RenderAllAttrs();
	$out .= '>'.$this->Value().$this->RenderContents().'</td>';
	return $out;
    }
}
class clsRTTable_HdrCell_HTML extends clsRTTable_Cell_HTML {
    public function Render() {
	$out = "\n  <th";
	$out .= $this->Elem()->RenderAllAttrs();
	$out .= '>'.$this->Value().$this->RenderContents().'</th>';
	return $out;
    }
}
/* These classes were originally separate, but should eventually be merged into clsRT* */
/*
class clsNavList {
    protected $List;

    public function Add($iName, $iValue=NULL) {
	$objItem = new clsNavItem($iName,$iValue);
	$this->List[] = $objItem;
	return $objItem;
    }
    public function Output($iPfx, $iSep, $iSfx) {
	$out = NULL;
	if (is_array($this->List)) {
	    foreach ($this->List as $objItem) {
		if (is_null($objItem->value)) {
		    $out .= $iPfx.$iSep.$objItem->name.$iSfx;
		} else {
		    $out .= $iPfx.$objItem->name.$iSep.$objItem->value.$iSfx;
		}
	    }
	}
	return $out;
    }
}
class clsNavItem {
  public $name;
  public $value;

  public function __construct($iName, $iValue=NULL) {
    $this->name = $iName;
    $this->value = $iValue;
  }
}
*/