<?php

//namespace mod_edusharing;
const EDUSHARING_MODULE_NAME = 'edusharing';
const EDUSHARING_TABLE = 'edusharing';

const EDUSHARING_DISPLAY_MODE_DISPLAY = 'window';
const EDUSHARING_DISPLAY_MODE_INLINE = 'inline';

class EduSharingUtilityFunctions
{

    protected ILIAS\DI\Container $dic;
//    private ?AppConfig $appConfig;

//    /**
//     * EduSharingUtilityFunctions constructor
//     *
//     * @param AppConfig|null $config
//     */
//    public function __construct(?AppConfig $config = null) {
//        $this->appConfig = $config;
//        $this->init();
//    }
    public function __construct() {
        global $DIC;
        $this->dic = $DIC;
    }
    /**
     * Function init
     *
     * @return void
     */
//    private function init(): void {
//        if ($this->appConfig === null) {
//            //ToDo: $this->appConfig = new DefaultAppConfig();
//        }
//    }

    /**
     * Function getObjectIdFromUrl
     *
     * Get the object-id from object-url.
     * E.g. "abc-123-xyz-456789" for "ccrep://homeRepository/abc-123-xyz-456789"
     *
     * @param string $url
     * @return string
     */
    public function getObjectIdFromUrl(?string $url): string {
        if (!empty($url)) {
            $objectId = parse_url($url, PHP_URL_PATH);
        }
        if (empty($url) || $objectId === false) {
            try {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure','error_get_object_id_from_url', true);
//                trigger_error(get_string('error_get_object_id_from_url', 'edusharing'), E_USER_WARNING);
            } catch (Exception $exception) {
                unset($exception);
                trigger_error('error_get_object_id_from_url', E_USER_WARNING);
            }
            return '';
        }

        return str_replace('/', '', $objectId);
    }

    /**
     * Function getRepositoryIdFromUrl
     *
     * Get the repository-id from object-url.
     * E.g. "homeRepository" for "ccrep://homeRepository/abc-123-xyz-456789"
     *
     * @param string $url
     * @return string
     * @throws Exception
     */
    public function getRepositoryIdFromUrl(string $url): string {
        $repoId = parse_url($url, PHP_URL_HOST);
        if ($repoId === false) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', 'error_get_repository_id_from_url',true);
//            throw new Exception(get_string('error_get_repository_id_from_url', 'edusharing'));
        }

