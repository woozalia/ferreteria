<?php
/*
  HISTORY:
    2016-07-14 started
*/


class clsDataResult_MySQLi extends clsDataResult {

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
    public function do_rewind() {
	$res = $this->GetNative();
	$this->GetNative()->data_seek(0);
    }
    /*----
      ACTION: Fetch the first/next row of data from a result set
    */
    public function get_next() {
	return $this->GetNative()->fetch_assoc();
    }
    /*----
      ACTION: Return the number of rows in the result set
    */
    public function get_count() {
	return $this->GetNative()->num_rows;
    }
    /*----
      ACTION: Return whether row currently has data.
    *//*
    public function is_filled() {
    }*/

    // -- CEMENTING -- //
    // ++ ENGINE-SPECIFIC ++ //

    /*----
      USAGE: used by Engine objects to pass a native Result object
      PUBLIC so Engine objects can access it
      HISTORY:
	2012-09-06 Made public
	2016-07-13 Renamed from Resource() and split into get/set methods.
	2016-07-15 Adapted from clsDataResult_MySQL.
    */
    private $oNative;
    public function SetNative(mysqli_result $o) {
	$this->oNative = $o;
    }
    /*---
      USAGE: used internally to retrieve the native Result handle
    */
    protected function GetNative() {
	return $this->oNative;
    }
    protected function HasNative() {
	return is_a($this->GetNative(),'mysqli_result');
    }

}