<?php
include_once("./Services/COPage/classes/class.ilPageComponentPlugin.php");
/**
 * LfEduSharing Page Component Plugin
 */
class ilLfEduSharingPageComponentPlugin extends ilPageComponentPlugin
{

    public const PLUGIN_ID = "xesp";
    public const PLUGIN_NAME = "LfEduSharingPageComponent";

    protected int $resId = 0;
    protected string $edus_uri = '';
    protected string $mimetype = '';
    public string $object_version = '0';
    protected int $object_version_use_exact = 0;
    protected string $window_float = 'no';
    protected int $window_width_org = 200;
    protected int $window_height_org = 100;
    public int $window_width = 200;
    public int $window_height = 100;

//	/**
//	 * @var ilLfEduSharingPageComponentPlugin
//	 */
//	protected static $instance = NULL;

    // /**
    // * @return ilLfEduSharingPageComponentPlugin
    // */
    // public static function getInstance() {
    // if (self::$instance === NULL) {
    // self::$instance = new self();
    // }

    // return self::$instance;
    // }

    // public function __construct() {
    // parent::__construct();
    // }

    /**
     * @return string
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @param string $a_type
     * @return bool
     */
    function isValidParentType($a_parent_type) : bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function beforeUninstall() : bool
    {
        // Nothing to delete
        return true;
    }

    public function setUri(string $a_val) : void
    {
        $this->edus_uri = $a_val;
    }

    function getUri() : string
    {
        return $this->edus_uri;
    }

    public function getUpperCourse() : int
    {
        global $tree;
        $parent_ref_id = $tree->getParentId($this->getRefId());
        $parent_id = ilObject::_lookupObjId($parent_ref_id);
        return $parent_id;
    }

    public function getObjID() : int
    {
        global $DIC;
        return ilObject::_lookupObjectId($DIC->http()->wrapper()->query()->retrieve('ref_id',
            $DIC->refinery()->kindlyTo()->int()));
//        return ilObject::_lookupObjectId($_GET['ref_id']);
    }

    /**
     * Set resource_id
     */
    public function setResId(int $a_val) : void
    {
        $this->resId = $a_val;
    }

    public function getResId() : int
    {
        return $this->resId;
    }

    public function getRefId() : int
    {
        global $DIC;
        return $DIC->http()->wrapper()->query()->retrieve('ref_id', $DIC->refinery()->kindlyTo()->int());
//        return $_GET['ref_id'];
    }

    public function setMimetype(string $a_val) : void
    {
        $this->mimetype = $a_val;
    }

