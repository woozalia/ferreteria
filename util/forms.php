<?php
/*
  FILE: forms.php -- handling of HTML form data
  HISTORY:
    2010-10-21 Created from scratch, reusing some ideas from richtext.php
    2011-10-06 added clsFieldBool_Int
  PIECES:
    FIELDS handle data but not how it is displayed or stored
    CTRLS handle how FIELDS are displayed
*/
/*###
  SECTION: utility functions
*/
/* 2013-12-14 use clsHTML::fromBool()
function Bool_toHTML($iVal) {
    if ($iVal) {
	$out = '<font color=green title="yes">&radic;</font>';
    } else {
	$out = '<font color=grey title="no">&ndash;</font>';
    }
    return $out;
}
*/
/*====
  CLASS: clsFields -- group of clsField objects
  USAGE: Descendant classes can implement a constructor which creates all the fields,
    or they can be loaded via Add() by an external routine.
*/
class clsFields {
    protected $arFields;
    protected $arValues;	// starting values
    protected $arChg;		// array of changed fields: array[name][old|new] = field object
    protected $strPfx;

    public function __construct(array $iVals=NULL) {
	$this->arValues = $iVals;
    }
    /*----
      RETURNS: array of field objects
    */
    public function Fields() {
	return $this->arFields;
    }
    /*----
      RETURNS: object for the named field
    */
    public function Field($iName) {
	return $this->arFields[$iName];
    }
    /*----
      ACTION: Clears any accumulated data, but retains field set and values
    */
    public function Reset() {
	$this->arChgStore = NULL;
	$this->arChgSQL = NULL;
    }
    /*----
      RETURNS: Naming prefix to use within HTML forms
    */
    public function FormPrefix($iPrefix=NULL) {
	if (!is_null($iPrefix)) {
	    $this->strPfx = $iPrefix;
	}
	return empty($this->strPfx)?'':$this->strPfx;
    }
    public function Add(clsField $iField) {
	$strName = $iField->Name();
	$iField->Parent($this);	// give control a pointer back to the group
	if (isset($this->arValues[$strName])) {
	    $iField->ValStore($this->arValues[$strName]);
	}
	$this->arFields[$strName] = $iField;
    }
    /*----
      ACTION: For each field in the group, checks to see
	if there's POST data. If there is, updates the
	control with that data.
      HISTORY:
	2010-11-02 Checkboxes that aren't checked don't return values in POST data,
	  so we can't skip values that aren't set. Must assume all form controls are included.
    */
/*
    public function RecvVals() {
	foreach($this->arFields as $name => $field) {
	    $htFormName = $field->FormName();
	    $strNewVal = nz($_POST[$htFormName],'');
	    $isSame = $field->ValSameAs($strNewVal);
	    if (!$isSame) {
		$this->arChg[$name]['old'] = $field->Value();
		$field->Value($strNewVal);
		$this->arChg[$name]['new'] = $field->Value();
		$field->Value($strNewVal);
	    }
	}
    }
*/
    /*----
      RETURNS: ValSameAs() return value
      HISTORY:
	2010-11-19 Added to help with multi-row forms
    */
/*
    public function SetField($iField,$iNewVal) {
	$isSame = $iField->ValSameAs($iNewVal);
	if (!$isSame) {
	    $this->arChg[$name]['old'] = $iField->Value();
	    $iField->Value($strNewVal);
	    $this->arChg[$name]['new'] = $iField->Value();
	}
	return $isSame;
    }
*/
    public function AddChange(clsField $iOldVal,clsField $iNewVal) {
	$strName = $iOldVal->Name();
	$this->arChg[$strName]['old'] = $iOldVal;
	$this->arChg[$strName]['new'] = $iNewVal;
    }
    /*----
      RETURNS: array of fields to update, with new values:
	return array['field name'] = new value
      USAGE: Run RecvVals() first
    */
    public function DataUpdates() {
	if (is_array($this->arChg)) {
	    foreach($this->arChg as $name => $ar) {
		$objNew = $ar['new'];
		$arUpd[$name] = $objNew->ValSQL();
	    }
	    return $arUpd;
	} else {
	    return NULL;
	}
    }
    /*----
      RETURNS: text description of fields to update
    */
    public function DescrUpdates() {
	$out = NULL;
	if (is_array($this->arChg)) {
	    foreach($this->arChg as $name => $ar) {
		$objOld = $ar['old'];
		$objNew = $ar['new'];
		if (!is_null($out)) {
		    $out .= ',';
		}
		$out .= $name.': ';
		$out .= self::ValueDescr($objOld->ValShow()).' => '.self::ValueDescr($objNew->ValShow());
	    }
	}
	return $out;
    }
    static public function ValueDescr($iVal,$iMaxLen=30) {
	$intLen = strlen($iVal);
	$out = ($intLen > $iMaxLen)?($intLen.' char'.Pluralize($intLen)):('"'.$iVal.'"');
	return $out;
    }
}

