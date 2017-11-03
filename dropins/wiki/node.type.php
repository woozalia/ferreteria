<?php
/*
  HISTORY:
    2017-08-26 abstract Node Type base class; "Handler" classes moved from here to node-dispatch.php
*/
// PURPOSE: Base class for specific node type classes
abstract class fctNodeTypesBase extends fctNodesBase implements fiLinkableTable, fiEventAware, fiInsertableTable {
    use ftSingleKeyedTable;
    use ftSource_forTable;
    use ftLinkableTable;
    use ftExecutableTwig;
    //use ftTableAccess_NodeLogic;	// for looking up core Node data
    //use ftTableAccess_LeafLogic;	// for looking up core Leaf data

    abstract public function GetTypeString();
    /*
    public function SelectRecords($sqlWhere = NULL, $sqlSort = NULL, $sqlOther = NULL) {
	return parent::SelectRecords($sqlWhere,$sqlSort,$sqlOther);
    }*/
}
// PURPOSE: So we can override FormInsert() and FormUpdate()
class fcrNodeTypeBase_trait extends fcrNodeBase {
    use ftSaveableRecord;	// implements fiEditableRecord
}
abstract class fcrNodeTypeBase extends fcrNodeTypeBase_trait implements fiLinkableRecord, fiEventAware, fiEditableRecord {
    use ftShowableRecord;
    use ftLinkableRecord;
    use ftExecutableTwig;	// dispatch events

    // ++ SETUP ++ //

    // OVERRIDE of field method
    protected function GetTypeString() {
	return $this->GetTableWrapper()->GetTypeString();
    }

    // ++ SETUP ++ //
    // ++ EVENTS ++ //
    
    // TODO: do we actually need event-handler stuff here? it's also in node.index.php
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$id = $this->GetKeyValue();
	$htTitle = 'Node #'.$id;
	$sTitle = 'n'.$id;
    
	$oPage = fcApp::Me()->GetPageObject();
	//$oPage->SetPageTitle($sTitle);
	$oPage->SetBrowserTitle($sTitle);
	$oPage->SetContentTitle($htTitle);
    }
    public function Render() {
	return $this->AdminPage();
    }

    // -- EVENTS -- //
    // ++ DATA WRITE ++ //

    /*----
      INPUT:
	$arData = data to insert, in STORAGE format
      RETURNS: nothing
    */
    public function FormInsert(array $arData) {
	$t = $this->GetTableWrapper();
	$t->InsertNode($arData,$this->GetTypeString());
    }
    public function FormUpdate(array $arData) {
	$this->UpdateNode($arData,$this->GetTypeString());
    }
    
    // -- DATA WRITE -- //
    // ++ WEB UI ++ //
    
/* 2017-10-13 not actually used?
    // CEMENT
    protected function AdminRows_settings_columns() {
	$arCols = array(
	  'ID'		=> 'ID',
	  'Class'	=> 'Class',
	  'WhenMade'	=> 'Created',
	  );
	return $arCols;
    }
    // UNSTUB
    protected function AdminField($sField) {
	switch($sField) {
	  case 'ID':
	    $val = $this->SelfLink();
	    break;
	  default:
	    $val = $this->GetFieldValue($sField);
	}
	return "<td>$val</td>";
    }
    */
    protected function AdminPage() {
	$oPathIn = fcApp::Me()->GetKioskObject()->GetInputObject();
	$oFormIn = fcHTTP::Request();
	$frmEdit = $this->PageForm();
	if ($this->IsNew()) {
	    $frmEdit->ClearValues();
	} else {
	    $this->LoadLeafValues();
	    $frmEdit->LoadRecord();
	}
	$doEdit = FALSE;	// for now

	$oTplt = $this->PageTemplate();
	$arCtrls = $frmEdit->RenderControls($doEdit);
	$arCtrls['ID'] = $this->SelfLink();
	
	$out = NULL;
	
	if ($doEdit) {
	    $out .= "\n<form method=post>";
	} else {
	}
	
	$oTplt->SetVariableValues($arCtrls);
	$out .= $oTplt->RenderRecursive();

	if ($doEdit) {
	    $out .= <<<__END__
<input type=submit name=btnSave value="Save">
</form>
__END__;
	}
	
	return $out;

    }
    private $tpPage;
    protected function PageTemplate() {
	if (empty($this->tpPage)) {
	    $sTplt = <<<__END__
  <table class=record-block>
    <tr><td align=right><b>ID</b>:</td><td>[[ID]]</td></tr>
    <tr><td align=right><b>Class</b>:</td><td>[[Class]]</td></tr>
    <tr><td align=right><b>Created</b>:</td><td>[[WhenMade]]</td></tr>
  </table>
__END__;
	    $this->tpPage = new fcTemplate_array('[[',']]',$sTplt);
	}
	return $this->tpPage;
    }
    private $oForm;
    private function PageForm() {
	if (empty($this->oForm)) {
	    $oForm = new fcForm_DB($this);
	    
	      $oField = new fcFormField_Time($oForm,'WhenMade');
		
	      $oField = new fcFormField_Text($oForm,'Class');
		$oCtrl = new fcFormControl_HTML($oField,array('size'=>40));

	    $this->oForm = $oForm;
	}
	return $this->oForm;
    }

    // -- WEB UI -- //
}