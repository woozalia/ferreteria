<?php
/*
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
    2015-11-24 moved from old forms.php (no longer used) into form-shortlist.php
*/
class clsWidget_ShortList {

    const KS_DEFAULT_DESCR = ' - list of [[cargo]], separated by';
    const KS_DEFAULT_LAYOUT = <<<__END__

[[input-text]][[input-instruct]]
<label style="white-space: nowrap;">[[choice-prefix]]</label>
<label style="white-space: nowrap;">[[choice-comma]]</label>
<label style="white-space: nowrap;">[[choice-space]]</label>
__END__;

    protected $arOpts;

    // ++ SETUP ++ //
    
    /*----
      OPTIONS:
	name		string to be used as a suffix for control names in this widget (must be unique on page)
			  This is never displayed.
	btnChk_Name	name of button for stage 1 (checking of user-entered list)
	btnChk_Text	text to display on stage 1 button
	txtDescr	description to go after entry form; should include "[[cargo]]" for txtCargo_*
	txtCargo_sng	description of what the list contains, singular (e.g. "topic name", "title ID")
	txtCargo_plr	same as txtCargo_sng but plural (e.g. "topic names", "title IDs")
	txtProd_sng	ultimate product of what the list contains, singular (e.g. "topic" or "title")
	txtProd_plr	same as txtProd_sng but plural (e.g. "topics", "titles")
	txtConf_list	description of what we're doing to the list of topics when they are acted on
	btnChg_Name	name of button for stage 2 (making changes approved by user)
	btnChg_Text	text to display on stage 2 button
	txtLayout	layout of control elements
    */
    public function Options(array $iOpts=NULL) {
	if (is_array($iOpts)) {
	    $this->arOpts = $iOpts;
	}
	return $this->arOpts;
    }
    public function Option($iName,$iValue=NULL,$vDefault=NULL) {
	if (!is_null($iValue)) {
	    $this->arOpts[$iName] = $iValue;
	}
	$val = clsArray::Nz($this->arOpts,$iName,$vDefault);
	return $val;
    }
    protected function GetOption($sName,$sDefault) {
	return clsArray::Nz($this->arOpts,$sName,$sDefault);
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
    public function CtrlName_Text_New() {
	return $this->CtrlName('txtNew');
    }
    public function CtrlName_Sep_Type() {
	return $this->CtrlName('sepType');
    }
    public function OptionValue_Layout($s=NULL) {
	return $this->Option('txtLayout',$s,self::KS_DEFAULT_LAYOUT);
    }
    
    // -- SETUP -- //
    // ++ INPUT ++ //
    
    // PUBLIC so outside functions know whether to do certain things
    //	This may be bad design, but not sure how to improve it.
    public function DidRequestCheck() {
    	$sName = $this->CtrlName_Btn_Check();
	return clsHTTP::Request()->GetBool($sName);
    }

    // ++ INTERNAL DATA ++ //
    
    protected function GetRequest($sName) {
	if (array_key_exists($sName,$_REQUEST)) {
	    return (string)$_REQUEST[$sName];
	} else {
	    throw new exception("Form error: Expected a value in '$sName' but didn't receive one.");
	}
    }
    /*----
      RETURNS: TRUE if the confirmation (stage 2) button should be shown
    */
    protected function OptShow_Conf_Button() {
	return (!is_null($this->Option('btnChg_Text')));
    }
    /*----
      RETURNS: The list originally entered, as received from the form
    */
    public function Data_ListRaw($iPfx='"',$iSfx='"',$iHideHTML=TRUE) {
	if (isset($this->strList)) {
	    if ($iHideHTML) {
		$txt = fcString::EncodeForHTML($this->strList);
	    } else {
		$txt = $this->strList;
	    }
	} else {
	    $txt = NULL;
	}
	return '"'.$txt.'"';	// always quote - input tag needs it
    }
    
    // -- INTERNAL DATA -- //
    // ++ OUTPUT ++ //
    
    public function Data_toChange() {
	$strName = $this->CtrlName('list');
	return clsHTTP::Request()->GetArray($strName);
    }
    /*
    private $arOut;
    public function OutputArray(array $ar=NULL) {
	if (!is_null($ar)) {
	    $this->arOut = $ar;
	}
	return $this->arOut;
    }*/
    
    // -- OUTPUT -- //
    // ++ MAIN PROCESS ++ //
    
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
      INPUT:
	$doAlways: if true, we expect to see a complete form submission regardless of whether
	  the Check button is seein in the input.
    */
    public function HandleInput($doAlways=FALSE) {
	$out = NULL;
	$out .= $this->ReceiveForm_Entry($doAlways);
	$out .= $this->ReceiveForm_Change();
	return $out;
    }
    protected function ReceiveForm_Entry($iAlways) {
	$out = NULL;
	$doChk = $this->DidRequestCheck();
	$this->doChk = $doChk;
	if ($doChk || $iAlways) {
	    $this->strList = $this->GetRequest($this->CtrlName_Text_New());
	    $this->sepType = $this->GetRequest($this->CtrlName_Sep_Type());
	    $out .= $this->HandleForm_Entry();
	}
	return $out;
    }
    protected function ReceiveForm_Change() {
	$sName = $this->CtrlName_Btn_Change();
	$doAdd = clsHTTP::Request()->GetBool($sName);
	if ($doAdd) {
	    $this->arAdd = $this->Data_toChange();
	    return $this->HandleForm_Change();
	}
	return NULL;
    }
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
	    $txtDescr = fcString::Pluralize($cntAdd,$txtCargoSi,$txtCargoPl);

	    $out = $this->Option('txtConf_list').' '.$cntAdd.' '.$txtDescr.': ';
	    $fHandle = $this->Option('fHandleData_Check');
	    //$arData = NULL;
	    $idx = 0;
	    foreach ($arRaw as $txt) {
		$txt = trim($txt);
		$arUse = $fHandle($txt);
		if (is_array($arUse)) {	// $fHandle should return NULL for non-viable entries
		    $htShow = $arUse['html'];
		    $htVal = $arUse['val'];
		    /*
		    if (clsArray::Exists($arUse,'id')) {
			$id = $arUse['id'];
			$arData[] = $id;
		    }*/
		    $idx++;	// used for array in form
		    $out .= '['.$htShow.']<input type=hidden name="'.$this->CtrlName('list').'['.$idx.']" value="'.$htVal.'">';
		}
	    }
	    //$this->OutputArray($arData);
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
	$txtProd = fcString::Pluralize($cntAdd,$this->Option('txtProd_sng'),$this->Option('txtProd_plr'));

	$cntAdd = count($arAdd);

	$fHandleEvStart = $this->Option('fHandleData_Change_Start');
	$fHandleEvFinish = $this->Option('fHandleData_Change_Finish');
	$fHandleItem = $this->Option('fHandleData_Change_Item');

	$fHandleEvStart($cntAdd.' '.$txtProd);
	$list = '';
	foreach ($arAdd as $idx => $txt) {
	    $list .= ' '.$fHandleItem($txt);
	}
	$out = 'New ID'.fcString::Pluralize($cntAdd).':'.$list;
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
	    $htListTxt = $this->Data_ListRaw();	// is sanitized (but apparently not quoted, at least not if blank)
	    $stDescr = $this->GetOption('txtDescr',self::KS_DEFAULT_DESCR);
	    $oTplt = new fcTemplate_array('[[',']]',$stDescr);
	    $oTplt->VariableValue('cargo',$txtCargo);
	    $sDescr = $oTplt->Render();
	    
	    $stLayout = $this->OptionValue_Layout();
	    $oTplt->Template($stLayout);
	    $oTplt->VariableValues(
	      array(
		'input-text'	=> "<input size=40 name=$htNameNew value=$htListTxt>",
		'input-instruct' => $sDescr,
		'choice-prefix'	=> "<input type=radio name=$htNameSep value='pfx'>prefix",
		'choice-comma' => "<input type=radio name=$htNameSep value='com'>comma",
		'choice-space' => "<input type=radio name=$htNameSep value='spc' checked>space",
		)
	      );
	    $out .= $oTplt->Render();
/*	    
	    $out .= <<<__END__

<input size=40 name=$htNameNew value=$htListTxt>$sDescr
<label style="white-space: nowrap;"><input type=radio name=$htNameSep value='pfx'>prefix</label>
<label style="white-space: nowrap;"><input type=radio name=$htNameSep value="com">comma</label>
<label style="white-space: nowrap;"><input type=radio name=$htNameSep value="spc" checked>space</label>
__END__;
*/
	    return $out;
	} else { return NULL; }
    }
    
    // -- MAIN PROCESS -- //

}
