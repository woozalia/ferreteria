<?php
/*
  HISTORY:
    2016-07-13 split off from data.php
    2016-08-25 deprecated db_query(), added db_read_query() and db_write_query()
*/
/*%%%%
  HISTORY:
    2013-01-25 InitSpec() only makes sense for _CliSrv, which is the first descendant that needs connection credentials. Removed it from here.
*/
abstract class fcDataEngine {
    private $arSQL;

    // ++ ABSTRACT ++ //

      //++public (API)++//

      //++connection++//
    abstract public function db_open();
    abstract public function db_shut();
      //++transaction++//
    abstract public function db_transaction_open();
    abstract public function db_transaction_save();
    abstract public function db_transaction_kill();
      //++miscellaneous++//
    abstract public function db_get_new_id();
    abstract public function db_safe_param($iVal);
    abstract public function db_has_error();
    abstract public function db_get_error_number();
    abstract public function db_get_error_string();
    abstract public function db_get_qty_rows_chgd();

      //--public (API)--//
      //++protected++//

    /*----
      RETURNS: clsDataResult descendant, or NULL
	Only SELECT query types return a result. Callers expecting a resultset should set $bGetResult to TRUE.
	Other types of queries should set $bGetResult to FALSE.
    */
    abstract protected function db_do_query($sql,$bGetResult);

      //--protected--//

    // -- ABSTRACT -- //
    // ++ IMPLEMENTED API ++ //

    /*----
      RETURNS: clsDataResult descendant
    */
    public function db_read_query($sql) {
	return $this->db_do_query($sql,TRUE);
    }
    /*----
      RETURNS: nothing
    */
    public function db_write_query($sql) {
	$this->db_do_query($sql,FALSE);
    }
    /*----
      RETURNS: clsDataResult descendant
    */
    public function db_query($iSQL) {
	throw new exception('db_query() is deprecated; call db_write_query() or db_read_query().');
	$this->LogSQL($iSQL);
	return $this->db_do_query($iSQL,$bGetResult);
    }

    // -- IMPLEMENTED API -- //
    // ++ DEBUGGING ++ //

    protected function LogSQL($iSQL) {
	$this->sql = $iSQL;
	$this->arSQL[] = $iSQL;
    }
    public function ListSQL($iPfx=NULL) {
	$out = '';
	foreach ($this->arSQL as $sql) {
	    $out .= $iPfx.$sql;
	}
	return $out;
    }

    // -- DEBUGGING -- //

}
