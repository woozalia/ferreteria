<?php
/*
  PURPOSE: returns JavaScript code in $sJS to pass HTML control IDs
    Needs to be surrounded by <script> tags before being emitted.
  NOTE: The file has a .js extension so that editors will render the JavaScript properly,
    even though overall it is technically PHP.
  HISTORY:
    2016-01-31 reorganizing JS files
*/
$jsOut = <<<__END__

var ctMain = document.getElementById('$sidShow');
var ctStatus = document.getElementById('$sidStatus');
var chkRun = document.getElementById('$sidChk');

__END__;
