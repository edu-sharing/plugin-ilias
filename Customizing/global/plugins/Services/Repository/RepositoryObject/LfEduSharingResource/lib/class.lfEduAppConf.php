<?php

/* Copyright (c) 2011-2012 Leifos GmbH, GPL2 */

/**
 * Edusharing configuration class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$ 
 */
class lfEduAppConf
{
	// general static properties
	private static $conf_dir = "";
	private static $cc_conf_appfile = "ccapp-registry.properties.xml";
	private static $home_conf_appfile = "homeApplication.properties.xml";
	private static $app_conf = array();
	private static $home_conf = null;
	private static $init = false;

	// app specific instance properties
	private $properties = array();
	private $conf_file = "";
	
	/**
	 * Constructor
	 *
	 * @param string configuration file name (no path)
	 */
	function __construct($a_conf_file)
	{
		if (!self::$init)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("lfEduAppConff::init() not called.");
		}
		$this->conf_file = $a_conf_file;
		$this->readProperties();
	}
	
	/**
	 * Set conf file
	 *
	 * @param string $a_val conf file	
	 */
	function setConfFile($a_val)
	{
		$this->conf_file = $a_val;
	}
	
	/**
	 * Get conf file
	 *
	 * @return string conf file
	 */
	function getConfFile()
	{
		return $this->conf_file;
	}
	
	/**
	 * Init all app configurations objects
	 */
	public static function initApps()
	{
		// return of already initialized
		if (self::$init)
		{
			return;
		}
		
		$settings = new ilSetting("xedus");
		self::$conf_dir = $settings->get("config_dir");

		self::$init = true;
		
		// get all apps
		$apps = self::getAppList();
		
		// create conf object for each app
		foreach ($apps as $app)
		{
			self::$app_conf[$app["prop_file"]] = new lfEduAppConf($app["prop_file"]);
			if ($app["prop_file"] == self::$home_conf_appfile)
			{
				self::$home_conf = self::$app_conf[$app["prop_file"]];
			}
		}
	}
	
	/**
	 * Get home app conf
	 *
	 * @return object home app conf object
	 */
	public static function getHomeAppConf()
	{
		if (!self::$init)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("lfEduAppConff::init() not called.");
		}
		return self::$home_conf;
	}
	
	
	/**
	 * Get app list
	 *
	 * @return array array of app arrays
	 */
	public static function getAppList()
	{
		$cc_conf_file = self::$conf_dir."/".self::$cc_conf_appfile;
		
		if (file_exists($cc_conf_file))
		{
			$l_DOMDocument = new DOMDocument();
			$l_DOMDocument->load($cc_conf_file);
		}
		else
		{	 		
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("edu-sharing error: File not found: ".$cc_conf_file);
		}
		$list = $l_DOMDocument->getElementsByTagName('entry');
		
		foreach ($list as $entry)
		{
			if ($entry->getAttribute("key")=="applicationfiles" )
			{
				$app_str  = $entry->nodeValue;
				break;
			}
		}
		
		$app_array = explode(',',$app_str);
		$apps = array();
		foreach ($app_array as $a)
		{
			$apps[] = array("prop_file" => trim($a));
		}
		
		return $apps;
	}
	
	
	/**
	 * Read properties from configuation file
	 */
	final public function readProperties()
	{
		$conf_file = self::$conf_dir."/".$this->conf_file;
		$this->properties = array();

		$l_DOMDocument = new DOMDocument();
		if (!$l_DOMDocument->load($conf_file))
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Error loading config "'.$conf_file.'".');
		}

		$comment = $l_DOMDocument->getElementsByTagName('comment');
		$this->properties['comment'] = $comment->item(0)->nodeValue;

		$list = $l_DOMDocument->getElementsByTagName('entry');
		foreach ($list as $entry)
		{
			$this->properties[$entry->getAttribute("key")] = $entry->nodeValue;
		}
	}

	/**
	 * Get entry
	 *
	 * @param string entry key
	 * @return string entry value
	 */
	function getEntry($a_key)
	{
		return $this->properties[$a_key];
	}
	
	/**
	 * Get all entries
	 *
	 * @param
	 * @return
	 */
	function getAllEntries()
	{
		return $this->properties;
	}
	
	
	/**
	 * Gett app conf
	 *
	 * @param
	 * @return
	 */
	static function getAppConf($a_prop_file)
	{
		return self::$app_conf[$a_prop_file];
	}
	
	/**
	 * Get app conf by app id
	 *
	 * @param
	 * @return
	 */
	static function getAppConfById($a_app_id)
	{
		if (!self::$init)
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException("getAppConfById: lfEduAppConff::init() not called.");
		}
		if (isset(self::$app_conf['app-'.$a_app_id.'.properties.xml']))
		{
			return self::$app_conf['app-'.$a_app_id.'.properties.xml'];
		}
		return null;
	}
	
}

?>