/*====
  CLASS: clsField - generic/text individual field
*/
class clsField {
    protected $objParent;
    protected $strName;
    protected $vValue;

    public function __construct($iName,$iVal=NULL) {
	$this->strName = $iName;
	$this->vValue = $iVal;
	$this->objParent = NULL;
    }
    public function Parent($iParent=NULL) {
	if (!is_null($iParent)) {
	    $this->objParent = $iParent;
	}
	return $this->objParent;
    }
    protected function HasParent() {
	return !is_null($this->objParent);
    }
    public function Name($iName=NULL) {		// name of field
	if (!is_null($iName)) {
	    $this->strName = $iName;
	}
	return $this->strName;
    }
    /*----
      ACTION: returns the value of the field.
	If iVal is not null, sets field to iVal first.
      HISTORY:
	2011-03-29 renamed from Value() to ValStore()
    */
    public function ValStore($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->vValue = $iVal;
	}
	return $this->vValue;
    }
    /*----
      RETURNS: the displayable value of the field
	if iVal is not null, converts it from shown to stored format,
	  saves that value, then returns the shown value derived from
	  what was stored.
      HISTORY:
	2011-03-29 created - we're distinguishing between "stored" and "displayed" values now
    */
    public function ValShow($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->vValue = self::Convert_ShowToStore($iVal);
	}
	return $this->Convert_StoreToShow($this->vValue);
    }
    /*----
      INPUT: value in format returned by SQL
      HISTORY:
	2011-03-29 created - we're now distinguishing between "stored", "displayed", and "SQL" formats
    */
    public function ValSQL() {
/* write this later; we may never need it
	if (!is_null($iVal)) {
	    $this->vValue = self::Convert_SQLToStore($iVal);
	}
*/
	return SQLValue($this->vValue);
    }
    /*----
      ACTION: Sets the value even if input is NULL
      HISTORY:
	2010-11-20 Created for multi-row editing
    */
    public function SetStored($iVal) {
	$this->vValue = $iVal;
    }
    /*----
      ACTION: Sets the field value; if the value has changed, records it in the parent (forms) object.
      RETURNS: ValSameAs() return value
      HISTORY:
	2010-11-19 Added to help with multi-row forms
	2010-11-20 Moved from clsForms to clsForm and adapted/renamed
    */
    public function Change_fromShown($iNewVal) {
	$valOld = $this->ValStore();
	$valNew = $this->Convert_ShowToStore($iNewVal);
	$isSame = ($valOld === $valNew);
	if (!$isSame) {
	    $fldOld = clone $this;
	    $this->SetStored($valNew);
	    $this->Parent()->AddChange($fldOld,$this);
	}
	return $isSame;
    }
    /*----
      ACTION: Forces the acceptance of the given value as a *change*,
	even if it matches what is already stored.
      PURPOSE: This is for saving new records, which may have default
	values displayed (and hence stored in memory) which need to be
	saved even if the user doesn't alter them.
    */
    public function Change_asNew($sNewVal) {
	$valNew = $this->Convert_ShowToStore($sNewVal);
	$fldOld = clone $this;
	$this->SetStored($valNew);
	$this->Parent()->AddChange($fldOld,$this);
    }
    /*----
      ACTION: Clears the field's value
    */
    public function Clear() {
	$this->vValue = NULL;
    }
    /*----
      ACTION: converts displayable input to storable value
	without setting anything.
      INPUT: display-formatted version
      RETURNS: storage-formatted version
      NOTE: must be non-static to allow for polymorphism
      HISTORY:
	2011-03-29 renamed from Value() to Convert_ShowToStore()
    */
    public function Convert_ShowToStore($iVal) {
	return $iVal;	// default/generic behavior
    }
    /*----
      ACTION: converts storable input to displayable value
	without setting anything.
      INPUT: storage-formatted version
      RETURNS: display-formatted version
      NOTE: must be non-static to allow for polymorphism
      HISTORY:
	2011-03-29 created
    */
    public function Convert_StoreToShow($iVal) {
	return $iVal;	// default/generic behavior
    }
    /*----
      ACTION: converts storable input to string usable in SQL statements
	without setting anything.
      INPUT: storage-formatted version
      RETURNS: display-formatted version
      NOTE: must be non-static to allow for polymorphism
      HISTORY:
	2011-03-29 created
    */
    public function Convert_StoreToSQL($iVal) {
	return SQLValue($iVal);	// default/generic behavior
    }
    /*----
      RETURNS: TRUE if Value() is essentially the same as iVal, FALSE otherwise
      USAGE: Mainly for descendant classes where the same value might be represented
	in different ways due to formatting.
      HISTORY:
	2014-07-22 Modified to handle situations where there is no Parent object.
    */
    public function FormName() {
	if ($this->HasParent()) {
	    $sName = $this->Parent()->FormPrefix().$this->Name();
	} else {
	    $sName = $this->Name();
	}
	return $sName;
    }
    public function RowName() {		// name of field (column) within data row (record)
	return $this->Name();
    }
}
/*====
  CLASS: clsFieldTime - date/time field
*/
class clsFieldTime extends clsField {
    private function NormalizeTimeString($iStr) {
	$val = NULL;
	if (is_string($iStr)) {
	    if (!empty($iStr)) {
		$dt = strtotime($iStr);
		$val = date('Y-m-d H:i:s',$dt);
	    }
	}
	return $val;
    }
    /*----
      IMPLEMENTATION: SQL seems to use a displayed date format
    */
    public function ValSQL() {
	$val = $this->NormalizeTimeString($this->ValStore());
	//$out = date('Y-m-d H:i:s',strtotime($this->ValStore()));
	$sql = SQLValue($val);
	return $sql;
    }
    /*----
      NOTES: It's kind of annoying that SQL stores timestamps in text format, because
	that just seems like a Wrong Kind of Format to store it in (i.e. Not Binary)...
	but there it is.
	Given that, the only normalization we'll do here is to convert the text into
	a binary format (*any* binary format) and then back again.
    */
    public function Convert_ShowToStore($iVal) {
	return $this->NormalizeTimeString($iVal);
    }
    /*----
      NOTES: See notes on Convert_ShowToStore(). We do exactly the same thing here,
	even though it's conceptually not the same thing.
    */
    public function Convert_StoreToShow($iVal) {
/*
	$dt = strtotime($iVal);	// stored in standard text format
	$val = date('Y-m-d H:i:s',$dt);	// convert to display format
	return $val;
*/
	return $this->NormalizeTimeString($iVal);
    }
}
/*====
  CLASS: clsFieldNum - numeric field
*/
class clsFieldNum extends clsField {
    public function ValStore($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->vValue = $iVal;
	}
	if (empty($this->vValue)) {
	    return NULL;
	} else {
	    return (float)$this->vValue;
	}
    }
    /*----
      NOTE: This mainly converts "" to NULL, and ensures that
	all non-blank values are handled numerically
    */
    public function Convert_ShowToStore($iVal) {
	if ($iVal == '') {
	    return NULL;
	} else {
	    return (float)$iVal;
	}
    }
}
/*----
  PURPOSE: Handles BIT-type fields, which are (oddly) returned as characters instead of numerical values
  FUTURE: Rename this to clsFieldBool_Bit
*/
class clsFieldBool extends clsField {
    protected function Convert_Show_toInternal($iVal) {
	if (is_numeric($iVal)) {
	    $val = ($iVal==0)?FALSE:TRUE;
	} else {
	    switch (strtolower($iVal)) {
	      case 1:
	      case chr(1):
	      case 'on':
	      case 'yes':
	      case 'true':
		$val = TRUE;
		break;
	      case '':
	      case chr(0):
	      case 0:
	      case 'off':
	      case 'no':
	      case 'false':
		$val = FALSE;
		break;
	      default:
		$val = NULL;
	    }
	}
	return $val;
    }
    protected function Convert_Internal_toStore($iVal) {
	return $iVal?chr(1):chr(0);
    }
    protected function Convert_Store_toInternal($iVal) {
	return (ord($iVal) != 0);
    }
    protected function Convert_Internal_toShow($iVal) {
	return $iVal?'YES':'no';
    }
    public function Convert_ShowToStore($iVal) {
	$val = $this->Convert_Show_toInternal($iVal);
	$val = $this->Convert_Internal_toStore($val);
	return $val;
    }
    public function Convert_StoreToShow($iVal) {
	$val = $this->Convert_Store_toInternal($iVal);
	$val = $this->Convert_Internal_toShow($val);
	return $val;
    }
    public function Convert_StoreToBool($iVal) {
	return (ord($iVal) != 0);
    }
    public function Convert_BoolToStore($iVal) {
	return $iVal?chr(1):chr(0);
    }
    public function ValBool($iVal=NULL) {
	if (!is_null($iVal)) {
	    $ch = $this->Convert_BoolToStore($iVal);
	    parent::ValStore($ch);
	}
	return $this->Convert_StoreToBool($this->ValStore());
    }
}

