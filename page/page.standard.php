<?php
/*
  FILE: page.php
  PURPOSE: "standard" page classes
    Most apps will probably use these, but some might descend from the base classes instead.
  STANDARD STRUCTURE:
    PAGE
      <html>
	<head>
	<body>
	  page header
	  nav sidebar
	  page content
  HISTORY:
    2016-11-20 split off from page.php
*/

// PURPOSE: <html> tag with all the standard contents and values
abstract class fcTag_html_standard extends fcTag_html {

    // ++ CEMENTING ++ //

    protected function OnCreateElements() {
	// add <header>:
	$oeHdr = $this->NewTagNode_header();
	$oeHdr->SetCharacterEncoding(KS_CHARACTER_ENCODING);
	// add <body>:
	$this->MakeNode('body',$this->Class_forTag_body());
    }
    protected function OnRunCalculations(){}

    // -- CEMENTING -- //
    // ++ CLASSES ++ //
    
    protected function Class_forTag_header() {
	return 'fcTag_header_standard';
    }
    abstract protected function Class_forTag_body();
    
    // -- CLASSES -- //
    // ++ SUB-ELEMENTS ++ //

    // PUBLIC so Page class can access it
    public function GetTagNode_body() {
	return $this->GetNode('body');
    }
    
    // -- SUB-ELEMENTS -- //

}
class fcTag_header_standard extends fcTag_header {

    // ++ NEW ELEMENTS ++ //
    
    public function SetMetaDescription($sDescr) {
	$this->AddMetaTag(
	  array(
	    'name'	=> 'description',
	    'content'	=> $sDescr
	    )
	  );
    }
    public function SetCharacterEncoding($s) {
	$this->AddMetaTag(
	  array(
	    'charset'	=> $s
	    )
	  );
    }
    
    // -- NEW ELEMENTS -- //
}
abstract class fcTag_body_standard extends fcTag_body {

    // ++ CEMENTING ++ //

    protected function OnCreateElements() {
	$this->GetElement_PageHeader();
	$this->GetElement_PageNavigation();
	$this->GetElement_PageContent();
    }

    // -- CEMENTING -- //
    // ++ NEW PAGE ELEMENTS ++ //
    
      //++classes++//
      
    abstract protected function Class_forPageHeader();
    abstract protected function Class_forPageNavigation();
    abstract protected function Class_forPageContent();
    
      //--classes--//
      //++objects++//
    
    // PUBLIC so outside elements can add stuff to the content
    public function GetElement_PageContent() {
	return $this->MakeNode('html.body.content',$this->Class_forPageContent());
    }
    // PUBLIC so outside elements can modify navbar
    public function GetElement_PageNavigation() {
	return $this->MakeNode('html.body.navbar',$this->Class_forPageNavigation());
    }
    // PUBLIC so outside elements can set header parameters
    public function GetElement_PageHeader() {
	return $this->MakeNode('html.body.header',$this->Class_forPageHeader());
    }

      //--objects--//
    // -- NEW PAGE ELEMENTS -- //
}
abstract class fcContentHeader extends fcpeSimple {

    protected function Class_forMenu() {
	return 'fcHeaderMenu';
    }

    // ++ FIELD VALUES ++ //
    
    private $sTitle;
    public function SetTitleString($s) {
	$this->sTitle = $s;
    }
    protected function GetTitleString() {
	return $this->sTitle;
    }

    // -- FIELD VALUES -- //
    // ++ ELEMENTS ++ //

    public function GetElement_Menu() {
	return $this->MakeNode('menu',$this->Class_forMenu());
    }

    // -- ELEMENTS -- //
    // ++ OUTPUT ++ //
    
      //++override++//
    public function Render() {
	return $this->RenderBefore()
	  .$this->RenderContent()
	  .$this->RenderAfter()
	  ;
    }
      //--override--//
      //++abstract++//
      
    abstract protected function RenderBefore();
    abstract protected function RenderContent();
    abstract protected function RenderAfter();
    
    // -- OUTPUT -- //
}
abstract class fcPage_standard extends fcHTMLPage {

    // ++ OPTIONS ++ //

    protected function SetDocType($s) {
	$oeNode = $this->DocTypeNode();
	$oeNode->SetValue($s);
	$this->SetNode($oeNode,'html.doctype');
    }
    protected function SetBrowserTitle($s) {
	$this->GetTagNode_header()->SetBrowserTitle($s);
    }
    protected function UseStyleSheet($sName) {
	$url = $this->GetStyleFolder().$sName.'.css';
	$this->SetStyleSheetURL($url);
    }
    abstract protected function GetStyleFolder();
    protected function SetStyleSheetURL($url) {
	$oHTML = $this->GetTagNode_html();
	$oHdr = $oHTML->GetTagNode_header();
	$this->GetTagNode_header()->SetStyleSheetURL($url);
    }
    /*----
      WARNING: Call this only ONCE, or multiple description meta-tags will be created.
	Yes, I could probably fix that, but then again why are you setting the description more than once?
	This at least lets you see that it is happening (look at the HTML source).
    */
    protected function SetMetaDescription($sDescr) {
	$this->GetTagNode_html()->SetMetaDescription($sDescr);
    }
    
    // -- OPTIONS -- //
    // ++ ELEMENTS ++ //
    
    protected function GetTagNode_header() {
	return $this->GetTagNode_html()->GetTagNode_header();
    }
    // PUBLIC so other elements can write to the page content
    public function GetElement_PageContent() {
	return $this->GetTagNode_body()->GetElement_PageContent();
    }
    abstract public function GetElement_PageHeader();
    abstract public function GetElement_HeaderMenu();

    // -- ELEMENTS -- //
    // ++ THRUPUT ++ //
    
    // PUBLIC so Content subnode can use it
    public function SetPageTitle($s) {
	$this->SetBrowserTitle($s);
	$this->SetContentTitle($s);
    }
    // PUBLIC so other elements can change it
    public function SetContentTitle($s) {
	$this->GetElement_PageHeader()->SetTitleString($s);
    }
    // 2017-01-26 This seems kind of silly.
    public function AddImmediateRender(fcPageElement $o) {
	$s = $o->Render();
	$this->GetElement_PageContent()->AddText($s);
    }
    
    // -- THRUPUT -- //
}
