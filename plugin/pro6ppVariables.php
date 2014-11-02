<?php
// No direct access.
defined('_JEXEC') or die('Restricted access');

/**
 * Controls the functions that inject a set of global variables
 * into the header tag of the page (as <script>)
 */
class Pro6ppVariables
{

    private $_provinces = array();

    private $_countries = array();

    /**
     * The Pro6PP components' name
     *
     * @var string
     */
    const PRO6PP_COMP_NAME = 'com_pro6pp';

    /**
     * The path to the component folder
     *
     * @var string
     */
    const PRO6PP_COMP_BASE = '/components/';

    /**
     * The path to the Admins input validation script
     *
     * @var string
     */
    const PRO6PP_JS_ADMIN_BASE = 'media/plg_pro6pp/js/validate_pro6pp.js';

    /**
     * The path to the Users Pro6PP autocomplete script
     * for virtuemart forms
     *
     * @var string
     */
    const PRO6PP_JS_SITE_VM = 'media/plg_pro6pp/js/autocomplete.js';

    /**
     * The path to the Users Pro6PP autocomplete script
     * for virtuemart forms
     *
     * @var string
     */
    const PRO6PP_JS_SITE_JFORM = 'media/plg_pro6pp/js/JformAutocomplete.js';

    /**
     * The path to an online jQuery library
     * Relatively old, since virtuemart is using this version
     *
     * @var string
     */
    const PRO6PP_JQUERY = "//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js";

    /**
     * The name of the javaScripts' country variable
     */
    const VAR_COUNTRY_NAME = 'PRO6PP_COUNTRY';

    /**
     * The base url of the site.
     */
    const BASE = 'PRO6PP_BASE';

    /**
     * The names' of the error message variables
     */
    const ER_UNAVAILABLE = 'PRO6PP_ERROR';

    const ER_POSTAL_FORMAT = 'PRO6PP_POSTAL_FORMAT';

    const ER_STREET = 'PRO6PP_STREET_FORMAT';

    function __construct ()
    {
        $this->loadLanguage(JFactory::getLanguage());
    }

