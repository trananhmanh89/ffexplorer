<?php 
use Joomla\Filesystem\Folder;
use Joomla\Archive\Zip;

define('JPATH_ROOT', realpath(__DIR__ . '/..') );

require __DIR__ . '/vendor/autoload.php';


$path = realpath(JPATH_ROOT . '/administrator/components/com_ffexplorer/');

$excludeFolders = array('.svn', 'CVS', '.DS_Store', '__MACOSX', 'node_modules');
$files = Folder::files($path, '.', true, true, $excludeFolders);
$contents = array();

foreach ($files as &$file) {
    $info = pathinfo($file);
    if ($info['basename'] === 'ffexplorer.xml') {
        continue;
    }

    $content = array();
    $content['data'] = file_get_contents($file);

    $name = realpath($file);
    $name = 'admin' . str_replace($path, '', $name);
    $content['name'] = $name;
    $contents[] = $content;
}

$xmlContent = file_get_contents($path . '/ffexplorer.xml');
$contents[] = array(
    'name' => 'ffexplorer.xml',
    'data' => $xmlContent
);

$languages = array(
    'en-GB.com_ffexplorer.ini',
    'en-GB.com_ffexplorer.sys.ini'
);

foreach ($languages as $lang) {
    $contents[] = array(
        'name' => 'admin/language/' . $lang,
        'data' => file_get_contents(JPATH_ROOT . '/administrator/language/en-GB/' . $lang)
    );
}

$archive = new Zip();
$archive->create(JPATH_ROOT . '/build/ffexplorer.zip', $contents);

echo "\n===> build done <===\n";