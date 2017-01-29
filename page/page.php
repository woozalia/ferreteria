<?php
/*
  FILE: page.php
  PURPOSE: skinnable page-rendering system
  USAGE RULES:
    * A Page is a hierarchy of page elements.
    * Functionality should be clearly separated into logic and display methods.
    * Descendant classes can then override those methods to change how a page is displayed.
  HISTORY:
    2012-05-08 split off from store.php
    2012-05-13 removed clsPageOutput (renamed clsPageOutput_WHO_USES_THIS some time ago)
    2013-09-16 clsVbzSkin_Standard renamed clsVbzPage_Standard
    2013-09-17 clsVbzPage_Standard renamed clsVbzPage_Browse0
    2013-10-23 stripped for use with ATC app (renamed as app.php)
    2013-11-11 re-adapted for general library (split off page classes from app.php into page.php)
    2016-11-09 started rewriting skin classes, which later became rewritten page classes
    2016-11-13 rewriting from scratch
    2016-11-19 still working...
    2016-11-20 splitting off "standard" classes
*/

// event codes:
define('KI_NODE_EVENT_DO_BUILDING',1);	// set up node structure
define('KI_NODE_EVENT_DO_FIGURING',2);	// do any necessary communication between nodes

// PURPOSE: So a node can respond to events (does not pass them down to subnodes)
trait ftExecutableTwig {
    public function DoEvent($nEvent) {
	$this->OnEventDispatch($nEvent);
    }
    protected function OnEventDispatch($nEvent) {
	switch ($nEvent) {
	  case KI_NODE_EVENT_DO_BUILDING:
	    $this->OnCreateElements();
	    break;
	  case KI_NODE_EVENT_DO_FIGURING:
/*	    if (get_class($this) == 'fcpeLoginWidget') {
		echo 'NAME ['.$this->GetName().'] EVENT DO_FIGURING<br>';
	    }*/
	    $this->OnRunCalculations();
	    break;
	}
    }
    abstract protected function OnCreateElements();
    abstract protected function OnRunCalculations();
}
// PURPOSE: So an ExecutableTwig node will pass events down to its subnodes
trait ftExecutableTree {
    use ftExecutableTwig;

    // ++ OVERRIDE ++ //

    public function DoEvent($nEvent) {
	$this->OnEventBefore($nEvent);
	if ($this->HasNodes()) {
	    $ar = $this->GetNodes();
	    foreach ($ar as $name => $oNode) {
		$oNode->DoEvent($nEvent);
	    }
	}
	$this->OnEventAfter($nEvent);
    }
    
    // -- OVERRIDE -- //
    // ++ NEW ++ //

    // PURPOSE: by default, Self handles event before passing it down
    protected function OnEventBefore($nEvent)	{ $this->OnEventDispatch($nEvent); }
    protected function OnEventAfter($nEvent)	{}	// stub
    
    // -- NEW -- //
    
}
trait ftRenderableTree {
    protected function RenderNodes() {
	$out = NULL;
	if ($this->HasNodes()) {
	    $out = $this->RenderBeforeNodes();
	    $ar = $this->GetNodes();
	    foreach ($ar as $name => $oNode) {
		$out .= $oNode->Render();
	    }
	    $out .= $this->RenderAfterNodes();
	}
	return $out;
    }
    protected function RenderBeforeNodes() { return ''; }
    protected function RenderAfterNodes() { return ''; }
}
/*::::
  PURPOSE: a Page Element is a renderable, executable Tree Node but is not a good parent (ignores any children).
*/
abstract class fcPageElement extends fcTreeNode {
    abstract public function Render();
    abstract public function DoEvent($nEvent);
}
/*::::
  PURPOSE: a Simple Page Element is a Page Element with a name and a value, no other contents to display; 
    defaults to obliviousness (ignores events).
*/
class fcpeSimple extends fcPageElement {
    public function Render() {
	return $this->GetValue();
    }
    public function DoEvent($nEvent) {}
}
/*::::
  RULES: 
    * Has a value and may have contents.
    * If there are contents, the tag is closed after the contents are rendered; otherwise it is left open.
    * Does not render attributes.
*/
abstract class fcHypertextTag extends fcPageElement {

