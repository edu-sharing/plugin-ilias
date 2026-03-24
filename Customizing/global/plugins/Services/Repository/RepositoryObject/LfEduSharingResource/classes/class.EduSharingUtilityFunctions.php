<?php

//namespace mod_edusharing;
const EDUSHARING_MODULE_NAME = 'edusharing';
const EDUSHARING_TABLE = 'edusharing';

const EDUSHARING_DISPLAY_MODE_DISPLAY = 'window';
const EDUSHARING_DISPLAY_MODE_INLINE = 'inline';

class EduSharingUtilityFunctions
{

    protected ILIAS\DI\Container $dic;

    public function __construct() {
        global $DIC;
        $this->dic = $DIC;
    }

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
        $repoId = $this->getRepositoryIdFromUrl($eduSharing->getUri()); //object_url
        $url     .= '&rep_id=' . urlencode($repoId);
        $url     .= '&obj_id=' . urlencode($this->getObjectIdFromUrl($eduSharing->getUri()));//object_url
        $url     .= '&resource_id=' . urlencode($eduSharing->getId());
        $url     .= '&course_id=' . urlencode($eduSharing->getUpperCourse());//course
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

        return $url;
    }

    /**
     * Function getAuthKey
     *
     */
    public function getAuthKey(): string {
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

        $eduauthkey = $settings->get('EDU_AUTH_KEY');

        switch($eduauthkey) {
            case 'id':
                return $DIC->user()->getLogin();

            case 'idnumber':
                return $DIC->user()->getId();

            case 'email':
                return $DIC->user()->getEmail();

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
