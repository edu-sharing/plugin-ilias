<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");
include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduUtil.php");


/**
 * User Interface class for edusharing resource repository object.
 *
 * User interface classes process GET and POST parameter and call
 * application classes to fulfill certain tasks.
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 *
 * $Id$
 *
 * @ilCtrl_isCalledBy ilObjLfEduSharingResourceGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjLfEduSharingResourceGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
 * @ilCtrl_Calls ilObjLfEduSharingResourceGUI: ilCommonActionDispatcherGUI
 *
 */
class ilObjLfEduSharingResourceGUI extends ilObjectPluginGUI
{

	/**
	* Initialisation
	*/
	protected function afterConstructor()
	{
		// anything needed after object has been constructed
		// - example: append my_id GET parameter to each request
		//   $ilCtrl->saveParameter($this, array("my_id"));
	}
	
	/**
	* Get type.
	*/
	final function getType()
	{
		return "xesr";
	}
	
	/**
	* Handles all commmands of this class, centralizes permission checks
	*/
	function performCommand($cmd)
	{
		switch ($cmd)
		{
			case "editProperties":		// list all commands that need write permission here
			case "updateProperties":
			case "browseResource":
			case "uploadResource":
			case "searchResource":
			case "setResource":
			case "registerUsage":
				$this->checkPermission("write");
				$this->$cmd();
				break;
			
			case "showContent":			// list all commands that need read permission here
				$this->checkPermission("read");
				$this->$cmd();
				break;
		}
	}

	/**
	* After object has been created -> jump to this command
	*/
	function getAfterCreationCmd()
	{
		return "editProperties";
	}

	/**
	* Get standard command
	*/
	function getStandardCmd()
	{
		return "showContent";
	}
	
//
// DISPLAY TABS
//
	