/*====
  CLASS: clsFieldBool_Int
  PURPOSE: boolean value stored in an integer field
  HISTORY:
    2011-10-06 created
*/
class clsFieldBool_Int extends clsFieldBool {
    protected function Convert_Internal_toStore($iVal) {
	return $iVal?1:0;
    }
    protected function Convert_Store_toInternal($iVal) {
	return !empty($iVal);
    }
    public function Convert_StoreToBool($iVal) {
	return ($iVal != 0);
    }
    public function Convert_BoolToStore($iVal) {
	return $iVal?FALSE:TRUE;
    }
    public function ValSQL() {
	return $this->vValue?1:0;
    }
}

/*====
  CLASS: clsCtrls -- group of form controls
    Does not implement a form, just the controls within a form.
    A form would generally contain exactly one of these.
*/
abstract class clsCtrls {
    protected $arCtrls;

    /*----
      RETURNS: object for the named control
    */
    public function Ctrl($iName) {
	return $this->arCtrls[$iName];
    }
    abstract protected function NewFieldsObject();
    private $oFields;
    /*----
      HISTORY:
	2013-12-21 renamed from Fields() to FieldsObject()
	2014-03-10 at some earlier point, was written to use NewFieldsObject()
	  if internal object was not set and no object was passed.
	  Prior to that, it threw an exception.
    */
    public function FieldsObject(clsFields $oFields=NULL) {
	if (is_null($this->oFields)) {
	    if (is_null($oFields)) {
		$this->oFields = $this->NewFieldsObject();
	    } else {
		$this->oFields = $oFields;
	    }
	}
	return $this->oFields;
    }
    public function AddField(clsField $oField, clsCtrl $oCtrl) {
	$sName = $oField->Name();
	$this->arCtrls[$sName] = $oCtrl;
	$oCtrl->Field($oField);
	$oCtrl->RowObject($this);
	$this->FieldsObject()->Add($oField);
    }
    /*
      HISTORY:
	2011-09-02 created
    */
    public function Debug_ListFields() {
	$out = '';
	$arCtrls = $this->arCtrls;
	if (is_array($arCtrls)) {
	    foreach ($arCtrls as $name => $ctrl) {
		$out .= ' '.$name;
	    }
	} else {
	    throw new exception('arCtrls is not an array');
	}
	return $out;
    }
}
/*====
  CLASS: clsCtrl -- abstract UI control
*/
abstract class clsCtrl {
    private $oField;
    private $oRow;
//    private $sIndex;	// indexing, for multi-record forms

