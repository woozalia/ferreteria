<?php
/*
  PURPOSE: extends clsUAcct_x_UGroup to use admin classes
  HISTORY:
    2014-01-26 created
*/
class clsUAcct_x_UGroup_admin extends clsUAcct_x_UGroup {

    // ++ CLASS NAMES ++ //

    protected function GroupsClass() {
	return KS_CLASS_ADMIN_USER_GROUPS;
    }

    // -- CLASS NAMES -- //
}