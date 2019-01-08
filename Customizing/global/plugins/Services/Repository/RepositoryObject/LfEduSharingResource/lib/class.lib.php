<?php
/**
 * Library of interface functions and constants for module edusharing
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the edusharing specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * modifications for ILIAS by Uwe Kohnle
 */

define('EDUSHARING_MODULE_NAME', 'edusharing');
define('EDUSHARING_TABLE', 'edusharing');

define('EDUSHARING_DISPLAY_MODE_DISPLAY', 'window');
define('EDUSHARING_DISPLAY_MODE_INLINE', 'inline');

// set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) .'/lib');
require_once('class.RenderParameter.php');//dirname(__FILE__).
require_once('class.cclib.php');
require_once('class.locallib.php');

/**
 * If you for some reason need to use global variables instead of constants, do not forget to make them
 * global as this file can be included inside a function scope. However, using the global variables
 * at the module level is not a recommended.
 */


/**
 * Given an object containing all the necessary data,
 * this function will create a new instance and return 
 * the id number of the new instance.
 *
 * @param $edusharing
 * @return int The id of the newly inserted edusharing record
 */
function edusharing_add_instance($edusharing) {

    // global $COURSE, $CFG, $DB, $SESSION, $USER;
	global $tree, $DIC;
	$settings = new ilSetting("xedus");
	// require_once 'class.locallib.php';
	include_once("class.lfEduUsage.php");

	// since this is called during update we must read the uri from db
	$uri = $edusharing->getUri();

	// course id is parent ID object in ILIAS tree
	$course_id = $edusharing->getUpperCourse();
	if ($course_id == 0)
	{
		ilLoggerFactory::getLogger('xesr')->warning('set usage: no upper object ref id given.');
		ilUtil::sendFailure('set usage: no upper object ref id given.');
	}

    $edusharing->timecreated = time();
    $edusharing->timemodified = time();

    // You may have to add extra stuff in here.
    $edusharing = edusharing_postprocess($edusharing);

/*    // Put the data of the new cc-resource into an array and create a neat XML-file out of it.
    $data4xml = array("ccrender");

    // if (isset($edusharing->object_version)) {
        // if ($edusharing->object_version == 1) {
            // $updateversion = true;
            // $edusharing->object_version = '';
        // } else {
            // $edusharing->object_version = 0;
        // }
    // } else {

        // if (isset($edusharing->window_versionshow) && $edusharing->window_versionshow == 'current') {
            // $edusharing->object_version = $edusharing->window_version;
        // } else {
            // $edusharing->object_version = 0;
        // }
    // }

    $data4xml[1]["ccuser"]["id"] = edusharing_get_auth_key();//UK
    $data4xml[1]["ccuser"]["name"] = $DIC->user()->getFirstname()." ".$DIC->user()->getLastname();
    $data4xml[1]["ccserver"]["ip"] = $settings->get('application_host');
    $data4xml[1]["ccserver"]["hostname"] = $_SERVER['SERVER_NAME'];
    $data4xml[1]["ccserver"]["mnet_localhost_id"] = 1;//$CFG->mnet_localhost_id;
    $data4xml[1]["metadata"] = edusharing_get_usage_metadata($course_id);//Datenschutz? ilObjLfEduSharingResource 

    // Move popup settings to array.
    if (!empty($edusharing->popup)) {
        $parray = explode(',', $edusharing->popup);
        foreach ($parray as $key => $fieldstring) {
            $field = explode('=', $fieldstring);
            $popupfield->$field[0] = $field[1];
        }
    }

    // loop trough the list of keys... get the value... put into XML
    $keylist = array('resizable', 'scrollbars', 'directories', 'location', 'menubar', 'toolbar', 'status', 'width', 'height');
    foreach ($keylist as $key) {
        $data4xml[1]["ccwindow"][$key] = isSet($popupfield->{$key}) ? $popupfield->{$key} : 0;
    }

    $data4xml[1]["ccwindow"]["forcepopup"] = isSet($edusharing->popup_window) ? 1 : 0;
    $data4xml[1]["ccdownload"]["download"] = isSet($edusharing->force_download) ? 1 : 0;
	// $data4xml[1]["cctracking"]["tracking"] = ($edusharing->tracking == 0) ? 0 : 1;

    $myxml  = new mod_edusharing_render_parameter();
    $xml = $myxml->edusharing_get_xml($data4xml);
*/
    // $id = $DB->insert_record(EDUSHARING_TABLE, $edusharing);//UK

    $soapclientparams = array();

    $client = new SigSoapClient($settings->get('repository_usagewebservice_wsdl'), $soapclientparams);
	$xml = edusharing_get_usage_xml($edusharing);
    try {

        session_write_close(); //check UK

        $params = array(
            "eduRef"  => $edusharing->getUri(),
            "user"  => edusharing_get_auth_key(),
            "lmsId"  => $settings->get('application_appid'),
            "courseId"  => $course_id,
            "userMail"  => $DIC->user()->getEmail(),
            "fromUsed"  => '2002-05-30T09:00:00',
            "toUsed"  => '2222-05-30T09:00:00',
            "distinctPersons"  => '0',
            "version"  => $edusharing->getObjectVersionForUse(),
            "resourceId"  => $edusharing->getResId(),//?was getRefid()
            "xmlParams"  => $xml,
        );

        $setusage = $client->setUsage($params);

		// // add usage to db
		// try {
		// lfEduUsage::addUsage($edusharing->getId(), $uri, $course_id);
		// } catch (Exception $e){}

		if ($edusharing->getObjectVersion() == 0) {
			$edusharing->setObjectVersion($setusage->setUsageReturn->usageVersion);
		}
        // if (isset($updateversion) && $updateversion === true) {
            // $edusharing->object_version = $setusage->setUsageReturn->usageVersion;
            // $edusharing->getId() = $id;
            // $DB->update_record(EDUSHARING_TABLE, $edusharing); //UK
        // }
		ilLoggerFactory::getLogger('xesr')->info("Calling setUsage() with: ".print_r($params, true));

    } catch (Exception $e) {
		ilUtil::sendFailure($e->getMessage());
		// throw ($e->getMessage());
        return false;
    }
    return $edusharing->getResId();
}

