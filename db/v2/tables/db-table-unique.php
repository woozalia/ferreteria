<?php
/*
  PURPOSE: table functions for handling maintenance of uniqueness of records within a table,
    where uniqueness isn't (or can't be) determined by db-engine-enforced unique IDs.
  HISTORY:
    2016-11-04 extracted from db-table-keyed.php
*/

trait ftUniqueRowsTable {
    /*----
      RETURNS: SQL filter string for record that matches the given array data
      INPUT: $arData = array of raw field values to match
      NOTES:
	* This could just as easily be in the Connection class. If more rarely-used utility functions (RUUFs) like this end up being written.
	  consider splitting them off into a trait.
	* $arVals needs to be converted to a slightly different format for use with fcSQLt_Filt.
	  That's what ValueArray_to_ConditionArray() does:
	    $arVals[field name] = value
	    $arCond[index] = "`field name` = value"
    */
    protected function FigureSQL_toMatchValues(array $arVals) {
	if (is_array($arVals)) {
	    $arValsSQL = $this->GetConnection()->Sanitize_andQuote_ValueArray($arVals);
	    $arCond = fcSQLt_Filt::ValueArray_to_ConditionArray($arValsSQL);
	    $oFilt = new fcSQLt_Filt('AND',$arCond);
	    return $oFilt->RenderValue();
	} else {
	    throw new InvalidArgumentException('Internal error: expecting an array, got this: '.print_r($arVals,TRUE));
	}
    }
    /*----
      NOTE: If we ever want to allow more than one match, call it FetchRecords_thatMatchValues() -- but it probably
	should not go in this trait. (Not sure what that would actually be useful for, really.)
    */
    protected function FetchRecord_toMatchValues(array $ar) {
	$sqlFilt = $this->FigureSQL_toMatchValues($ar);
	$rs = $this->SelectRecords($sqlFilt);
	if ($rs->HasRows()) {
	    if ($rs->RowCount() > 1) {
		// TODO: change this so it doesn't show up on the screen, but only logs/emails an error
		throw new exception('Ferreteria Data Warning: more than one row found for query: '.$this->sql);
	    }
	    return $rs;
	} else {
	    return NULL;	// no matches found
	}
    }
}