<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

/**
 * EduSharing user interface hook class
 *
 * @author Alex Killing <killing@leifos.de>
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
		global $ilUser, $rbacreview;

		// if we are on the personal desktop and the left column is rendered
		if (/*1 ||*/ $a_comp == "Services/PersonalDesktop" && $a_part == "right_column")
		{
			$this->getPluginObject()->includeClass("../../../../Repository/RepositoryObject/LfEdusharingResource/lib/class.lfEduUtil.php");
			
			if ($this->getPluginObject()->checkMainPlugin() || 1)
			{
				$settings = new ilSetting("xedus");
				if ($settings->get("show_block"))
				{
					// check if plugin is activated for role
					$roles = explode(",", $settings->get("activated_roles"));
					$ass_roles = $rbacreview->assignedRoles($ilUser->getId());
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
		global $ilUser, $ilCtrl, $tpl;
		
		$this->performCommand();
		
		$pl = $this->getPluginObject();
		$settings = new ilSetting("xedus");


		$btpl = $pl->getTemplate("tpl.edus_block.html");

		// output text from lang file
		$btpl->setVariable("TITLE", "EduSharing");
		
		$btpl->setVariable("FORM_ACTION",
			"ilias.php?baseClass=ilPersonalDesktopGUI&amp;edus_cmd=search");
		
		$btpl->setVariable("HREF_WORKSPACE",
			"ilias.php?baseClass=ilPersonalDesktopGUI&amp;edus_cmd=workspace");

		$btpl->setVariable("HREF_UPLOAD",
			"ilias.php?baseClass=ilPersonalDesktopGUI&amp;edus_cmd=upload");

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
		global $ilUser;

		if ($_GET["edus_cmd"] == "")
		{
			//return;
		}

		try
		{
			$settings = new ilSetting("xedus");
			$cd = $settings->get("config_dir");
	
			// get home app conf
			$pl = $this->getPluginObject();
			//error_reporting(E_ALL);ini_set('display_errors', 1);
			$conf = $pl->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduAppConf.php");
			
			lfEduAppConf::initApps($cd);
			$home_app_conf = lfEduAppConf::getHomeAppConf();
			
			// get edu sharing soap client and a ticket
			$pl->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduSoapClient.php");
			
			$soap_client = new lfEduSoapClient($pl, $home_app_conf);
			$ticket = $soap_client->getAuthenticationTicket();
			
			$pl->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduUtil.php");
			
			switch ($_GET["edus_cmd"])
			{
				case "search":
					$stext = ilUtil::stripSlashes($_POST["edus_svalue"]);
					$url = lfEduUtil::buildUrl($home_app_conf, "search", $ticket, $stext, "");
					ilUtil::redirect($url);
					break;

					//not supported anymore
				case "upload":
					$url = lfEduUtil::buildUrl($home_app_conf, "workspace", $ticket, "", "");
					ilUtil::redirect($url);
					break;
					
				case "workspace":
					$url = lfEduUtil::buildUrl($home_app_conf, "workspace", $ticket, "", "");
					ilUtil::redirect($url);
					break;
			}
		}
		catch (Exception $e)
		{
			$pl->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduUtil.php");
			ilUtil::sendFailure(lfEduUtil::formatException($e), true);
			ilUtil::redirect("ilias.php?baseClass=ilPersonalDesktopGUI");
		}

	}
	
	/**
	 * Build edusharing url
	 *
	 * @param
	 * @return
	 */
	function buildUrl($a_base_url, $a_mode = 0, $a_pars = array())
	{
		global $ilUser;
		
		// build link to search
		$link = $a_base_url;
		$link .= '/components/' . $a_mode;

        // todo: what if no email given!?
		$user = $ilUser->getEmail();
		$link .= '&user='.urlencode($user);

		// add ticket
		$link .= '&ticket='.urlencode($ticket);

		// add language
		$link .= '&locale='.urlencode($ilUser->getLanguage());
        $link .= '&language='.urlencode($ilUser->getLanguage());

		if (is_array($a_pars))
		{
			foreach ($a_pars  as $k => $v)
			{
				$link.= "&".$k."=".$v;
			}
		}
		
		return $link;
	}
	
}
?>
