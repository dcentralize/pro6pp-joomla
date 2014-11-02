<?php
// models are responsible for managing the data

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// import log library
jimport('joomla.log.log');

/**
 * The model holds the user input and the administrator preferences.
 * Is responsible for connecting to the Pro6PP web-service and retrieving the
 * response.
 * It has only two public functions getResponse() and getCallback().
 * "getResponse()" is the trigger that initialises almost all the fields
 */
class Pro6ppModelPro6pp extends JModelItem
{

    /**
     * A json string holding the user configuration
     *
     * @var string
     */
    private $_pluginParams;
   /*  /**
     * The JFactory Object as reference to post variables
     * Used in J2.5 and later
     * @var JFactory Object
     * /
    private $_postParams;
    */
    /**
     * the base url of the Pro6PP service
     *
     * @var string
     */
    private $_apiBaseUrl;

    /**
     * the authentication key for the Pro6PP service
     *
     * @var string
     */
    private $_authKey;

    /**
     * The interval to w8 before give up connecting to the service
     *
     * @var number
     */
    private $_timeout;

    /**
     * the users prefered method of connection
     * the string value is inheritted from the Pro6PP plug-in's xml
     *
     * @var string
     */
    private $_method;

    /**
     * The user defined postcode
     *
     * @var string
     */
    protected $_postcode;

    /**
     * The user defined streetnumber
     *
     * @var number
     */
    protected $_streetnumber;

    /**
     * The ajax callback, used to return the call(back)
     *
     * @var string
     */
    protected $_callback;

    /**
     * A json string holding the response from the service
     *
     * @var string
     */
    protected $_response;

    /**
     * Stores the error message if exceptions occure
     *
     * @todo if custom connection functions are used, the message should
     *       be saved in a log file
     * @var string
     */
    protected $_errorMsg;

    /**
     * Contains the error messages the service can respond
     * and their corresponding localization variable.
     * @var string
     */
    protected $_serviceMsg;

    /**
     * initialises the pluginParams and callback variables
     *
     * @param array $config
     */
    public function __construct ($config = array('name'=>'Json'))
    {
        parent::__construct($config);

        $plugin = &JPluginHelper::getPlugin('system', 'pro6pp');
        $this->_pluginParams = new JRegistry();
        $this->_pluginParams->loadString($plugin->params);
//J2.5  $this->_postParams = JFactory::getApplication()->input->post;
        $this->_callback = $this->getCallback();

        $this->_serviceMsg = array(
                "Unable to contact Pro6PP validation service"
             => 'COM_PRO6PP_MESSAGE_UNABLE_TO_CONNECT',
                "Invalid nl_sixpp format"
             => 'COM_PRO6PP_MESSAGE_INVALID_INPUT',
                "nl_sixpp not found"
             => 'COM_PRO6PP_MESSAGE_SIXPP_NOT_FOUND',
                "Streetnumber not found"
             => 'COM_PRO6PP_MESSAGE_STREETNUMBER_NOT_FOUND',
                "streetnumber is missing a number"
             => 'COM_PRO6PP_MESSAGE_STREETNUMBER_MISSING_NUMBER',
                "invalid postcode format"
             => 'COM_PRO6PP_MESSAGE_INVALID_FORMAT',
                "Pro6PP validation service is unavailable at this time"
             => 'COM_PRO6PP_MESSAGE_UNAVAILABLE',
                "Unspecified"
             => 'COM_PRO6PP_CUSTOM_MESSAGE_UNSPECIFIED'
        );

    }

    /**
     * gets the ajax callback, initialises the callback variable
     * and returns it
     *
     * @return string the callback, empty string if none found
     */
    public function getCallback ()
    { //J2.5 $this->_postParams->get('callback', '', 'STRING'); J2.5 Compatible
        if (! isset($this->_callback))//J1.5 Compatible
            $this->_callback = JRequest::getString('callback', 'empty');
        return $this->_callback;
    }

    /**
     * gets the user input, initialises the postcode
     * variable and returns it to the user
     *
     * @return string the postcode, empty string if no user input found
     */
    private function getPostcode ()
    { //J2.5 $this->_postParams->get('postcode', '', 'STRING');
        if (! isset($this->_postcode))
            $this->_postcode = JRequest::getString('postcode', '1234AB');
        return $this->_postcode;
    }

    /**
     * gets the user input, initialises the streetnumber
     * variable and returns it to the user
     *
     * @return number the streetnumber, empty string if no user input found
     */
    private function getStreetnumber ()
    { //J2.5 $this->_postParams->get('streetnumber', 0, 'INT');
        if (! isset($this->_streetnumber))
            $this->_streetnumber = JRequest::getInt('streetnumber', 'NONE');
        return $this->_streetnumber;
    }