    abstract protected function TagName();
    abstract protected function TagValue();
    public function Render() {
	$out = $this->RenderTagOpen();
	// if there's content, add it in and show a closing tag:
	$sContent = $this->GetContent();
	if (!is_null($sContent)) {
	    $out .= $sContent."\n".$this->RenderTagShut();
	}
	return $out;
    }
    protected function RenderTagOpen() {
	$sName = $this->TagName();
	$sValue = $this->TagValue();
	$htValue = empty($sValue)?'':" $sValue";
	$sAttrs = $this->RenderTagAttributes();
	return "<$sName$htValue$sAttrs>";
    }
    // PURPOSE: largely for debugging
    protected function RenderTagAttributes() {
	$sTag = get_class($this).'-'.$this->GetName();
	return " id='$sTag'";
    }
    protected function RenderTagShut() {
	return '</'.$this->TagName().'>';
    }
    protected function GetContent() {
	return $this->GetValue();
    }
    protected function DumpHTML_self() {
	return parent::DumpHTML_self().' => '.htmlspecialchars($this->RenderTagOpen());
    }

}
abstract class fcpeHTMLContainer extends fcHypertextTag {
    use ftRenderableTree;

    // ++ OVERRIDES ++ //

    protected function GetContent() {
	return $this->RenderNodes();
    }
    
    // -- OVERRIDES -- //
}
/*::::
  RULES: The DOCTYPE tag is only sort-of like HTML tags; it has no attribute, just a value.
*/
class fcpeDocType extends fcHypertextTag {

    // ++ CEMENTING ++ //

    public function DoEvent($nEvent){}	// oblivious
    protected function TagName() {
	return '!DOCTYPE';
    }
    protected function TagValue() {
	return $this->GetValue();
    }
    
    // -- CEMENTING -- //
    // ++ OVERRIDES ++ //

    // OVERRIDE: DOCTYPE has no attributes as such, just a value
    protected function RenderTagAttributes() {
	return NULL;
    }
    // OVERRIDE: This pseudotag-type has no content; the value is the doctype.
    protected function GetContent() {
	return NULL;
    }
    
    // -- OVERRIDES -- //

}
/*::::
  PURPOSE: JUST the attributes for a tag; not a tag-with-attributes
    To create a tag with attributes, create an object of this class
    and call its Render() method for the tag's value.
    This is the purpose of fcpeTag_withAttribs.
*/
class fcTagAttribs extends fcTreeNode {
    use ftAutomakeable;

    protected function ClassForSubs() {
	return 'fcTreeNode';
    }
    public function Render() {
	$arAttr = $this->GetValues();
	$htAttr = fcHTML::ArrayToAttrs($arAttr);
	return $htAttr;
    }
}
abstract class fcpeTag_withAttribs extends fcHypertextTag {

    // ++ CEMENTING ++ //

    protected function TagValue() {
	return $this->Attributes()->Render();
    }

    // -- CEMENTING -- //
    // ++ OBJECTS ++ //
    
    private $oAttribs;
    protected function Attributes() {
	if (empty($this->oAttribs)) {
	    $this->oAttribs = new fcTagAttribs();
	}
	return $this->oAttribs;
    }
    
    // -- OBJECTS -- //
    // ++ API ++ //
    
    public function SetAttribute($sName,$sValue) {
	$this->Attributes()->SetSubValue($sName,$sValue);
    }

    // -- API -- //
    
}
/*::::
  RULES: A container tag has::
    * 'attribs' subnodes for its attributes 
    * children subnodes for any contained page elements
    * a method for its contents to be displayed before sub-elements (via ftContainer)
    * a method for its contents to be displayed after sub-elements (via ftContainer)
*/
abstract class fcContainerTag extends fcpeTag_withAttribs {
    use ftRenderableTree, ftExecutableTree;

    // ++ CEMENTING ++ //
    
    protected function TagValue() {
	return $this->Attributes()->Render();
    }

    // -- CEMENTING -- //
    // ++ OVERRIDES ++ //

