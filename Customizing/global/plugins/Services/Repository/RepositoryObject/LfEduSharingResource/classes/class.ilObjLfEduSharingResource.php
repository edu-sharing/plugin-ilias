<?php

/* Copyright (c) 2012 Leifos GmbH, GPL */

#include_once("./Services/Repository/classes/class.ilObjectPlugin.php");
#require_once("./Services/Tracking/interfaces/interface.ilLPStatusPlugin.php");

/**
 * Application class for edusharing resource repository object.
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 *
 * $Id$
 */
class ilObjLfEduSharingResource extends ilObjectPlugin //implements ilLPStatusPluginInterface
{

	public int $window_width = 200;
	public int $window_height = 100;
	public string $object_version = '0';
	protected int $object_version_use_exact = 1;
	protected int $version_restricted = 0;

	protected bool $online = false;

	protected ?string $uri = null;

	/**
	 * Constructor
	 * @access    public
	 */
	public function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}

	/**
	 * Get type.
	 */
	protected function initType() : void
	{
		$this->setType("xesr");
	}

	/**
	 * Get resource_id
	 * used in lib
	 * @return string uri
	 */
	public function getResId() : int
	{
		return $this->getId();
	}

	public function setUri(?string $a_val) : void
	{
		$this->uri = $a_val;
	}

	public function getUri() : ?string
	{
		return $this->uri;
	}

	public function setObjectVersion(string $a_val) : void
	{
		$this->object_version = $a_val;
	}

	public function getObjectVersion() : string
	{
		return $this->object_version;
	}

	public function setObjectVersionUseExact(int $a_val) : void
	{
		$this->object_version_use_exact = $a_val;
	}

	public function getObjectVersionUseExact() : int
	{
		return $this->object_version_use_exact;
	}

	public function setVersionRestricted(int $a_val) : void
	{
		$this->version_restricted = $a_val;
	}

	public function getVersionRestricted() : int
	{
		return $this->version_restricted;
	}

	public function getObjectVersionForUse() : string
	{
		if ($this->object_version_use_exact == 0) {
			return 0;
		} else {
			return $this->object_version;
		}
	}

	public function setOnline(int $a_val) : void
	{
		$this->online = (bool) $a_val;
	}

	public function getOnline() : bool
	{
		return (bool) $this->online;
	}

	/**
	 * Create object
	 */
	protected function doCreate(bool $clone_mode = false) : void
	{
		global $DIC;
		$db = $DIC->database();
		$db->insert('rep_robj_xesr_usage',
			array(
				'id' => array('integer', $this->getId()),
				'edus_uri' => array('text', $this->getUri()), //""
				'parent_obj_id' => array('integer', $this->getId()),
				'is_online' => array('integer', $this->getOnline()),
				'object_version' => array('text', $this->getObjectVersion()),
				'object_version_use_exact' => array('integer', $this->getObjectVersionUseExact()),
				'version_restricted' => array('integer', $this->getVersionRestricted()),
				'timecreated' => array('timestamp', date('Y-m-d H:i:s')),
				'timemodified' => array('timestamp', date('Y-m-d H:i:s')),
				'crs_ref_id' => array('integer', 0)
			)
		);
	}

	// function afterCreateSetParentObj() {
	// global $DIC;
	// $db = $DIC->database();
	// $db->update('rep_robj_xesr_usage',
	// array(
	// 'parent_obj_id'	=> array('integer', $this->getUpperCourse())
	// ),
	// array(
	// 'id' => array('integer', $this->getId()),
	// 'parent_obj_id' => array('integer', $this->getId())
	// )
	// );

	// }

	/**
	 * Read data from db
	 */
	protected function doRead() : void
	{
		global $DIC;
		$check_parent_obj_id = 0;

		$db = $DIC->database();
		$query = "SELECT * FROM rep_robj_xesr_usage WHERE id = " . $db->quote($this->getId(), 'integer') .
			" AND parent_obj_id = " . $db->quote($this->getUpperCourse(), "integer");
		$result = $db->query($query);
		while ($row = $result->fetchAssoc()) {
			$this->setUri($row['edus_uri']);
			$this->setOnline((int) $row["is_online"]);
			$this->setObjectVersion($row['object_version']);
			$this->setObjectVersionUseExact($row['object_version_use_exact']);
			$this->setVersionRestricted((int) ($row['version_restricted'] ?? 0));
			// $this->set($row['timecreated']);
			// $this->set($row['timemodified']);
			$check_parent_obj_id = $row['parent_obj_id'];
		}

		if ($check_parent_obj_id != $this->getUpperCourse()) { //after creation or cloning
			$db->update('rep_robj_xesr_usage',
				array(
					'parent_obj_id' => array('integer', $this->getUpperCourse())
				),
				array(
					'id' => array('integer', $this->getId())//,
					// 'parent_obj_id' => array('integer', $check_parent_obj_id)
					// 'parent_obj_id' => array('integer', $this->getId())
				)
			);
			if ($this->getUri() != "") { //after cloning or moving
//				$this->plugin->includeClass('../lib/class.lib.php');
//				edusharing_add_instance($this);
				$service = new EduSharingService();
				$usageResult = $service->addInstance($this);
				//die('usageResult = '.$usageResult);
				//ToDo catch
			}
		}
	}

	/**
	 * Update data
	 */
	protected function doUpdate() : void
	{
		// die URI setzen
		$old_uri = self::lookupUri($this->getId(), $this->getUpperCourse());
		$new_uri = $this->getUri();

		// change of uri not allowed
		if ($old_uri != $new_uri && $old_uri != "") {
//			$this->plugin->includeClass("../exceptions/class.ilLfEduSharingResourceException.php");
			throw new ilLfEduSharingResourceException("Update: Change of URI not supported.");
		}

		// if ($old_uri != $new_uri && $old_uri == "" && $new_uri != "")
		// {
//		$this->plugin->includeClass('../lib/class.lib.php');
//		edusharing_add_instance($this);
		$service = new EduSharingService();
		$usageResult = $service->addInstance($this);
		if (!$usageResult) {
			//delete
			die('usageResult = false');
		}
//		$this->setUsage();
		// }
		//new $service->addInstance and ToDo Check
//		$course_id = $this->getUpperCourse();
//		if ($course_id == 0) {
//			ilLoggerFactory::getLogger('xesr')->warning('set usage: no upper object ref id given.');
//			ilUtil::sendFailure('set usage: no upper object ref id given.');
//		}
//		$usageData = new stdClass();
//		$usageData->containerId = $course_id;
//		$usageData->resourceId = $this->getId();//$id;
//		$usageData->nodeId = $service->utils->getObjectIdFromUrl($this->getUri());  //$this->utils->getObjectIdFromUrl($eduSharing->object_url);
//		$usageData->nodeVersion = $this->getObjectVersion();//$eduSharing->object_version;
		global $DIC;
		$db = $DIC->database();
		$db->update('rep_robj_xesr_usage',
			array(
				'edus_uri' => array('text', $this->getUri()),
				'is_online' => array('integer', $this->getOnline()),
				'object_version' => array('text', $this->getObjectVersion()),
				'object_version_use_exact' => array('integer', $this->getObjectVersionUseExact()),
				'version_restricted' => array('integer', $this->getVersionRestricted()),
				'timemodified' => array('timestamp', date('Y-m-d H:i:s'))
			),
			array(
				'id' => array('integer', $this->getId()),
				'parent_obj_id' => array('integer', $this->getUpperCourse())
			)
		);

//		return true;
	}

	protected function doClone($new_obj, $a_target_id, $a_copy_id) : void
	{

		global $tree;
		$parent_ref_id = $tree->getParentId($new_obj->getRefId());
		$course_id = ilObject::_lookupObjId($parent_ref_id);
		if (empty($new_obj->course)) {
			//$new_obj->course = $course_id;
		}
		$new_obj->setUri($this->getUri());
		$new_obj->setObjectVersion($this->getObjectVersion());
		$new_obj->setObjectVersionUseExact($this->getObjectVersionUseExact());
		$new_obj->setVersionRestricted($this->getVersionRestricted());
		$new_obj->setOnline($this->getOnline());

		global $DIC;
		$db = $DIC->database();
		$db->update('rep_robj_xesr_usage',
			array(
				'parent_obj_id' => array('integer', $course_id),
				'edus_uri' => array('text', $this->getUri()),
				'object_version' => array('text', $this->getObjectVersion()),
				'object_version_use_exact' => array('integer', $this->getObjectVersionUseExact()),
				'version_restricted' => array('integer', $this->getVersionRestricted()),
				'is_online' => array('integer', $this->getOnline())//,
				//				'timecreated' => array('timestamp', date('Y-m-d H:i:s')),
				//				'timemodified' => array('timestamp', date('Y-m-d H:i:s'))
			),
			array(
				'id' => array('integer', $new_obj->getId()),
				'parent_obj_id' => array('integer', $new_obj->getId())
			)
		);
		$service = new EduSharingService();
		$checkUsage = $service->addInstance($new_obj);
		if (!$checkUsage) {
			die('usage not successful');
		}
	}

	/**
	 * Delete data from db
	 */
	protected function doDelete() : void
	{
		global $DIC;
		//check Verknüpfungen; ToDo simplify
		// deleteAllUsages()
		$query = "SELECT edus_uri, parent_obj_id FROM rep_robj_xesr_usage " .
			" WHERE id = " . $DIC->database()->quote($this->getId(), "integer");
		$result = $DIC->database()->query($query);
		while ($rec = $result->fetchAssoc()) {
//			edusharing_delete_instance($this->getId(), $rec['edus_uri'], $rec['parent_obj_id']);
			$service = new EduSharingService();
			$service->deleteInstance((string) $rec['edus_uri'], $this->getId(), $rec['parent_obj_id']);
//			$this->deleteUsage($this->getId(), $rec['edus_uri'], $rec['parent_obj_id']);
		}
		$DIC->database()->manipulate("DELETE FROM rep_robj_xesr_usage WHERE " .
			" id = " . $DIC->database()->quote($this->getId(), "integer")
		);
	}

	/**
	 * Lookup uri
	 * @param
	 * @return
	 */
	static function lookupUri($a_id, $a_parent_obj_id)
	{
		global $DIC;
		$set = $DIC->database()->query("SELECT edus_uri FROM rep_robj_xesr_usage " .
			" WHERE id = " . $DIC->database()->quote($a_id, "integer") .
			" AND parent_obj_id = " . $DIC->database()->quote($a_parent_obj_id, "integer")
		);
		$rec = $DIC->database()->fetchAssoc($set);
		return $rec["edus_uri"];
	}

	/**
	 * Do Cloning
	 */
	protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null) : void
	{
		$this->doClone($new_obj, $a_target_id, $a_copy_id);
	}

	/**
	 * Get ticket
	 * @param
	 * @return
	 */
	public function getTicket() : string
	{
		$eduSharingService = new EduSharingService();
		return $eduSharingService->getTicket();
	}

	// /**
	// * Delete usage
	// *
	// * @param
	// * @return
	// */
	// function deleteAllUsages()
	// {
	// // get edu sharing soap client and a ticket
	// // $this->plugin->includeClass("../lib/class.sigSoapClient.php");
	// $this->plugin->includeClass('../lib/class.lib.php');
	// $this->plugin->includeClass("../lib/class.lfEduUsage.php");

	// $usages = lfEduUsage::getUsagesOfObject($this->getId());
	// foreach ($usages as $u)
	// {
	// if ($u["edus_uri"] != "" && $u["crs_ref_id"] > 0)
	// {
	// edusharing_delete_instance($this);
	// }
	// }
	// }

	public function setUsage() : void
	{
//		$this->plugin->includeClass('../lib/class.lib.php');
//		edusharing_add_instance($this);
		$service = new EduSharingService();
		$id = $service->addInstance($this);
		// return true;
	}

	/**
	 * Get upper object
	 */
	public function getUpperCourse() : int
	{
		global $tree;
		$parent_ref_id = $tree->getParentId($this->getRefId());
		$parent_id = ilObject::_lookupObjId($parent_ref_id);
		return $parent_id;
	}

	/**
	 * Check registered usage
	 */
	function checkRegisteredUsage() : bool
	{
		if ($this->getUri() == "") {
			return false;
		}
		global $DIC;
		$db = $DIC->database();
		$query = "SELECT parent_obj_id FROM rep_robj_xesr_usage WHERE id = " . $db->quote($this->getId(), 'integer');
		$result = $db->query($query);
		while ($row = $result->fetchAssoc()) {
			if ($row['parent_obj_id'] == $this->getUpperCourse()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Delete usage
	 */
	public static function deleteUsage($a_obj_id, $a_uri, $a_parent_obj_id) : void
	{
		global $DIC;
		$DIC->database()->manipulate("DELETE FROM rep_robj_xesr_usage " .
			" WHERE id = " . $DIC->database()->quote($a_obj_id, "integer") .
			" AND edus_uri = " . $DIC->database()->quote($a_uri, "text") .
			" AND parent_obj_id = " . $DIC->database()->quote($a_parent_obj_id, "integer")
		);
	}

}
?>
