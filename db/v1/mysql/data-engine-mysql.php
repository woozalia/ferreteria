<?php
/*
  HISTORY:
    2016-07-13 split off from data.php
      Significantly rewritten but not actually tested (because PHP 7).
*/
class clsDataEngine_MySQL extends clsDataEngine_CliSrv {

    // ++ CEMENTING ++ //

      //++connection++//

    public function db_open() {
	$rConn =
	  @mysql_connect(
	    $this->strHost,
	    $this->strUser,
	    $this->strPass,
	    false
	    )
	$this->NativeConnectionResource($rConn);
	if ($this->IsConnectionError()) {
	    $arErr = error_get_last();
	    throw new exception('MySQL could not connect: '.$arErr['message']);
	} else {
	    $ok = mysql_select_db($this->strName, $this->NativeConnectionResource());
	    if (!$ok) {
		throw new exception('MySQL could not select database "'.$this->strName.'": '.mysql_error());
	    }
	}
    }
    public function db_shut() {
	mysql_close($this->NativeConnectionResource());
    }

      //--connection--//
      //++transaction++//

    public function db_transaction_open() {
	$oRes = $this->db_do_query('START TRANSACTION');
    }
    public function db_transaction_save() {
	$oRes = $this->db_do_query('COMMIT');
    }
    public function db_transaction_kill() {
	$this->db_do_query(' ROLLBACK');
    }

      //--transaction--//
      //++miscellaneous++//

    public function db_get_new_id() {
	$id = mysql_insert_id($this->objConn);
	return $id;
    }
    public function db_get_error() {
	return mysql_error();
    }
    public function db_safe_param($sVal) {
	if ($this->HasNativeConnection()) {
	    $out = mysql_real_escape_string($sVal,$this->NativeConnectionResource());
	} else {
	    throw new exception("No connection available to correctly sanitize '$sVal'.");
	}
	return $out;
    }
    public function db_get_qty_rows_chgd() {
	return mysql_affected_rows($this->NativeConnectionResource());
    }

      //--miscellaneous--//
      //++protected++//

    /*----
      RETURNS: clsDataResult descendant
    */
    protected function db_do_query($sql) {
	if ($this->HasNativeConnection()) {
	    $oFerret = $this->Spawn_ResultObject();
	    $rNative = mysql_query($sql,$this->NativeConnectionObject());
	    $oFerret->SetHandle($rNative);
	    return $oFerret;
	} else {
	    throw new Exception("Must open database connection before attempting query. (SQL: $sql)");
	}
    }

      //--protected--//

    // -- CEMENTING -- //
    // ++ WRAPPER OBJECTS ++ //

      //++connection++//

    private $rNativeConnection;
    protected function NativeConnectionResource($r=NULL) {
	if (!is_null($r)) {
	    $this->rNativeConnection = $r;
	}
	return $this->rNativeConnection;
    }
    protected function HasNativeConnection() {
	return is_resource($this->rNativeConnection);
    }
    protected function IsConnectionError() {
	return $this->rNativeConnection === FALSE;
    }

      //--connection--//
      //++result++//

    protected function Spawn_ResultObject() {
	return new clsDataResult_MySQL();
    }

      //--result--//

    // -- WRAPPER OBJECTS -- //

}