    public function getMimetype() : string
    {
        return $this->mimetype;
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

    public function getObjectVersionForUse() : string
    {
        if ($this->object_version_use_exact == 0) {
            return 0;
        } else {
            return $this->object_version;
        }
    }

    public function setWindowFloat(string $a_val) : void
    {
        if ($a_val != 'left' && $a_val != 'right') {
            $a_val = 'no';
        }
        $this->window_float = $a_val;
    }

    public function getWindowFloat() : string
    {
        return $this->window_float;
    }

    public function setWindowWidthOrg(int $a_val) : void
    {
        $this->window_width_org = $a_val;
    }

    public function getWindowWidthOrg() : int
    {
        return $this->window_width_org;
    }

    public function setWindowHeightOrg(int $a_val) : void
    {
        $this->window_height_org = $a_val;
    }

    public function getWindowHeightOrg() : int
    {
        return $this->window_height_org;
    }

    public function setWindowWidth(int $a_val) : void
    {
        $this->window_width = $a_val;
    }

    public function getWindowWidth() : int
    {
        return $this->window_width;
    }

    public function setWindowHeight($a_val) : void
    {
        $this->window_height = $a_val;
    }

    public function getWindowHeight() : int
    {
        return $this->window_height;
    }

    /**
     * Save new additional data
     * @return integer ILIAS-id of Resource
     */
    public function addUsage(string $edus_uri) : int
    {
        global $DIC;
        $db = $DIC->database();

        $id = $db->nextId('rep_robj_xesp_usage');
        $db->insert('rep_robj_xesp_usage',
            array(
                'id' => array('integer', $id),
                'edus_uri' => array('text', $edus_uri),
                'obj_id' => array('integer', $this->getObjID()),
                'timecreated' => array('timestamp', date('Y-m-d H:i:s')),
                'timemodified' => array('timestamp', date('Y-m-d H:i:s'))
            )
        );
        return $id;
    }

    public function updateUsage(int $id) : bool
    {
        global $DIC;
        $db = $DIC->database();

        $db->update('rep_robj_xesp_usage',
            array(
                'edus_uri' => array('text', $this->getUri()),
                'mimetype' => array('text', $this->getMimetype()),
                'object_version' => array('text', $this->getObjectVersion()),
                'object_version_use_exact' => array('integer', $this->getObjectVersionUseExact()),
                'window_float' => array('text', $this->getWindowFloat()),
                'window_width_org' => array('integer', $this->getWindowWidthOrg()),
                'window_height_org' => array('integer', $this->getWindowHeightOrg()),
                'window_width' => array('integer', $this->getWindowWidth()),
                'window_height' => array('integer', $this->getWindowHeight()),
                'timemodified' => array('timestamp', date('Y-m-d H:i:s'))
            ),
            array(
                'id' => array('integer', $id)
            )
        );
        return true;
    }

    public function setVars(int $id)
    {
        global $DIC;
        $this->setResId($id);
        $org_obj = 0;
        $db = $DIC->database();
        $query = "SELECT * FROM rep_robj_xesp_usage WHERE id = " . $db->quote($id, 'integer');
        $result = $db->query($query);
        while ($row = $result->fetchAssoc()) {
            if (isset($row['edus_uri'])) {
                $this->setUri((string) $row['edus_uri']);
                $this->setMimetype((string) $row['mimetype']);
                $this->setObjectVersion((string) $row['object_version']);
                $this->setObjectVersionUseExact((int) $row['object_version_use_exact']);
                $this->setWindowFloat((string) $row['window_float']);
                $this->setWindowWidthOrg((int) $row['window_width_org']);
                $this->setWindowHeightOrg((int) $row['window_height_org']);
                $this->setWindowWidth((int) $row['window_width']);
                $this->setWindowHeight((int) $row['window_height']);
                $org_obj = (int) $row['obj_id'];
                // $this->set($row['timecreated']);
                // $this->set($row['timemodified']);
            }
        }
        if ($this->getUri() !== null && $org_obj != $this->getObjID()) {
            $service = new EduSharingService();
            $eduObj = new ilObjLfEduSharingResource();
//            $eduObj->containerId = $this->getUpperCourse();//deprecated
            $eduObj->setUri($this->getUri());
            $eduObj->setId($id);
            $eduObj->setRefId($this->getRefId());
            $usageResult = $service->addInstance($eduObj);
        }
    }

    public function getCounter($id) : int
    {
        global $DIC;
        $db = $DIC->database();
        $counter = -1;
        $page_component_contents_in_use = []; //notwendig weil onDelete in 5.2 nicht funktioniert
        $query = "SELECT content,parent_type,page_id FROM page_object WHERE parent_id = " . $db->quote($this->getObjID(),
                'integer');
        $result = $db->query($query);
        while ($page_component = $result->fetchAssoc()) {

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
        if ($page_component_contents_in_use[0] == $id) {
            $counter = 0;
        }
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
     * @param array  $a_properties     properties saved in the page (will be deleted afterwards)
     * @param string $a_plugin_version plugin version of the properties
     */
    public function onDelete(array $a_properties, string $a_plugin_version, bool $move_operation = false) : void
    {
        global $DIC;
        $db = $DIC->database();
        $query = "DELETE FROM rep_robj_xesp_usage WHERE id = " . $db->quote($a_properties['resId'], 'integer');
        $db->manipulate($query);
    }

}
