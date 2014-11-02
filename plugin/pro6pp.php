<?php

// No direct access.
defined('_JEXEC') or die('Restricted access');

// Import the general joomla plugin library.
jimport('joomla.plugin.plugin');

class PlgSystemPro6pp extends JPlugin
{
    /**
     * The key value pairs for the supported component-view pages
     *
     * @var array $_supported
     */
    private static $_supported = array(
            'com_virtuemart' => 'user',
            'com_plugins' => 'plugin',
            'com_users' => 'registration'
    );

    function PlgSystemPro6pp (&$subject, $params)
    {
        parent::__construct($subject, $params);
    }

    /**
     * Executes before the header buffers are parsed into the page.
     *
     * @uses the GET array values that the user requested
     */
    function onBeforeCompileHead ()
    {
        // Get the application.
        $app = &JFactory::getApplication();
        // Get the document - Used to inject the JavaScript.
        $doc = &JFactory::getDocument();
        // Get the url input parameters.
        $jinput = $app->input;
        // Get the current component
        $comp = strtolower($jinput->get('option', '', 'STRING'));
        // Get the current view
        $view = strtolower($jinput->get('view', '', 'STRING'));

        // If one value is empty or unsupported component - view. return
        if ($comp === '' && ! self::isSupported($comp, $view)) {
            return;
        }

        /**
         * Import the Pro6ppVariables file and instantiate the class.
         * If something goes wrong here, the whole site will be bricked.
         * That's why the use of try{}catch().
         */
        try {
            require_once __DIR__ . '/pro6ppVariables.php';
            $this->_variables = new Pro6ppVariables();
        } catch (Exception $e) {
            unset($e);
            return;
        }
        // If the request was from the client side.
        if ($app->isSite()) {

            $baseVar = $this->_variables->getBaseVar();
            $translations = $this->_variables->getTranslationStrings();

            $jQuery = $this->_variables->getJquery();

            // Inject the variables inside the <header>.
            // Include jQuery where not present.
            if ($comp === 'com_virtuemart') {
                $initialisation = $this->_variables->getDeclaration('vm');
                $countries = $this->_variables->getFormattedCountries();
                $doc->addScriptDeclaration($countries, 'text/javascript');
                $scriptFile = $this->_variables->getSiteScript('vm');
            } elseif ($comp === 'com_users') {
                $initialisation = $this->_variables->getDeclaration('jform');
                $doc->addScript($jQuery, 'text/javascript');
                $scriptFile = $this->_variables->getSiteScript('jform');
            }
            else
                return;

            // Add the base URI.
            $doc->addScriptDeclaration($baseVar, 'text/javascript');
            // Include the translation strings.
            $doc->addScriptDeclaration($translations, 'text/javascript');
            // Include the JS file.
            $doc->addScript($scriptFile);
            // Inject the script initialisation.
            $doc->addScriptDeclaration($initialisation, 'text/javascript');
        } else { // Administrator panel is active.

            $title = $app->get('JComponentTitle', false); // Empty in Joomla3.
            // Ensure that it is pro6pp's title.
            if ($title && preg_match("/pro6pp/i", $title)) {
                $scriptFile = $this->_variables->getAdminScript();
                // Add the validation javascript file.
                $doc->addScript($scriptFile);
            }
        }
    }

    /**
     * This plug-in supports specific controller - view combinations
     *
     * @param string $component
     * @param string $view
     * @return bool True if valid combination, False otherwise
     */
    static function isSupported ($component, $view)
    {
        foreach (self::$_supported as $c => $v)
            if ($component === $c && $view === $v)
                return true;
        return false;
    }
}