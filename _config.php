<?php


SilverStripe\Admin\CMSMenu::add_link('dashboard', 'Dashboard', '_bc', -100, null, 'font-icon-cart');

/** Tiny MCE configurations **/
$cms_editor = \SilverStripe\Forms\HTMLEditor\HTMLEditorConfig::get('cms');
$editor = clone $cms_editor;
\SilverStripe\Forms\HTMLEditor\HTMLEditorConfig::set_config('bigcommerce',$editor);
$editor->setOption('friendly_name','frontend');
$editor->setOption('editorIdentifier','bigcommerce');

$contentCSS = $editor->getContentCSS();
$contentCSS[] = 'public/resources/themes/mysite/css/editor.css';
$contentCSS = $editor->setContentCSS($contentCSS);

$editor->removeButtons([
	'ssmedia',
	'sslink',
	'ssembed'
]);

$editor->enablePlugins([
	'hr',
	'importcss',
	'charmap',
	'advlist',
	'anchor',
	'code',
	'link',
	'image'
]);
$editor->disablePlugins([
	'contextmenu',
	'sslink',
	'sslinkexternal',
	'sslinkemail',
	'sslinkinternal',
	'sslinkanchor',
	'sslinkfile',
	'ssmedia',
	'ssembed'
]);
$editor->addButtonsToLine(1,[
	'blockquote',
	'subscript',
	'superscript',
	'hr'
]);

//$editor->setOption('style_formats', [
////	['title' => 'clear', 'styles' => ['clear' => 'both']]
//	['title' => 'cleartext', 'classes' => ".clear"]
//]);
$editor->setOption('style_formats_merge',true);
$editor->insertButtonsBefore('formatselect','styleselect');
$editor->insertButtonsBefore('anchor',['link','image']);
$editor->removeButtons(['formatselect']);
$editor->setOption('importcss_selector_filter','.text');
$editor->setOption('relative_urls',false);
$editor->setOption('importcss_append',true);
$editor->setOption('body_class','typography');
$extended_valid_elements = explode(',',$editor->getOption('extended_valid_elements'));
$extended_valid_elements = array_merge(['-ol[start|class]','i[class]'],['table[border|cellpadding|cellspacing]'],$extended_valid_elements);
$editor->setOption('extended_valid_elements',implode(',',$extended_valid_elements));
