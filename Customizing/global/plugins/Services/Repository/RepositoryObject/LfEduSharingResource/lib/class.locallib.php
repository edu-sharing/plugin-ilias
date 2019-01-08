<?php
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//

/**
 * Internal library of functions for module edusharing
 *
 * All the edusharing specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('class.lib.php');

/**
 * Get the parameter for authentication
 * @return string
 */
function edusharing_get_auth_key() {

    global $DIC;
	$settings = new ilSetting("xedus");

    $guestoption = $settings->get('edu_guest_option');
    if (!empty($guestoption) || $DIC->user()->getId() == 13) { //13=anonymous
        $guestid = $settings->get('edu_guest_guest_id');
        if (empty($guestid)) {
            $guestid = 'esguest';
        }
        return $guestid;
    }

    // Set by external sso script.
    // if ($settings->get('EDU_AUTH_PARAM_NAME_USERID') != 'no' && array_key_exists('sso', $_SESSION) && !empty($_SESSION['sso'])) {
        // $eduauthparamnameuserid = $settings->get('EDU_AUTH_PARAM_NAME_USERID');
        // return $_SESSION['sso'][$eduauthparamnameuserid];
    // }

    $eduauthkey = $settings->get('EDU_AUTH_KEY');

    switch($eduauthkey) {
        case 'id':
            return $DIC->user()->getLogin();
        break;

        case 'idnumber':
            return $DIC->user()->getId();
        break;

        case 'email':
            return $DIC->user()->getEmail();
        break;

        case 'username':
            return $DIC->user()->getFirstname()." ".$DIC->user()->getLastname();//$DIC->user()->getFullname();
			
       case 'ShibbolethUId':
                return $DIC->user()->getExternalAccount();

        case 'ZOERR_Auth':
                global $ilUser;
                $udf = ilUserDefinedFields::_getInstance();
                $udd = $ilUser->getUserDefinedData();
                $udf_data = array();
                foreach ($udd as $fieldId => $value)
                {
                        $udf_data[str_replace('f_', '', $fieldId)] = $value;
                }
                return $udf_data[(int)$udf->fetchFieldIdFromName('ZOERR_Auth')];

		case 'idnumber;http_path;client_id':
        default:
			$iliasDomain = substr(ILIAS_HTTP_PATH,7);
			if (substr($iliasDomain,0,1) == "\/") $iliasDomain = substr($iliasDomain,1);
			if (substr($iliasDomain,0,4) == "www.") $iliasDomain = substr($iliasDomain,4);
			return $DIC->user()->getId().';'.$iliasDomain.';'.CLIENT_ID;
    }
}


/**
 * Return data for authByTrustedApp
 *
 * @return array
 */
function edusharing_get_auth_data() {

    global $DIC;
	$settings = new ilSetting("xedus");
	
	$guestoption = $settings->get('edu_guest_option');
	$eduauthaffiliation = $settings->get('EDU_AUTH_AFFILIATION');
	$eduauthaffiliationname = $settings->get('EDU_AUTH_AFFILIATION_NAME');

	// Keep defaults in sync with settings.php.
	$eduauthparamnameuserid = $settings->get('EDU_AUTH_PARAM_NAME_USERID');
	if (empty($eduauthparamnameuserid)) {
		$eduauthparamnameuserid = '';
	}

	$eduauthparamnamelastname = $settings->get('EDU_AUTH_PARAM_NAME_LASTNAME');
	if (empty($eduauthparamnamelastname)) {
		$eduauthparamnamelastname = '';
	}

	$eduauthparamnamefirstname = $settings->get('EDU_AUTH_PARAM_NAME_FIRSTNAME');
	if (empty($eduauthparamnamefirstname)) {
		$eduauthparamnamefirstname = '';
	}

	$eduauthparamnameemail = $settings->get('EDU_AUTH_PARAM_NAME_EMAIL');
	if (empty($eduauthparamnameemail)) {
		$eduauthparamnameemail = '';
	}


	if (!empty($guestoption) || $DIC->user()->getId() == 13) {//13=anonymous
		$guestid = $settings->get('edu_guest_guest_id');
		if (empty($guestid)) {
			$guestid = 'esguest';
		}

		$authparams = array(
			array('key'  => $eduauthparamnameuserid, 'value'  => $guestid),
			array('key'  => $eduauthparamnamelastname, 'value'  => ''),
			array('key'  => $eduauthparamnamefirstname, 'value'  => ''),
			array('key'  => $eduauthparamnameemail, 'value'  => ''),
			array('key'  => 'affiliation', 'value'  => $eduauthaffiliation),
			array('key'  => 'affiliationname', 'value' => $eduauthaffiliationname)
		);
	}
    // Set by external sso script. Do not change to moodle $SESSION!
    else if ($settings->get('EDU_AUTH_PARAM_NAME_USERID') != 'no' && array_key_exists('sso', $_SESSION) && !empty($_SESSION['sso'])) {
        $authparams = array();
        foreach ($_SESSION['sso'] as $key => $value) {
            $authparams[] = array('key'  => $key, 'value'  => $value);
        }
    } else {
		$authparams = array(
			array('key'  => $eduauthparamnameuserid, 'value'  => edusharing_get_auth_key()),
			array('key'  => $eduauthparamnamelastname, 'value'  => $DIC->user()->lastname),
			array('key'  => $eduauthparamnamefirstname, 'value'  => $DIC->user()->firstname),
			array('key'  => $eduauthparamnameemail, 'value'  => $DIC->user()->email),
			array('key'  => 'affiliation', 'value'  => $eduauthaffiliation),
			array('key'  => 'affiliationname', 'value' => $eduauthaffiliationname)
		);
    }

    if ($settings->get('EDU_AUTH_CONVEYGLOBALGROUPS') == 'yes' ||
            $settings->get('EDU_AUTH_CONVEYGLOBALGROUPS') == '1') {
        $authparams[] = array('key'  => 'globalgroups', 'value'  => $this->edusharing_get_user_cohorts());
    }
    return $authparams;
}

