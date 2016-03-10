<?php
/*
  PURPOSE: returns JavaScript code in $sJS to add a click-handler directly from PHP variables
    This is basically just so PHP can call the JS function RunOnClick() with different arguments.
  NOTE: The file has a .js extension so that editors will render the JavaScript properly,
    though it is wrapped in PHP (which consequently displays with errors).
*/
$jsOut = <<<__END__

phpSID = '$sidClick';
phpUrlStart = '$urlStart';
phpUrlCheck = '$urlCheck';

RunOnClick(phpSID,phpUrlStart,phpUrlCheck);

__END__;
