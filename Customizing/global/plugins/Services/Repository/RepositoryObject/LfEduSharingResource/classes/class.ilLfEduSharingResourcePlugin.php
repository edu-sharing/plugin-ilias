<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */


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
	protected static ?ilLfEduSharingResourcePlugin $instance = NULL;

	public function __construct()
	{
		global $DIC;
		$this->db = $DIC->database();
		parent::__construct($this->db, $DIC["component.repository"], self::ID);
	}
	function getPluginName(): string
	{
		return "LfEduSharingResource";
	}
	
	public static function getInstance(): ilLfEduSharingResourcePlugin
	{
		if (self::$instance === NULL) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	
	protected function uninstallCustom(): void
	{
		// TODO: delete database
	}

	public function allowCopy() : bool
	{
		return true;
	}

}
?>