/**
 * Get cohorts the user belongs to
 *
 * @return array
 */
function edusharing_get_user_cohorts() {
    global $DIC;
    $ret = array();
    // $cohortmemberships = $DIC->database()->get_records('cohort_members', array('userid'  => $DIC->user()->getId()));
    // if ($cohortmemberships) {
        // foreach ($cohortmemberships as $cohortmembership) {
            // $cohort = $DIC->database()->get_record('cohort', array('id'  => $cohortmembership->cohortid));
            // if($cohort->contextid == 1)
                // $ret[] = array(
                        // 'id'  => $cohortmembership->cohortid,
                        // 'contextid'  => $cohort->contextid,
                        // 'name'  => $cohort->name,
                        // 'idnumber'  => $cohort->idnumber
                // );
        // }
    // }
    return json_encode($ret);
}

/**
 * Generate redirection-url
 *
 * @param stdClass $edusharing
 * @param string $displaymode
 *
 * @return string
 */

function edusharing_get_redirect_url(
    $edusharing,
    $displaymode = EDUSHARING_DISPLAY_MODE_DISPLAY) {

	global $DIC;
	$settings = new ilSetting("xedus");
	// $course_id = $edusharing->getUpperCourse();
	
    $url = $settings->get('application_cc_gui_url') . '/renderingproxy';

    $url .= '?app_id='.urlencode($settings->get('application_appid'));

    $url .= '&session='.urlencode(session_id());

    $repid = edusharing_get_repository_id_from_url($edusharing->getUri());//object_url
    $url .= '&rep_id='.urlencode($repid);

    $url .= '&obj_id='.urlencode(edusharing_get_object_id_from_url($edusharing->getUri()));//object_url

    $url .= '&resource_id='.urlencode($edusharing->getResId());//->id
    $url .= '&course_id='.urlencode($edusharing->getUpperCourse());
// $url .= '&role=member';
    $url .= '&display='.urlencode($displaymode);

    $url .= '&width='. urlencode($edusharing->window_width);
    $url .= '&height=' . urlencode($edusharing->window_height);
    $url .= '&version=' . urlencode($edusharing->getObjectVersionForUse());//UK
    $url .= '&locale=' . urlencode($DIC->user()->getLanguage()); //repository
    $url .= '&language=' . urlencode($DIC->user()->getLanguage()); //rendering service
// die($url);
    if(version_compare($settings->get('repository_version'), '4.1' ) >= 0) {
        $url .= '&u='. rawurlencode(base64_encode(edusharing_encrypt_with_repo_public(edusharing_get_auth_key())));
		// var_dump(edusharing_get_auth_key().': '.base64_encode(edusharing_encrypt_with_repo_public(edusharing_get_auth_key())));
    } else {
        $eskey = $settings->get('application_blowfishkey');
        $esiv = $settings->get('application_blowfishiv');
        $res = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_CBC, ''); //UK This function has been DEPRECATED as of PHP 7.1.0
        mcrypt_generic_init($res, $eskey, $esiv); //UK This function has been DEPRECATED as of PHP 7.1.0
        $u = base64_encode(mcrypt_generic($res, edusharing_get_auth_key())); //UK mcrypt_generic has been DEPRECATED as of PHP 7.1.0
        mcrypt_generic_deinit($res); //UK mcrypt_generic_deinit has been DEPRECATED as of PHP 7.1.0
        $url .= '&u=' . rawurlencode($u);
    }

    return $url;
}

/**
 * Generate ssl signature
 *
 * @param string $data
 * @return string
 */
function edusharing_get_signature($data) {
	$settings = new ilSetting("xedus");
    $privkey = $settings->get('application_private_key');
    $pkeyid = openssl_get_privatekey($privkey);
    openssl_sign($data, $signature, $pkeyid);
    $signature = base64_encode($signature);
    openssl_free_key($pkeyid);
    return $signature;
}

/**
 * Return openssl encrypted data
 * Uses repositorys openssl public key
 *
 * @param string $data
 * @return string
 */
function edusharing_encrypt_with_repo_public($data) {
	$settings = new ilSetting("xedus");
    $crypted = '';
    $key = openssl_get_publickey($settings->get('repository_public_key'));
    openssl_public_encrypt($data ,$crypted, $key);
    if($crypted === false) {
		ilLoggerFactory::getLogger('xesr')->warning('error_encrypt_with_repo_public');
        // trigger_error(get_string('error_encrypt_with_repo_public', 'edusharing'), E_USER_WARNING);
        return false;
    }
    return $crypted;
}