    /**
     * gets the Pro6PP plugin's timeout parameter, stores it in the timeout
     * variable and return it.
     * If timeout is less that a second it changes to
     * 5 seconds
     *
     * @return number the timeout intervall
     */
    private function getTimeout ()
    {
        if (! isset($this->_timeout)) {
            $this->_timeout = $this->_pluginParams->
                                                get('pro6pp_timeout', 5000);
            $this->_timeout = ($this->_timeout <= 1000) ?
                                                5000 : $this->_timeout;
        }
        return $this->_timeout;
    }

    /**
     * gets the Pro6PP plugin's authentication parameter,
     * stores it in the $authKey variable
     * and returns it.
     * If none found it defaultst to 'empty'
     *
     * @return string the Pro6PP authentication key
     */
    private function getAuthKey ()
    {
        if (! isset($this->_authKey)) {
            $this->_authKey = $this->_pluginParams->get('pro6pp_authentication',
                    'empty');
        }
        return $this->_authKey;
    }

    /**
     * gets the Pro6PP plugin's method parameter,
     * stores it in the $method variable
     * and returns it.
     * If none found it defaultst to the custom stream method
     *
     * @return number the method's index
     */
    private function getMethod ()
    {
        if (! isset($this->_method))
            $this->_method = $this->_pluginParams->get('pro6pp_connection',
                    'stream');
        return $this->_method;
    }

    /**
     * Initialises the apiBaseurl variable and returns the Pro6PP
     * services url
     *
     * @return string
     */
    private function getApiBase ()
    {
        if (! isset($this->_apiBaseUrl)) {
            $this->_apiBaseUrl = 'http://api.pro6pp.nl/v1/autocomplete';
        }
        return $this->_apiBaseUrl;
    }

    /**
     * concatenates all the parameters required by the pro6pp
     * to a url format string
     *
     * @return string the required by Pro6PP service parameters
     */
    private function getUrlData ()
    {
        $data = 'auth_key=' . $this->getAuthKey() . '&nl_sixpp=' .
                 urldecode($this->getPostcode());

        $streetNr = $this->getStreetnumber();
        if ($streetNr != 'NONE') {
            $data .= '&streetnumber=' . $streetNr;
        }
        return $data;
    }

    /**
     * Returns the apis callable url by concatenating the base url
     * and the parameters url
     *
     * @return string the url to connect to
     */
    private function getApiUrl ()
    {
        $url = $this->getApiBase() . '?' . $this->getUrlData();

        return $url;
    }

