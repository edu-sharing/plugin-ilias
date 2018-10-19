<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

/**
 * EduSharing user interface hook class
 *
 * @author Alex Killing <killing@leifos.de>
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 *
 * @version $Id$
 */
class ilLfEduSharingUIUIHookGUI extends ilUIHookPluginGUI
{
	/**
	 * Get html for a user interface area
	 *
	 * @param
	 * @return
	 */
	function getHTML($a_comp, $a_part, $a_par = array())
	{
		global $DIC, $rbacreview;

		// if we are on the personal desktop and the left column is rendered
		if (/*1 ||*/ $a_comp == "Services/PersonalDesktop" && $a_part == "right_column")
		{
			$this->getPluginObject()->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduUtil.php");
			
			if ($this->getPluginObject()->checkMainPlugin() || 1)
			{
				$settings = new ilSetting("xedus");
				if ($settings->get("show_block"))
				{
					// check if plugin is activated for role
					$roles = explode(",", $settings->get("activated_roles"));
					$ass_roles = $rbacreview->assignedRoles($DIC->user()->getId());
					$show = false;
					foreach ($roles as $r)
					{
						if (in_array($r, $ass_roles))
						{
							$show = true;
						}
					}
					
					if ($show)
					{
						// prepend the HTML of the EduSharing block
						return array("mode" => ilUIHookPluginGUI::PREPEND,
							"html" => $this->getBlockHTML());
					}
				}
			}
		}
		
		// in all other cases, keep everything as it is
		return array("mode" => ilUIHookPluginGUI::KEEP, "html" => "");
	}
	
	/**
	 * Get EduSharing block html
	 *
	 * @return string HTML of EduSharing block
	 */
	function getBlockHTML()
	{
		$avaliable = $this->performCommand();
		
		$pl = $this->getPluginObject();
		$settings = new ilSetting("xedus");


		$btpl = $pl->getTemplate("tpl.edus_block.html");

		$btpl->setVariable("TITLE", $pl->txt("edu_sharing"));
		if ($avaliable != false) {
			$btpl->setCurrentBlock("edu_sharing_available");
			$btpl->setVariable("FORM_ACTION",
				"ilias.php?baseClass=ilPersonalDesktopGUI&amp;edus_cmd=search");
			
			$btpl->setVariable("HREF_WORKSPACE",
				"ilias.php?baseClass=ilPersonalDesktopGUI&amp;edus_cmd=workspace");

			$btpl->setVariable("WORKSPACE", $pl->txt("workspace"));

			$btpl->setVariable("SEARCH", $pl->txt("search"));
			$btpl->parseCurrentBlock();
		} else {
			$btpl->setCurrentBlock("edu_sharing_not_available");
			$btpl->setVariable("NOT_AVAILABLE", $pl->txt("not_available"));
			$btpl->parseCurrentBlock();
		}

		return $btpl->get();
	}

	/**
	 * Perform command
	 *
	 * @param
	 * @return
	 */
	function performCommand()
	{
		global $DIC;

		// if ($_GET["edus_cmd"] == "")
		// {
			// //return;
		// }

		try
		{
			$settings = new ilSetting("xedus");
			$cd = $settings->get("config_dir");
	
			// get home app conf
			$pl = $this->getPluginObject();
			
			// get edu sharing soap client and a ticket
			$pl->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.cclib.php");
			
			$soap_client = new mod_edusharing_web_service_factory();
			$ticket = $soap_client->edusharing_authentication_get_ticket();
			if ($ticket) {
				$pl->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduUtil.php");
				
				switch ($_GET["edus_cmd"])
				{
					case "search":
						$stext = ilUtil::stripSlashes($_POST["edus_svalue"]);
						$url = lfEduUtil::buildUrl("search", $ticket, $stext, "", $DIC->user());
						ilUtil::redirect($url);
						break;

					case "workspace":
						$url = lfEduUtil::buildUrl("workspace", $ticket, "", "", $DIC->user());
						ilUtil::redirect($url);
						break;
				}
				return true;
			} else {
				return false;
			}
		}
		catch (Exception $e)
		{
			$pl->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduUtil.php");
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			ilUtil::redirect("ilias.php?baseClass=ilPersonalDesktopGUI");
		}

	}
	
}
?>
