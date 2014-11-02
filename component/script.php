<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Script file of Pro6pp Component
 * Executes when installing/ updating/ uninstalling
 * - Checks Compatibility with the Joomla installation
 * - Updates /inserts supported countries and provinces to the virtuemart DB
 * - Inserts a streetnumber field in the virtuemart DB
 * - Updates the ordering of the form fields through the virtuemart DB
 */
class com_pro6ppInstallerScript
{
    /**
     * Supported countries that need to exist in virtueMart country table
     * Follow the convention below for new countries.
     *
     * @var $_country
     */
    private $_country = array(
            'NL' => array(
                    'fullname' => 'Netherlands',
                    '3code' => 'NLD'
            )
    );

    /**
     * The provinces of a given country
     * Follow the convention below for new countries
     *
     * @var $_province
     */
    private $_province = array(
                            "NL" => array(
                                        array(
                                            'Drenthe',
                                            'DRE',
                                            'DR'
                                        ),
                                        array(
                                            'Flevoland',
                                            'FLE',
                                            'FL'
                                        ),
                                        array(
                                            'Friesland',
                                            'FRI',
                                            'FR'
                                        ),
                                        array(
                                            'Gelderland',
                                            'GEL',
                                            'GE'
                                        ),
                                        array(
                                            'Groningen',
                                            'GRO',
                                            'GR'
                                        ),
                                        array(
                                            'Limburg',
                                            'LIM',
                                            'LI'
                                        ),
                                        array(
                                            'Noord-Brabant',
                                            'NBR',
                                            'NB'
                                        ),
                                        array(
                                            'Noord-Holland',
                                            'NHO',
                                            'NH'
                                        ),
                                        array(
                                            'Overijssel',
                                            'OVE',
                                            'OV'
                                        ),
                                        array(
                                            'Utrecht',
                                            'UTR',
                                            'UT'
                                        ),
                                        array(
                                            'Zeeland',
                                            'ZEL',
                                            'ZE'
                                        ),
                                        array(
                                            'Zuid-Holland',
                                            'ZHO',
                                            'ZH'
                                        )
                            )
    );

    private $_app;
    private $_abort = false;
    /**
     * method to run before an install/update/uninstall method
     *
     * @return void
     */
    function preflight ($type, $parent)
    {
        $this->_app = &JFactory::getApplication();

        /**
         * 5 connection methods are implemented.
         * Feedback on which one to use!!
         * 4 of them have same prerequisities (stream & curl)
         * Count is initialised to 0.
         * If before exiting the function count is 7,
         * None of the prerequisites was met.
         * Set the abort variable that runs on the beginning of every other
         * method to true.
         */
        // the count
        $unsupported = 0;
        // a string to show to the user the supported methods
        $supported = '';

        // Requirements for Stream method.
        if (! function_exists('fopen') || ! is_callable('fopen')) {
            $this->_app->enqueueMessage(
                JText::_('COM_PRO6PP_SCRIPT_CONNECTION_CHECK_STREAM_FOPEN'),
                'warning'
            );

            $unsupported ++;
        }
        // One more requirement for Stream
        if (! ini_get('allow_url_fopen')) {
            $this->_app->enqueueMessage(
                JText::_(
                    'COM_PRO6PP_SCRIPT_CONNECTION_CHECK_STREAM_URL_FOPEN'
                ),
                'warning'
            );
        }
        $supported .= $unsupported === 0 ? ' | stream |' : '';

        // Requirements for cUrl method
        if (! function_exists('curl_init') || ! is_callable('curl_init')) {
            $unsupported += 2;
            $this->_app->enqueueMessage(
                JText::_('COM_PRO6PP_SCRIPT_CONNECTION_CHECK_CURL'),
                'warning'
            );
        }
        $supported .= $unsupported < 3 && $unsupported < 2 ? ' cUrl |' : '';

        // Requirement for Socket method
        if (! function_exists('fsockopen') || ! is_callable('fsockopen')) {
            $unsupported += 4;
            $this->_app->enqueueMessage(
                JText::_('COM_PRO6PP_SCRIPT_CONNECTION_CHECK_SOCKET'),
                'warning'
            );
        }
        $supported .= $unsupported < 7 && $unsupported < 4 ? ' socket' : '';

        // Check if the Component can be used if installed | abort if not.
        if ($unsupported === 7) {
            $this->_app->enqueueMessage(
                JText::_('COM_PRO6PP_SCRIPT_CONNECTION_CHECK_ERROR'),
                'error'
            );
            return false;
        } else {
            echo '<p>' . JText::_('COM_PRO6PP_SCRIPT_CONNECTION_CHECK_SUCCESS');
            echo $supported . '</p>';
        }

    }

