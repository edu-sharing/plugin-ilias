<?php
include_once("./Services/COPage/classes/class.ilPageComponentPlugin.php");
/**
 * LfEduSharing Page Component Plugin
 */
class ilLfEduSharingPageComponentPlugin extends ilPageComponentPlugin {

	const PLUGIN_ID = "xesp";
	const PLUGIN_NAME = "LfEduSharingPageComponent";
	
	protected $resId = 0;
	protected $edus_uri = '';
	protected $mimetype = '';
	public $object_version = 0;
	protected $object_version_use_exact = 0;
	protected $window_float = 'no';
	protected $window_width_org = 200;
	protected $window_height_org = 100;
	public $window_width = 200;
	public $window_height = 100;
	
	
	/**
	 * @var ilLfEduSharingPageComponentPlugin
	 */
	// protected static $instance = NULL;


	// /**
	 // * @return ilLfEduSharingPageComponentPlugin
	 // */
	// public static function getInstance() {
		// if (self::$instance === NULL) {
			// self::$instance = new self();
		// }

		// return self::$instance;
	// }


	/**
	 *
	 */
	// public function __construct() {
		// parent::__construct();
	// }


	/**
	 * @return string
	 */
	public function getPluginName() {
		return self::PLUGIN_NAME;
	}


	/**
	 * @param string $a_type
	 *
	 * @return bool
	 */
	function isValidParentType($a_parent_type) {
		return true;
	}

	/**
	 * @return bool
	 */
	protected function beforeUninstall() {
		// Nothing to delete
		return true;
	}
	
	/**
	 * Set URI
	 *
	 * @param string $a_val uri	
	 */
	function setUri($a_val)
	{
		$this->edus_uri = $a_val;
	}
	
	/**
	 * Get URI
	 *
	 * @return string uri
	 */
	function getUri()
	{
		return $this->edus_uri;
	}
	
	
	function getUpperCourse()
	{
		return ilObject::_lookupObjectId($_GET['ref_id']);//CRS if possible? UK
	}

	function getObjID() {
		return ilObject::_lookupObjectId($_GET['ref_id']);
	}
		/**
	 * Set resource_id
	 * 
	 * @param string $a_val resId	
	 */
	function setResId($a_val)
	{
		$this->resId = $a_val;
	}

	function getResId()
	{
		return $this->resId;
	}
	
	// function setRefId($a_id)
	// {
		// $this->pc_id = $a_id;
	// }



	function setMimetype($a_val)
	{
		$this->mimetype = $a_val;
	}

	function getMimetype()
	{
		return $this->mimetype;
	}


	function setObjectVersion($a_val)
	{
		$this->object_version = $a_val;
	}

	function getObjectVersion()
	{
		return $this->object_version;
	}

	
	function setObjectVersionUseExact($a_val)
	{
		$this->object_version_use_exact = $a_val;
	}

	function getObjectVersionUseExact()
	{
		return $this->object_version_use_exact;
	}


	function getObjectVersionForUse()
	{
		if ($this->object_version_use_exact == 0) return 0;
		else return $this->object_version;
	}

	
	function setWindowFloat($a_val)
	{
		if ($a_val != 'left' && $a_val != 'right') $a_val = 'no';
		$this->window_float = $a_val;
	}

	function getWindowFloat()
	{
		return $this->window_float;
	}


	function setWindowWidthOrg($a_val)
	{
		$this->window_width_org = $a_val;
	}

	function getWindowWidthOrg()
	{
		return $this->window_width_org;
	}


	function setWindowHeightOrg($a_val)
	{
		$this->window_height_org = $a_val;
	}

	function getWindowHeightOrg()
	{
		return $this->window_height_org;
	}


	function setWindowWidth($a_val)
	{
		$this->window_width = $a_val;
	}

	function getWindowWidth()
	{
		return $this->window_width;
	}


	function setWindowHeight($a_val)
	{
		$this->window_height = $a_val;
	}

	function getWindowHeight()
	{
		return $this->window_height;
	}




	
	/**
	 * Save new additional data
	 * @return integer ILIAS-id of Resource
	 */
	public function addUsage($edus_uri) {
		global $DIC;
		$db = $DIC->database();
		
		$id = $db->nextId('rep_robj_xesp_usage');
		$db->insert('rep_robj_xesp_usage',
			array(
				'id'			=> array('integer', $id),
				'edus_uri'		=> array('text', $edus_uri),
				'obj_id' 		=> array('integer', $this->getObjID()),
				'timecreated'	=> array('timestamp', date('Y-m-d H:i:s')),
				'timemodified'	=> array('timestamp', date('Y-m-d H:i:s'))
			)
		);
		return $id;
	}
	