    public function __construct() {
	//$this->sIndex = NULL;
	$this->oRow = NULL;
    }
    public function Field(clsField $oField=NULL) {
	if (!is_null($oField)) {
	    $this->oField = $oField;
	}
	return $this->oField;
    }/*
    public function Index($sIndex=NULL) {
	if (!is_null($sIndex)) {
	    $this->SetIndex($sIndex);
	}
	return $this->sIndex;
    }
    public function SetIndex($sIndex) {
	if ($sIndex == '') {
	    throw new exception('Setting index to empty string');
	}
	$this->sIndex = $sIndex;
    }
    public function HasIndex() {
	return !is_null($this->sIndex);
    }*/
    // PUBLIC so row objects can add themselves
    public function RowObject(clsCtrls $oRow=NULL) {
	if (!is_null($oRow)) {
	    $this->oRow = $oRow;
	}
	return $this->oRow;
    }
    protected function HasIndex() {
	return $this->RowObject()->HasIndex();
    }
    protected function IndexString() {
	return $this->RowObject()->IndexString();
    }
    abstract public function Render();	// render code to display the control
    abstract public function Receive();	// receive user-entered value for this control
}
/*====
  CLASS: clsCtrlHTML -- basic HTML input control
*/
class clsCtrlHTML extends clsCtrl {
    protected $arAttr;

    public function __construct(array $iAttr=NULL) {
	parent::__construct();
	$this->arAttr = $iAttr;
    }
    /*----
      RETURNS: base name of field, without any formatting or affixes
      HISTORY:
	2010-11-20 written
    */
    protected function NameBase() {
	return $this->Field()->FormName();
    }
    /*----
      RETURNS: full name of field, possibly including array index, for use in forms
      HISTORY:
	2010-11-18 written
    */
    protected function NameOut() {
	$strPart = $this->NameBase();
	if ($this->HasIndex()) {
	    $strOut = $strPart.'['.$this->IndexString().']';
	} else {
	    $strOut = $strPart;
	}
	return $strOut;
    }
    public function Render() {
	$out = '<input name="'
	  .$this->NameOut()
	  .'" value="'
	  .$this->RenderValue().'"'
	  .$this->RenderAttr().'>';
	return $out;
    }
    public function RenderValue() {
	return htmlspecialchars($this->Field()->ValShow());
    }
    protected function RenderAttr() {
	$out = '';
	if (is_array($this->arAttr)) {
	    foreach ($this->arAttr as $name => $value) {
		$out .= ' '.$name.'="'.$value.'"';
	    }
	}
	return $out;
    }
    /*----
      ACTION: Interpret a single received value (not an array)
      RETURNS: interpreted value
    */
    protected function ReceiveVal($iVal) {
	return $iVal;	// most controls need no further interpretation
    }
    /*----
      ACTION: Receive this control's data from user input
	Control data may be an array, in the case of a multi-row form.
    */
    public function Receive() {
	$htName = $this->NameBase();
	if (isset($_POST[$htName])) {
	    if ($this->HasIndex()) {
		$ar = $_POST[$htName];
		$strIdx = $this->IndexString();
		if (is_array($ar)) {
		    // if this line throws an error, find out why.
		    $val = $ar[$strIdx];
		} else {
		    $sMsg = '<b>Problem</b>: The field named "'
		      .$htName
		      .'" is lacking an array index. Value is "'.$ar.'", current index value is "'.$strIdx.'".'
		      .'<br>';
		    echo $sMsg;
		    $sClass = get_class($this);
		    throw new exception("Internal error (class:[$sClass] field:[$htName] value:[$ar] index:[$strIdx]");
		}
	    } else {
		$val = $_POST[$htName];
	    }
	} else {
	    $val = NULL;
	}
	$oVal = $this->ReceiveVal($val);
	return $oVal;
    }
}
/*====
  CLASS: clsCtrlHTML_TextArea -- <textarea> control
*/
class clsCtrlHTML_TextArea extends clsCtrlHTML {
    public function Render() {
	$objFld = $this->Field();
	$out = '<textarea name="'.$this->NameOut().'"';
	$out .= $this->RenderAttr().'>';
	$out .= htmlspecialchars($objFld->ValShow());
	$out .= '</textarea>';
	return $out;
    }
}
/*====
  CLASS: clsCtrlHTML_TextArea -- <textarea> control
*/
class clsCtrlHTML_CheckBox extends clsCtrlHTML {
    public function Render() {
	$objFld = $this->Field();
	$out = '<input type=checkbox name="'.$this->NameOut().'"';
	$out .= $this->RenderAttr();
	$out .= ($objFld->ValBool())?' checked':'';
	$out .= '>';
	return $out;
    }
    /*----
      NOTES:
	normally, TRUE is given as "On" and FALSE is blank, but evaluating empty()
	  allows for other values besides "On".
    */
    protected function ReceiveVal($iVal) {
	$oVal = !empty($iVal);
	return ($oVal);
    }
}
/*====
  CLASS: clsCtrlHTML_DropDown -- single-choice <select> control
*/
class clsCtrlHTML_DropDown extends clsCtrlHTML {
    protected $arRows;
    protected $strNoRows;	// show this if no rows to choose from
    protected $strChoose;	// show this for no-value

