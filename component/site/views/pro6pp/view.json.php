<?php
// No direct access to this file should be called by Joomla
defined('_JEXEC') or die('Restricted Access');

// import joomla controller library
jimport('joomla.application.component.view');


/**
 * The json format view class.
 * Gathers the data from the model class (model/pro6pp.php)
 * and calls the display function to present them (tmpl/default.php)
 */
// Name of the view Class is always like:
// [ComponentName]["View"][FolderName]
class Pro6ppViewPro6pp extends JView
{

    // Overwriting JView display method
    function display ($tpl = null)
    {
        // Get the model
        $model = $this::getModel('pro6pp');

        // Change the documents encoding
        $doc = & JFactory::getDocument();
        $doc->setMimeEncoding('application/json');

        // Get data from model
        $response = $model->getResponse();
        $callback = $model->getCallback();

        // Get the response as an array
        $decoded = json_decode($response, true);
        // Add the plug-in options to the response
        $decoded["options"]= $model->getOptions();
        // Localize the errors if any
        if ($decoded["status"] === "error") {
            $msg = $decoded["error"]["message"];
            $decoded["error"]["severity"] = $model->getErrorSeverity($msg);
            $decoded["error"]["message"] = $model->getLocalizedMsg($msg);
        }

        // Make it Json again
        $response = json_encode($decoded);

        // Assign data to the view
        $this->response = $response;
        $this->callback = $callback;

        // Display the view (calls the tmpl/default.php)
        parent::display($tpl);
    }
}