/**
 * Given an object containing all the necessary data, this function
 * will update an existing instance with new data.
 *
 * @param $edusharing
 * @return boolean Success/Fail
 */
// function edusharing_update_instance(stdClass $edusharing) {

    // global $CFG, $COURSE, $DB, $SESSION, $USER;

    // // FIX: when editing a moodle-course-module the $edusharing->id will be named $edusharing->instance
    // if ( ! empty($edusharing->instance) ) {
        // $edusharing->id = $edusharing->instance;
    // }

    // $edusharing->timemodified = time();

    // // Load previous state.
    // $memento = $DB->get_record(EDUSHARING_TABLE, array('id'  => $edusharing->id));
    // if ( ! $memento ) {
        // throw new Exception(get_string('error_loading_memento', 'edusharing'));
    // }

    // // You may have to add extra stuff in here.
    // $edusharing = edusharing_postprocess($edusharing);
/*
    // // Put the data of the new cc-resource into an array and create a neat XML-file out of it.
    // $data4xml = array("ccrender");

    // $data4xml[1]["ccuser"]["id"] = edusharing_get_auth_key();
    // $data4xml[1]["ccuser"]["name"] = $USER->firstname." ".$USER->lastname;
    // $data4xml[1]["ccserver"]["ip"] = get_config('edusharing', 'application_host');
    // $data4xml[1]["ccserver"]["hostname"] = $_SERVER['SERVER_NAME'];
    // $data4xml[1]["ccserver"]["mnet_localhost_id"] = $CFG->mnet_localhost_id;
    // $data4xml[1]["metadata"] = edusharing_get_usage_metadata($edusharing->course);

    // // Move popup settings to array.
    // if (!empty($edusharing->popup)) {
        // $parray = explode(',', $edusharing->popup);
        // foreach ($parray as $key => $fieldstring) {
            // $field = explode('=', $fieldstring);
            // $popupfield->$field[0] = $field[1];
        // }
    // }
    // // Loop trough the list of keys... get the value... put into XML.
    // $keylist = array('resizable', 'scrollbars', 'directories', 'location', 'menubar', 'toolbar', 'status', 'width', 'height');
    // foreach ($keylist as $key) {
        // $data4xml[1]["ccwindow"][$key] = isSet($popupfield->{$key}) ? $popupfield->{$key} : 0;
    // }

    // $data4xml[1]["ccwindow"]["forcepopup"] = isSet($edusharing->popup_window) ? 1 : 0;
    // $data4xml[1]["ccdownload"]["download"] = isSet($edusharing->force_download) ? 1 : 0;
    // $data4xml[1]["cctracking"]["tracking"] = ($edusharing->tracking == 0) ? 0 : 1;

    // $myxml = new mod_edusharing_render_parameter();
    // $xml = $myxml->edusharing_get_xml($data4xml);
*/
	// $xml = edusharing_get_usage_xml($edusharing);
    // try {
        // $connectionurl = get_config('edusharing', 'repository_usagewebservice_wsdl');
        // if (!$connectionurl) {
            // trigger_error(get_string('error_missing_usagewsdl', 'edusharing'), E_USER_WARNING);
        // }

        // $client = new mod_edusharing_sig_soap_client($connectionurl, array());

        // $params = array(
            // "eduRef"  => $edusharing->object_url,
            // "user"  => edusharing_get_auth_key(),
            // "lmsId"  => get_config('edusharing', 'application_appid'),
            // "courseId"  => $edusharing->course,
            // "userMail"  => $USER->email,
            // "fromUsed"  => '2002-05-30T09:00:00',
            // "toUsed"  => '2222-05-30T09:00:00',
            // "distinctPersons"  => '0',
            // "version"  => $memento->object_version,
            // "resourceId"  => $edusharing->id,
            // "xmlParams"  => $xml,
        // );

        // $setusage = $client->setUsage($params);
        // $edusharing->object_version = $memento->object_version;
        // // Throws exception on error, so no further checking required.
        // $DB->update_record(EDUSHARING_TABLE, $edusharing);
    // } catch (SoapFault $exception) {
        // // Roll back.
        // $DB->update_record(EDUSHARING_TABLE, $memento);

        // trigger_error($exception->getMessage(), E_USER_WARNING);

        // return false;
    // }

    // return true;
