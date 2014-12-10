<?php
/*
  PURPOSE: Skin class that supports login controls
*/
abstract class clsSkin_login extends clsSkin_standard {
    public function RenderLogin($iUName=NULL) {
	$out =
	  ' Username:<input name=uname size=10 value="'.$iUName.'">'
	  .' Password:<input type=password name=upass size=10>'
	  .' <input type=submit value="Log In" name=btnLogIn>';
	return $out;
    }
    public function RenderLogout($iText='log out') {
	$out = '<a href="'.KWP_LOGOUT.'">'.$iText.'</a>';
	return $out;
    }
    /*----
      ACTION: Renders controls for setting username and password
	This appears very similar to RenderLogin(), but it needs
	to include an auth code as well.
      INPUT:
	iAuth: authorization code emailed to user (URL format)
	iUser: current username (optional)
    */
/* OLD
    public function RenderUserSet($iAuth,$iUser) {
	$htUser = htmlspecialchars($iUser);
	$out =
	  '<input type=hidden name=auth value="'.$iAuth.'">'
	  .' Username:<input name=uname size=10 value="'.$htUser.'">'
	  .' Password:<input type=password name=upass size=10>'
	  .' <input type=submit value="Create Account" name=btnSetLogIn>';
	return $out;
    }
    */
    protected function RenderUserModify($iAuth,$iUser,$sButtonName,$sButtonText) {
	$htUser = htmlspecialchars($iUser);
	if (is_null($iUser)) {
	    $ctUser = '<input name=uname size=10 value="'.$htUser.'">';
	} else {
	    $ctUser = "<b>$htUser</b>";
	}
	$cnPass1 = KSF_USER_CTRL_SET_PASS1;
	$cnPass2 = KSF_USER_CTRL_SET_PASS2;
	$out = <<<__END__
<input type=hidden name=auth value="$iAuth">
<center>
<table>
  <tr><td align=right>Username:</td><td>$ctUser</td></tr>
  <tr><td align=right>New Password:</td><td><input type=password name=$cnPass1 size=10></td></tr>
  <tr><td align=right>Password again:</td><td><input type=password name=$cnPass2 size=10></td></tr>
  <tr><td colspan=2 align=center><input type=submit value="$sButtonText" name="$sButtonName"></td></tr>
</table>
</center>
__END__;
	return $out;
    }

    public function RenderUserCreate($iAuth,$iUser=NULL) {
	return $this->RenderUserModify($iAuth,$iUser,KSF_USER_BTN_NEW_ACCT,'Create New Account');
    }
    /*----
      ACTION: Renders controls for setting username and password
      INPUT:
	iAuth: authorization code emailed to user (URL format)
	iUser: current username (optional)
    */
    public function RenderUserUpdate($iAuth,$iUser) {
	return $this->RenderUserModify($iAuth,$iUser,KSF_USER_BTN_SET_PASS,'Set Password');
    }
    /*----
      ACTION: renders control for sending a password reset email
    */
    public function RenderForm_Email_RequestReset($iEmail) {
	$out =
	  ' Email address:<input name=uemail size=40 value="'.htmlspecialchars($iEmail).'">'
	  .' <input type=submit value="Send Email" name="btnSendAuth">';
	return $out;
    }
}