        return $repoId;
    }

    /**
     * Functions getRedirectUrl
     * @throws Exception
     */
    public function getRedirectUrl(ilObject $eduSharing, string $displaymode = EDUSHARING_DISPLAY_MODE_DISPLAY): string {
        global $DIC;
        $url = rtrim($this->getConfigEntry('application_cc_gui_url'), '/');
        $url .= '/renderingproxy';
        $url .= '?app_id=' . urlencode($this->getConfigEntry('application_appid'));
        $url .= '&session=' . urlencode(session_id());
//        try {
            $repoId = $this->getRepositoryIdFromUrl($eduSharing->getUri()); //object_url
//        } catch (Exception $exception) {
//            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $exception->getMessage(), true);
////            error_log($exception->getMessage());
//            return '';
//        }
        $url     .= '&rep_id=' . urlencode($repoId);
        $url     .= '&obj_id=' . urlencode($this->getObjectIdFromUrl($eduSharing->getUri()));//object_url
//        $url     .= '&resource_id=' . urlencode($eduSharing->id);
        $url     .= '&resource_id=' . urlencode($eduSharing->getId());
        $url     .= '&course_id=' . urlencode($eduSharing->getUpperCourse());//course
//        $context = context_course::instance($eduSharing->course);
//        $roles   = get_user_roles($context, $USER->id);
//        foreach ($roles as $role) {
//            $url .= '&role=' . urlencode($role->shortname);
//        }
        $role = 'member';
        if ($DIC->rbac()->system()->checkAccess("write",$eduSharing->getRefId())) {
            $role = 'editingteacher';
        }
        $url .= '&role=' . $role;
        $url .= '&display=' . urlencode($displaymode);
        $url .= '&version=' . urlencode($eduSharing->getObjectVersionForUse());//object_version
        $url .= '&locale=' . urlencode($DIC->user()->getLanguage()); //repository
        $url .= '&language=' . urlencode($DIC->user()->getLanguage()); //rendering service
        $url .= '&u=' . rawurlencode(base64_encode($this->encryptWithRepoKey($this->getAuthKey())));
//        die($url);
        //die($this->encryptWithRepoKey($this->getAuthKey()));

        return $url;
    }

    /**
     * Function getAuthKey
     *
     */
    public function getAuthKey(): string {
        global $DIC;
    	$settings = new ilSetting("xedus");

        // Set by external sso script.
//        if (!empty($SESSION->edusharing_sso)) {
//            return $SESSION->edusharing_sso[$this->getConfigEntry('EDU_AUTH_PARAM_NAME_USERID')];
//        }
//        if ($settings->get('EDU_AUTH_PARAM_NAME_USERID') != 'no' && array_key_exists('sso', $_SESSION) && !empty($_SESSION['sso'])) {
//            $eduauthparamnameuserid = $settings->get('EDU_AUTH_PARAM_NAME_USERID');
//            return $_SESSION['sso'][$eduauthparamnameuserid];
//        }

        $guestoption = $settings->get('edu_guest_option');
        if (!empty($guestoption) || $DIC->user()->getId() == 13) { //13=anonymous
            $guestid = $settings->get('edu_guest_guest_id');
            if (empty($guestid)) {
                $guestid = 'esguest';
            }
            return $guestid;
        }

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
                return $DIC->user()->getFirstname() . " " . $DIC->user()->getLastname();//$DIC->user()->getFullname();

            case 'ShibbolethUId':
                return $DIC->user()->getExternalAccount();

            case 'ZOERR_Auth':
                global $ilUser;
                $udf = ilUserDefinedFields::_getInstance();
                $udd = $ilUser->getUserDefinedData();
                $udf_data = array();
                foreach ($udd as $fieldId => $value) {
                    $udf_data[str_replace('f_', '', $fieldId)] = $value;
                }
                if(!isset($udf_data[$udf->fetchFieldIdFromName('ZOERR_Auth')])) {
                    $DIC->language()->loadLanguageModule('rep_robj_xesr');
                    $DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $DIC->language()->txt('rep_robj_xesr_error_get_zoerr_auth'), true);
                    $guestid = $settings->get('edu_guest_guest_id');
                    if (empty($guestid)) {
                        $guestid = 'esguest';
                    }
                    return $guestid;
                }
                return $udf_data[$udf->fetchFieldIdFromName('ZOERR_Auth')];

            case 'randomUId':
                $usr_ident = $this->getUserIdent() . '@' . ilCmiXapiUser::getIliasUuid() . '.ilias';
                //            ilLoggerFactory::getLogger('xesr')->info('usr_ident: '.$usr_ident);
                return $usr_ident;

            case 'idnumber;http_path;client_id':
            default:
                $iliasDomain = substr(ILIAS_HTTP_PATH, 7);
                if (substr($iliasDomain, 0, 1) == "\/") {
                    $iliasDomain = substr($iliasDomain, 1);
                }
                if (substr($iliasDomain, 0, 4) == "www.") {
                    $iliasDomain = substr($iliasDomain, 4);
                }
                return $DIC->user()->getId() . ';' . $iliasDomain . ';' . CLIENT_ID;
        }
    }

    protected function getUserIdent() : string
    {
        global $DIC;
        $usrIdent = "";
        $res = $DIC->database()->queryF(
            "SELECT usr_ident FROM rep_robj_xesr_users WHERE usr_id=%s",
            array('integer'),
            array($DIC->user()->getId())
        );
        while ($row = $DIC->database()->fetchAssoc($res))
        {
            $usrIdent = $row['usr_ident'];
        }

        if ($usrIdent != "") return $usrIdent;

        $usrIdent = $this->getUserObjectUniqueId(32);
        $DIC->database()->insert('rep_robj_xesr_users', array(
            'usr_id' => array('int', $DIC->user()->getId()),
            'usr_ident' => array('text', $usrIdent)
        ));
        return $usrIdent;
    }

    /**
     * @param int $length
     * @return string
     */
    protected function getUserObjectUniqueId( int $length = 32 ) : string
    {
        $id = ilCmiXapiUser::getUUID($length);
        $exists = $this->userUniqueIdExists($id);
        while( $exists ) {
            $id = ilCmiXapiUser::getUUID($length);
            $exists = $this->userUniqueIdExists($id);
        }
        return $id;
    }

    protected function userUniqueIdExists($id): bool
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $res = $DIC->database()->queryF(
            "SELECT usr_ident FROM rep_robj_xesr_users WHERE usr_ident = %s",
            array('text'),
            array($id)
        );
        if ($res->numRows() == 0) {
            return false;
        }
        return true;
    }

    /**
     * Function encryptWithRepoKey
     *
     */
    public function encryptWithRepoKey(string $data): string {
        $encrypted = '';
        $key       = openssl_get_publickey($this->getConfigEntry('repository_public_key'));
        openssl_public_encrypt($data, $encrypted, $key);
        if (!openssl_public_encrypt($data, $encrypted, $key)) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', 'error_encrypt_with_repo_public',true);
//            trigger_error(get_string('error_encrypt_with_repo_public', 'edusharing'), E_USER_WARNING);
            return '';
        }
        return $encrypted;
    }

    /**
     * Function setModuleIdInDb
     *
     */
    public function setModuleIdInDb(string $text, array $data, string $id_type): void {
        global $DB;
        preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi', $text, $matchesImgAtto, PREG_PATTERN_ORDER);
        preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi', $text, $matchesAAtto, PREG_PATTERN_ORDER);
        $matchesAtto = array_merge($matchesImgAtto[0], $matchesAAtto[0]);
        foreach ($matchesAtto as $match) {
            $resourceId = '';
            $pos        = strpos($match, "resourceId=");
            if ($pos !== false) {
                $resourceId = substr($match, $pos + 11);
                $resourceId = substr($resourceId, 0, strpos($resourceId, "&"));
            }
            try {
                $DB->set_field('edusharing', $id_type, $data['objectid'], ['id' => $resourceId]);
            } catch (Exception $exception) {
                error_log('Could not set module_id: ' . $exception->getMessage());
            }
        }
    }


