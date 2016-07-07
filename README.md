edu-sharing ILIAS plugin
===========================

This extension is tested with ILIAS v4.4.4
More information about edu-sharing can be found on the [edu-sharing homepage](http://www.edu-sharing.com).

[Demo](http://stable.demo.edu-sharing.net/ilias/)
--------------------------------------

Installation
------------
- Put the directory LfEduSharingResource into your ILIAS Customizing directory at: Customizing/global/plugins/Services/Repository/RepositoryObject/LfEduSharingResource (create all missing subdirectories)
- Put the directory LfEduSharingUI into your ILIAS Customizing directory at: Customizing/global/plugins/Services/Repository/UIComponent/UserInterfaceHook/LfEduSharingUI (create all missing subdirectories)
- Within ILIAS open Administration > Plugins, Modules and Services
- Click on "Administrate" in the "RepositoryObject" slot row.
- Install/update and activate the "LfEduSharingResource" plugin
- After activation click on "Configure" and enter your configuration directory path and hit "Save".

Plugin registration
----------------------
- Go to Customizing/global/plugins/Services/Repository/RepositoryObject/LfEduSharingResource/config/
- Rename app-local.properties.xml to app-[home repository app id].properties.xml and adjust filename in ccapp-registry.properties.xml.
- Adjust all plugin and repository paths accordingly to your home repository in the following files
  - app-[home repository app id].properties.xml
  - homeApplication.properties.xml
- Change the ssl keypair in homeApplication.properties.xml. You really should do this to avoid a security gap.
- Register the plugin in home repository

Contributing
------------
If you plan to contribute on a regular basis, please visit our [community site](http://edu-sharing-network.org/?lang=en).