// }

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $edusharing
 * @return boolean Success/Failure
 */
// replaces function deleteUsage($a_edu_obj, $a_uri, $a_course_ref_id)
function edusharing_delete_instance($a_edu_obj, $a_uri, $a_parent_obj_id) {
	$settings = new ilSetting("xedus");
    // Load from DATABASE to get object-data for repository-operations.
    // if (! $edusharing = $DB->get_record(EDUSHARING_TABLE, array('id'  => $id))) {
        // throw new Exception(get_string('error_load_resource', 'edusharing'));
    // }
	// since this is called during update we must read the uri from db
	// course id is parent ID object object in ILIAS tree
	// $course_id = $edusharing->getUpperCourse();
	// if ($course_id == 0)
	// {
		// include_once("class.ilLfEduSharingLibException.php");
		// throw new ilLfEduSharingLibException("set usage: no upper course ref id given.");
	// }

    try {

        $connectionurl = $settings->get('repository_usagewebservice_wsdl');
        if ( ! $connectionurl ) {
			ilLoggerFactory::getLogger('xesr')->warning('error_missing_usagewsdl');
			ilUtil::sendFailure('error: missing usagewsdl');
        }

        $ccwsusage = new SigSoapClient($connectionurl, array());

        $params = array(
           'eduRef'  => $a_uri,
           'user'  => edusharing_get_auth_key(),
           'lmsId'  => $settings->get('application_appid'),
           'courseId'  => $a_parent_obj_id,
           'resourceId'  => $a_edu_obj
        );

        $ccwsusage->deleteUsage($params);

    } catch (Exception $exception) {
		ilLoggerFactory::getLogger('xesr')->warning($exception->getMessage());
        // trigger_error($exception->getMessage(), E_USER_WARNING);
    }

    // Usage is removed so it can be deleted from DATABASE .
	$edusharing->deleteUsage($a_edu_obj, $a_uri, $a_parent_obj_id);

    return true;

}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $edusharing
 *
 * @return stdClass
 */
// function edusharing_user_outline($course, $user, $mod, $edusharing) {

    // $return = new stdClass;

    // $return->time = time();
    // $return->info = 'edusharing_user_outline() - edu-sharing activity outline.';

    // return $return;
