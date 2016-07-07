<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
 
/**
* Edusharing resource repository object plugin
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
*/
class ilLfEduSharingResourcePlugin extends ilRepositoryObjectPlugin
{
	function getPluginName()
	{
		return "LfEduSharingResource";
	}
}
?>