//    /**
//     * Function getCourseModuleInfo
//     *
//     * @param stdClass $courseModule
//     * @return cached_cm_info|bool
//     */
//    public function getCourseModuleInfo(stdClass $courseModule) {
//        global $DB;
//        try {
//            $edusharing = $DB->get_record('edusharing', ['id' => $courseModule->instance], 'id, name, intro, introformat', MUST_EXIST);
//        } catch (Exception $exception) {
//            error_log($exception->getMessage());
//            return false;
//        }
//        $info = new cached_cm_info();
//        if ($courseModule->showdescription) {
//            // Convert intro to html. Do not filter cached version, filters run at display time.
//            $info->content = format_module_intro('edusharing', $edusharing, $courseModule->id, false);
//        }
//        try {
//            $resource = $DB->get_record('edusharing', ['id' => $courseModule->instance], '*', MUST_EXIST);
//            if (!empty($resource->popup_window)) {
//                $info->onclick = 'this.target=\'_blank\';';
//            }
//        } catch (Exception $exception) {
//            error_log($exception->getMessage());
//        }
//        return $info;
//    }

//    /**
//     * Function getInlineObjectMatches
//     *
//     * @param string $inputText
//     * @return array
//     */
//    public function getInlineObjectMatches(string $inputText): array {
//        preg_match_all('#<img(.*)class="(.*)edusharing_atto(.*)"(.*)>#Umsi', $inputText, $matchesImg, PREG_PATTERN_ORDER);
//        preg_match_all('#<a(.*)class="(.*)edusharing_atto(.*)">(.*)</a>#Umsi', $inputText, $matchesA, PREG_PATTERN_ORDER);
//        return array_merge($matchesImg[0], $matchesA[0]);
//    }

    /**
     * Function getConfigEntry
     */
    public function getConfigEntry(string $name): string { //Todo: mixed
        $settings = new ilSetting("xedus");
        return $settings->get($name);
    }

    /**
     * Function setConfigEntry
     */
    public function setConfigEntry(string $name, string $value): void {
        $settings = new ilSetting("xedus");
        $settings->set($name, $value);
    }

    /**
     * Function getInternalUrl
     * Retrieves the internal URL from config.
     */
    public function getInternalUrl(): string {
        try {
//            $internalUrl = $this->appConfig->get('application_docker_network_url');
//            if (empty($internalUrl)) {
//                $internalUrl = $this->appConfig->get('application_cc_gui_url');
//            }
            $settings = new ilSetting("xedus");
            $internalUrl = $settings->get('application_cc_gui_url');
            return rtrim($internalUrl, '/');
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            unset($exception);
        }
        return '';
    }

}