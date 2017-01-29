<?php
/*
  PURPOSE: extends fctUAcct_x_UGroup to use admin classes
  HISTORY:
    2014-01-26 created
    2017-01-27 rewriting from scratch; basically only keeping the file name
*/

class fctUGroups_for_UAcct_admin extends fctUGroups_for_UAcct {
    // ++ CLASSES ++ //
    
    protected function GroupsClass() {
	return KS_CLASS_ADMIN_USER_GROUPS;
    }

    // -- CLASSES -- //
}

/*class fctUAcct_x_UGroup_admin extends fctUAcct_x_UGroup {

    // OVERRIDE
    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_GROUP;
    }

    // ++ CLASS NAMES ++ //

    protected function GroupsClass() {
	return KS_CLASS_ADMIN_USER_GROUPS;
    }

    // -- CLASS NAMES -- //
}*/