    public function __construct(array $iAttr=NULL,array $iRows=NULL) {
	parent::__construct($iAttr);
	$this->arRows = $iRows;
	$this->strNoRows = 'no choices';
	$this->strChoose = NULL;
    }
    public function Data_Rows(array $iRows) {
	$this->arRows = $iRows;
    }
    public function Text_NoRows($iText) {
	$this->strNoRows = $iText;
    }
    public function Text_Choose($iText) {
	$this->strChoose = $iText;
    }
    public function Render() {
	if (count($this->arRows > 0)) {
	    $oField = $this->Field();
	    if (!is_object($oField)) {
		throw new exception('Could not retrieve object for field index "'.$this->Index().'".');
	    }
	    $valCur = $oField->ValStore();
	    $out = "\n".'<select name="'.$this->NameOut().'"'.$this->RenderAttr().'>';
	    if (!is_null($this->strChoose)) {
		$out .= static::DropDown_row(NULL,$this->strChoose,$valCur);
	    }
	    foreach ($this->arRows as $val => $text) {
		$out .= static::DropDown_row($val,$text,$valCur);
	    }
	    $out .= "\n".'</select>';
	} else {
	    $out = 'no choices available';
	}
	return $out;
    }
    /*----
      NOTES:
	If we compare ($iVal == $iDefault), then NULL apparently equals 0 -- not wanted if NULL should mean "choose one".
	If we compare ($iVal === $iDefault), then under some circumstances numbers apparently get converted
	  to strings and the comparison fails when it should succed. So force both sides to (string) before
	  comparing. (Will this cause trouble when comparing 0 to NULL?)
    */
    protected static function DropDown_row($iVal,$iTxt,$iDefault=NULL) {
	if (!is_scalar($iTxt)) {
	    throw new exception('Expected string for iTxt, got something else.');
	}
	if ((string)$iVal == (string)$iDefault) {	// must be "===", else NULL=0 apparently
	    $htSelect = " selected";
	} else {
	    $htSelect = '';
	}
	$out = "\n".'<option'.$htSelect.' value="'.$iVal.'">'.htmlspecialchars($iTxt).'</option>';
	return $out;
    }
}
/*====
  CLASS: clsCtrlHTML_Hidden
  USAGE: for calculated fields that we don't need to show at edit time
  HISTORY:
    2012-02-06 created for isEnabled fields in Bins and Places (VbzCart)
*/
class clsCtrlHTML_Hidden extends clsCtrlHTML {
    public function Render() {
	$out = '<input type=hidden name="'.$this->NameOut().'"';
	$out .= $this->RenderAttr();
	$out .= ' value="'.$this->Field()->ValShow().'"';
	$out .= '>';
	return $out;
    }
}
/*%%%%
  CLASS: clsCtrlHTML_Fixed -- preset text
  USAGE: Makes it easier to have pre-set field values in new records
    Renders as a hidden control plus Text_Show().
  NOTE: I think there's a better way to do this, already written
    and in use. Does anything still use this control? Document why.
*/
class clsCtrlHTML_Fixed extends clsCtrlHTML {
    protected $strShow;

