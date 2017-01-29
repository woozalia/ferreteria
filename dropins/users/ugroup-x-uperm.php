<?php
/*
  PURPOSE: classes for cross-referencing user groups and user permits
    For now, just a class for looking up permits from a group.
  HISTORY:
    2017-01-27 created
*/
class fctUPermits_for_UGroup_admin extends fctUPermits_for_UGroup {
/*
    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_PERMISSION;
    } */
    protected function PermitsClass() {
	return KS_CLASS_ADMIN_USER_PERMISSIONS;
    }
}
