<?php

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die('Restricted access');

$controller = BaseController::getInstance('ffexplorer');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();