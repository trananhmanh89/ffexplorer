<?php

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die('Restricted access');

class FfexplorerControllerExplorer extends BaseController
{
    public function openFile()
    {
        $this->checkToken();

        $path = $this->input->getString('path');
        if (!$path) {
            $this->response('error', 'empty path');
        }

        $file = JPATH_ROOT . $path;
        if (!File::exists($file)) {
            $this->response('error', 'file not existed');
        }

        $info = pathinfo($path);
        $ext = isset($info['extension']) ? $info['extension'] : 'unknow';
        $content = file_get_contents($file);

        $languages = array(
            'js' => 'javascript',
            'php' => 'php',
            'scss' => 'scss',
            'css' => 'css',
            'less' => 'less',
            'sql' => 'mysql',
            'ini' => 'ini',
            'xml' => 'xml',
            'html' => 'html',
            'svg' => 'html',
            'json' => 'json',
            'md' => 'markdown',
        );

        $language = isset($languages[$ext]) ? $languages[$ext] : '';

        $this->response('data', array(
            'language' => $language,
            'content' => $content,
        ));
    }

    public function explodeFolder()
    {
        $this->checkToken();

        $path = $this->input->getString('path');

        $path = $path ? JPATH_ROOT . $path : JPATH_ROOT;
        $folders = Folder::folders($path, '.', false, true);
        $folders = array_map(function($folder) {
            $folder = realpath($folder);
            $info = pathinfo($folder);

            $item = new stdClass;
            $item->path = str_replace(JPATH_ROOT, '', $folder);
            $item->name = $info['basename'];
            $item->type = 'folder';

            return $item;
        }, $folders);

        $files = Folder::files($path, '', false, true, array('.svn', 'CVS', '.DS_Store', '__MACOSX'), array('.*~'));
        $files = array_map(function($file) {
            $file = realpath($file);
            $info = pathinfo($file);

            $item = new stdClass;
            $item->path = str_replace(JPATH_ROOT, '', $file);
            $item->name = $info['basename'];
            $item->type = 'file';

            return $item;
        }, $files);

        $result = array_merge($folders, $files);

        die(json_encode($result));
    }

    public function newFile()
    {
        $this->checkToken();

        $name = $this->input->getString('name');
        $path = $this->input->getString('path');

        if (!$name || !$path) {
            die(json_encode(array('error' => 'empty')));
        }

        $file = JPATH_ROOT . $path . '/' . $name;
        if (File::exists($file)) {
            $this->response('error', 'File is already existed');
        }

        if (File::write($file, '')) {
            $this->response('success', 'File has been created');
        } else {
            $this->response('error', 'Create file failed');
        }
    }

    public function newFolder()
    {
        $this->checkToken();

        $name = $this->input->getString('name');
        $path = $this->input->getString('path');

        if (!$name || !$path) {
            die(json_encode(array('error' => 'empty')));
        }

        $folder = JPATH_ROOT . $path . '/' . $name;
        if (Folder::exists($folder)) {
            $this->response('error', 'Folder is already existed');
        }

        if (Folder::create($folder)) {
            $this->response('success', 'Folder has been created');
        } else {
            $this->response('error', 'Create folder failed');
        }
    }

    public function renameFolder()
    {
        $this->checkToken();

        $newName = $this->input->getString('newName');
        $oldPath = $this->input->getString('oldPath');

        if (!$newName || !$oldPath) {
            $this->response('error', 'empty');
        }

        if (!Folder::exists(JPATH_ROOT . $oldPath)) {
            $this->response('error', 'Folder not found');
        }

        $info = pathinfo($oldPath);
        $folder = JPATH_ROOT . $info['dirname'] . '/' . $newName;
        if (Folder::exists($folder)) {
            $this->response('error', 'Folder is already existed');
        }

        $result = rename( JPATH_ROOT . $oldPath, $folder);
        if ($result) {
            $this->response('success', 'Folder name changed to ' . $folder);
        } else {
            $this->response('error', 'rename error');
        }
    }

    public function deleteFolder()
    {
        $this->checkToken();

        $path = $this->input->getString('path');
        if (!$path) {
            $this->response('error', 'empty path');
        }

        if (Folder::exists(JPATH_ROOT . $path)) {
            if(Folder::delete(JPATH_ROOT . $path)) {
                $this->response('success', 'deleted');
            } else {
                $this->response('error', 'Delete failed');
            }
        } else {
            $this->response('error', 'Folder is not existed');
        }
    }

    public function renameFile()
    {
        $this->checkToken();

        $newName = $this->input->getString('newName');
        $oldPath = $this->input->getString('oldPath');

        if (!$newName || !$oldPath) {
            $this->response('error', 'empty');
        }

        if (!File::exists(JPATH_ROOT . $oldPath)) {
            $this->response('error', 'File not found');
        }

        $info = pathinfo($oldPath);
        $file = JPATH_ROOT . $info['dirname'] . '/' . $newName;
        if (File::exists($file)) {
            $this->response('error', 'File is already existed');
        }

        $result = rename( JPATH_ROOT . $oldPath, $file);
        if ($result) {
            $this->response('success', 'File name changed to ' . $file);
        } else {
            $this->response('error', 'rename error');
        }
    }

    public function deleteFile()
    {
        $this->checkToken();

        $path = $this->input->getString('path');
        if (!$path) {
            $this->response('error', 'empty path');
        }

        if (File::exists(JPATH_ROOT . $path)) {
            if(File::delete(JPATH_ROOT . $path)) {
                $this->response('success', 'deleted');
            } else {
                $this->response('error', 'Delete failed');
            }
        } else {
            $this->response('error', 'File is not existed');
        }
    }

    public function checkToken($method = 'post', $redirect = false)
    {
        if (!parent::checkToken($method, $redirect)) {
            $this->response('error', 'csrf token error');
        }
    }

    protected function response($type = 'success', $data)
    {
        die(@json_encode(array($type => $data)));
    }
}