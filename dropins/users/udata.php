<?php
/*
  PURPOSE: for storing user-data
  HISTORY:
    2017-07-28 started
*/
class fctNode_UserData extends fctNodeTableType implements fiLinkableTable, fiEventAware {

    // ++ SETUP ++ //

    // CEMENT
    protected function SingularName() {
	return 'fcrNode_UserDatum';
    }
    // CEMENT
    public function GetKeyName() {
	return 'ID';
    }
    // CEMENT
    public function GetActionKey() {
	return KS_ACTION_TF_USER_DATUM_PAGE;
    }
    // CEMENT
    public function GetTypeKey() {
	return KS_TF_NODE_TYPE_PAGE;
    }
    protected function FieldArray() {
	return array('key','value','user');
    }
    
    // -- SETUP -- //
    // ++ EVENTS ++ //
    
    /*----
      NOTE: Direct editing wouldn't be done normally; this is mainly for debugging.
	Possibly it should be disabled for production use.
	It should certainly be limited to administrators.
    */
  
    protected function OnCreateElements() {}
    /*----
      NOTE: Most of this stuff doesn't actually *have* to be here, but the title calculations do, and it just
	made sense to put it all together here.
    */
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle('User Data');
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    /*----
      NOTE: App()->GetUserRecord() may return the non-dropin class (TODO: FIX), so
	we have to upgrade it here.
    */
    public function Render() {
	return $this->AdminPage();
    }

    // -- EVENTS -- //
    // ++ API ++ //
    
    // NOTE: for now, values will be strings; this could be expanded later
    public function SetValue($idUser,$sName,$sValue) {
	// find node with matching user and name leafs
	$ar = array(
	  'user'	=> $idUser,
	  'name'	=> $sName
	  );
	$rc = $this->SelectRecords_forLeafValues($ar);
	$rc->Update(array('value' => $sValue),TRUE);
    }
    
    // -- API -- //
    
}
class fcrNode_UserDatum {
}