	public function updateUsage($id) {
		global $DIC;
		$db = $DIC->database();

		$db->update('rep_robj_xesp_usage',
			array(
				'edus_uri' 			=> array('text', $this->getUri()),
				'mimetype' 			=> array('text', $this->getMimetype()),
				'object_version'	=> array('text', $this->getObjectVersion()),
				'object_version_use_exact'	=> array('integer', $this->getObjectVersionUseExact()),
				'window_float' 		=> array('text', $this->getWindowFloat()),
				'window_width_org' 	=> array('integer', $this->getWindowWidthOrg()),
				'window_height_org'	=> array('integer', $this->getWindowHeightOrg()),
				'window_width' 		=> array('integer', $this->getWindowWidth()),
				'window_height'		=> array('integer', $this->getWindowHeight()),
				'timemodified'	=> array('timestamp', date('Y-m-d H:i:s'))
			),
			array(
				'id' => array('integer', $id)
			)
		);
		return true;
	}
		
	public function setVars($id) {
		global $DIC;
		$this->setResId($id);
		$org_obj = 0;
		$db = $DIC->database();
		$query = "SELECT * FROM rep_robj_xesp_usage WHERE id = " . $db->quote($id, 'integer');
		$result = $db->query($query);
		while (($row = $result->fetchAssoc()) !== false) {
			$this->setUri($row['edus_uri']);
			$this->setMimetype($row['mimetype']);
			$this->setObjectVersion($row['object_version']);
			$this->setObjectVersionUseExact($row['object_version_use_exact']);
			$this->setWindowFloat($row['window_float']);
			$this->setWindowWidthOrg($row['window_width_org']);
			$this->setWindowHeightOrg($row['window_height_org']);
			$this->setWindowWidth($row['window_width']);
			$this->setWindowHeight($row['window_height']);
			$org_obj = $row['obj_id'];
			// $this->set($row['timecreated']);
			// $this->set($row['timemodified']);
		}
		if ($org_obj != $this->getObjID()) {
			$this->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lib.php');
			edusharing_add_instance($this);
		}
	}
	

	public function getCounter($id) {
		global $DIC;
		$db = $DIC->database();
		$counter = -1;
		$page_component_contents_in_use = []; //notwendig weil onDelete in 5.2 nicht funktioniert
		$query = "SELECT content,parent_type,page_id FROM page_object WHERE parent_id = " .$db->quote($this->getObjID(), 'integer');
		$result = $db->query($query);
		while (($page_component = $result->fetchAssoc()) !== false) {

			$page_obj = ilPageObjectFactory::getInstance($page_component["parent_type"], $page_component["page_id"]);
			$page_obj->buildDom();
			$page_obj->addHierIDs();

			foreach ($page_obj->getHierIds() as $hier_id) {
				try {
					$content_obj = $page_obj->getContentObject($hier_id);

					if ($content_obj instanceof ilPCPlugged) {
						$properties = $content_obj->getProperties();

						if (isset($properties["resId"])) {
							$page_component_contents_in_use[] = $properties["resId"];
						};
					}
				} catch (Exception $ex) {
				}
			}
		}
		if ($page_component_contents_in_use[0] == $id) $counter = 0;
		return $counter;
	}
	
	
	// public function addInstanceAfterCopy() {
		// global $DIC;
		// $db = $DIC->database();
		// $page_component_contents_in_use = []; //notwendig weil onDelete in 5.2 nicht funktioniert
		// $query = "SELECT content,parent_type,page_id FROM page_object WHERE parent_id = " .$db->quote($this->getObjID(), 'integer');
		// $result = $db->query($query);
		// while (($page_component = $result->fetchAssoc()) !== false) {

			// $page_obj = ilPageObjectFactory::getInstance($page_component["parent_type"], $page_component["page_id"]);
			// $page_obj->buildDom();
			// $page_obj->addHierIDs();

			// foreach ($page_obj->getHierIds() as $hier_id) {
				// try {
					// $content_obj = $page_obj->getContentObject($hier_id);

					// if ($content_obj instanceof ilPCPlugged) {
						// $properties = $content_obj->getProperties();

						// if (isset($properties["resId"])) {
							// $page_component_contents_in_use[] = $properties["resId"];
						// };
					// }
				// } catch (Exception $ex) {
				// }
			// }
		// }
		// foreach ($page_component_contents_in_use as $resId) {
			// $pc = new ilLfEduSharingPageComponentPlugin();
			// $pc->setVars($resId);
			// $pc->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lib.php');
			// edusharing_add_instance($pc);
		// }

	// }
	
	/**
	 * This function is called before the page content is deleted (ILIAS 5.3!)
	 * @param array 	$a_properties		properties saved in the page (will be deleted afterwards)
	 * @param string	$a_plugin_version	plugin version of the properties
	 */
	public function onDelete($a_properties, $a_plugin_version)
	{
		global $DIC;
		$db = $DIC->database();
		$query = "DELETE FROM rep_robj_xesp_usage WHERE id = " .$db->quote($a_properties['resId'], 'integer');
		$db->manipulate($query);
	}





}
