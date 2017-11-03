<?php
/*::::
  PURPOSE: A Simple Wiki Page has one Node each for Title and Content.
    Titles do not have to be unique; there will be a Topic tree for context.
  HISTORY:
    2017-09-20 Somehow, fcrNode_SimpleWikiPage was still descending from fcrNodeBase instead of fcrNodeTypeBase. Fixed.
*/
class fctNodes_SimpleWikiPage extends fctNodeTypesBase {
    //use ftTableAccess_AdminNode, ftTableAccess_Leaf;
    use ftTableAccess_LeafIndex;

    // ++ SETUP ++ //

    protected function InitVars() {
	self::RegisterNodeType($this);
	// so far, this node-type only uses text values:
	$sClassText = $this->LeafsTextClass();
	fcMapLeafNames::Register('title',$sClassText);
	fcMapLeafNames::Register('content',$sClassText);
    }
    // CEMENT
    protected function SingularName() {
	return 'fcrNode_SimpleWikiPage';
    }
    // CEMENT
    public function GetKeyName() {
	return 'ID';
    }
    // CEMENT
    public function GetActionKey() {
	return KS_ACTION_TF_NODE_PAGE;
    }
    // CEMENT
    public function GetTypeString() {
	return KS_TF_NODE_TYPE_PAGE;
    }
    protected function FieldArray() {	// 2017-09-02 is this used?
	return array('title','content');
    }
    
    // -- SETUP -- //
    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    /*----
      NOTE: Most of this stuff doesn't actually *have* to be here, but the title calculations do, and it just
	made sense to put it all together here.
    */
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle('Wiki Pages');
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    /*----
      NOTE: App()->GetUserRecord() may return the non-dropin class (TODO: FIX), so
	we have to upgrade it here.
    */
    public function Render() {
	return $this->AdminPage();
    }

    // -- EVENTS -- //
    // ++ CLASSES ++ //

    protected function NodesIndexClass() {
	return KS_CLASS_TF_NODE_INDEX;
    }
    protected function LeafsTextClass() {
	return KS_CLASS_TF_LEAF_VALUE_TEXT;
    }

    // -- CLASSES -- //
    // ++ TABLES ++ //
    
    protected function NodeIndexTable() {
    	return $this->GetConnection()->MakeTableWrapper($this->NodesIndexClass());
    }
    protected function NodeTextValueTable() {
	return $this->GetConnection()->MakeTableWrapper($this->LeafsTextClass());
    }
    
    // -- TABLES -- //
    // ++ DATA READ (RECORDS) ++ //
    
    /*----
      RETURNS: Recordset of all field-subnodes from wiki-type nodes
      TODO: Possibly this should be generalized in fctNodes
    */
  /* 2017-10-04 There's a problem here in that there's no way for it to use "ID" as a filter; it has to be "n.ID"...
    public function SelectRecords($sqlWhere = NULL, $sqlSort = NULL, $sqlOther = NULL) {
	// NEW in progress
    
	// OLD
	$sqnNodes = $this->NodeIndexTable()->TableName_cooked();
	$sqnLeafs = $this->LeafIndexTable()->TableName_cooked();
	$sqnTexts = $this->NodeTextValueTable()->TableName_cooked();
	$sType = KS_TF_NODE_TYPE_PAGE;
	
	if (is_null($sqlWhere)) {
	    $sqlWhereClause = '';
	} else {
	    $sqlWhereClause = " AND ($sqlWhere)";
	}
	
	if (is_null($sqlSort)) {
	    $sqlSortClause = '';
	} else {
	    $sqlSortClause = " ORDER BY $sqlSort";
	}
	
	$sql = <<<__END__
SELECT n.ID AS ID, ntitle.Value AS PageTitle, ncont.Value AS PageText
FROM ($sqnNodes AS n
  LEFT JOIN (
    SELECT * FROM $sqnTexts AS t LEFT JOIN $sqnLeafs AS l ON t.ID_Leaf=l.ID
  ) AS ntitle
    ON ntitle.ID_Node=n.ID)
  LEFT JOIN (
    SELECT * FROM $sqnTexts AS t LEFT JOIN $sqnLeafs AS l ON t.ID_Leaf=l.ID
  ) AS ncont
    ON ncont.ID_Node=n.ID
WHERE (n.Type='$sType') AND (ntitle.Name='title') AND (ncont.Name='content')
$sqlWhereClause
$sqlSortClause
$sqlOther
__END__;
	return $this->FetchRecords($sql);
    } */
    
    // -- DATA READ (RECORDS) -- //
    // ++ ADMIN UI ++ //

