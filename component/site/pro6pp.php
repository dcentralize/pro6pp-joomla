<?php
// No direct access to the file
defined('_JEXEC') or die('Restricted Access not allowed');

// import joomla controller library
jimport('joomla.application.component.controller');

// Get the session
$session = & JFactory::getSession();

// get the domain
$juriBase = JURI::base();

// get the http referer, if no referer, initialise an empty string
if (! isset($_SERVER['HTTP_REFERER'])) {
    jexit('Sorry: Inaccessible URI<br /><a href="' . $juriBase
        . '"> Return to HomePage </a>');
}

    // get an instance of the controller prefixed by HelloWorld
    $controller = JController::getInstance('Pro6pp');

    // get the application input
$input = JFactory::getApplication()->input;

// perform the requested task
$controller->execute(JRequest::getCmd('task'));

// Redirect if set by controller
$controller->redirect();