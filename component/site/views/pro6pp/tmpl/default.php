<?php
// No direct access to this file should be called by Joomla
defined('_JEXEC') or die('Restricted Access');

// This is the template file of the component, presents the data to the client.
// It returns the raw reply of the service
print($this->callback . '(' . $this->response . ')');