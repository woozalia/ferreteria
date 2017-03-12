<?php
/*
  PURPOSE: Ferreteria form library, version 2
  HISTORY:
    2015-03-29 created
*/

$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');

$om = new fcCodeModule(__FILE__, 'field-ctrl.php');
  $om->AddClass('fcFormControl_HTML');
  $om->AddClass('fcFormControl_HTML_Hidden');
  $om->AddClass('fcFormControl_HTML_DropDown');
  $om->AddClass('fcFormControl_HTML_CheckBox');
  $om->AddClass('fcFormControl_HTML_TextArea');
  $om->AddClass('fcFormControl_HTML_Timestamp');
$om = new fcCodeModule(__FILE__, 'field-ctrl-more.php');
  $om->AddClass('fcInstaModeButton');
$om = new fcCodeModule(__FILE__, 'field-native.php');
  $om->AddClass('fcFormField_Text');
  $om->AddClass('fcFormField_Num');
  $om->AddClass('fcFormField_Time');
  $om->AddClass('fcFormField_Bit');
  $om->AddClass('fcFormField_BoolInt');
$om = new fcCodeModule(__FILE__, 'field-store.php');
  $om->AddClass('fcFieldStorage_Text');
  $om->AddClass('fcFieldStorage_Num');
  $om->AddClass('fcFieldStorage_Time');
  $om->AddClass('fcFieldStorage_Bit');
$om = new fcCodeModule(__FILE__, 'form.php');
  $om->AddClass('fcForm');
  $om->AddClass('fcForm_keyed');
$om = new fcCodeModule(__FILE__, 'form-shortlist.php');
  $om->AddClass('clsWidget_ShortList');
