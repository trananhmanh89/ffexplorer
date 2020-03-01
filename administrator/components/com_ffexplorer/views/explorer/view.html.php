<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

class FfexplorerViewExplorer extends HtmlView
{
    public function display($tpl = null)
    {
        ToolbarHelper::title('Funfis Explorer');
        ToolbarHelper::preferences('com_ffexplorer');

        HTMLHelper::_('jquery.framework');
        HTMLHelper::_('behavior.core');
        HTMLHelper::_('behavior.keepalive');

        $doc = $this->document;
        $doc->addScript('https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.20.0/min/vs/loader.min.js');
        $doc->addScriptDeclaration(";require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.20.0/min/vs' }});");

        $doc->addScript(Uri::root(true) . '/administrator/components/com_ffexplorer/assets/explorer/dist/app.js');
        $doc->addStyleSheet(Uri::root(true) . '/administrator/components/com_ffexplorer/assets/explorer/dist/app.css');
        $doc->addScriptOptions('ffexplorer_max_file_size_upload', ini_get('upload_max_filesize'));
        parent::display($tpl);
    }
}