<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Session\Session;
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
        $doc->addScriptDeclaration(";window.require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.20.0/min/vs' }});");

        $doc->addScript(Uri::root(true) . '/administrator/components/com_ffexplorer/assets/explorer/dist/app.js');
        $doc->addStyleSheet(Uri::root(true) . '/administrator/components/com_ffexplorer/assets/explorer/dist/app.css');
        $doc->addScriptOptions('ffexplorer_max_file_size_upload', ini_get('upload_max_filesize'));

        $csrfToken = Session::getFormToken();
        
        $language = array(
            
        );

        $data = array(
            'path' => array(
                'ajax' => Uri::base() . '?option=com_ffexplorer',
                'root' => Uri::root(),
            ),
            'params' => array(
                $csrfToken => 1,
            ),
            'maxFileSizeUpload' => ini_get('upload_max_filesize'),
            'uploadForm' => implode('', array(
                '<form class="context-download-file" action="' . Uri::base() . '?option=com_ffexplorer" method="post">',
                    '<input type="hidden" name="option" value="com_ffexplorer">',
                    '<input type="hidden" name="task" value="explorer.download">',
                    '<input type="hidden" name="path" value="" class="file-path">',
                    '<input type="hidden" name="'.$csrfToken.'" value="1">',
                '</form>',
            )),
            'language' => $language,
        );

        $doc->addScriptDeclaration(';var FF_EXPLORER_DATA = ' . json_encode($data) . ';');
        parent::display($tpl);
    }
}