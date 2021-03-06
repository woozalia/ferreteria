<?php
/*
  HISTORY:
    2016-07-13 split off from data.php
*/
/*
  These interfaces marked "abstract" have not been completed or tested.
    They're mainly here as a place to stick the partial code I wrote for them
    back when I first started writing the data.php library.
*/
class fcDataEngine_MySQLi extends fcDataEngine_CliSrv {
    private $objConn;	// connection object

    // ++ CEMENTING ++ //

      //++connection++//

    public function db_open() {
	$oNative = new mysqli($this->strHost,$this->strUser,$this->strPass,$this->strName);
	$this->NativeConnectionObject($oNative);
    }
    public function db_shut() {
	$this->NativeConnectionObject()->close();
    }

      //--connection--//
      //++transaction++//

    public function db_transaction_open() {
	//$oRes = $this->NativeConnectionObject()->query('START TRANSACTION');
	$this->NativeConnectionObject()->autocommit(FALSE);
    }
    public function db_transaction_save() {
	//$oRes = $this->objConn->query('COMMIT');
	$this->NativeConnectionObject()->commit();
	$this->NativeConnectionObject()->autocommit(TRUE);
    }
    public function db_transaction_kill() {
	//$this->objConn->query('ROLLBACK');
	$this->NativeConnectionObject()->rollback();
	$this->NativeConnectionObject()->autocommit(TRUE);
    }

      //--transaction--//
      //++miscellaneous++//

    public function db_has_error() {
	return ($this->db_get_error_number() != 0);
    }
    public function db_get_error() {
	throw new exception('db_get_error() is deprecated; call db_get_error_number() or db_get_error_string().');
    }
    public function db_get_error_number() {
	return $this->NativeConnectionObject()->errno;
    }
    public function db_get_error_string() {
	return $this->NativeConnectionObject()->error;
    }
    public function db_safe_param($sVal) {
	return $this->NativeConnectionObject()->escape_string($sVal);
    }
    public function db_get_new_id() {
	$id = $this->NativeConnectionObject()->insert_id;
	return $id;
    }
    public function db_get_qty_rows_chgd() {
	return $this->NativeConnectionObject()->affected_rows;
    }

      //--miscellaneous--//
      //++protected++//

    /*----
      RETURNS: clsDataResult descendant, or NULL
	Only SELECT query types return a result. Callers expecting a resultset should set $bGetResult to TRUE.
	Other types of queries should set $bGetResult to FALSE.
    */
    protected function db_do_query($sql,$bGetResult) {
	$this->NativeConnectionObject()->real_query($sql);
	$oNativeResult = $this->NativeConnectionObject()->store_result();
	
	if ($bGetResult) {
	    if ($oNativeResult === FALSE) {
		throw new exception('Could not get result object for some reason. SQL: '.$sql);
	    }

	    $oWrapper = $this->Spawn_ResultObject($oNativeResult);
	    return $oWrapper;
	} else {
	    return NULL;
	}
    }

    //--protected--//
/*
    public function row_do_rewind(array $iBox) {
	$this->NativeConnectionObject()
    }
    public function row_get_next(array $iBox) {
	return $iRes->fetch_assoc();
    }
    public function row_get_count(array $iBox) {
	return $iRes->num_rows;
    }
    public function row_was_filled(array $iBox) {
	return ($this->objData !== FALSE) ;
    }
    public function db_get_qty_rows_chgd() {
	return $this->objConn->affected_rows;
    }
*/

    // -- CEMENTING -- //
    // ++ WRAPPER OBJECTS ++ //

      //++connection++//

    private $oNativeConnection;
    protected function NativeConnectionObject(mysqli $o=NULL) {
	if (!is_null($o)) {
	    $this->oNativeConnection = $o;
	}
	return $this->oNativeConnection;
    }
    protected function HasNativeConnection() {
	return is_a($this->oNativeConnection,'mysqli');
    }
    protected function IsConnectionError() {
	return $this->oNativeConnection === FALSE;
    }

      //--connection--//
      //++result++//

    protected function Spawn_ResultObject(mysqli_result $oResult) {
	$oWrapper = new clsDataResult_MySQLi();
	$oWrapper->SetNative($oResult);
	return $oWrapper;
    }

      //--result--//

    // ++ WRAPPER OBJECTS ++ //
}