    // OVERRIDE: This isn't critical; it just makes the HTML source easier to read.
    protected function RenderTagOpen() {
	return "\n".parent::RenderTagOpen();
    }
    protected function GetContent() {
	return $this->RenderNodes();
    }
    
    // -- OVERRIDES -- //

}
// NOTE: If we ever need for a <title> tag to have attributes, descend from fcpeTag_withAttribs instead.
class fcpeBrowserTitle extends fcHypertextTag {

    // ++ CEMENTING ++ //

    public function DoEvent($nEvent){}	// oblivious
    protected function TagName() {
	return 'title';
    }
    protected function TagValue() {
	return NULL;
    }

    // -- CEMENTING -- //

}
class fcpeHTMLMetatag extends fcpeTag_withAttribs {

    // CEMENT
    public function DoEvent($nEvent){}	// oblivious
    // CEMENT
    protected function TagName() {
	return 'meta';
    }
    // OVERRIDE
    protected function RenderTagOpen() {
	return "\n".parent::RenderTagOpen();
    }
    
}
class fcpeHTML_StyleSheet extends fcpeTag_withAttribs {

    // ++ SETUP ++ //
    
    // 2016-11-26 possibly this should respond to an event instead
    public function __construct() {
	$this->SetAttribute('rel','StyleSheet');
    }

    // -- SETUP -- //
    // ++ CEMENTING ++ //

    public function DoEvent($nEvent){}	// oblivious
    protected function TagName() {
	return 'link';
    }

    // -- CEMENTING -- //
    // ++ SETUP ++ //
    
    public function SetURL($url) {
	$this->SetAttribute('href',$url);
    }
    
    // -- SETUP -- //
}
// PURPOSE: what goes inside the <html></html> tags
abstract class fcTag_html extends fcContainerTag {

    // ++ CEMENTING ++ //
    
    protected function TagName() {
	return 'html';
    }

    // -- CEMENTING -- //
    // ++ CLASSES ++ //

    protected function Class_forTag_header() {
	return 'fcTag_header';
    }

    // -- CLASSES -- //
    // ++ SUB-ELEMENTS ++ //

    protected function NewTagNode_header() {
	return $this->CreateNamedNode('header',$this->Class_forTag_header());
    }
    /*----
      ASSUMES: NewTagNode_header() has previously been called
      PUBLIC so Page class can access it
    */
    public function GetTagNode_header() {
	return $this->GetNode('header');
    }

    // -- SUB-ELEMENTS -- //

}
class fcTag_header extends fcContainerTag {

    // ++ CEMENTING ++ //

    protected function OnCreateElements() {
	// add meta: Content-Type
	$arAtts = array(
	  'http-equiv'	=> 'Content-Type',
	  'content'	=> 'text/html; charset='.KS_CHARACTER_ENCODING,
	  );
	$this->AddMetaTag($arAtts);
    }
    protected function OnRunCalculations(){}

    // -- SETUP -- //
    // ++ CEMENTING ++ //
    
    protected function TagName() {
	return 'head';
    }

    // -- CEMENTING -- //
    // ++ NODE FACTORY ++ //
    
    protected function GetTagNode_title() {
	return $this->MakeNode('title','fcpeBrowserTitle');
    }
    protected function GetTagNode_meta() {
	return $this->CreateAnonymousNode('fcpeHTMLMetatag');
    }
    protected function GetTagNode_StyleSheet() {
	return $this->MakeNode('css','fcpeHTML_StyleSheet');
    }
    
    // -- NODE FACTORY -- //
    // ++ NEW OPTIONS ++ //
    
    // PUBLIC so Page (parent) element can use it
    public function SetBrowserTitle($s) {
	$this->GetTagNode_title()->SetValue($s);
    }
    protected function AddMetaTag(array $arAttrs) {
	$o = $this->GetTagNode_meta();
	foreach ($arAttrs as $sName => $sValue) {
	  $o->SetAttribute($sName,$sValue);
	}
	return $o;
    }
    // PUBLIC so container class can use it
    public function SetStyleSheetURL($url) {
	$o = $this->GetTagNode_StyleSheet();
	$this->GetTagNode_StyleSheet()->SetURL($url);
    }
    