    public function Text_Show($iText) {
	$this->strShow = $iText;
    }
    public function Render() {
	$out = '<input type=hidden name="'.$this->NameOut().'"';
	$out .= $this->RenderAttr();
	$out .= ' value="'.$this->Field()->ValShow().'"';
	$out .= '>'.$this->strShow;
	return $out;
    }
}
/*%%%%
  CLASS: clsCtrlHTML_ReadOnly -- read-only text
  USAGE: For fields that cannot be edited and which
    do not need to be set
    Renders just the value; does not submit data
    as part of the form.
*/
class clsCtrlHTML_ReadOnly extends clsCtrlHTML {
    public function Render() {
	return $this->Field()->ValShow();
    }
}

/*%%%%
  CLASS: clsCtrlHTML_Rating -- for entering a rating on a given scale
  LATER:
    * This version is crude; eventually we should do something more intuitive and user-friendly with JavaScript.
    * Add ability to set the range and interval; starting with -10 to +10 increment 1.
*/
class clsCtrlHTML_Rating extends clsCtrlHTML {
    public function Render() {
	$out = NULL;
	$idxVal = $this->Field()->ValShow();
	for ($idx=-10;$idx<=+10;$idx++) {
	    $out .= "\n".'<input type=radio name="'.$this->NameOut().'"';
	    $out .= $this->RenderAttr();
	    $out .= ' value='.$idx.' title='.$idx;
	    if ($idx == $idxVal) {
		$out .= ' CHECKED';
	    }
	    $out .= '>';
	}
	return $out;
    }
}
/*====
  UTILITY FUNCTIONS
*/
function DropDown_arr($iName,array $iList,$iDefault=NULL,$iChoose=NULL) {
    $out = "\n<select name=\"$iName\">";
    if (!is_null($iChoose)) {
	$out .= DropDown_row(NULL,$iChoose,$iDefault);
    }
    foreach ($iList as $key => $val) {
	$out .= DropDown_row($key,$val,$iDefault);
    }
    $out .= "\n</select>";
    return $out;
}
/*----
  NOTES:
    If we compare ($iVal == $iDefault), then NULL apparently equals 0 -- not wanted if NULL should mean "choose one".
    If we compare ($iVal === $iDefault), then under some circumstances numbers apparently get converted
      to strings and the comparison fails when it should succed. So force both sides to (string) before
      comparing. (Will this cause trouble when comparing 0 to NULL?)
  DEPRECATED - use clsCtrlHTML_DropDown::RenderRow()
*/
function DropDown_row($iVal,$iTxt,$iDefault=NULL) {
    if ((string)$iVal == (string)$iDefault) {	// must be "===", else NULL=0 apparently
	$htSelect = " selected";
    } else {
	$htSelect = '';
    }
    $out = "\n".'<option'.$htSelect.' value="'.$iVal.'">'.htmlspecialchars($iTxt).'</option>';
    return $out;
}
function RadioBtns($iName,array $iList,$iDefault=NULL) {
    $out = '';
    foreach ($iList as $val => $txt) {
	$out .= RadioBtn($iName,$val,$txt,$iDefault);
    }
    return $out;
}
function RadioBtn($iName,$iVal,$iTxt,$iDefault=NULL) {
    if ((string)$iVal == (string)$iDefault) {	// must be "===", else NULL=0 apparently
	$htSelect = " checked";
    } else {
	$htSelect = '';
    }
    $out = "\n".'<input type=radio name="'.$iName.'"'.$htSelect.' value="'.$iVal.'">'.htmlspecialchars($iTxt).'</option>';
    return $out;
}
/*====
  PURPOSE: Handles all aspects for entering a short list referring to items which may or may not exist in a specific table
    This may eventually descend from an abstract clsWidget type, or maybe it can be descended from one of the Form types
    such as clsCtrlHTML. Right now I'm just trying to encapsulate this code before it's being used in a zillion places, each
    with minor variations.
  HISTORY:
    2011-01-31 created from code in clsAdminTopic
    2011-09-30
	added txtConf_list option
      	btnChg_Text = NULL now means don't show the button at all
	  if it's not displayed, then the calling code needs to handle the topic list
	    NEED TO DOCUMENT where it can be retrieved from
*/
class clsWidget_ShortList {
    protected $arOpts;