	/**
	* Set tabs
	*/
	function setTabs()
	{
		global $DIC;
		
		// tab for the "show content" command
		if ($DIC->access()->checkAccess("read", "", $this->object->getRefId()))
		{
			$DIC->tabs()->addTab("content", $this->txt("content"), $DIC->ctrl()->getLinkTarget($this, "showContent"), "_blank");
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($DIC->access()->checkAccess("write", "", $this->object->getRefId()))
		{
			$DIC->tabs()->addTab("properties", $this->txt("properties"), $DIC->ctrl()->getLinkTarget($this, "editProperties"));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}
	

	/**
	 * Edit Properties. This commands uses the form class to display an input form.
	 */
	function editProperties()
	{
		global $DIC;
		
		$ilToolbar = $DIC->toolbar();
		// toolbar
		$ilToolbar->setFormAction($DIC->ctrl()->getFormAction($this));
		if ($this->object->getUri() == "")
		{
		// //set parent_obj for edu-sharing
			// $this->object->afterCreateSetParentObj();
			$ilToolbar->addText($this->plugin->txt("select_resource"));
			include_once("./Services/Form/classes/class.ilTextInputGUI.php");
			$ti = new ilTextInputGUI("", "edus_svalue");
			$ti->setMaxLength(200);
			$ti->setSize(30);
			$ilToolbar->addInputItem($ti, false);
			$ilToolbar->addFormButton($this->plugin->txt("search"), "searchResource");
			
			$ilToolbar->addSeparator();
			$ilToolbar->addFormButton($this->plugin->txt("upload"), "uploadResource");
			
		}
		
		// check whether upper course is given
		if ($this->object->getUri() != "" && $this->object->getUpperCourse() == 0)
		{
			ilUtil::sendFailure($this->plugin->txt("not_usable_no_parent_object"));
		}
		// else if ($this->object->getUri() == "" && !$this->object->checkRegisteredUsage())
		// {
			// ilUtil::sendFailure($this->plugin->txt("failure after copy"));
		// }
		else
		{
			// check whether usage is registered
			if ($this->object->getUri() != "" && !$this->object->checkRegisteredUsage())
			{
				ilUtil::sendFailure($this->plugin->txt("usage_not_registered"));
				if ($this->object->getUpperCourse() > 0)
				{
					$ilToolbar->addFormButton($this->plugin->txt("register_usage"), "registerUsage");
				}
			}
		}
		
		$DIC->tabs()->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$DIC->ui()->mainTemplate()->setContent($this->form->getHTML());
	}
	
	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
		global $DIC;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
	
		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);
		
		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);
		
		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);
		
		// version setting
		$cb = new ilCheckboxInputGUI($this->plugin->txt("object_version_use_exact"), "object_version_use_exact");
		$cb->setValue("1");
		$cb->setInfo($this->plugin->txt("object_version_use_exact_info").' '.$this->object->getObjectVersion());
		$this->form->addItem($cb);

		// uri
		$ne = new ilNonEditableValueGUI($this->lng->txt("uri"), "uri");
		$ne->setValue($this->object->getUri());
		$this->form->addItem($ne);
		

		$this->form->addCommandButton("updateProperties", $this->txt("save"));
	                
		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($DIC->ctrl()->getFormAction($this));
	}
	
	/**
	* Get values for edit properties form
	*/
	function getPropertiesValues()
	{
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["uri"] = $this->object->getUri();
		$values["online"] = $this->object->getOnline();
		$values["object_version_use_exact"] = $this->object->getObjectVersionUseExact();
		$this->form->setValuesByArray($values);
	}
	
	/**
	* Update properties
	*/
	public function updateProperties()
	{
		global $DIC;
	
		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setOnline($this->form->getInput("online"));
			$this->object->setObjectVersionUseExact($this->form->getInput("object_version_use_exact"));
			$this->object->update();
			ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$DIC->ui()->mainTemplate()->setContent($this->form->getHtml());
	}
	
	
	/**
	 * Search resource
	 */
	function searchResource()
	{
		global $DIC;
		
		$this->plugin->includeClass("../lib/class.lfEduUtil.php");
		
		try
		{
			$ticket = $this->object->getTicket();
			$stext = ilUtil::stripSlashes($_POST["edus_svalue"]);
			$re_url = ILIAS_HTTP_PATH.'/'.$DIC->ctrl()->getLinkTarget($this, "setResource", "", false, false);
			$url = lfEduUtil::buildUrl("search", $ticket, $stext, $re_url, $DIC->user());
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}
		
		ilUtil::redirect($url);
	}
	
	/**
	 * Browse for a resource
	 */
	function browseResource()
	{
		global $DIC;
		
		try
		{
			$this->plugin->includeClass("../lib/class.lfEduUtil.php");
			$ticket = $this->object->getTicket();
			$re_url = ILIAS_HTTP_PATH.'/'.$DIC->ctrl()->getLinkTarget($this, "setResource", "", false, false);
			$url = lfEduUtil::buildUrl("browse", $ticket, "", $re_url, $DIC->user());
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}

		ilUtil::redirect($url);
	}
	
	/**
	 * Upload resource //needed anymore?
	 */
	function uploadResource()
	{
		global $DIC;
		
		// see mod/mod_form.php 114
		
		$this->plugin->includeClass("../lib/class.lfEduUtil.php");
		
		try
		{
			$this->plugin->includeClass("../lib/class.lfEduUtil.php");
			$ticket = $this->object->getTicket();
			$re_url = ILIAS_HTTP_PATH.'/'.$DIC->ctrl()->getLinkTarget($this, "setResource", "", false, false);
			$url = lfEduUtil::buildUrl("upload", $ticket, "", $re_url, $DIC->user());
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}
		
		ilUtil::redirect($url);
	}

	/**
	 * Set resource
	 *
	 * @param
	 * @return
	 */
	function setResource()
	{
		global $DIC;
		
		try
		{
			$new_uri = ilUtil::stripSlashes($_REQUEST["nodeId"]);
			$this->object->setUri($new_uri);
			$this->object->update();
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}
		
		$DIC->ctrl()->redirect($this, "editProperties");
	}
	
	/**
	 * Register usage
	 *
	 * @param
	 * @return
	 */
	function registerUsage()
	{
		global $DIC;
		
		try
		{
//$this->object->deleteAllUsages();
			$this->object->setUsage();
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}
		
		$DIC->ctrl()->redirect($this, "editProperties");
	}

	
	/**
	 * Show content
	 */
	function showContent()
	{
		global $DIC;
		
		$DIC->tabs()->activateTab("content");
				
		$this->plugin->includeClass("../lib/class.lfEduUtil.php");
		
		if (!$this->object->getUri()) {
			ilUtil::sendFailure($this->plugin->txt("no_resource_set"), true);
			return;
		}
		
		if (!$this->object->checkRegisteredUsage()) {
			ilUtil::sendFailure($this->plugin->txt("not_visible_now"), true);
			return;
		}

		$redirect_url = lfEduUtil::getRenderUrl($this->object, 'window');

		ilUtil::redirect($redirect_url);
	}

}
?>
