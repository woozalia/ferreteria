<?php
/*
  PURPOSE: While the SQL calculation classes are Ferreteria.db-version-agnostic, the helper traits are not.
  HISTORY:
    2016-11-06 split off from db/sql/db-sql.php
*/
trait QueryableTable {
    public function SQO_Source($sAbbr=NULL) {
	return new fcSQL_TableSource($this->Name(),$sAbbr);
    }
    public function SQO_Select($sAbbr=NULL) {
	return new fcSQL_Select($this->SQO_Source($sAbbr));
    }
}
