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
	const ID = "xesr";
	protected static $instance = NULL;
	
	function getPluginName() {
		return "LfEduSharingResource";
	}
	
	public static function getInstance() {
		if (self::$instance === NULL) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	
	protected function uninstallCustom() {
		// TODO: Nothing to do here.
	}
}
?>