    /**
     * method to install the component
     *
     * @return void
     */
    function install ($parent)
    {
        if (! $this->vm_exists()) {
            $this->_abort = true;
            return;
        }

        if ($this->_abort)
            return;
        $strtNrId = $this->streetNumberManipulation();

        if ($strtNrId === - 1)
            return;

        if (! $this->provinceManipulation())
            jexit(JText::_('COM_PRO6PP_SCRIPT_FAILURE'));

            // -- START OF FIELD ORDERING MANIPULATION --

        // Get the ordering number of that field and
            // use it as the starting point to add from
        $upperBound = $this->getOrderByName('last_name');

        $this->updateOrder($upperBound + 2, 'virtuemart_country_id');
        $this->updateOrder($upperBound + 4, 'zip');

        // ---- START OF STREETNUMBER MANIPULATION 2 ----
        // If no strNr id was found, add that field.
        if ($strtNrId == 0) {
            $this->insertStreetnumber($upperBound + 6);
            echo '<p>' . JText::_('COM_PRO6PP_SCRIPT_ADDED_STREET_FIELD') .
                     '</p>';
        } else {
            // A streetnumber field already exists, update it.
            $this->updateOrder(
                $upperBound + 6,
                $this->getFieldNameById($strtNrId)
            );
        }
        // ---- END OF STREETNUMBER MANIPULATION 2 ----

        $this->updateOrder($upperBound + 8, 'address_1');
        $this->updateOrder($upperBound + 10, 'address_2');
        $this->updateOrder($upperBound + 12, 'city');
        $this->updateOrder($upperBound + 14, 'province');
        $this->updateOrder($upperBound + 16, 'virtuemart_state_id');

        // -- END OF FIELD ORDERING MAIPULATION --
        echo '<p>' .
                 JText::_('COM_PRO6PP_SCRIPT_ALTERED_FORM_FIELDS') . '</p>';

        echo '<p>' .
                 JText::_('COM_PRO6PP_SCRIPT_SUCCESS') . '</p>';
    }

    /**
     * method to update the component
     *
     * @return void
     */
    function update ($parent)
    {
        if ($this->_abort)
            return;
        $this->install($parent);
    }

    /**
     * method to uninstall the component
     *
     * @return void
     */
    function uninstall ($parent)
    {
        // The script needs not to take actions here
    }

    /**
     * Method to run after an install/update/uninstall method.
     *
     * @return void
     */
    function postflight ($type, $parent)
    {
        $currentV = $parent->get('manifest')->version;

        // Show the new updated/installed version.
        if ($type === 'install')
            echo 'Installed Version: ' . $currentV;
        elseif ($type === 'update')
            echo 'New Version: ' . $currentV;
        // $parent is the class calling this method.
        // $type is the type of change (install, update or discover_install).
    }