    /*----
      NOTE: Admin for specific node types is handled by this class-family (fctNodeTypesBase).
	Admin for dynamicaly-determined node-types is handled by fctNodesIndex. I don't yet know
	if this makes sense, but at least in theory the more specific code should be faster and
	more... purpose-built. ...okay, I still don't know if it makes sense.
    */
    protected function AdminPage() {
	// set up header action-links
	$oMenu = fcApp::Me()->GetHeaderMenu();
	    // ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	  $oMenu->SetNode(new fcMenuOptionLink(KS_PATHARG_NAME_INDEX,KS_NEW_REC,NULL,NULL,'add a new wiki page'));
	
	$tIndex = $this->NodeIndexTable();
	$rs = $tIndex->SelectNodeRecords();
	$out = $rs->AdminRows();
	return $out;
    }

    // -- ADMIN UI -- //
}
class fcrNode_SimpleWikiPage extends fcrNodeTypeBase {
    use ftTableAccess_LeafIndex;	// implements LeafIndexTable()

    // ++ EVENTS ++ //
  
    //protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	if ($this->IsNew()) {
	    $sTitle = 'New Wiki Page';
	} else {
	    $sTitle = 'Wiki Node #'.$this->GetKeyValue();
	}
    
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle($sTitle);
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
//    public function Render() {
//	return $this->AdminPage();
  //  }

    // -- EVENTS -- //
    // ++ WEB UI ++ //
    
      // ++ record

    protected function AdminPage() {
	$oPathInput = fcApp::Me()->GetKioskObject()->GetInputObject();
	$oFormInput = fcHTTP::Request();

	// saving changes to the user record?
	$doSave = $oFormInput->GetBool('btnSave');
	if ($doSave) {
	    $this->PageForm()->Save();
	    $this->SelfRedirect();
	}

	// prepare the form

	$frmEdit = $this->PageForm();
	if ($this->IsNew()) {
	    $frmEdit->ClearValues();
	    $doEdit = TRUE;
	} else {
	    $this->LoadLeafValues();
	    $frmEdit->LoadRecord();
	    
	    // set up header action-links (only for existing record)
	    $oMenu = fcApp::Me()->GetHeaderMenu();
		// ($sGroupKey,$sKeyValue=TRUE,$sDispOff=NULL,$sDispOn=NULL,$sPopup=NULL)
	      $oMenu->SetNode($ol = new fcMenuOptionLink('edit'));
		$doEdit = $ol->GetIsSelected();
	}
	$oTplt = $this->PageTemplate();
	$arCtrls = $frmEdit->RenderControls($doEdit);
	  // custom vars
	  $htID = $this->SelfLink();
	  $arCtrls['ID'] = $htID;

	// render the form
	$oTplt->SetVariableValues($arCtrls);
	$htForm = $oTplt->Render();
	
	if ($doEdit) {
	    $htFormHdr = "\n<form method=post id='fwiki.AdminPage'>";
	    $htFormBtn = "\n<input type=submit name=btnSave value='Save'>";
	    $htFormFtr = "\n</form>";
	} else {
	    $htFormHdr = NULL;
	    $htFormBtn = NULL;
	    $htFormFtr = NULL;
	}
		    
	return $htFormHdr.$htForm.$htFormBtn.$htFormFtr;
    }
    private $frmPage;
    protected function PageForm() {
	if (empty($this->frmPage)) {
	    $oForm = new fcForm_DB($this);

	      $oField = new fcFormField_Num($oForm,'ID');
		$oField->StorageObject()->Writable(FALSE);	// never update this field in the db
		$oCtrl = new fcFormControl_HTML_Hidden($oField,array());

	      $oField = new fcFormField_Text($oForm,'title');
		$oCtrl = new fcFormControl_HTML($oField,array('size'=>40));

	      $oField = new fcFormField_Text($oForm,'content');
		$oCtrl = new fcFormControl_HTML_TextArea($oField,array('rows'=>20,'cols'=>60));

	    $this->frmPage = $oForm;
	}
	return $this->frmPage;
    }
    private $tpPage;
    protected function PageTemplate() {
	if (empty($this->tpPage)) {
	    $sTplt = <<<__END__
<table class=listing>
  <tr class=odd><td align=right><b>Node ID</b>:</td><td>{{ID}}</td></tr>
  <tr class=even><td align=right><b>Title</b>:</td><td>{{title}}</td></tr>
  <tr class=odd><td colspan=2><b>Content</b>:<br>{{content}}</td></tr>
</table>
__END__;
	    $this->tpPage = new fcTemplate_array('{{','}}',$sTplt);
	}
	return $this->tpPage;
    }
    
      // -- record
      // ++ rows

    protected function AdminRows_settings_columns() {
	$arCols = array(
	  'ID'		=> 'ID',
	  'PageTitle'	=> 'Title',
	  'PageText'	=> 'Content',
	  );
	return $arCols;
    }

      // -- rows

    // -- WEB UI -- //
}