    // -- NEW OPTIONS -- //

}
abstract class fcPageContent extends fcpeSimple {
    use ftExecutableTree, ftRenderableTree;
    
    // ++ RECORDS ++ //
    
    protected function GetSessionRecord() {
	return fcApp::Me()->GetSessionRecord();
    }
    
    // -- RECORDS -- //
    // ++ OUTPUT ++ //

    /*----
      NOTE: We shouldn't ever need to have RenderBefore()/RenderAfter() methods here.
	The only time this has come up so far, it turned out they belong in the page header.
	If we want to wrap the content in, say, <span> tags, then add a tag element node.
    */
    public function Render() {
	
	return
	  $this->RenderStashed()
	  .$this->RenderNodes()
	  .$this->GetValue()
	  ;
    }
    protected function RenderStashed() {
	$rcSess = $this->GetSessionRecord();
	$out = $rcSess->PullStashValue('ferreteria','page contents');
	$rcSess->Save();
	return $out;
    }
    /*----
      PUBLIC so widgets (so far, just the login widget) can call it
      HISTORY:
	2016-12-25 moved here from the login widget, because this is the content we want to save
    */
    public function RedirectToEraseRequest() {
	// save the current page contents for redisplay after redirect
	$s = $this->GetValue();	// get content that won't be displayed because we're redirecting
	$rcSess = $this->GetSessionRecord();

	// debugging
	$rcSess->SetStashValue('ferreteria','page contents',$s);
	$rcSess->Save();	// write to persistent storage
	
	// now actually redirect
	$url = fcApp::Me()->GetKioskObject()->GetBasePath();	// until we can come up with something more fine-tuned
	fcHTTP::Redirect($url);
	die();	// stop doing stuff; we're redirecting
    }
}
abstract class fcTag_body extends fcContainerTag {

    // ++ CEMENTING ++ //
    
    protected function TagName() {
	return 'body';
    }

    // -- CEMENTING -- //

}
// NOTE: This is already a sort of vaguely-HTMLish page because it (optionally) does DOCTYPE.
abstract class fcPage extends fcPageElement {
    use ftRenderableTree, ftExecutableTree;
    
    // ++ EXECUTION ++ //
    
    public function DoBuilding() {
	$this->DoEvent(KI_NODE_EVENT_DO_BUILDING);
    }
    public function DoFiguring() {
	$this->DoEvent(KI_NODE_EVENT_DO_FIGURING);
    }
    public function DoOutput() {
	echo $this->Render();
    }

    // -- EXECUTION -- //
    // ++ CEMENTING ++ //

    // NOTE: All of a Page's contents are subnodes; it has none aside from that.
    public function Render() {
	return $this->RenderNodes();
    }
    
    // -- CEMENTING -- //
    // ++ CLASSES ++ //
    
    protected function DocTypeClass() {
	return 'fcpeDocType';
    }
    
    // -- CLASSES -- //
    // ++ NODES ++ //
    
    protected function DocTypeNode() {
	return $this->Spawn($this->DocTypeClass());
    }

    // -- NODES -- //
}
// PURPOSE: Page formatted as HTML
abstract class fcHTMLPage extends fcPage {

    // ++ SETUP ++ //

    protected function OnCreateElements() {
	// add <!DOCTYPE> and set its value
	$this->SetDocType('HTML');
	// add <html>
	$this->AddTagNode_html();
    }
    
    // -- SETUP -- //
    // ++ SUB-ELEMENTS ++ //

    // USAGE: only call at initialization
    protected function AddTagNode_html() {
	$this->MakeNode('html',$this->Class_forTagHTML());
    }
    // ASSUMES: HTML tag node has already been created
    protected function GetTagNode_html() {
	return $this->GetNode('html');
    }
    // ASSUMES: HTML tag node has been created and has a GetTagNode_body() method
    protected function GetTagNode_body() {
	return $this->GetTagNode_html()->GetTagNode_body();
    }
    
    // -- SUB-ELEMENTS -- //
    // ++ CLASSES ++ //
    
    abstract protected function Class_forTagHTML();

    // -- CLASSES -- //
    
}