    /**
     * Iterates through every supported country and adds it in the DB if not
     * existed.
     * Deletes the provinces if any and populates them with the
     * supported ones.
     * Returns true on success
     *
     * @return boolean true on success | false otherwise
     */
    function provinceManipulation ()
    {
        // Sanity check.
        if ($this->_abort)
            return false;

        foreach ($this->_country as $countryCode => $values) {
            // Get the country's id
            $id = $this->getCountryId($countryCode);
            if (! $id)
                break;

                // if no country, create it and then get the id
            if ($id == null) {
                $this->setCountry(
                    $values['fullname'],
                    $values['3code'],
                    $countryCode
                );
                $id = $this->getCountryId($countryCode);
            }

            // Remove all the provinces if already exist
            if (! $this->deleteProvinces($id)) {
                return false;
            }

            // Add all the supported provinces for this id
            if (! $this->insertProvinces($id, $countryCode)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if the order of the fields is the default one.
     * Inserts a streetnumber field if it doesn't exist.
     * Returns -1 when the order is not the default and the field exists
     * or is inserted.
     * Otherwise returns the id of the field
     *
     * @return number true if not the default order
     *         the id of the field otherwise
     */
    function streetNumberManipulation ()
    {
        $strtNrId = $this->strNrXists();
        // The order is not the default one.
        if (! $this->checkDefaultOrder()) {
            // Add the streetnumber if it doesn't exist.
            if ($strtNrId == 0) {
                $this->insertStreetnumber($this->getOrderByName('zip') + 1);
                echo '<p>' .
                         JText::_('COM_PRO6PP_SCRIPT_ADDED_STREET_FIELD') .
                         '</p>';
            }
            // Notify the user for the actions he has to take.
            echo '<p>' .
                     JText::_('COM_PRO6PP_SCRIPT_NOTIFY_USER_ACTIONS') . '</p>';
            echo '<p>' .
                     JText::_('COM_PRO6PP_SCRIPT_USER_ACTIONS_SUGGESTION') .
                     '</p>';
            // Installation finishes here because the ordering of the form
            // fields in the DB is not the default.
            echo '<p>' .
                     JText::_('COM_PRO6PP_SCRIPT_SUCCESS') . '</p>';
            return - 1;
        }
        return $strtNrId;
    }

    /**
     * Checks whether VirtueMart is installed or not.
     *
     * @return boolean Ambigous NULL> False if query fails, Null if not
     *         installed, true otherwise.
     */
    function vm_exists ()
    {
        $db = JFactory::getDBO();
        $sql = $db->getQuery(true);

        $sql->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where(array($db->quoteName('name'). '=' . $db->quote('virtuemart')));

        $db->setQuery($sql);
        if (! $db->query()) {
            return false;
        } else {
            return $db->loadResult();
        }
    }

    /**
     * Function to query the DB and find out if the streetnumber
     * field already exists in some variation.
     *
     * @return true if exists : false otherwise
     */
    function strNrXists ()
    {
        $db = JFactory::getDBO();
        $sql = $db->getQuery(true);
        $sql->select('COUNT(*)')
            ->from($db->quoteName('#__virtuemart_userfields'))
            ->where('LOWER(`name`) IN (' . $db->quote('str') . ',' .
                 $db->quote('streetnumber') . ',' . $db->quote('street_number') .
                 ',' . $db->quote('street-number') . ',' .
                 $db->quote('straatnummer') . ',' . $db->quote('huisnummer') .
                 ',' . $db->quote('straat_nummer') . ',' .
                 $db->quote('straat-nummer') . ',' . $db->quote('street') . ',' .
                 $db->quote('straat') . ',' . $db->quote('number') . ',' .
                 $db->quote('nummer') . ',' . $db->quote('nr') . ')'
            );

        $db->setQuery($sql);
        if (! $db->query()) {
            return - 1;
        } else {
            return $db->loadResult();
        }
    }

    /**
     * method to insert a country into the DB
     *
     * @param string $name
     *            countrys name to add
     * @param string $codeThreeLtrs
     *            3 characters coed of the country
     * @param string $codeTwoLtrs
     *            2 characters code of the country
     */
    function setCountry ($name, $codeThreeLtrs, $codeTwoLtrs)
    {
        $db = &JFactory::getDBO();
        $sql = $db->getQuery(true);
        $fields = array(
                        $db->quoteName('country_name'),
                        $db->quoteName('country_3_code'),
                        $db->quoteName('country_2_code')
        );
        $values = array(
                        $db->quote($name),
                        $db->quote($codeThreeLtrs),
                        $db->quote($codeTwoLtrs)
        );
        $sql->insert($db->quoteName('#__virtuemart_countries'))
            ->set($fields)
            ->values($values);

        $db->setQuery($sql);
        if (! $db->query()) {
                    $this->_app->enqueueMessage(
                        'Error while storing a new country: ' . $db->stderr(),
                        'error'
                    );
            $countryId = -1;
        }
        else
            $countryId = $db->loadObject();

        return $countryId;
    }

    /**
     * method to get the country id from the database
     *
     * @param string $name
     *            2 letters code of the country (ie. 'NL', 'GB')
     * @return int the result of this query
     */
    function getCountryId ($codeToName)
    {
        $db = &JFactory::getDBO();
        $sql = $db->getQuery(true);
        $sql->select($db->quoteName('virtuemart_country_id'))
            ->from($db->quoteName('#__virtuemart_countries'))
            ->where(
                array(
                    $db->quoteName('country_2_code'). '=' .
                    $db->quote($codeToName)
                )
            );

        $db->setQuery($sql);

        if (! $db->query()) {
            $this->_app->enqueueMessage(
                'Error fetching country ID: ' . $db->stderr(),
                'error'
            );
            return - 1;
        }
        $countryId = $db->loadResult();
        return $countryId;
    }

    /**
     * Runs either on update or the install
     * and deletes the provinces of Holland
     *
     * @param integer $cid
     *            id to delete from
     * @return boolean True if sucessfull false on error
     */
    function deleteProvinces ($cid)
    {
        $db = &JFactory::getDBO();
        $sql = $db->getQuery(true);
        $condition = array(
               $db->quoteName('virtuemart_country_id') . '=' . $cid
        );
        $sql->delete()->from($db->quoteName('#__virtuemart_states'))
            ->where($condition);

        $db->setQuery($sql);

        if (! $db->query()) {
            $this->_app->enqueueMessage(
                'Failed to delete provinces: ' . $db->stderr(),
                'error'
            );
            return false;
        }
        return true;
    }

    /**
     * populates the provinces for a country according to her DB id
     *
     * @param integer $cid
     *            id of the country in the virtuemart_countries table
     * @param string $countryCode
     *            The key for the province array, the 2 letter country code
     * @return boolean True if success false otherwise
     */
    function insertProvinces ($cid, $countryCode)
    {
        $db = &JFactory::getDBO();

        $fields = array(
                        $db->quoteName('virtuemart_country_id'),
                        $db->quoteName('state_name'),
                        $db->quoteName('state_3_code'),
                        $db->quoteName('state_2_code')
        );
        $values = [];
        foreach ($this->_province[$countryCode] as $state) {
            $values[] = array(
                            $cid,
                            $db->quote($state[0]),
                            $db->quote($state[1]),
                            $db->quote($state[2])
            );
        }

        for ($i = 0; $i < count($values); $i ++) {
            $sql = $db->getQuery(true);
            $sql->insert($db->quoteName('#__virtuemart_states'))
                ->columns($fields)
                ->values($values[$i]);
        }

        $db->setQuery($sql);

        if (! $db->query()) {
            $this->_app->enqueueMessage(
                'Failed to insert provinces: ' . $db->stderr(),
                'error'
            );
            return false;
        }

        return true;
    }

    /**
     * method to test if the field order in VM is the default
     * Works indeed like a test :P
     *
     * @return boolean : true if virtuemart values are default, false otherwise
     */
    function checkDefaultOrder ()
    {
        // HARDWIRED the default order (at least in version 2.0.22c/d and 2.0.2.24) is known
        // Check if known order of: zip, address1, country
        // match the order of the DB
        $order = array(
                    array(
                        'zip',
                        34
                    ),
                    array(
                        'address_1',
                        30
                    ),
                    array(
                        'virtuemart_country_id',
                        38
                    )
        );

        $db = &JFactory::getDBO();

        foreach ($order as $field) {
            $sql = $db->getQuery(true);

            $sql->select($db->quoteName('ordering'))
                ->from($db->quoteName('#__virtuemart_userfields'))
                ->where(array('LOWER('.$db->quoteName('name').')=' . $db->quote($field[0])));

            $db->setQuery($sql);

            if ($db->loadResult() != $field[1])
                    return false;
          $db->freeResult();
        }
        return true;
    }

    /**
     * Get the order number of a given field name
     *
     * @param integer $fname
     *            The field name to get the ordering from
     * @return number The order number of the field
     */
    function getOrderByName ($fname)
    {
        $db = &JFactory::getDBO();
        $sql = $db->getQuery(true);

        $sql->select($db->quoteName('ordering'))
            ->from($db->quoteName('#__virtuemart_userfields'))
            ->where(array($db->quoteName('name') . '=' . $db->quote($fname)));

        $db->setQuery($sql);

        if (! $db->query()) {
            $this->_app->enqueueMessage(
                'Failed to get order by name: ' . $db->stderr(),
                'error'
            );
            return 0;
        }
        return $db->loadResult();
    }

    /**
     * Get the field name for a given id
     *
     * @param integer $id
     *            The field id to get the name for
     * @return string The order name of the field
     */
    function getFieldNameById ($id)
    {
        $db = &JFactory::getDBO();
        $sql = $db->getQuery(true);

        $sql->select($db->quoteName('name'))
            ->from($db->quoteName('#__virtuemart_userfields'))
            ->where(array($db->quoteName('virtuemart_userfield_id') . '=' . $id));

        $db->setQuery($sql);

        if (! $db->query()) {
            $this->_app->enqueueMessage(
                'Error fetching field by name: ' . $db->stderr(),
                'error'
            );
            return 0;
        }
        return $db->loadResult();
    }

    /**
     * Update the order of the userfields by shifting the order
     * and the field name
     *
     * @param integer $order
     *            The new order number
     * @param unknown $fname
     *            The fields name to update
     */
    function updateOrder ($order, $fname)
    {
        $db = &JFactory::getDBO();
        $sql = $db->getQuery(true);
        $fields = array(
                        $db->quoteName('ordering') . '=' . $order . ''
        );
        $conditions = array(
                            $db->quoteName('name') . '=' . $db->quote($fname) .
                                     ''
        );
        $sql->update($db->quoteName('#__virtuemart_userfields'))
            ->set($fields)
            ->where($conditions);

        $db->setQuery($sql);

        if (! $db->query()) {
            $this->_app->enqueueMessage(
                'Failed to update field ordering: ' . $db->stderr(),
                'error'
            );
            return false;
        }
        return true;
    }

    /**
     * Inserts the streetnumber field into the VM table.
     *
     * @param number $order
     *            The ordering number to use, by default 0 is used
     * @return boolean True if succesfull, false otherwise
     */
    function insertStreetnumber ($order = 0)
    {
        $db = &JFactory::getDBO();

        $columns = array(
                        'name',
                        'title',
                        'type',
                        'size',
                        'required',
                        'registration',
                        'shipment',
                        'account',
                        'maxlength',
                        'ordering'
        );
        $values = array(
                        $db->quote('streetnumber'),
                        $db->quote('Huisnummer'),
                        $db->quote('text'),
                        10,
                        1,
                        1,
                        1,
                        1,
                        10,
                        $order
        );
        $sql = $db->getQuery(true);
        $sql->insert($db->quoteName('#__virtuemart_userfields'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        $db->setQuery($sql);

        if (! $db->query()) {
            $this->_app->enqueueMessage(
                'Failed to insert a streetnumber field: ' . $db->stderr(),
                'error'
            );
            return false;
        }
        return true;
    }
}

