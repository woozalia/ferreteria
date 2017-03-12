<?php
/*
  FILE: admin.sess.php -- shopping session administration for VbzCart
  HISTORY:
    2010-10-17 created
*/
class fctAdminUserSessions extends fctUserSessions {
    use ftLinkableTable;

    // ++ SETUP ++ //

    protected function GetActionKey() {
	return KS_PAGE_KEY_SESSION;
    }
    protected function SingularName() {
	return KS_CLASS_ADMIN_USER_SESSION;
    }
    
    // -- SETUP -- //
    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec(array $arArgs=NULL) {
	return $this->AdminListing();
    }

    // -- DROP-IN API -- //
    // ++ WEB UI ++ //

    protected function AdminListing() {
	$rs = $this->SelectRecords();
	$arCols = array(
	  'ID'		=> 'ID',
	  'ID_Client'	=> 'Client',
	  'ID_Acct'	=> 'User',
	  'Stash'	=> 'Data',
	  'WhenCreated'	=> 'Created',
	  'WhenExpires'	=> 'Exp',
	  'WhenClosed'	=> 'End',
	  );
	$out = $rs->AdminRows($arCols);
	return $out;
    }
}
class fcrAdminUserSession extends fcrUserSession {
    use ftLinkableRecord;
    use ftShowableRecord;

    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec(array $arArgs=NULL) {
	return $this->AdminPage();
    }

    // -- DROP-IN API -- //
    // ++ CLASS NAMES ++ //

    protected function ClientsClass() {
	return KS_CLASS_ADMIN_USER_CLIENTS;
    }
    protected function UsersClass() {
	return KS_CLASS_ADMIN_USER_ACCOUNTS;
    }

    // -- CLASS NAMES -- //
    // ++ FIELD CALCULATIONS ++ //

    protected function ClientString_admin() {
	if (is_null($this->ClientID())) {
	    return '<i>n/a</i>';
	} else {
	    $rcCli = $this->ClientRecord_asSet();
	    $htID = $rcCli->AdminLink();
	    $sBrowser = $rcCli->BrowserString();
	    $sAddress = $rcCli->SourceShort();
	    return "$htID: $sAddress / $sBrowser";
	}
    }

    // -- FIELD CALCULATIONS -- //
    // ++ ADMIN WEB UI ++ //

    protected function AdminPage() {
	$oPage = $this->Engine()->App()->Page();

	$id = $this->GetKeyValue();
	$ftClient = $this->ClientString_admin();
	$ftUser = $this->UserID();	// TODO: show username too
	$ftCart = $this->CartID();
	$ftOrd = $this->OrderID();	// TODO: show order # too
	$sWhenCre = $this->WhenCreated();
	$sWhenExp = $this->WhenExpires();
	$sWhenClo = $this->WhenClosed();

	$out = NULL;

	$out .= <<<__END__
<table>
<tr><td align=right><b>ID</b>:</td><td>$id</td></tr>
<tr><td align=right><b>Client</b>:</td><td>$ftClient</td></tr>
<tr><td align=right><b>User</b>:</td><td>$ftUser</td></tr>
<tr><td align=right><b>Cart</b>:</td><td>$ftCart</td></tr>
<tr><td align=right><b>Order</b>:</td><td>$ftOrd</td></tr>
<tr><td align=right><b>When Created</b>:</td><td>$sWhenCre</td></tr>
<tr><td align=right><b>When Expires</b>:</td><td>$sWhenExp</td></tr>
<tr><td align=right><b>When Closed</b>:</td><td>$sWhenClo</td></tr>
</table>
__END__;

	return $out;
    }

    // -- ADMIN WEB UI -- //
}