    /**
     * Loads a language file into the current language file.
     *
     * @param JFactory::getLanguage $lang
     *            The Joomla language object
     */
    public function loadLanguage ($lang)
    {
        try {
            // Load the language files of the pro6pp component
            $extension = self::PRO6PP_COMP_NAME;
            $baseDir = JPATH_SITE . self::PRO6PP_COMP_BASE .
                     self::PRO6PP_COMP_NAME;
            $languageTag = $lang->getTag();
            $reload = true;
            $lang->load($extension, $baseDir, $languageTag, $reload);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Stores all the supported countries into the _countries array.
     * Fetches them from the virtueMart database
     *
     * @param JFactory::GetDbo $db
     *            The database connection object
     */
    public function populateCountries ($db)
    {
        $country = array(
                        'NL'
        );
        // For every supported country fetch the id into $_countries
        $index = 0;
        try {
            foreach ($country as $c) {

                $sql = $db->getQuery(true);
                $sql->select($db->quoteName('virtuemart_country_id'))
                    ->from($db->quoteName('#__virtuemart_countries'))
                    ->where(
                        array(
                           $db->quoteName('country_2_code'). '=' .
                           $db->quote($c)
                        )
                    );

                $db->setQuery($sql);

                if (! $db->query()) {
                    // Suppress the error. Most likely VirtueMart is not
                    // installed.
                    return false;
                } else {
                    $this->_countries[$c] = $db->loadResult();
                }
                $db->freeResult();
            }
        } catch (Exception $e) {
            // DB errors are not the concern here.
            unset($e);
            // Suppress the error.
            return false;
        }
        return true;
    }

    /**
     * Populates the _provinces array with a country key and a province array.
     *
     * @param JFactory::getDbo $db
     *            The database connection object
     */
    public function populateStates ($db)
    {
        if (! isset($this->_countries))
            return false;
        try {
            // Load the provinces for this country.
            // For every country fetch the province names and ids
            foreach ($this->_countries as $code => $id) {
                $sql = $db->getQuery(true);
                $sql->select(
                    array(
                        $db->quoteName('virtuemart_state_id'),
                        $db->quoteName('state_name')
                    )
                )
                    ->from($db->quoteName('#__virtuemart_states'))
                    ->where(
                        array(
                            $db->quoteName('virtuemart_country_id') . '=' .
                            $id
                        )
                    );

                $db->setQuery($sql);

                if (! $db->query()) {
                    return false;
                } else {
                    // Store the values in a jagged array.
                    $this->_provinces[$id] = $db->loadAssocList(
                        'state_name',
                        'virtuemart_state_id'
                    );
                }
                $db->freeResult();
            }
        } catch (Exception $e) {
            // Suppress the DB errors, will keep the site running normally.
            unset($e);
            return false;
        }
        return true;
    }

    /**
     * Returns the base link/path of the website.
     * Used by the ajax call.
     *
     * @return string A javaScript variable that hold the base URI
     */
    public function getBaseVar ()
    {
        $base = "var %s = '%sindex.php'";
        return sprintf($base, 'PRO6PP_BASE', Juri::base());
    }

    /**
     * Outputs the path for the autocomplete javascript file
     * for the client side
     *
     * @return string The full path to the file
     */
    public function getSiteScript ($scope)
    {
        switch ($scope) {
            case 'jform':
                return JURI::base() . self::PRO6PP_JS_SITE_JFORM;
            case 'vm':
            default:
                return JURI::base() . self::PRO6PP_JS_SITE_VM;
        }
    }

    /**
     * Returns the path to the validate file for the administrator side.
     *
     * @return string The full path to the file
     */
    public function getAdminScript ()
    {
        return JURI::root() . self::PRO6PP_JS_ADMIN_BASE;
    }

    public function getJquery ()
    {
        return self::PRO6PP_JQUERY;
    }

    /**
     * Returns the translated as javaScript variables
     * for errors that might occur if the service is down.
     *
     * @return string A string of javaScript syntax string variables.
     */
    public function getTranslationStrings ()
    {
        $strings = '';

        try {
            // Define the template
            $tmpl = "var %s = '%s';\n";
            // Create the JS translation scripts
            $strings = sprintf(
                $tmpl,
                self::ER_UNAVAILABLE,
                JText::_('COM_PRO6PP_MESSAGE_UNAVAILABLE')
            );

            $strings .= sprintf(
                $tmpl,
                self::ER_POSTAL_FORMAT,
                JText::_('COM_PRO6PP_MESSAGE_INVALID_FORMAT')
            );

            $strings .= sprintf(
                $tmpl,
                self::ER_STREET,
                JText::_('COM_PRO6PP_MESSAGE_INVALID_STREET_FORMAT')
            );
        } catch (Exception $e) {
            unset($e);
        }
        return $strings;
    }

    /**
     * Returns the declaration of the autocomplete function
     *
     * @param
     *            string scope The variables scope
     * @return string A javaScript syntax string (script)
     */
    public function getDeclaration ($scope)
    {
        return $this->getDocString($scope);
    }

    /**
     * Returns the Pro6PP supported countries as a javaScript object
     *
     * @return string A javaScript syntax string of object declarations.
     */
    public function getFormattedCountries ()
    {
        if ($this->populateCountries(JFactory::getDbo()) &&
                 $this->populateStates(JFactory::getDbo())) {
            $formatted = $this->formatCountries(
                $this->_countries,
                $this->_provinces
            );
            return $formatted;
        } else
            return $this->formatCountries(array(), array());
    }

    public function getDocString ($component)
    {
        $head = '';
        switch ($component) {
            case 'jform':
                $head = <<<JFORM_EOF
 $ = jQuery.noConflict();
$(document).ready(function($) {
   $('main').applyAutocomplete({
        postcode:       '#' + 'jform_profile_postal_code',
        street:         '#' + 'jform_profile_address1',
        streetnumber:   '#' + 'jform_profile_address2',
        city:           '#' + 'jform_profile_city',
        country:        '#' + 'jform_profile_country',
        province:       '#' + 'jform_profile_region',
    });
});
JFORM_EOF;
                break;
            case 'vm':
            default:
                $head = <<<VM_EOF
 $ = jQuery.noConflict();
$(document).ready(function($) {

  var prefix = document.getElementById('shipto_zip_field') !== null ?
               'shipto_' : '' ;
    $('main').applyAutocomplete({
        postcode:       '#' + prefix + 'zip_field',
        streetnumber:   '#' + prefix + 'streetnumber_field',
        street:         '#' + prefix + 'address_1_field',
        city:           '#' + prefix + 'city_field',
        country:        '#' + prefix + 'virtuemart_country_id',
        province:       '#' + prefix + 'virtuemart_state_id',
        provinceLbl:    '#' + prefix + 'virtuemart_state_id_chzn',
    });
});
VM_EOF;
                break;
        }
        return $head;
    }

    /**
     * Accepts 2 arrays that contain all the countries and all the states
     * respectivelly.
     * The states array is a jagged array defined by a country
     * key.
     * Returns a JavaScript array as string.
     * Contains the countries as objects with states as attribute
     *
     * @param array $countries
     *            All the supported countries
     * @param array $states
     *            All the supported/existing states
     */
    public function formatCountries (Array $countries, Array $provinces)
    {
        $formatBase = "var PRO6PP_COUNTRY = new Object();\n %s";
        $formatCountry = "PRO6PP_COUNTRY[%d] = " .
                 "{ name:'%s', id:%d, provinces: [%s] };\n";
        $formatProvince = "{name: '%s', id: %d},";
        $result = "";

        foreach ($countries as $countryName => $countryId) {
            $province = "";
            $provs = $provinces[$countryId];

            foreach ($provs as $provName => $provId) {
                $province .= sprintf($formatProvince, addslashes($provName), $provId);
            }

            $country = sprintf(
                $formatCountry,
                $countryId,
                $countryName,
                $countryId,
                $province
            );
            $result .= $country;
        }
        return sprintf($formatBase, $result);
    }
}