<?php
// No direct access to this file.
defined('_JEXEC') or die('Restricted access');

/* No Menu is created for this component. user is redirected to the plug-in
 * manager instead with a message of stating where
 * to find the configuration menu
 */

$app = JFactory::getApplication('administrator');
$link = 'index.php?option=com_plugins&view=plugin&layout=edit';

$goTo = JURI::base() . $link;
$app->redirect($goTo, JText::_('COM_PRO6PP_ADMIN_REDIRECT'));