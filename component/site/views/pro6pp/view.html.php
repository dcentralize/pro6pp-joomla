<?php
// No direct access to this file should be called by Joomla
defined('_JEXEC') or die('Restricted Access');

// import joomla controller library
jimport('joomla.application.component.view');

// Name of the view Class is always like:
// [ComponentName]["View"][FolderName]
class Pro6ppViewPro6pp extends JView
{

    // Overwrite JView display method
    function display ($tpl = null)
    { // Assign data to the view (Retrieved from model/pro6pp)

        // Get the model
        $model = &$this->getModel('pro6pp');

        // Change the encoding
        $doc = & JFactory::getDocument();
        $doc->setMimeEncoding('text/html');

        $this->response = 'error, no HTML view is supported';
        $this->callback = $model->getCallback();

        // Display the View
        $tpl = 'default';
        parent::display($tpl);
    }
}