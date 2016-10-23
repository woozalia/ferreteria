<?php
/*
  HISTORY:
    2016-07-13 split off from data.php
*/
abstract class clsDataEngine_DBX extends clsDataEngine_CliSrv {
    private $objConn;	// connection object

    public function db_open() {
	$this->objConn = dbx_connect($this->strType,$this->strHost,$this->strName,$this->strUser,$this->strPass);
    }
    public function db_shut() {
	dbx_close($this->Conn);
    }

    protected function db_do_query($iSQL) {
	return dbx_query($this->objConn,$iSQL,DBX_RESULT_ASSOC);
    }
    public function db_get_new_id() {
    }
    public function row_do_rewind(array $iBox) {
    }
    public function row_get_next(array $iBox) {
    }
    public function row_get_count(array $iBox) {
    }
    public function row_was_filled(array $iBox) {
    }
}