// }

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $edusharing
 *
 * @return boolean
 */
// function edusharing_user_complete($course, $user, $mod, $edusharing) {
    // return true;
// }

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in edusharing activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course
 * @param object $isteacher
 * @param object $timestart
 *
 * @return boolean
 */
// function edusharing_print_recent_activity($course, $isteacher, $timestart) {
    // return false; // True if anything was printed, otherwise false
// }

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 **/
// function edusharing_cron() {
    // return true;
// }

/**
 * Must return an array of users who are participants for a given instance
 * of edusharing. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $edusharingid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
// function edusharing_get_participants($edusharingid) {
    // return false;
// }

/**
 * This function returns if a scale is being used by one edusharing
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $edusharingid ID of an instance of this module
 * @param int $scaleid
 * @return mixed
 */
// function edusharing_scale_used($edusharingid, $scaleid) {
    // global $DB;

    // $return = false;
    // return $return;
// }

/**
 * Checks if scale is being used by any instance of edusharing.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid
 * @return boolean True if the scale is used by any edusharing
 */
// function edusharing_scale_used_anywhere($scaleid) {
    // global $DB;

    // return false;
// }

/**
 * Execute post-install actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
// function edusharing_install() {
    // return true;
// }

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
// function edusharing_uninstall() {
    // return true;
// }

/**
 * Moodle will cache the outpu of this method, so it gets only called after
 * adding or updating an edu-sharing-resource, NOT every time the course
 * is shown.
 *
 * @param stdClass $coursemodule
 *
 * @return stdClass
 */
// function edusharing_get_coursemodule_info($coursemodule) {
    // global $CFG;
    // global $DB;

    // $info = new cached_cm_info();

    // $resource = $DB->get_record(EDUSHARING_TABLE, array('id'  => $coursemodule->instance));
    // if ( ! $resource ) {
        // trigger_error(get_string('error_load_resource', 'edusharing'), E_USER_WARNING);
    // }

    // if (!empty($resource->popup_window)) {
        // $info->onclick = 'this.target=\'_blank\';';
    // }

    // return $info;
// }

/**
 * Normalize form-values ...
 *
 * @param stdclass $edusharing
 *
 * @return stdClass
 *
 */
function edusharing_postprocess($edusharing) {
    // global $CFG;
    // global $COURSE;
	global $tree;

    if ( empty($edusharing->timecreated) ) {
        $edusharing->timecreated = time();
    }

    $edusharing->timeupdated = time();

    if (!empty($edusharing->force_download)) {
        $edusharing->force_download = 1;
        $edusharing->popup_window = 0;
    } else if (!empty($edusharing->popup_window)) {
        $edusharing->force_download = 0;
        $edusharing->options = '';
    } else {
        if (empty($edusharing->blockdisplay)) {
            $edusharing->options = '';
        }

        $edusharing->popup_window = '';
    }

    $edusharing->tracking = empty($edusharing->tracking) ? 0 : $edusharing->tracking;

    if ( ! $edusharing->course ) {
        $edusharing->course = $edusharing->getUpperCourse();
		// if ($edusharing->course == 0) {
			// include_once("class.ilLfEduSharingLibException.php");
			// throw new ilLfEduSharingLibException("set usage: no upper course ref id given.");
		// }
    }

    return $edusharing;
}

/**
 * Get the object-id from object-url.
 * E.g. "abc-123-xyz-456789" for "ccrep://homeRepository/abc-123-xyz-456789"
 *
 * @param string $objecturl
 * @throws Exception
 * @return string
 */
function edusharing_get_object_id_from_url($objecturl) {
    $objectid = parse_url($objecturl, PHP_URL_PATH);
    if ( ! $objectid ) {
		ilLoggerFactory::getLogger('xesr')->debug('error_get_object_id_from_url');
        return false;
    }

    $objectid = str_replace('/', '', $objectid);

    return $objectid;
}

/**
 * Get the repository-id from object-url.
 * E.g. "homeRepository" for "ccrep://homeRepository/abc-123-xyz-456789"
 *
 * @param string $objecturl
 * @throws Exception
 * @return string
 */
