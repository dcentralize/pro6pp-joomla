Joomla! Pro6pp Component & Plugin
===================================

This repository contains the folders of the pro6pp Joomla! plugin and component.

The plugin is intended to work with the VirtueMart 2 extension but is not dependant on it.

## Requirements:
This plugin requires the following to be already installed and configured in order to function as expected.

- A Joomla! website, running a version greater or equal to Joomla! 2.5
- Administration rights to that Joomla! installation.
- [Optional] Virtuemart Joomla! component with version greater or equal to 1.9.8
- [Optional] Joomla! `User Profile` plugin.

_The optional requirements are not necessarsy for the plugin to get installed, but if none of them is installed or enabled there will be no functional use of it._

## How to install:
### Plugin
The plug-in makes use of the virtueMart JavaScript code that handles the user input forms; and the user-field naming conventions that are retrieved from the database fields.

####Installation
You can install the plug-in either from a .zip file that contains the source folder, or by copying/uploading the folder that contains the plugin source files.

Go to the joomla administrator panel

    Extensions -> Extension Manager

* Install from the .zip file.
    * At the `Upload file` section, browse for the zip file and select to install it.

* Install from a folder.
    * At the `Install from directory` section, type the directory where the plug-in resides and select install.

####Configuration
The plug-in is designed to work out of the box (minimum configuration).
Some basic configuration is required though.

Redirect to the plug-in manager:

    Extensions -> Plug-in Manager

1. Search for the keyword `pro6pp` OR sort by type `system`
2. Select the pro6pp title (it is a hyperlink)
4. At the panel on the right, select `Enabled` from the drop-down to enable the plugin.
5. At the panle on the left side, type in your pro6pp authentication key
6. Select `Save&Close` from the options bar and you are ready to use the plugin.

###Component
The pro6pp component is responsible for contacting the pro6pp service and reterning the response to the Joomla! client.

####Installation
To install the component you can use one of the two ways, that are similar to the plugins installation.

The Only difference to the installation process, is the path where the component .zip or folder is found.

####Configuration
There is no configuration needed for the component. It is designed to stay hidden from the administrator (components usually have a menu entry but for the pro6pp usecase, until now, a menu entry is not required).

The two functionalities the component implements are:

## How to use:
### Virtuemart forms
####Usage workflow

* Select `Netherlands` from the country drop down menu
* Type a valid postcode into the postcode field
* Leave the postcode field (Cursor should not be in the postcode field).
* City, Address1 and Province fields are filled in automatically.
* In the case where an invalid postcode is typed, an error message will be shown.

### Registration Forms
_This functionality is supported only if the User Profile plug-in is enabled as well as the address fields are enabled._

_This usecase doesn't require Virtuemart to be installed._

####Usage workflow
* Type into the country field any variation of the words `Netherland`, `Nederland` or `Holland` (it is case insensitive)
* Type a valid postcode into the postcode field.
* City, Address1 and Region fields are filled automatically.
* If an invalid postcode is typed, an error message will be shown.

## Contributing:
If you would like to contribute to the project, you are more than welcome.
Here are some guidelines to keep in mind.

### Guidelines
* Follow the conventions that are used in the files.
* Spaces are preferred over tabs.
* Document your code.
* Test and lint your code first before submitting a pull request.

### Testing
Tests are important and currently missing.
We use Travis CI to run our tests and have created a basic structure to do that.

###Structure
Both the plugin and the component are using the default structure, as defined in the Joomla! documentation.

####Things to note
##### Plugin
The plugin is used to inject specific variables for use with JavaScript according to the page the user has requested.
Some of these variables are making use or are dependent on VirtueMart JavaScript variables and are prone to breakage if changes happen.

##### Component
The component does not implement a menu entry or any view usefull to the administrator. It is only using the MVC pattern in order to keep the connection and response of the service structured in the same way as the rest of the Joomla! files.

#### Useful links
Documentation on how to implement a plugin for Joomla!:
http://docs.joomla.org/Category:Plugin_Development

Documentation on how to implement a component for Joomla!:
http://docs.joomla.org/Category:Component_Development

Documentation of the Joomla! code can be found on the Joomla! documentation site:
http://api.joomla.org/
