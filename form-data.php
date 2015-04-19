<?php
/*
  LIBRARY: form-data.php - form-database connector classes
  HISTORY:
    2010-11-01 created for clsForm_DataSet
    2010-11-18 trying this as a descendent of clsCtrls
    2013-12-14 adapting admin.forms.php as form-data.php for non-MW
    2015-03-22 redoing how indexes work
*/
class clsForm_recs extends clsCtrls { // 2015-02-12 should probably be renamed clsCtrl_recs for consistency
    private $rcData;
    protected $arNewVals;
    private $sTemplate;

    /*----
      HISTORY:
	2011-12-20 changed param 1 from clsDataSet to clsRecs_key_single
	  clsDataSet is deprecated, and the only thing it adds is the field overloading anyway.
	  Later changed to clsRecs_keyed_abstract so we could use this class for non-indexed data.
    */
    public function __construct(clsRecs_keyed_abstract $rcData) {
	$this->rcData = $rcData;
	$oFields = new clsFields($rcData->Values());
	$this->FieldsObject($oFields);
    }
    protected function NewFieldsObject() {
	throw new exception('Internal error: $this->oFields is not being set properly.');
    }
    protected function FieldsArray() {
	return $this->FieldsObject()->Fields();
    }
    protected function DataRecord() {
	return $this->rcData;
    }
    public function HasIndex() {
	return FALSE;
    }
    /*----
      ACTION: Sets or retrieves the template used for displaying the form
    */
    public function FormTemplate($sTplt=NULL) {
	if (!is_null($sTplt)) {
	    $this->sTemplate = $sTplt;
	}
	return $this->sTemplate;
    }
    /*----
      PURPOSE: sets default values to use for new records.
      INPUT: array of field names and SQL values
	iVals[name] = raw SQL
	if a value is set to NULL, then a new row *must* set that value to non-null before it will be added.
    */
    public function NewVals(array $iVals=NULL) {
	if (!is_null($iVals)) {
	    $this->arNewVals = $iVals;
	}
	if (!is_array($this->arNewVals)) {
	    // need to be able to pass return value to array functions
	    //   so make sure it's an array even if empty.
	    $this->arNewVals = array();
	}
	return $this->arNewVals;
    }
    /*----
      RETURNS: Array of any required fields
	 i.e. fields which must have non-NULL value in any new record
    */
    public function FieldsRequired() {
	$arNew = $this->NewVals();
	$arReq = NULL;
	foreach ($arNew as $key => $val) {
	    if (is_null($val)) {
		$arReq[] = $key;
	    }
	}
	return $arReq;
    }
    public function RenderControl($sName) {
	$oCtrl = $this->Ctrl($sName);
	if (is_object($oCtrl)) {
	    return $oCtrl->Render();
	} else {
	    throw new exception('No control found for "'.$sName.'"');
	}
    }
    public function RenderForm($bEdit,$sSaveBtnName='btnSave') {
	$arVars = array();
	foreach($this->FieldsArray() as $name => $field) {
	    $oCtrl = $this->Ctrl($name);
	    if ($bEdit) {
		$ht = $oCtrl->Render();
	    } else {
		$ht = $oCtrl->RenderValue();
	    }
	    $arVals[$name] = $ht;
	}
	$xtt = new clsStringTemplate_array('{{','}}',$arVals);
	$out = $xtt->Replace($this->FormTemplate());
	if ($bEdit) {
	    $out .= "\n".'<input type=submit name="'.$sSaveBtnName.'" value="Save">';
	}
	return $out;
    }
    /*----
      ACTION: For each field in the group, checks to see
	if there's POST data. If there is, updates the
	control with that data.
      HISTORY:
	2010-11-02 Checkboxes that aren't checked don't return values in POST data,
	  so we can't skip values that aren't set. Must assume all form controls are included.
	2010-11-19 Moved from clsFields to clsForm_DataSet
    */
    public function RecvVals() {
	$isNew = $this->DataRecord()->IsNew();
	foreach($this->FieldsArray() as $name => $field) {
	    $oCtrl = $this->Ctrl($name);
	    if (!is_object($oCtrl)) {
		throw new exception('Attempt to access nonexistent control "'.$name.'".');
	    }
	    $sNewVal = $oCtrl->Receive();
	    if (is_null($sNewVal)) {	// NULL means no data received for this control
//		echo 'NOTHING RECEIVED';
	    } else {
		if ($isNew) {
		    $field->Change_asNew($sNewVal);
		} else {
		    $field->Change_fromShown($sNewVal);
		}
	    }
	}
    }
    /*----
      ACTION: Saves the data (UPDATE or INSERT as appropriate) and then reloads the page
	so that subsequent browser page-reloads won't re-submit the data (possibly creating
	duplicate records).
      INPUT:
	Notes (optional) -- human-entered text to go in "Notes" field of event log
	arRedir (optional) -- array of path overrides for redirect, as defined by record class
	  ($this->DataRecord()), which is in turn typically defined by clsMenuData_helper::_AdminRedirect()
	sRedir (optional) -- path override for redirect; you can supply either arRedir, sRedir, or neither
      NOTE:
	Reloading: We always want to reload the page (via header redirect)
	  (a) to get rid of the POST data (so user-reloads don't re-save)
	  (b) to display the current data (there are other ways of doing this for existing records, but not for new ones)
	  (c) to get rid of the "edit" directive in the URL (this could also be done by removing it from the form's target URL)
	  There are other ways of doing B and C but not A. Not having to deal with the alternatives for B and C also simplifies things.
      HISTORY:
	2010-11-18 added iNotes parameter
	2015-02-16 see http://htyp.org/User:Woozle/Ferreteria/changes/1
    */
    public function Save($iNotes=NULL) {
	$oFlds = $this->FieldsObject();
	$rcData = $this->DataRecord();

	// get the form data and note any changes
	$this->RecvVals();

	// get the list of field updates
	$arUpd = $oFlds->DataUpdates();

	// is this a new record, or updating an existing one?
	$isNew = $rcData->IsNew();

	$arDataAdd = array();	// so array_merge() doesn't barf if nothing is added
	if ($isNew) {
	    $strAct = 'Creating: ';
	    $strHdr = 'Creating';
	    $strCode = 'NEW';
	    if (method_exists($rcData,'Fields_forCreate')) {
		$arDataAdd = $rcData->Fields_forCreate();
	    }
	} else {
	    $strAct = 'Edited: ';
	    $strHdr = 'Saving Edit';
	    $strCode = 'ED';
	    if (method_exists($rcData,'Fields_forUpdate')) {
		$arDataAdd = $rcData->Fields_forUpdate();
	    }
	}
	// log that we are about to make the change
	$strDescr = $strAct.$oFlds->DescrUpdates();
	$out = "<b>$strHdr</b>: $strDescr";	// TODO: this should go through the Skin object somehow

	if (is_array($arUpd)) {
	    $arEv = array(
	      'descr'	=> $strDescr,
	      'where'	=> __METHOD__,
	      'code'	=> $strCode
	      );
	    if (!is_null($iNotes)) {
		$arEv['notes'] = $iNotes;
	    }
	    // 2014-12-14 this is a bit of a kluge; we need a way to record events for multiple-index tables
	    if (method_exists($rcData,'CreateEvent')) {
		$rcEvent = $rcData->CreateEvent($arEv);
	    } else {
		$rcEvent = NULL;
	    }
	    // update/create the recordset
	    // -- add any "new-default" fields or hard-coded changes
	    $arUpd = array_merge($arUpd,$this->NewVals(),$arDataAdd);

	    $ok = $rcData->Make($arUpd);
	    $arEv = array();
	    if ($ok === FALSE) {
		$strErr = $rcData->Engine()->getError();
		$arEv['error'] = TRUE;
		$arEv['descrfin'] = 'DB Error: '.$strErr;
		$out .= '<br><b>Database Error</b>: '.$strErr;
		$out .= '<br><b>SQL was [</b>'.$rcData->sqlExec.'<b>]</b>';
		$out .= '<br><b>Class</b>: '.get_class($rcData);
		echo $out.'<br>';
		throw new exception('Database error caught.');
	    }

	    // log completion
	    if ($isNew) {
		$arEv['id'] = $rcData->KeyValue();
		if ($ok) {
		    $out .'<br>New record: '.$rcData->AdminLink();
		}
	    }
	    if (!is_null($rcEvent)) {
		$rcEvent->Finish($arEv);
	    }
	} else {
	    $out .= 'No changes to save.';
	    $ok = FALSE;	// stay on edit form -- maybe we forgot something
	}
	$this->htMsg = $out;	// save any display messages for caller
	return $ok;
    }
}
class clsForm_recs_indexed extends clsForm_recs {
    public function HasIndex() {
	return TRUE;
    }
    public function IndexString() {
	return static::IndexString_toUse($this->DataRecord()->KeyValue());
    }
    protected function NewFieldsObject() {
	return new clsFields(array());
    }
    public function RowPrefix() {
	$strKeyVal = $this->DataRecord()->KeyValue();
	$strIndex = self::IndexString_toUse($strKeyVal);
	$out = '<input type=hidden name="_update['.$strIndex.']" value=1>';
	return $out;
    }
    static protected function IndexString_toUse($iIndex) {
	return empty($iIndex)?'new':$iIndex;
    }
    protected function LoadCtrl($iName,$iIndex) {
	$oCtrl = $this->Ctrl($iName);
	//$oCtrl->Index(self::IndexString_toUse($iIndex));
	return $oCtrl;
    }
    public function Render($iName) {
	$objCtrl = $this->LoadCtrl($iName,$this->DataRecord()->KeyValue());
	$objCtrl->Field()->Clear();
	if ($this->DataRecord()->IsNew()) {
	    $objCtrl->Field()->Clear();
	} else {
	    $objCtrl->Field()->ValStore($this->DataRecord()->Value($iName));
	}
	return $objCtrl->Render();
    }
    /*----
      INPUT:
	POST vars:
	  _update[]: list of rows to check
	    e.g (_update[x] = TRUE,_update[y] = TRUE) means rows x and y have been displayed for editing
	$this->objFields
	old data in records ID=x and ID=y, loaded into $objData sequentially
	  each field's old value is loaded into a field object in objFields
	$this->arCtrls, loaded into $objCtrl sequentially (per field)
	  each control receives its newly POSTed value
	  each control is then given the data's old value from the corresponding field object
	  the control object decides if there has been a change
      OUTPUT:
	$this->arUpd[ID] = array of arrays compatible with dataset Update() method
	  arUpd[ID][field] = value
	$this->arIns = array compatible with table Insert() method
	  arIns[field] = value
      HISTORY:
	2011-02-09 actually returning some output now (it wasn't, before)
	2014-06-15 finally de-MediaWikified this
    */
    public function RecvVals() {
	$out = NULL;
	$this->arUpd = NULL;
	$this->arIns = NULL;

	$objData = $this->DataRecord();

	// get a list of rows being modified
	$arUpd = clsHTTP::Request()->GetArray('_update');
	$cntRows = count($arUpd);
	if ($cntRows > 0) {
	    $out .= '<b>Updating</b>: checking '.$cntRows.' record'.Pluralize($cntRows);
	    // iterate through rows of data received
	    foreach($arUpd as $id => $on) {
		$isNew = ($id == 'new');
		$objFlds = $this->FieldsObject();
		$objFlds->Reset();
		$arFlds = $objFlds->Fields();

		if ($isNew) {
		    $isDiff = TRUE;
		    $objData->Clear();

		    foreach($arFlds as $name => $field) {
			$objCtrl = $this->LoadCtrl($name,$id);
			$strNewVal = $objCtrl->Receive();
			$isDiff = !empty($strNewVal);	// must be at least one non-blank field
			$field->SetStored(NULL);
			$isSame = $field->Change_fromShown($strNewVal);
		    }
		} else {
		    $isDiff = FALSE;	// no existing data to compare with
		    // load stored data
		    $objData->Load_fromKey($id);

		    // iterate through each received value
		    foreach($arFlds as $name => $field) {
			// compare stored data with each received value
			if ($objData->HasValue($name)) {
			    $field->ValStore($objData->Value($name));
			}
			$objCtrl = $this->LoadCtrl($name,$id);
			$strNewVal = $objCtrl->Receive();
			$isSame = $field->Change_fromShown($strNewVal);
			$isDiff = $isDiff || !$isSame;
		    }
		}
		if ($isDiff) {	// if there was a change in this row
		    $arEdit = $objFlds->DataUpdates();
		    if ($isNew) {
			// make sure there's some data
			$arReq = $this->FieldsRequired();
			$arNew = $this->NewVals();
			$okIns = TRUE;	// we can insert unless there's an empty required field
			$arMissing = NULL;
			$arChg = ArrayJoin($arEdit,$this->NewVals(),FALSE,TRUE);	// fill in any unfilled values from default list
			if (is_array($arReq)) {
			    foreach ($arReq as $key) {
				if (empty($arChg[$key])) {
				    // if not submitted by user, check for new-default
				    if (array_key_exists($key,$arNew)) {
					// use new-default value
					$arChg[$key] = $arNew[$key];
				    } else {
					// value required, not submitted, no default available = fail
					$okIns = FALSE;
					$arMissing[] = $key;
				    }
				}
			    }
			}
			if ($okIns) {
			    $this->arIns = $arChg;	// copy new data to insertion array
			} else {
			    $strPl = Pluralize(count($arMissing));
			    echo 'New record cannot be created because of missing field'.$strPl.':';
			    foreach ($arMissing as $key) {
				echo ' ['.$key.']';
			    }
			    echo '<br>Default value array:<pre>'.print_r($this->NewVals(),TRUE).'</pre>';
			    echo '<br>Entered value array:<pre>'.print_r($arEdit,TRUE).'</pre>';
			    echo '<br>Combined value array:<pre>'.print_r($arChg,TRUE).'</pre>';
			    throw new exception('Cannot INSERT because of missing field'.$strPl);
			}
		    } else {
			$this->arUpd[$id] = $arEdit;
		    }
//$out .= '<br>SQL: '.$sql.'<br>';
		    // it might be useful later on to build a description of the updates
		    // but I don't feel like testing this right now. (2011-02-09)
		    //$strUpd .= $objFlds->DescrUpdates();
		}
	    }
	}
	return $out;
    }
    /*----
      USAGE: Called once for the whole form
      HISTORY:
	2010-11-19 Adapted from clsForm_DataSet::Save()
	2014-01-26 added arPath param -- this may need to be reworked
    */
    public function Save($iNotes=NULL,clsEvents $iLog=NULL) {
	$objFlds = $this->FieldsObject();
	$objData = $this->DataRecord();
	// get the form data and note any changes
	$out = $this->RecvVals();
	// get the list of field updates
//	$arUpd = $objFlds->DataUpdates();
	$arUpds = $this->arUpd;
	$arIns = $this->arIns;

	$isChg = FALSE;
	$strErrAll = NULL;
	$strUpd = NULL;
	if (is_array($arUpds)) {
	    $isChg = TRUE;
	    $cntRows = count($arUpds);

	    // log that we are about to make the change
	    $strUpd = $objFlds->DescrUpdates();
	    $strDescr = $cntRows.' row'.Pluralize($cntRows).' to update';
	    $out .= '<br><b>Updating</b>: '.$strUpd;

	    if (is_object($iLog)) {
		$arEv = array(
		  'descr'	=> $strDescr,
		  'where'	=> __METHOD__,
		  'code'	=> $strCode
		  );
		if (!is_null($iNotes)) {
		    $arEv['notes'] = $iNotes;
		}
		$iLog->StartEvent($arEv);
	    }
	    foreach ($arUpds as $id => $arUpd) {
		assert('!is_null($id);');
		// update/create the recordset
		//$ok = $objData->Make($arUpd);
		$objData->KeyValue($id);
		$ok = $objData->Update($arUpd);

		if ($ok) {
		    $strUpd .= ' '.$id;
		} else {
		    $strErr = $objData->Engine()->getError();
		    $out .= '<br>Database Error: '.$strErr;
		    $out .= '<br> - SQL: '.$objData->sqlExec;
		    $strErrAll .= '[Error updating ID='.$id.': '.$strErr.']';
		}
	    }
	    $objData->Reload();
	}
	if (is_array($arIns)) {
	    $isChg = TRUE;
	    $ok = $objData->Table()->Insert($arIns);

	    $strUpd .= ' new:'.$objData->KeyValue();
	    if ($ok) {
		$out .'<br>New record: '.$objData->AdminLink();
	    }
	    $arEv = array();
	    if (!$ok) {
		$strErr = $objData->objDB->getError();
		$out .= '<br>Database Error: '.$strErr;
		$out .= '<br> - SQL: '.$objData->sqlExec;
		$strErrAll .= '[Error inserting row: '.$strErr.']';
	    }
	}
	if (is_object($iLog)) {
	    $arEv = array();
	    if (is_null($strErrAll)) {
		$arEv['error'] = FALSE;
		$arEv['descrfin'] = 'ok - '.$strUpd;
	    } else {
		$arEv['error'] = TRUE;
		$arEv['descrfin'] = $strErrAll;
	    }
	    $iLog->FinishEvent($arEv);
	}

	if (!$isChg) {
	    $out .= '<br>No changes to save.';
	}
	return $out;
    }
}
