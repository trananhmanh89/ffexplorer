<?php

/**
 * @package     FF Explorer
 * @subpackage  com_ffexplorer
 *
 * @copyright   https://github.com/trananhmanh89/ffexplorer
 * @license     MIT
 */


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die('Restricted access');

if (!Factory::getUser()->authorise('core.manage', 'com_ffexplorer')) 
{
    throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = BaseController::getInstance('ffexplorer');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();