function edusharing_get_repository_id_from_url($objecturl) {
    $repid = parse_url($objecturl, PHP_URL_HOST);
    if ( ! $repid ) {
		ilLoggerFactory::getLogger('xesr')->warning('error_get_repository_id_from_url');
    }

    return $repid;
}


/**
 * Get additional usage information
 *
 * @param stdClass $edusharing
 * @return string
 */
function edusharing_get_usage_xml($edusharing) {
    global $tree, $DIC;

	$settings = new ilSetting("xedus");
	
	// course id is parent ID object in ILIAS tree
	$course_id = $edusharing->getUpperCourse();
	if ($course_id == 0)
	{
		ilLoggerFactory::getLogger('xesr')->warning('set usage: no upper object ref id given.');
		ilUtil::sendFailure('set usage: no upper object ref id given.');
	}
	
	if(version_compare($settings->get('repository_version'), '4.1' ) <= 0) {
		$data4xml = array("ccrender");
		$data4xml[1]["ccuser"]["id"] = edusharing_get_auth_key();//UK
		$data4xml[1]["ccuser"]["name"] = $DIC->user()->getFirstname()." ".$DIC->user()->getLastname();
		$data4xml[1]["ccserver"]["ip"] = $settings->get('application_host');
		$data4xml[1]["ccserver"]["hostname"] = $_SERVER['SERVER_NAME'];
		$data4xml[1]["ccserver"]["mnet_localhost_id"] = 1;//$CFG->mnet_localhost_id;
		$data4xml[1]["metadata"]['courseId'] = $course_id;
		$data4xml[1]["metadata"]['courseFullname'] = '';
		$data4xml[1]["metadata"]['courseShortname'] = '';
		$data4xml[1]["metadata"]['courseSummary'] = ''; //description
		$data4xml[1]["metadata"]['categoryId'] = '';//$course->category;
		$data4xml[1]["metadata"]['categoryName'] = '';//$category->name;
		//		= edusharing_get_usage_metadata($course_id);//Datenschutz?
		// Move popup settings to array.
		if (!empty($edusharing->popup)) {
			$parray = explode(',', $edusharing->popup);
			foreach ($parray as $key => $fieldstring) {
				$field = explode('=', $fieldstring);
				$popupfield->$field[0] = $field[1];
			}
		}

		// loop trough the list of keys... get the value... put into XML
		$keylist = array('resizable', 'scrollbars', 'directories', 'location', 'menubar', 'toolbar', 'status', 'width', 'height');
		foreach ($keylist as $key) {
			$data4xml[1]["ccwindow"][$key] = isSet($popupfield->{$key}) ? $popupfield->{$key} : 0;
		}

		$data4xml[1]["ccwindow"]["forcepopup"] = isSet($edusharing->popup_window) ? 1 : 0;
		$data4xml[1]["ccdownload"]["download"] = isSet($edusharing->force_download) ? 1 : 0;
		// $data4xml[1]["cctracking"]["tracking"] = ($edusharing->tracking == 0) ? 0 : 1;
		// //call with Thorsten 18-12-03
		// $data4xml[1]["general"]['referencedInName'] = '';//$course->fullname;
		// $data4xml[1]["general"]['referencedInType'] = $course_id;
		// $data4xml[1]["general"]['referencedInInstance'] = $_SERVER['SERVER_NAME'];
	} else { //4.2
		$data4xml = array("usage");

		$data4xml[1]["general"]['referencedInName'] = '';//$course->fullname;
		$data4xml[1]["general"]['referencedInType'] = $course_id;
		$data4xml[1]["general"]['referencedInInstance'] = $_SERVER['SERVER_NAME'];

		$data4xml[1]["specific"]['type'] = 'ILIAS';
		$data4xml[1]["specific"]['courseId'] = $course_id;
		$data4xml[1]["specific"]['courseFullname'] = '';//$course->fullname;
		$data4xml[1]["specific"]['courseShortname'] = '';//$course->shortname;
		$data4xml[1]["specific"]['courseSummary'] = '';//$course->summary;
		$data4xml[1]["specific"]['categoryId'] = '';//$course->category;
		$data4xml[1]["specific"]['categoryName'] = '';//$category->name;
	}
    $myxml  = new mod_edusharing_render_parameter();
    $xml = $myxml->edusharing_get_xml($data4xml);
    return $xml;
}
