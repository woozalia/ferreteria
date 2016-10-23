<?php
/*
  HISTORY:
    2016-07-13 split off from data.php
*/

/*%%%%
  PURPOSE: encapsulates the results of a query
*/
abstract class clsDataResult {

    // ++ ABSTRACT ++ //

      //++public (API)++//

    /*----
      RETURNS: TRUE iff last action was successful
    */
    abstract public function is_okay();
    /*----
      ACTION: set the record pointer so the first row in the set will be read next
    */
    abstract public function do_rewind();
    /*----
      ACTION: Fetch the first/next row of data from a result set
    */
    abstract public function get_next();
    /*----
      ACTION: Return the number of rows in the result set
    */
    abstract public function get_count();
    /*----
      ACTION: Return whether row currently has data.
    */ /* 2016-07-15 need usage cases for this.

    Rows are normally returned to the caller, so why would we need to save one in the Result?

    abstract public function is_filled();
    */

      //--public (API)--//

    // -- ABSTRACT -- //

/* 2016-07-13 All the "box" stuff now seems unnecessary.
    protected $box;

    public function __construct(array $iBox=NULL) {
	$this->box = $iBox;
    } *
    /*----
      PURPOSE: The "Box" is an array containing information which this class needs but which
	the calling class has to be responsible for. The caller doesn't need to know what's
	in the box, it just needs to keep it safe.
    */
/* 2016-07-13 All the "box" stuff now seems unnecessary.
    public function Box(array $iBox=NULL) {
	if (!is_null($iBox)) {
	    $this->box = $iBox;
	}
	return $this->box;
    }
    public function Row(array $iRow=NULL) {
	if (!is_null($iRow)) {
	    $this->box['row'] = $iRow;
	    return $iRow;
	}
	if ($this->HasRow()) {
	    return $this->box['row'];
	} else {
	    return NULL;
	}
    }*/
    /*----
      USAGE: used internally when row retrieval comes back FALSE
    */
    /* 2016-07-14 more Box stuff
    protected function RowClear() {
	$this->box['row'] = NULL;
    }
    public function Val($iKey,$iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->box['row'][$iKey] = $iVal;
	    return $iVal;
	} else {
	    if (!array_key_exists('row',$this->box)) {
		throw new exception('Row data not loaded yet.');
	    }
	    return $this->box['row'][$iKey];
	}
    }
    public function HasRow() {
	if (array_key_exists('row',$this->box)) {
	    return (!is_null($this->box['row']));
	} else {
	    return FALSE;
	}
    } */
/* might be useful, but not actually needed now
    public function HasVal($iKey) {
	$row = $this->Row();
	return array_key_exists($iKey,$this->box['row']);
    }
*/
}
