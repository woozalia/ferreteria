<?php
/*
  PURPOSE: recordset functions for handling maintenance of uniqueness of records within a table,
    where uniqueness isn't (or can't be) determined by db-engine-enforced unique IDs.
  HISTORY:
    2016-11-04 ported from db.v1, split off into a trait
*/

trait ftUniqueRecords {

    /*----
      RETURNS: SQL filter string in WHERE clause format
      NOTE: 2017-01-16 I need to document how this is different from fcRecord_keyed_single->GetSelfFilter().
	Maybe this is the same thing.
    */
    abstract protected function FingerprintFilter();
    /*----
      ACTION:
	If ID is set, saves data to the identified record in the database.
	If ID is not set:
	  If data does not match any existing record, creates a new one.
	  If data matches an existing record, morphs into that record.
	  Undefined function FingerprintFilter() defines the SQL used to search for a match.
      REQUIRES:
	* Class must use ftSaveableRecord
      PUBLIC because sometimes things just have to be done by the table wrapper
    */
    public function SaveUnique() {
	if ($this->IsNew()) {
	    // ID not set, so check for duplicates
	    $sqlFilt = $this->FingerprintFilter();
	    $rs = $this->GetTableWrapper()->SelectRecords($sqlFilt);
	    if ($rs->HasRows()) {
		$rs->NextRow();
		$this->SetFieldValues($rs->GetFieldValues());	// copy the record here
		$ok = TRUE;
	    } else {
		$ok = $this->Save();
	    }
	} else {
	    $ok = $this->Save();
	}
	return $ok;
    }
}