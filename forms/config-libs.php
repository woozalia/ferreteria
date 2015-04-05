<?php
/*
  PURPOSE: Ferreteria form library, version 2
  HISTORY:
    2015-03-29 created
*/

$fp = dirname( __FILE__ );
clsModule::BasePath($fp.'/');

$om = new clsModule(__FILE__, 'ctrl.php');
  $om->AddClass('fcFormControl_HTML');
//$om = new clsModule(__FILE__, 'ctrls.php');
//  $om->AddClass('fcDataConn');
//  $om->AddClass('fcDataConn_CliSrv');
$om = new clsModule(__FILE__, 'field.php');
  $om->AddClass('fcFormField_Num');
  $om->AddClass('fcFormField_Time');
$om = new clsModule(__FILE__, 'form.php');
  $om->AddClass('fcForm_keyed');
$om = new clsModule(__FILE__, 'form-data.php');
  $om->AddClass('fcForm_DB');
//$om = new clsModule(__FILE__, 'form-rec.php');
//  $om->AddClass('fcDataTable_indexed');
