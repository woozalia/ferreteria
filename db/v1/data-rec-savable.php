<?php

trait ftSaveableRecord {
    /*----
      HISTORY:
	2016-06-04 I'm not sure why this was private; it seems like a good general-use function.
	  I specifically needed it to be public when loading up Customer Address records via either
	  of two different methods. I *could* have written public SaveThis() and SaveThat() methods,
	  but that would have increased the amount of special coding needed for This and That yet again.
	2016-10-12 Added $arSave argument so forms could inject additional SQL-format data to save.
	  Isn't there some other way to do this? Can't think of it.
    */
    public function Save($arSave=NULL) {
	$out = NULL;
	$sql = NULL;	// for debugging
	if ($this->IsNew()) {
	    $arSave = $this->InsertArray($arSave);
//	    echo 'INSERTING:'.fcArray::Render($arSave);
	    if (is_array($arSave)) {
		$out = $this->Table()->Insert($arSave);
		$sql = $this->Table()->sqlExec;
		if ($out !== FALSE) {
		    $this->Value($this->KeyName(),$out);	// retrieve new record's ID
		}
	    }
	} else {
	    $arSave = $this->UpdateArray($arSave);
//	    echo 'UPDATING:'.fcArray::Render($arSave);
	    if (is_array($arSave)) {
		$out = $this->Update($arSave);
		$sql = $this->sqlExec;
	    }
	}
//	echo "SQL: $sql";
//	die();
	return $out;
    }
    /*----
      RETURNS array of values to insert for new records, formatted for $this->Insert().
      PURPOSE: Mainly so it can be overridden to calculate specific fields
	when a record is inserted, e.g. set WhenCreated.
      HISTORY:
	2016-04-19 This couldn't have been working reliably until now, because the values
	  were not being sanitize-and-quoted. (Now they are.)
	2016-10-16 Now defaults to ChangeArray() instead of UpdateArray().
    */
    protected function InsertArray($ar=NULL) {
	return $this->ChangeArray($ar);
    }
    /*----
      RETURNS: array of values to update, formatted for $this->Update().
      PURPOSE: Mainly so it can be overridden to calculate specific fields
	when a record is updated, e.g. set WhenEdited.
      HISTORY:
	2016-04-19 This couldn't have been working reliably until now, because the values
	  were not being sanitize-and-quoted. (Now they are.)
	2016-10-16 Calculations moved to ChangeArray().
    */
    protected function UpdateArray($ar=NULL) {
	return $this->ChangeArray($ar);
    }
    /*----
      RETURNS array of record values to set, formatted for $this->Insert()/Update().
      PURPOSE: Mainly so it can be overridden to calculate specific fields
	when a record is inserted or updated. Override InsertArray() or UpdateArray() for
	fields that should only be set on insert or update specifically.
      FORMAT: Sanitized SQL; can include expressions like "NOW()".
	Default is just to return all changed values, sanitized.
    */
    protected function ChangeArray($arIns=NULL) {
	$arTouch = $this->TouchedArray();
	if (is_array($arTouch)) {
	    $db = $this->Engine();
	    foreach ($arTouch as $sField) {
		$arUpd[$sField] = $db->SanitizeAndQuote($this->Value($sField));
	    }
	}
	return $arUpd;
    }

}
