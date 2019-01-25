<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
 
/**
 * Edusharing resource configuration user interface class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
  * @version $Id$
 *
 */
class ilLfEduSharingResourceConfigGUI extends ilPluginConfigGUI
{
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilLfEduSharingResourcePlugin
	 */
	protected $pl;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;

	/**
	 *
	 */
	public function __construct() {
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->pl = ilLfEduSharingResourcePlugin::getInstance();
		$this->tabs = $DIC->tabs();
		$this->tpl = $DIC->ui()->mainTemplate();
	}

	/**
	 * Handles all commmands, default is "configure"
	 */
	function performCommand($cmd)
	{
		switch ($cmd)
		{
			default:
				$this->$cmd();
				break;
		}
	}

	/**
	 * Configure screen
	 */
	function configure()
	{
		$form = $this->initConfigurationForm();
		$this->setTabs("settings");
		$this->tpl->setContent($form->getHTML());
	}
	
	/**
	 * import metadata from edu-sharing
	 * 1/2 
	 */
	public function importMetadata(){
		$this->setTabs("import_metadata");
		$form = $this->importMetadataForm();
		$this->tpl->setContent($form->getHTML());
		
	}
	
	public function importMetadataForm() {
		// settings object for EduSharing
		$settings = new ilSetting("xedus");
		
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		$ti = new ilTextInputGUI($this->pl->txt("metadata_endpoint"), "metadata_endpoint");
		$ti->setRequired(true);
		$ti->setMaxLength(200);
		$ti->setSize(40);
		$ti->setInfo($this->pl->txt("metadata_endpoint_info"));
		$ti->setValue($settings->get("metadata_endpoint"));
		$form->addItem($ti);
	
		$form->addCommandButton("importMetadataSave", $this->pl->txt("metadata_import"));
	                
		$form->setTitle($this->pl->txt("import_application_metadata"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}
	
	public function importMetadataSave() {
		$form = $this->importMetadataForm();
		if ($form->checkInput()) {
			try {
				$mde = $form->getInput("metadata_endpoint");
				$xml = new DOMDocument();
				libxml_use_internal_errors(true);
				if ($xml->load($mde) == false) {
					ilUtil::sendFailure($this->pl->txt("import_metadata_failure"), true);
					$this->ctrl->redirect($this, "importMetadata");
				}
				
				$xml->preserveWhiteSpace = false;
				$xml->formatOutput = true;
				$entrys = $xml->getElementsByTagName('entry');
				
				$settings = new ilSetting("xedus");
				$settings->set("metadata_endpoint", $mde);
				foreach ($entrys as $entry) {
					$settings->set('repository_'.$entry->getAttribute('key'), $entry->nodeValue);
				}
				require_once(__DIR__ . '/../lib/class.AppPropertyHelper.php');
				$modedusharingapppropertyhelper = new mod_edusharing_app_property_helper();
				$sslkeypair = $modedusharingapppropertyhelper->edusharing_get_ssl_keypair();
				
				$host = $_SERVER['SERVER_ADDR'];
				if(empty($host)) $host = gethostbyname($_SERVER['SERVER_NAME']);
				
				$settings->set('application_host', $host);
				$settings->set('application_appid', 'ILIAS_'.CLIENT_ID);
				$settings->set('application_type', 'LMS');
				$settings->set('application_homerepid', $settings->get('repository_appid'));
				$settings->set('application_cc_gui_url', $settings->get('repository_clientprotocol') . '://' .
						$settings->get('repository_domain') . ':' .
						$settings->get('repository_clientport') . '/edu-sharing/');
				$settings->set('application_private_key', $sslkeypair['privateKey']);
				$settings->set('application_public_key', $sslkeypair['publicKey']);
				$settings->set('application_blowfishkey', 'thetestkey');
				$settings->set('application_blowfishiv', 'initvect');

				$settings->set('EDU_AUTH_KEY', 'username');
				$settings->set('EDU_AUTH_PARAM_NAME_USERID', 'userid');
				$settings->set('EDU_AUTH_PARAM_NAME_LASTNAME', 'lastname');
				$settings->set('EDU_AUTH_PARAM_NAME_FIRSTNAME', 'firstname');
				$settings->set('EDU_AUTH_PARAM_NAME_EMAIL', 'email');
				$settings->set('EDU_AUTH_AFFILIATION', ''); //$CFG->siteidentifier
				$settings->set('EDU_AUTH_AFFILIATION_NAME', ''); //$CFG->siteidentifier

				if (empty($sslkeypair['privateKey'])) {
					ilUtil::sendFailure($this->pl->txt("generate_ssl_keys_failed"), true);
					$this->ctrl->redirect($this, "importMetadata");
				}
				
				ilUtil::sendSuccess($this->pl->txt("import_metadata_saved"), true);
				$this->ctrl->redirect($this, "configure");
			} catch (Exception $e) {
				ilUtil::sendFailure($this->pl->txt("import_metadata_failure").' '.$e->getMessage(), true);
					$this->ctrl->redirect($this, "importMetadata");
			}
		}
	}
	
	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationForm()
	{
		// $this->setTabs("settings");

		// settings object for EduSharing
		$settings = new ilSetting("xedus");

		$iliasDomain = substr(ILIAS_HTTP_PATH,7);
		if (substr($iliasDomain,0,1) == "\/") $iliasDomain = substr($iliasDomain,1);
		if (substr($iliasDomain,0,4) == "www.") $iliasDomain = substr($iliasDomain,4);
		$iliasDomainRep = str_replace('/','',$iliasDomain).CLIENT_ID;
		$iliasDomain .= ';'.CLIENT_ID;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		$sh = new ilFormSectionHeaderGUI();
		$sh->setTitle($this->pl->txt('application_properties'));
		$sh->setInfo('URL: '.ILIAS_HTTP_PATH.'/Customizing/global/plugins/Services/Repository/RepositoryObject/LfEduSharingResource/metadata.php');
		$form->addItem($sh);
		
		$ti = new ilTextInputGUI($this->pl->txt('application_appid'), 'application_appid');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('application_appid'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('application_type'), 'application_type');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('application_type'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('application_homerepid'), 'application_homerepid');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('application_homerepid'));
		$form->addItem($ti);
		
		$ti = new ilTextInputGUI($this->pl->txt('application_cc_gui_url'), 'application_cc_gui_url');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('application_cc_gui_url'));
		$form->addItem($ti);
		
		$ti = new ilTextAreaInputGUI($this->pl->txt('application_private_key'), 'application_private_key');
		// $ti->setMaxLength(50);
		$ti->setValue($settings->get('application_private_key'));
		$form->addItem($ti);

		$ti = new ilTextAreaInputGUI($this->pl->txt('application_public_key'), 'application_public_key');
		// $ti->setMaxLength(50);
		$ti->setValue($settings->get('application_public_key'));
		$form->addItem($ti);

		if(version_compare($settings->get('repository_version'), '4.1' ) < 0) {
			$ti = new ilTextInputGUI($this->pl->txt('application_blowfishkey'), 'application_blowfishkey');
			$ti->setMaxLength(50);
			$ti->setValue($settings->get('application_blowfishkey'));
			$form->addItem($ti);
			$ti = new ilTextInputGUI($this->pl->txt('application_blowfishiv'), 'application_blowfishiv');
			$ti->setMaxLength(50);
			$ti->setValue($settings->get('application_blowfishiv'));
			$form->addItem($ti);
		}
		
		$sh = new ilFormSectionHeaderGUI();
		$sh->setTitle($this->pl->txt('repository_properties'));
		$form->addItem($sh);

		$ti = new ilTextAreaInputGUI($this->pl->txt('repository_public_key'), 'repository_public_key');
		// $ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_public_key'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_clientport'), 'repository_clientport');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_clientport'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_port'), 'repository_port');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_port'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_domain'), 'repository_domain');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_domain'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_authenticationwebservice_wsdl'), 'repository_authenticationwebservice_wsdl');
		$ti->setMaxLength(100);
		$ti->setValue($settings->get('repository_authenticationwebservice_wsdl'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_type'), 'repository_type');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_type'));
		$form->addItem($ti);
		
		$ti = new ilTextInputGUI($this->pl->txt('repository_appid'), 'repository_appid');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_appid'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_usagewebservice_wsdl'), 'repository_usagewebservice_wsdl');
		$ti->setMaxLength(100);
		$ti->setValue($settings->get('repository_usagewebservice_wsdl'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_protocol'), 'repository_protocol');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_protocol'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_host'), 'repository_host');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_host'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('repository_version'), 'repository_version');
		$ti->setMaxLength(50);
		$ti->setValue($settings->get('repository_version'));
		$ti->setInfo($this->pl->txt('repository_version_info'));
		$ti->setRequired(true);
		$form->addItem($ti);

	    // Defaults according to locallib.php.
		$sh = new ilFormSectionHeaderGUI();
		$sh->setTitle($this->pl->txt('authentication_properties'));
		$form->addItem($sh);

        $rg = new ilRadioGroupInputGUI($this->pl->txt('edu_auth_key'), 'EDU_AUTH_KEY');
        $rg->setRequired(true);
        $rg->setValue($settings->get('EDU_AUTH_KEY'));
        $ro = new ilRadioOption($this->pl->txt('edu_auth_id'),'id', $this->pl->txt('edu_auth_id_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->pl->txt('edu_auth_idnumber'),'idnumber', $this->pl->txt('edu_auth_idnumber_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->pl->txt('edu_auth_email'),'email', $this->pl->txt('edu_auth_email_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->pl->txt('edu_auth_username'),'username', $this->pl->txt('edu_auth_username_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->pl->txt('edu_auth_idnumber_http_path_client_id'),'idnumber;http_path;client_id', $this->pl->txt('edu_auth_idnumber_http_path_client_id_info').' 6;'.$iliasDomain);
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->pl->txt('edu_auth_shibbolethuid'),'ShibbolethUId', $this->pl->txt('edu_auth_shibbolethuid_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->pl->txt('edu_auth_zoerr_auth'),'ZOERR_Auth', $this->pl->txt('edu_auth_zoerr_auth_info'));
        $rg->addOption($ro);
		$form->addItem($rg);
	
		
		$ti = new ilTextInputGUI($this->pl->txt('edu_auth_param_name_userid'), 'EDU_AUTH_PARAM_NAME_USERID');
		$ti->setMaxLength(50);
		$ti->setInfo($this->pl->txt('edu_auth_param_name_userid_info'));
		$ti->setValue($settings->get('EDU_AUTH_PARAM_NAME_USERID'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('edu_auth_param_name_lastname'), 'EDU_AUTH_PARAM_NAME_LASTNAME');
		$ti->setMaxLength(50);
		$ti->setInfo($this->pl->txt('edu_auth_param_name_lastname_info'));
		$ti->setValue($settings->get('EDU_AUTH_PARAM_NAME_LASTNAME'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('edu_auth_param_name_firstname'), 'EDU_AUTH_PARAM_NAME_FIRSTNAME');
		$ti->setMaxLength(50);
		$ti->setInfo($this->pl->txt('edu_auth_param_name_firstname_info'));
		$ti->setValue($settings->get('EDU_AUTH_PARAM_NAME_FIRSTNAME'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('edu_auth_param_name_email'), 'EDU_AUTH_PARAM_NAME_EMAIL');
		$ti->setMaxLength(50);
		$ti->setInfo($this->pl->txt('edu_auth_param_name_email_info'));
		$ti->setValue($settings->get('EDU_AUTH_PARAM_NAME_EMAIL'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('edu_auth_affiliation'), 'EDU_AUTH_AFFILIATION');
		$ti->setMaxLength(50);
		$ti->setInfo($this->pl->txt('edu_auth_affiliation_info').' '.$iliasDomainRep);
		$ti->setValue($settings->get('EDU_AUTH_AFFILIATION'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('edu_auth_affiliation_name'), 'EDU_AUTH_AFFILIATION_NAME');
		$ti->setMaxLength(50);
		$ti->setInfo($this->pl->txt('edu_auth_affiliation_name_info').' '.$iliasDomainRep);
		$ti->setValue($settings->get('EDU_AUTH_AFFILIATION_NAME'));
		$form->addItem($ti);

		// $cb = new ilCheckboxInputGUI($this->pl->txt('edu_auth_conveyglobalgroups'), 'EDU_AUTH_CONVEYGLOBALGROUPS');
		// $cb->setInfo($this->pl->txt('edu_auth_conveyglobalgroups_info'));
		// $cb->setValue('1');
		// if ($settings->get('EDU_AUTH_CONVEYGLOBALGROUPS') == '1') $cb->setChecked(true);
		// $form->addItem($cb);


		$sh = new ilFormSectionHeaderGUI();
		$sh->setTitle($this->pl->txt('guest_properties'));
		$form->addItem($sh);

		$cb = new ilCheckboxInputGUI($this->pl->txt('edu_guest_option'), 'edu_guest_option');
		$cb->setInfo($this->pl->txt('edu_guest_option_info'));
		$cb->setValue('1');
		if ($settings->get('edu_guest_option') == '1') $cb->setChecked(true);
		$form->addItem($cb);

		$ti = new ilTextInputGUI($this->pl->txt('edu_guest_guest_id'), 'edu_guest_guest_id');
		$ti->setMaxLength(50);
		$ti->setInfo($this->pl->txt('edu_guest_guest_id_info'));
		$ti->setValue($settings->get('edu_guest_guest_id'));
		$form->addItem($ti);

	
		$form->addCommandButton("initConfigurationSave", $this->pl->txt("save"));
	                
		$form->setTitle($this->pl->txt("edus_configuration"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}
	
	/**
	 * Save form input
	 *
	 */
	public function initConfigurationSave() {
		
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
			// use ilSetting to save
			$settings = new ilSetting("xedus");

			$settings->set('application_appid', $form->getInput('application_appid'));
			$settings->set('application_type', $form->getInput('application_type'));
			$settings->set('application_homerepid', $form->getInput('application_homerepid'));
			$settings->set('application_cc_gui_url', $form->getInput('application_cc_gui_url'));
			$settings->set('application_private_key', $form->getInput('application_private_key'));
			$settings->set('application_public_key', $form->getInput('application_public_key'));
			if(version_compare($settings->get('repository_version'), '4.1' ) < 0) {
				$settings->set('application_blowfishkey', $form->getInput('application_blowfishkey'));
				$settings->set('application_blowfishiv', $form->getInput('application_blowfishiv'));
			}

			$settings->set('repository_public_key', $form->getInput('repository_public_key'));
			$settings->set('repository_clientport', $form->getInput('repository_clientport'));
			$settings->set('repository_port', $form->getInput('repository_port'));
			$settings->set('repository_domain', $form->getInput('repository_domain'));
			$settings->set('repository_authenticationwebservice_wsdl', $form->getInput('repository_authenticationwebservice_wsdl'));
			$settings->set('repository_type', $form->getInput('repository_type'));
			$settings->set('repository_appid', $form->getInput('repository_appid'));
			$settings->set('repository_usagewebservice_wsdl', $form->getInput('repository_usagewebservice_wsdl'));
			$settings->set('repository_protocol', $form->getInput('repository_protocol'));
			$settings->set('repository_host', $form->getInput('repository_host'));
			$settings->set('repository_version', $form->getInput('repository_version'));

			$settings->set('EDU_AUTH_KEY', $form->getInput('EDU_AUTH_KEY'));
			$settings->set('EDU_AUTH_PARAM_NAME_USERID', $form->getInput('EDU_AUTH_PARAM_NAME_USERID'));
			$settings->set('EDU_AUTH_PARAM_NAME_LASTNAME', $form->getInput('EDU_AUTH_PARAM_NAME_LASTNAME'));
			$settings->set('EDU_AUTH_PARAM_NAME_FIRSTNAME', $form->getInput('EDU_AUTH_PARAM_NAME_FIRSTNAME'));
			$settings->set('EDU_AUTH_PARAM_NAME_EMAIL', $form->getInput('EDU_AUTH_PARAM_NAME_EMAIL'));
			$settings->set('EDU_AUTH_AFFILIATION', $form->getInput('EDU_AUTH_AFFILIATION'));
			$settings->set('EDU_AUTH_AFFILIATION_NAME', $form->getInput('EDU_AUTH_AFFILIATION_NAME'));
			$settings->set('EDU_AUTH_CONVEYGLOBALGROUPS', $form->getInput('EDU_AUTH_CONVEYGLOBALGROUPS'));
			$settings->set('edu_guest_option', $form->getInput('edu_guest_option'));
			$settings->set('edu_guest_guest_id', $form->getInput('edu_guest_guest_id'));
			
			ilUtil::sendSuccess($this->pl->txt("configuration_saved"), true);
			$this->ctrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHtml());
		}
	}
	
	
	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationFormOLD()
	{

		// settings object for EduSharing
		$settings = new ilSetting("xedus");

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
	
		// setting
		$ti = new ilTextInputGUI($this->pl->txt("config_dir"), "config_dir");
		$ti->setRequired(true);
		$ti->setMaxLength(200);
		$ti->setSize(40);
		$ti->setValue($settings->get("config_dir"));
		$form->addItem($ti);
	
		$form->addCommandButton("save", $this->pl->txt("save"));
	                
		$form->setTitle($this->pl->txt("edus_configuration"));
		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}
	
	/**
	 * Save form input (currently does not save anything to db)
	 *
	 */
	public function save()
	{
		
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
			$cd = $form->getInput("config_dir");
	
			// use ilSetting to save
			$settings = new ilSetting("xedus");
			$settings->set("config_dir", $cd);
			
			ilUtil::sendSuccess($this->pl->txt("configuration_saved"), true);
			$this->ctrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHtml());
		}
	}
	
	
	/**
	 * Set tabs
	 *
	 * @param
	 * @return
	 */
	function setTabs($a_active)
	{
		// $pl = $this->getPluginObject();
		
		$this->tabs->addTab("settings",
			$this->pl->txt("settings"),
			$this->ctrl->getLinkTarget($this, "configure"));

		$this->tabs->addTab("import_metadata",
			$this->pl->txt("import_metadata"),
			$this->ctrl->getLinkTarget($this, "importMetadata"));
			
		$this->tabs->activateTab($a_active);
	}
	
}
?>