    /*----
      OPTIONS:
	name		string to be used as a suffix for control names in this widget (must be unique on page)
			  This is never displayed.
	btnChk_Name	name of button for stage 1 (checking of user-entered list)
	btnChk_Text	text to display on stage 1 button
	txtCargo_sng	description of what the list contains, singular (e.g. "topic name", "title ID")
	txtCargo_plr	same as txtCargo_sng but plural (e.g. "topic names", "title IDs")
	txtProd_sng	ultimate product of what the list contains, singular (e.g. "topic" or "title")
	txtProd_plr	same as txtProd_sng but plural (e.g. "topics", "titles")
	txtConf_list	description of what we're doing to the list of topics when they are acted on
	btnChg_Name	name of button for stage 2 (making changes approved by user)
	btnChg_Text	text to display on stage 2 button
    */
    public function Options(array $iOpts=NULL) {
	if (is_array($iOpts)) {
	    $this->arOpts = $iOpts;
	}
	return $this->arOpts;
    }
    public function Option($iName,$iValue=NULL) {
	if (!is_null($iValue)) {
	    $this->arOpts[$iName] = $iValue;
	}
	$val = nzArray($this->arOpts,$iName);
	return $val;
    }
    public function CtrlName($iAffix) {
	return $this->Option('name').'-'.$iAffix;
    }
    public function CtrlName_Btn_Check() {
	return $this->CtrlName('btnChk');
    }
    public function CtrlName_Btn_Change() {
	return $this->CtrlName('btnChg');
    }
    public function Data_toChange() {
	global $wgRequest;

	$strName = $this->CtrlName('list');
	return $wgRequest->GetArray($strName);
    }
    /*----
      RETURNS: TRUE if the confirmation (stage 2) button should be shown
    */
    public function OptShow_Conf_Button() {
	return (!is_null($this->Option('btnChg_Text')));
    }
    /*----
      RETURNS: The list originally entered, as received from the form
    */
    public function Data_ListRaw($iPfx='"',$iSfx='"',$iHideHTML=TRUE) {
	if (isset($this->strList)) {
	    if ($iHideHTML) {
		$txt = htmlspecialchars($this->strList);
	    } else {
		$txt = $this->strList;
	    }
	    $out = $iPfx.$txt.$iSfx;
	} else {
	    $out = NULL;
	}
	return $out;
    }
    /*----------

      SEQUENCE:
	PROCESS:
	  RenderForm_Entry() - initial entry of list
	  HandleForm_Entry() - show parsed list for approval
	RENDER:
	  1. HandleInput()
	    ReceiveForm_Entry()
	      HandleForm_Entry() - if correct button pressed
		$fHandleData_Check()
		shows list to confirm, and approval button
	    ReceiveForm_Change()
	      HandleForm_Change() - if correct button pressed
		$fHandleEvStart
		for each item: $fHandleItem
		$fHandleEvFinish
	  2. (open the form, display the controls)
	  3. RenderForm_Entry()
	  4. (close the form)
    */
    /*----
      ACTION: Receives data from the Entry form, parses and processes it, and renders the next stage form
    */
    public function HandleInput($iAlways=FALSE) {
	$out = NULL;
	$out .= $this->ReceiveForm_Entry($iAlways);
	$out .= $this->ReceiveForm_Change();
	return $out;
    }
    /*----
      FUTURE: create a platform-independent Request object.
	For now, keeping MW interactions isolated by putting them in separate methods
	  and rewriting those as needed.
    */
    protected function ReceiveForm_Entry($iAlways) {
	$out = NULL;
	$sName = $this->CtrlName_Btn_Check();
	$doChk = clsHTTP::Request()->GetBool($sName);
	$this->doChk = $doChk;
	if ($doChk || $iAlways) {
	    $this->strList = (string)$_REQUEST[$this->CtrlName('txtNew')];
	    $this->sepType = (string)$_REQUEST[$this->CtrlName('sepType')];
	    $out .= $this->HandleForm_Entry();
	}
	return $out;
    }
    /* MW version
    protected function ReceiveForm_Entry($iAlways) {
	global $wgRequest;

	$doChk = $wgRequest->GetBool($this->CtrlName_Btn_Check());
	$this->doChk = $doChk;
	$out = NULL;
	if ($doChk || $iAlways) {
	    $this->strList = $wgRequest->GetText($this->CtrlName('txtNew'));
	    $this->sepType = $wgRequest->GetText($this->CtrlName('sepType'));
	    $out .= $this->HandleForm_Entry();
	}
	return $out;
    }
    */
    /*----
      FUTURE: create a platform-independent Request object.
	For now, keeping MW interactions isolated by putting them in separate methods
	  and rewriting those as needed.
    */
    protected function ReceiveForm_Change() {
	$sName = $this->CtrlName_Btn_Change();
	$doAdd = clsHTTP::Request()->GetBool($sName);
	if ($doAdd) {
	    $this->arAdd = $this->Data_toChange();
	    return $this->HandleForm_Change();
	}
	return NULL;
    }
    /* MW version
    protected function ReceiveForm_Change() {
	global $wgRequest;

	$this->doAdd = $wgRequest->GetBool($this->CtrlName_Btn_Change());
	if ($this->doAdd) {
	    $this->arAdd = $this->Data_toChange();
	    return $this->HandleForm_Change();
	}
    } */
    /*----
      ACTION: parses the input text into an array
      INPUT:
	$iNone = what to display if no form data has been received yet
    */
    protected function HandleForm_Entry($iNone=NULL) {
	$sepType = $this->sepType;
	if (empty($sepType)) {
	    $out = $iNone;
	} else {
	    $strList = $this->strList;
	    $txtCargoSi = $this->Option('txtProd_sng');
	    $txtCargoPl = $this->Option('txtProd_plr');
	    if (strlen($strList) > 0) {
		switch ($sepType) {
		  case 'pfx':	// prefix-separated
		    $xts = new xtString($strList);
		    $arRaw = $xts->Xplode();
		    break;
		  case 'com':	// commas
		    $arRaw = preg_split('/,/',$strList);
		    break;
		  case 'spc':	// spaces
		    $arRaw = preg_split('/[ \t]/',$strList);
		    break;
		}
		$cntAdd = count($arRaw);
	    } else {
		// apparently the splitting functions don't gracefully handle zero-length strings
		$cntAdd = 0;
		$arRaw = array();
	    }
	    $txtDescr = Pluralize($cntAdd,$txtCargoSi,$txtCargoPl);

	    $out = $this->Option('txtConf_list').' '.$cntAdd.' '.$txtDescr.': ';
	    $fHandle = $this->Option('fHandleData_Check');
	    $idx = 0;
	    foreach ($arRaw as $txt) {
		$txt = trim($txt);
		$arUse = $fHandle($txt);
		$htShow = $arUse['html'];
		$htVal = htmlspecialchars($arUse['text']);
		$idx++;	// used for array in form
		$out .= '['.$htShow.']<input type=hidden name="'.$this->CtrlName('list').'['.$idx.']" value="'.$htVal.'">';
	    }
	    if ($this->OptShow_Conf_Button()) {
		$btnName = $this->CtrlName_Btn_Change();
		$btnText = $this->Option('btnChg_Text').' '.$txtDescr;
		$out .= '<input type=submit name="'.$btnName.'" value="'.$btnText.'">';
	    }
	}
	return $out;
    }
    protected function HandleForm_Change() {
	$arAdd = $this->arAdd;
	$cntAdd = count($arAdd);
	$txtProd = Pluralize($cntAdd,$this->Option('txtProd_sng'),$this->Option('txtProd_plr'));

	$cntAdd = count($arAdd);

	$fHandleEvStart = $this->Option('fHandleData_Change_Start');
	$fHandleEvFinish = $this->Option('fHandleData_Change_Finish');
	$fHandleItem = $this->Option('fHandleData_Change_Item');

	$fHandleEvStart($cntAdd.' '.$txtProd);
	$list = '';
	foreach ($arAdd as $idx => $txt) {
	    $list .= ' '.$fHandleItem($txt);
	}
	$out = 'New ID'.Pluralize($cntAdd).':'.$list;
	$fHandleEvFinish($out);
	return $out;
    }
    /*----
      RETURNS: HTML for the initial entry form and button
    */
    public function RenderForm_Entry($iAlways=FALSE) {
	if (!$this->doChk || $iAlways) {
	    $htName_Btn = '"'.$this->CtrlName_Btn_Check().'"';
	    $htText_Btn = '"'.$this->Option('btnChk_Text').'"';
	    $txtCargo = $this->Option('txtCargo_plr');

	    $out = "\n<input type=submit name=$htName_Btn value=$htText_Btn>";
	    $htNameNew = '"'.$this->CtrlName('txtNew').'"';
	    $htNameSep = '"'.$this->CtrlName('sepType').'"';
	    $htListTxt = $this->Data_ListRaw();
	    $out .= "\n<input size=40 name=$htNameNew value=$htListTxt> - list of $txtCargo separated by ";
	    $out .= '<label style="white-space: nowrap;"><input type=radio name='.$htNameSep.' value="pfx">prefix</label>';
	    $out .= '<label style="white-space: nowrap;"><input type=radio name='.$htNameSep.' value="com">comma</label>';
	    $out .= '<label style="white-space: nowrap;"><input type=radio name='.$htNameSep.' value="spc" checked>space</label>';	// default

	    return $out;
	} else { return NULL; }
    }
}

/*
  PURPOSE: This is primarily for debugging, but may eventually have some use in a general db-management app.
*/
function ShowRecords(clsRecs_abstract $iRows) {
    if ($iRows->HasRows()) {
	// PASS 1: get all column names
	$arCols = array();
	while ($iRows->NextRow()) {
	    $arRow = $iRows->Values();
	    foreach ($arRow as $strCol => $val) {
		if (!array_key_exists($strCol,$arCols)) {
		    $arCols[$strCol] = NULL;
		}
	    }
	}
	// SHOW THE HEADER
	$out = '<table><tr>';
	foreach ($arCols as $strCol => $null) {
	    $out .= "\n<th>$strCol</th>";
	}
	$out .= "\n</tr>";

	// PASS 2: show the data
	$iRows->StartRows();
	while ($iRows->NextRow()) {
	    $arRow = $iRows->Values();
	    $out .= "\n<tr>";
	    foreach ($arCols as $strCol => $null) {
		$val = $arRow[$strCol];
		$htVal = htmlspecialchars($val);
		$out .= "<td>$htVal</td>";
	    }
	    $out .= "\n</tr>";
	}
	$out .= "\n</table>";
    } else {
	$out = 'NO DATA';
    }
    return $out;
}