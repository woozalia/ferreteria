<?php
/*
  PURPOSE: admin forms for event entries
  HISTORY:
    2017-04-15 started
    2017-04-29 This is pretty seriously clumsy -- in fact, most of EventPlex is kind of clumsy -- but the basic design seems sound. Optimize and streamline later; just getting it working, for now.
*/
class fcForm_EventPlex extends fcForm_DB {
    public function BuildControls() {
	$oField = new fcFormField_Time($this,'e_WhenStart');

/* 2017-04-15 Actually, this seems like a waste of CPU.	    
	$oField = new fcFormField_Num($oForm,'e_ID_Session');
	  $oCtrl = new fcFormControl_HTML_DropDown($oField,array());
	    $oCtrl->SetRecords($this->UserSessionRecords());
	      $oCtrl->AddChoice(NULL,'(none)');

	$oField = new fcFormField_Num($oForm,'e_ID_Acct');
	  $oCtrl = new fcFormControl_HTML_DropDown($oField,array());
	    $oCtrl->SetRecords($this->UserAccountRecords());
	      $oCtrl->AddChoice(NULL,'(none)');
*/	    
	$oField = new fcFormField_Text($this,'e_TypeCode');
	  //$oCtrl = new fcFormControl_HTML($oField,array('size'=>30));

	$oField = new fcFormField_Text($this,'e_Descrip');
	  //$oCtrl = new fcFormControl_HTML($oField,array('size'=>4));

	$oField = new fcFormField_Text($this,'n_Notes');
	  //$oCtrl = new fcFormControl_HTML_TextArea($oField,array('rows'=>4,'cols'=>60));
    }
    private $tp=NULL;
    public function GetTemplate() {
	if (is_null($this->tp)) {
	    $sTplt = <<<__END__
<table class=form-record>
  <tr><td class='form-label'>ID</td><td>: [#ID#] created [#e_WhenStart#]</td></tr>
  <tr><td class='form-label'>Session</td><td>: [#ID_Session#]</td></tr>
  <tr><td class='form-label'>Account</td><td>: [#ID_Acct#]</td></tr>
  <tr><td class='form-label'>Type Code</td><td>: [#e_TypeCode#]</td></tr>
  <tr><td class='form-label'>Description</td><td>: [#e_Descrip#]</td></tr>
  <tr><td class='form-label'>Stashed Data</td><td>: [#Stash#]</td></tr>
  <tr><td colspan=3>
  [#section:done#]
  [#section:table#]
  [#section:note#]
  </td></tr>
</table>
__END__;
	    $this->tp = new fcTemplate_array('[#','#]',$sTplt);
	}
	return $this->tp;
    }
}
class fcForm_EventPlex_Done extends fcForm_DB {
    public function BuildControls() {
	$oField = new fcFormField_Time($this,fcrEventPlect::kEVENT_TYPE_KEY_DONE.'_WhenFinish');
	$oField = new fcFormField_Text($this,fcrEventPlect::kEVENT_TYPE_KEY_DONE.'_StateCode');
	$oField = new fcFormField_Text($this,fcrEventPlect::kEVENT_TYPE_KEY_DONE.'_Descrip');
	$oField = new fcFormField_Text($this,fcrEventPlect::kEVENT_TYPE_KEY_DONE.'_Stash');	// for now; TODO: control-type for stashes
    }
    private $tpPageDone=NULL;
    public function GetTemplate() {
	if (is_null($this->tpPageDone)) {
	
	// TODO: revise
	    $sTplt = <<<__END__
<table class=form-record>
  <tr><td class='form-label'>Finished</td><td>: [#done_WhenFinish#]</td></tr>
  <tr><td class='form-label'>State Code</td><td>: [#done_StateCode#]</td></tr>
  <tr><td class='form-label'>Description</td><td>: [#done_Descrip#]</td></tr>
  <tr><td class='form-label'>Data Stash</td><td>: [#done_Stash#]</td></tr>
</table>
__END__;
	    $this->tpPageDone = new fcTemplate_array('[#','#]',$sTplt);
	}
	return $this->tpPageDone;
    }
}
class fcForm_EventPlex_Table extends fcForm_DB {
    public function BuildControls() {
	$oField = new fcFormField_Num($this,fcrEventPlect::kEVENT_TYPE_KEY_INTABLE.'_TableKey');
	$oField = new fcFormField_Num($this,fcrEventPlect::kEVENT_TYPE_KEY_INTABLE.'_TableRow');
    }

    private $tp=NULL;
    public function GetTemplate() {
	if (is_null($this->tp)) {
	    $sPfx = fcrEventPlect::kEVENT_TYPE_KEY_INTABLE;
	    $sTplt = <<<__END__
<table class=form-record>
  <tr><td class='form-label'><b>Table</b>:</td><td>[#{$sPfx}_TableKey#]</td></tr>
  <tr><td class='form-label'><b>Row</b>:</td><td>[#{$sPfx}_TableRow#]</td></tr>
</table>
__END__;
	    $this->tp = new fcTemplate_array('[#','#]',$sTplt);
	}
	return $this->tp;
    }
}
class fcForm_EventPlex_Note extends fcForm_DB {
    public function BuildControls() {
	$oField = new fcFormField_Text($this,fcrEventPlect::kEVENT_TYPE_KEY_NOTE.'_Notes');
    }
    private $tp=NULL;
    public function GetTemplate() {
	if (is_null($this->tp)) {
	    $sfNotes = fcrEventPlect::kEVENT_TYPE_KEY_NOTE.'_Notes';
	    $sTplt = <<<__END__
  <tr><td class='form-label'><b>Event Note</b>:</td><td>[#$sfNotes#]</td></tr>
__END__;
	    $this->tp = new fcTemplate_array('[#','#]',$sTplt);
	}
	return $this->tp;
    }
}