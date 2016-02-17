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
			//case "...":
			//case "...":
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
		global $ilTabs, $ilCtrl, $ilAccess;
		
		// tab for the "show content" command
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"), "_blank");
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}
	

	/**
	 * Edit Properties. This commands uses the form class to display an input form.
	 */
	function editProperties()
	{
		global $tpl, $ilTabs, $ilToolbar, $ilCtrl;
		
		// toolbar
		$ilToolbar->setFormAction($ilCtrl->getFormAction($this));
		if ($this->object->getUri() == "")
		{
			$ilToolbar->addText($this->plugin->txt("select_resource"));
//			$ilToolbar->addFormButton($this->plugin->txt("browse"), "browseResource");
//			$ilToolbar->addSeparator();
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
			ilUtil::sendFailure($this->plugin->txt("not_usable_no_course"));
		}
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
		
		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$tpl->setContent($this->form->getHTML());
	}
	
	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
		global $ilCtrl;
	
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
		
		// uri
		$ne = new ilNonEditableValueGUI($this->plugin->txt("uri"), "uri");
		$ne->setValue($this->object->getUri());
		$this->form->addItem($ne);

		$this->form->addCommandButton("updateProperties", $this->txt("save"));
	                
		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
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
		$this->form->setValuesByArray($values);
	}
	
	/**
	* Update properties
	*/
	public function updateProperties()
	{
		global $tpl, $lng, $ilCtrl;
	
		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setOnline($this->form->getInput("online"));
			$this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}
	
	
	/**
	 * Search resource
	 */
	function searchResource()
	{
		global $ilCtrl;
		
		// see mod/mod_form.php 114
		
		$this->plugin->includeClass("../lib/class.lfEduUtil.php");
		
		try
		{
			$home_app_conf = $this->object->getHomeAppConf();
			$ticket = $this->object->getTicket();
			$stext = ilUtil::stripSlashes($_POST["edus_svalue"]);
			$re_url = ILIAS_HTTP_PATH.'/'.$ilCtrl->getLinkTarget($this, "setResource", "", false, false);
			$url = lfEduUtil::buildUrl($home_app_conf, "search", $ticket, $stext, $re_url);
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		
		ilUtil::redirect($url);
	}
	
	/**
	 * Browse for a resource
	 */
	function browseResource()
	{
		global $ilCtrl;
		
		try
		{
			$this->plugin->includeClass("../lib/class.lfEduUtil.php");
			$home_app_conf = $this->object->getHomeAppConf();
			$ticket = $this->object->getTicket();
			$re_url = ILIAS_HTTP_PATH.'/'.$ilCtrl->getLinkTarget($this, "setResource", "", false, false);
			$url = lfEduUtil::buildUrl($home_app_conf, "browse", $ticket, "", $re_url);
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		ilUtil::redirect($url);
	}
	
	/**
	 * Upload resource
	 */
	function uploadResource()
	{
		global $ilCtrl;
		
		// see mod/mod_form.php 114
		
		$this->plugin->includeClass("../lib/class.lfEduUtil.php");
		
		try
		{
			$this->plugin->includeClass("../lib/class.lfEduUtil.php");
			$home_app_conf = $this->object->getHomeAppConf();
			$ticket = $this->object->getTicket();
			$re_url = ILIAS_HTTP_PATH.'/'.$ilCtrl->getLinkTarget($this, "setResource", "", false, false);
			$url = lfEduUtil::buildUrl($home_app_conf, "upload", $ticket, "", $re_url);
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$ilCtrl->redirect($this, "editProperties");
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
		global $ilCtrl;
		
		try
		{
			$new_uri = ilUtil::stripSlashes($_REQUEST["nodeId"]);
			$this->object->setUri($new_uri);
			$this->object->update();
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$ilCtrl->redirect($this, "editProperties");
		}
		
		$ilCtrl->redirect($this, "editProperties");
	}
	
	/**
	 * Register usage
	 *
	 * @param
	 * @return
	 */
	function registerUsage()
	{
		global $ilCtrl;
		
		try
		{
//$this->object->deleteAllUsages();
			$this->object->setUsage();
		}
		catch (Exception $e)
		{
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			$ilCtrl->redirect($this, "editProperties");
		}
		
		$ilCtrl->redirect($this, "editProperties");
	}

	
	/**
	 * Show content
	 */
	function showContent()
	{
		global $tpl, $ilTabs, $ilUser;
		
		$ilTabs->activateTab("content");

				
		$this->plugin->includeClass("../lib/class.lfEduUtil.php");
		
		
		if (!$this->object->checkRegisteredUsage())
		{
			return;
		}

		$redirect_url = lfEduUtil::getRenderUrl($this->object);

		ilUtil::redirect($redirect_url);
	}
	

}
?>