    /**
     * Returns the json response of the service to the calling script.
     * If connection fails a pre-defined json response is returned
     *
     * @return string a json response originated form the Pro6PP service
     */
    public function getResponse ()
    {
        $this->connect($this->getMethod());
        if ($this->_response == null) {
            $this->_response = '{"status":"error",
                                "results":[],
                                "error":{"message":
                   "Pro6PP validation service is unavailable at this time"}}';
        }
        return $this->_response;
    }

    /**
     * Connects to the service with the method the user defined
     * and stores the response in the $response variable
     *
     * @param string $method
     *            : The user defined connection method, socket is default
     * @throws Exception JException if the custom stream method fails
     */
    private function connect ($method = 'jsocket')
    {
        if (! $this->validData()) {
            $this->_response = '{"status":"error",
                                "results":[],
                                "error":{"message":"Invalid Input"}}';
            return;
        }

        switch ($method) {
            case 'stream': // d-centralize stream method
                    // Requires: 'allow_url_fopen=on' in the php_ini_system

                $params = array(
                        'http' => array(
                                'method' => 'POST',
                                'content' => $this->getUrlData(),
                                'timeout' => $this->getTimeout()
                        )
                );

                try {
                    $ctx = stream_context_create($params);

                    $fp = @fopen($this->getApiBase(), 'rb', false, $ctx);
                } catch (Exception $e) {
                    $this->_errorMsg = $e->getMessage();
                    $this->_response = null;
                    return;
                }

                if (! $fp) {
                    throw new JException("Problem with $url, $phpErrorMsg");
                }

                $this->_response = @stream_get_contents($fp);

                break;
            case 'curl': // d-centralize curl method
                    // Requires: apache's curl extension
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_URL, $this->getApiUrl());
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->getTimeout());

                $this->_response = curl_exec($ch);
                curl_close($ch);

                break;
            case 'jstream': // Joomla! stream class
                    // Requires: same requirements as case 0
                $protocol = new JHttpTransportStream(new JRegistry());
                $this->_response = $this->JConnect($protocol);

                break;
            case 'jsocket': // Joomla! socket class
                    // Requires: No requirements as far as my knowlledge goes
                $protocol = new JHttpTransportSocket(new JRegistry());
                $this->_response = $this->JConnect($protocol);
                break;
            case 'jcurl': // Joomla! curl class
                    // Requires: Same requirements as case 1
                $protocol = new JHttpTransportCurl(new JRegistry());
                $this->_response = $this->JConnect($protocol);
                break;
        }
    }

    /**
     *
     * @method Jconnect
     *         Generic function to connect to the web-service and get the
     *         response
     * @uses the joomla JHttpTransport Interface
     * @param JHttpTransport $protocol:
     *            the initialised Joomla class
     *            to conncet with
     * @return Ambigous <NULL, string>: NULL if error code recieved |
     *         the server response if no errors
     */
    private function JConnect (JHttpTransport & $protocol)
    {
        $uri = new JURI($this->getApiBase());
        $data = $this->getUrlData();
        $headers = null;
        $server = $protocol->request('POST', $uri, $data, $headers,
                $this->getTimeout());
        $result = $server->code >= 200 && $server->code < 300 ?
                    $server->body : null;
        return $result;
    }

    /**
     * Primitive failsafe.
     * Checks whether the user input was of right length
     *
     * @todo extend the function to use regular expressions
     * @return boolean true if input is valid
     */
    private function validData ()
    {
        if (strlen($this->getStreetnumber()) <= 0 ||
                 strlen($this->getPostcode()) < 6)
            return false;
        else
            return true;
    }

    /**
     * It actually initialises a comment :P
     *
     * @todo implement a log file for errors occuring from
     *       custom connect methods
     * @return string the error message
     */
    public function getErrorMsg ()
    {
        if (! isset($this->msg)) {
          $this->msg = 'Call defines the Controller. Default task is view!'
                     . 'View gets the data from Model(where the message is).'
                     . 'Calls the template(/tmpl/default.php)'
                     . 'Data are presented';
        }
        return $this->_errorMsg;
    }

    /**
     * Accepts a known string and returns the equivelant translation
     *
     * @param string $msg
     *            The service's response message
     */
    public function getLocalizedMsg ($msg = '')
    {
        return $msg;
        if (array_key_exists($msg, $this->_serviceMsg))
            return JText::_($this->_serviceMsg[$msg]);
        else{
            // Log the actual message the user shouldn't care about
            JLog::add('End-user Message: '
                    . JText::_($this->_serviceMsg['Unspecified'])
                    . ' | Actual service message: ' . $msg, JLog::WARNING);
            // Return a generic message to the user
            return JText::_($this->_serviceMsg['Unspecified']);
        }
    }

    /**
     * Used on error responses.
     * Retrieves the administrator defined options
     * @method getOptions
     *
     * @return associative multidimensional array
     *          The plug-in configurations
     *
     */
    public function getOptions()
    {

        $options["pro6pp_autocomplete"] = $this->_pluginParams->
                                   get('pro6pp_autocomplete', '1');

        $options["pro6pp_enforce_validation"] = $this->_pluginParams->
                                   get('pro6pp_enforce_validation', '0');

        $options["pro6pp_gracefully_degrade"] = $this->_pluginParams->
                                   get('pro6pp_gracefully_degrade', '1');

        $options["pro6pp_provide_feedback"] = $this->_pluginParams->
                                   get('pro6pp_provide_feedback', '0');

        return $options;
    }



    /**
     * Determines whether an error response was caused by the user
     * or by the service.     *
     * Accepts the non localized error response from the service
     * Returns either 1 or 2 used as scale of severity.
     * The scale is used in the javaScript to determine required actions
     * scale meter:
     * 1 : enable user input, reset fields, release handlers
     * 2 : enable user input, reset fields
     * 3 : don't allow user input, reset fields
     * 4 : don't allow user input, reset fields, release fields
     * @param string $msg The message response from the service
     * @return number the severity scale of the response msg
     */
    public function getErrorSeverity ($msg)
    {
        $scale = "";
        $options = $this->getOptions();

        switch ($msg) {
            case "Unable to contact Pro6PP validation service":
            case "Unspecified":
            case "Pro6PP validation service is unavailable at this time":
                $scale = $options["pro6pp_gracefully_degrade"] === "1"? "die" : "release";
                $scale = $scale === "die" &&
                         $options["pro6pp_enforce_validation"] === "1"
                        ? $scale : "release";
                break;
            case "Invalid nl_sixpp format":
            case "nl_sixpp not found":
            case "Streetnumber not found":
            case "streetnumber is missing a number":
            case "invalid postcode format":
                $scale = ($options["pro6pp_enforce_validation"] === "1")
                       ? "reset" : "reset+release";
                break;
            default:
                if ($options["pro6pp_enforce_validation"])
                    $scale = "reset";
                else $scale = "reset+release";
                break;
        }

        return $scale;
    }
}