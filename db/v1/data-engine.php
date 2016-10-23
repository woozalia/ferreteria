<?php
/*
  HISTORY:
    2016-07-13 split off from data.php
*/
/*%%%%
  HISTORY:
    2013-01-25 InitSpec() only makes sense for _CliSrv, which is the first descendant that needs connection credentials.
*/
abstract class clsDataEngine {
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
    abstract public function db_get_error();
    abstract public function db_get_qty_rows_chgd();

      //--public (API)--//
      //++protected++//

    /*----
      RETURNS: clsDataResult descendant
    */
    abstract protected function db_do_query($sql);

      //--protected--//

    // -- ABSTRACT -- //
    // ++ IMPLEMENTED API ++ //

    /*----
      RETURNS: clsDataResult descendant
      TODO: SQL logging is probably pointless, so eventually deprecate this and make db_do_query() public.
    */
    public function db_query($iSQL) {
	$this->LogSQL($iSQL);
	return $this->db_do_query($iSQL);
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
