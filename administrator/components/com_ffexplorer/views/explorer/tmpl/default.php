<?php 

/**
 * @package     FF Explorer
 * @subpackage  com_ffexplorer
 *
 * @copyright   https://github.com/trananhmanh89/ffexplorer
 * @license     MIT
 */

use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');
?>

<div id="explorer-app"></div>
<div style="line-height: 30px;">
    <a href="<?php echo Uri::base() ?>" style="text-decoration: underline;">back to Joomla</a> • 
    <a href="https://github.com/trananhmanh89/ffexplorer" target="_blank" style="text-decoration: underline;">FF Explorer</a>
</div>

<style>
body {
    overflow: hidden;
}
</style>