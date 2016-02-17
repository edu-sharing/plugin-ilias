<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");
 
/**
 * EduSharing UI plugin
 *
 * @author Alex Killing <killing@leifos.de>
 *
 * @version $Id$
 */
class ilLfEduSharingUIPlugin extends ilUserInterfaceHookPlugin
{
	function getPluginName()
	{
		return "LfEduSharingUI";
	}
	
	/**
	 * Check edusharing resource plugin existence
	 */
	function checkMainPlugin()
	{
		global $ilPluginAdmin;
		
		if (!is_file("./Customizing/global/plugins/Services/Repository/".
			"RepositoryObject/LfEduSharingResource/plugin.php"))
		{
			return false;
		}
		
		if (!$ilPluginAdmin->isActive(IL_COMP_SERVICE, "Repository", "robj", "LfEduSharingResource"))
		{
			return false;
		}
		
		return true;
	}
	
}

?>
