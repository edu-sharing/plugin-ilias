<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
 
/**
 * Edu Sharing configuration user interface class
 *
 * @author Alex Killing <killing@leifos.de>
 *
 * @version $Id$
 */
class ilLfEduSharingUIConfigGUI extends ilPluginConfigGUI
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
		
		$pl = $this->getPluginObject();
		if (!$pl->checkMainPlugin())
		{
			ilUtil::sendFailure($pl->txt("main_plugin_missing"));
		}

		$form = $this->initConfigurationForm();
		$tpl->setContent($form->getHTML());
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
		global $ilCtrl, $rbacreview;
		
		$pl = $this->getPluginObject();

		// settings object for EduSharing
		$settings = new ilSetting("xedus");

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
	
		// show block?
		$cb = new ilCheckboxInputGUI($pl->txt("show_block"), "show_block");
		$cb->setValue(1);
		$cb->setChecked($settings->get("show_block"));
		$form->addItem($cb);
		
		// activate for roles
		$sh = new ilFormSectionHeaderGUI();
		$sh->setTitle($pl->txt("role_activation"));
		$form->addItem($sh);
		
		// activate for roles
		$roles = explode(",", $settings->get("activated_roles"));
		foreach ($rbacreview->getRolesByFilter(ilRbacReview::FILTER_ALL_GLOBAL) as $r)
		{
			if ($r["title"] != "Anonymous")
			{
				$cb = new ilCheckboxInputGUI($r["title"], "activate_for_role_".$r["obj_id"]);
				$cb->setValue(1);
				if (in_array($r["obj_id"], $roles))
				{
					$cb->setChecked($settings->get("show_block"));
				}
				$form->addItem($cb);
			}
		}

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
		global $tpl, $ilCtrl, $rbacreview;

		$pl = $this->getPluginObject();
		
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
			$sb = $form->getInput("show_block");
	
			// use ilSetting to save
			$settings = new ilSetting("xedus");
			$settings->set("show_block", $sb);
			
			$roles = array();
			foreach ($rbacreview->getRolesByFilter(ilRbacReview::FILTER_ALL_GLOBAL) as $r)
			{
				if ($r["title"] != "Anonymous")
				{
					if ($form->getInput("activate_for_role_".$r["obj_id"]))
					{
						$roles[] = $r["obj_id"];
					}
				}
			}
			$settings->set("activated_roles", implode($roles, ","));
			
			ilUtil::sendSuccess($pl->txt("configuration_saved"), true);
			$ilCtrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}
	
}
?>
