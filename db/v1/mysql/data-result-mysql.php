<?php
/*
  HISTORY:
    2016-07-13 split off from data.php
*/

class clsDataResult_MySQL extends clsDataResult {

    // ++ CEMENTING ++ //

    /*----
      RETURNS: TRUE iff last action was successful
      USAGE: call after do_query()
      FUTURE: should probably reflect status of other operations besides do_query()
      HISTORY:
	2012-02-04 revised to use box['ok']
	2016-07-13 doing away with box[].
    */
    public function is_okay() {
	return $this->HasNative();
    }
    /* 2016-07-13 OLD VERSION
    public function is_okay() {
	return $this->box['ok'];
    } */
    public function do_rewind() {
	$res = $this->GetNative();
	mysql_data_seek($res, 0);
    }
    public function get_next() {
	if ($this->HasNative()) {
	    $res = $this->GetNative();
	    $arRow = mysql_fetch_assoc($res);
	    if ($arRow === FALSE) {
		return NULL;
	    } else {
		return $row;
	    }
	} else {
	    return NULL;
	}
    }
    /*=====
      ACTION: Return the number of rows in the result set
    */
    public function get_count() {
	if ($this->HasNative()) {
	    $res = $this->GetNative();
	    $nRows = mysql_num_rows($res);
	    return $nRows;
	} else {
	    return NULL;
	}
    }
    /* 2016-07-14 Does this mean "has a row loaded" or "has at least one row"? Need usage case.
    public function is_filled() {
	return $this->HasRow();
    }


    // -- CEMENTING -- //
    // ++ ENGINE-SPECIFIC ++ //

    /*----
      USAGE: used by Engine objects to pass a native Result handle
      PUBLIC so Engine objects can access it
      HISTORY:
	2012-09-06 Made public
	2016-07-13 Renamed from Resource() and split into get/set methods.
    */
    private $rNative;	// handle to native resultset
    public function SetNative($r) {
	$this->rNative = $r;
    }
    /*---
      USAGE: used internally to retrieve the native Result handle
    */
    protected function GetNative() {
	return $this->rNative;
    }
    protected function HasNative() {
	return is_resource($this->GetNative());
    }

    /* 2016-07-13 OLD VERSION
    public function Resource($iRes=NULL) {
	if (!is_null($iRes)) {
	    $this->box['res'] = $iRes;
	}
	return $this->box['res'];
    } */

    // -- ENGINE-SPECIFIC -- //

    /*----
      NOTES:
	* For queries returning a resultset, mysql_query() returns a resource on success, or FALSE on error.
	* For other SQL statements, INSERT, UPDATE, DELETE, DROP, etc, mysql_query() returns TRUE on success or FALSE on error.
      HISTORY:
	2012-02-04 revised to use box['ok']
    */ /* 2016-07-13 do_query() should not be a Result method.
    public function do_query($iConn,$iSQL) {
	$res = mysql_query($iSQL,$iConn);
	if (is_resource($res)) {
	    $this->Resource($res);
	    $this->box['ok'] = TRUE;
	} else {
	    $this->Resource(NULL);
	    $this->box['ok'] = $res;	// TRUE if successful, false otherwise
	}
    } */
}
