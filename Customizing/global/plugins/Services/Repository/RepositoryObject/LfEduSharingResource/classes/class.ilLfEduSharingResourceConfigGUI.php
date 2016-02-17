<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
 
/**
 * Edusharing resource configuration user interface class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 */
class ilLfEduSharingResourceConfigGUI extends ilPluginConfigGUI
{
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
		global $tpl;

		$form = $this->initConfigurationForm();
		$this->setTabs("settings");
		$tpl->setContent($form->getHTML());
	}
	
	/**
	 * Show configuration
	 */
	function showConfiguration()
	{
		global $tpl;

		$this->setTabs("show_configuration");
		$tpl->setContent($this->getConfigHTML());
	}
	
	//
	// From here on, this is just an example implementation using
	// a standard form (without saving anything)
	//
	
	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationForm()
	{
		global $ilCtrl;
		
		$pl = $this->getPluginObject();

		// settings object for EduSharing
		$settings = new ilSetting("xedus");

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
	
		// setting
		$ti = new ilTextInputGUI($pl->txt("config_dir"), "config_dir");
		$ti->setRequired(true);
		$ti->setMaxLength(200);
		$ti->setSize(40);
		$ti->setValue($settings->get("config_dir"));
		$form->addItem($ti);
	
		$form->addCommandButton("save", $pl->txt("save"));
	                
		$form->setTitle($pl->txt("edus_configuration"));
		$form->setFormAction($ilCtrl->getFormAction($this));

		return $form;
	}
	
	/**
	 * Save form input (currently does not save anything to db)
	 *
	 */
	public function save()
	{
		global $tpl, $ilCtrl;

		$pl = $this->getPluginObject();
		
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
			$cd = $form->getInput("config_dir");
	
			// use ilSetting to save
			$settings = new ilSetting("xedus");
			$settings->set("config_dir", $cd);
			
			ilUtil::sendSuccess($pl->txt("configuration_saved"), true);
			$ilCtrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}
	
	/**
	 * Get current configuration as HTML
	 *
	 * @param
	 * @return
	 */
	function getConfigHTML()
	{
		$pl = $this->getPluginObject();
		
		$ctpl = $pl->getTemplate("tpl.config.html");
		
		$settings = new ilSetting("xedus");
		$cd = $settings->get("config_dir");
		
		$ctpl->setVariable("CONFIG_TITLE", $pl->txt("config"));
		$ctpl->setVariable("TXT_CONFIG_DIR", $pl->txt("config_dir"));
		$ctpl->setVariable("CONFIG_DIR", $cd);
		$ctpl->setVariable("TXT_CONFIG_DIR_READ", $pl->txt("config_dir_read"));
		if (is_dir($cd))
		{
			$ctpl->setVariable("CONFIG_DIR_READ", $pl->txt("yes"));
			
			$conf = $pl->includeClass("../lib/class.lfEduAppConf.php");
			lfEduAppConf::initApps($cd);
			
			$home_app = lfEduAppConf::getHomeAppConf();
			$this->renderAppConfig($ctpl, $home_app);
			
			$apps = lfEduAppConf::getAppList();
			foreach ($apps as $app)
			{
				$this->renderAppConfig($ctpl, lfEduAppConf::getAppConf($app["prop_file"]));
			}
		}
		else
		{
			$ctpl->setVariable("CONFIG_DIR_READ", $pl->txt("no"));
		}

		return $ctpl->get();
	}
	
	/**
	 * Render app conf
	 *
	 * @param
	 * @return
	 */
	function renderAppConfig($a_tpl, $a_app_conf)
	{
		foreach ($a_app_conf->getAllEntries() as $k => $v)
		{
			$a_tpl->setCurrentBlock("entry");
			$a_tpl->setVariable("ENTRY", $k);
			$a_tpl->setVariable("ENTRY_VAL", $v);
			$a_tpl->parseCurrentBlock();
		}
		
		$a_tpl->setCurrentBlock("app");
		$a_tpl->setVariable("APP", $a_app_conf->getConfFile());
		$a_tpl->parseCurrentBlock();
	}
	
	
	/**
	 * Set tabs
	 *
	 * @param
	 * @return
	 */
	function setTabs($a_active)
	{
		global $ilTabs, $ilCtrl;
		
		$pl = $this->getPluginObject();
		
		$ilTabs->addTab("settings",
			$pl->txt("settings"),
			$ilCtrl->getLinkTarget($this, "configure"));
		
		$ilTabs->addTab("show_configuration",
			$pl->txt("show_configuration"),
			$ilCtrl->getLinkTarget($this, "showConfiguration"));
		
		$ilTabs->activateTab($a_active);
	}
	
}
?>
