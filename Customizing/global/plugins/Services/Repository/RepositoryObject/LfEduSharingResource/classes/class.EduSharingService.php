<?php

use EduSharingApiClient\CurlResult;
use EduSharingApiClient\CurlHandler as EdusharingCurlHandler;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\NodeDeletedException;
use EduSharingApiClient\SecuredNode;
use EduSharingApiClient\UrlHandling;
use EduSharingApiClient\Usage;
use EduSharingApiClient\UsageDeletedException;

class EduSharingService
{
    protected ILIAS\DI\Container $dic;
    private ?EduSharingAuthHelper $authHelper;
    private ?EduSharingNodeHelper $nodeHelper;
    private ?EduSharingUtilityFunctions     $utils;

    /**
     * EduSharingService constructor
     *
     * constructor params are optional if you want to use DI.
     * This possibility is needed for unit testing
     *
//     * @throws dml_exception
     * @throws Exception
     */
    public function __construct(?EduSharingAuthHelper $authHelper = null, ?EduSharingNodeHelper $nodeHelper = null, ?EduSharingUtilityFunctions $utils = null) {
        global $DIC;
        $this->dic = $DIC;
        $this->authHelper = $authHelper;
        $this->nodeHelper = $nodeHelper;
        $this->utils      = $utils;
        $this->init();
    }

    /**
     * Function init
     *
     * @throws Exception
     */
    private function init(): void {
        $this->utils === null && $this->utils = new EduSharingUtilityFunctions();
        if ($this->authHelper === null || $this->nodeHelper === null) {
            $internalUrl = $this->utils->getInternalUrl();
            $baseHelper  = new EduSharingHelperBase($internalUrl, $this->utils->getConfigEntry('application_private_key'), $this->utils->getConfigEntry('application_appid'));
            $baseHelper->registerCurlHandler(new ilLfEduSharingCurlHandler());
            $this->authHelper === null && $this->authHelper = new EduSharingAuthHelper($baseHelper);
            if ($this->nodeHelper === null) {
                $nodeConfig       = new EduSharingNodeHelperConfig(new UrlHandling(true));
                $this->nodeHelper = new EduSharingNodeHelper($baseHelper, $nodeConfig);
            }
            $baseHelper->registerAboutApiCacheHandler(new ilLfEduSharingAboutApiCacheHandler($this->nodeHelper));
        }
    }

    /**
     * Function createUsage
     *
     * @throws Exception
     */
    public function createUsage(stdClass $usageData): Usage {
        return $this->nodeHelper->createUsage(
            ticket: !empty($usageData->ticket) ? $usageData->ticket : $this->getTicket(),
            containerId: (string)$usageData->containerId,
            resourceId: (string)$usageData->resourceId,
            nodeId: (string)$usageData->nodeId,
            nodeVersion: (string)$usageData->nodeVersion,
            courseTitle: (string)$usageData->courseTitle
        );
    }

    /**
     * Function getUsageId
     *
     * @throws Exception
     */
    public function getUsageId(stdClass $usageData): ?string {
        $usageId = $this->nodeHelper->getUsageIdByParameters($usageData->ticket, $usageData->nodeId, $usageData->containerId, $usageData->resourceId);
        if ($usageId == null) {
            error_log('No usage found');
        }
        return $usageId;
    }

    /**
     * Function deleteUsage
     *
     * @throws Exception
     */
    public function deleteUsage(stdClass $usageData): void {
        //ToDo: !isset($usageData->usageId) && throw new Exception('No usage id provided, deletion cannot be performed');
        try {
            $this->nodeHelper->deleteUsage($usageData->nodeId, $usageData->usageId);
        } catch (UsageDeletedException $usageDeletedException) {
            error_log('noted, deleting locally: ' . $usageDeletedException->getMessage());
        }
    }

    /**
     * Function getNode
     *
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     * @throws JsonException
     */
    public function getNode($postData): array {
        $usage = new Usage($postData->nodeId, $postData->nodeVersion, $postData->containerId, $postData->resourceId, $postData->usageId);
        return $this->nodeHelper->getNodeByUsage($usage);
    }

    /**
     * Function getNodeAspects
     *
     * fetches the aspects of a node from the repository metadata endpoint
     *
     * @param string $nodeId
     * @return array|null the aspects or null on error
     */
    public function getNodeAspects(string $nodeId): ?array {
        try {
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                $this->authHelper->getRESTAuthenticationHeader($this->getTicket())
            ];
            $url = rtrim($this->utils->getInternalUrl(), '/')
                . '/rest/node/v1/nodes/-home-/' . rawurlencode($nodeId) . '/metadata?propertyFilter=-all-';
            $result = $this->authHelper->base->handleCurlRequest($url, [
                CURLOPT_FAILONERROR    => false,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HTTPHEADER     => $headers
            ]);
            if ($result->error !== 0 || (int)($result->info['http_code'] ?? 0) !== 200) {
                ilLoggerFactory::getLogger('xesr')->warning(
                    'Fetching node metadata failed for node ' . $nodeId . ' (http ' . ($result->info['http_code'] ?? 'n/a') . ')'
                );
                return null;
            }
            $data = json_decode($result->content, true, 512, JSON_THROW_ON_ERROR);
            return $data['node']['aspects'] ?? null;
        } catch (Exception $exception) {
            ilLoggerFactory::getLogger('xesr')->warning(
                'Fetching node metadata failed for node ' . $nodeId . ': ' . $exception->getMessage()
            );
            return null;
        }
    }

    /**
     * Function isVersioningRestricted
     *
     * versioning options are restricted for nodes without a version
     * as well as published copies and collection references
     *
     * @param string $nodeId
     * @param string|null $version
     * @return bool
     */
    public function isVersioningRestricted(string $nodeId, ?string $version): bool {
        if (empty($version) || $version === '-1') {
            return true;
        }
        $aspects = $this->getNodeAspects($nodeId);
        if ($aspects === null) {
            return false;
        }
        return in_array('ccm:published', $aspects, true)
            || in_array('ccm:collection_io_reference', $aspects, true);
    }

    /**
     * Function getTicket
     *
     * @throws Exception
     */
    public function getTicket(): string {
        global $DIC;
        $additionalFields = null;
        if ($this->utils->getConfigEntry('edu_guest_option') != '1') {
            $additionalFields = [
                'firstName' => $DIC->user()->getFirstname(),
                'lastName'  => $DIC->user()->getLastname(),
                'email'     => $DIC->user()->getEmail()
            ];
        }
        return $this->authHelper->getTicketForUser($this->utils->getAuthKey(), $additionalFields);
    }

    /**
     * Function deleteInstance
     *
     * Given an ID of an instance of this module,
     * this function will permanently delete the instance
     * and any data that depends on it.
     */
    public function deleteInstance(string $edusUri, int $id, int $parentObjId): void {
        $usageData              = new stdClass();
        $usageData->ticket      = $this->getTicket();
        $usageData->nodeId      = $this->utils->getObjectIdFromUrl($edusUri);
        $usageData->containerId = (string) $edusUri;
        $usageData->resourceId  = $id;
        $usageData->usageId     = $this->getUsageId($usageData);
        if ($usageData->usageId != null) {
            $this->deleteUsage($usageData);
        }
    }

    /**
     * Function addInstance
     */
    public function addInstance(ilObjLfEduSharingResource $eduSharing): bool
    {
        global $DIC;

        $this->postProcessEdusharingObject($eduSharing);

        if ($DIC->http()->wrapper()->post()->has('object_version')
            && $DIC->http()->wrapper()->post()->retrieve('object_version', $DIC->refinery()->kindlyTo()->string()) != '0') {
            $eduSharing->object_version = $DIC->http()->wrapper()->post()->retrieve('object_version', $DIC->refinery()->kindlyTo()->string());
        }

        $usageData              = new stdClass();
        $usageData->containerId = $eduSharing->getUpperCourse();
        $usageData->resourceId  = $eduSharing->getId();//$id;
        $usageData->nodeId      = $this->utils->getObjectIdFromUrl($eduSharing->getUri()); //$eduSharing->object_url
        $usageData->nodeVersion = $eduSharing->object_version;
        $usageData->courseTitle = $eduSharing->getUpperCourse() > 0
            ? ilObject::_lookupTitle($eduSharing->getUpperCourse())
            : '';
        $this->createUsage($usageData);
        $eduSharing->getId();//$id;

        return true;
    }

    /**
     * Function postProcessEdusharingObject
     *
     * @param ilObjLfEduSharingResource $edusharing //was stdclass
     * @param int|null $updateTime
     * @return void
     */
    private function postProcessEdusharingObject(ilObjLfEduSharingResource $edusharing): void
    {
        if (!empty($edusharing->force_download)) {
            $edusharing->force_download = 1;
        }
        $course_id = $edusharing->getUpperCourse();
        if ($course_id == 0) {
            ilLoggerFactory::getLogger('xesr')->warning('set usage: no upper object ref id given.');
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', 'set usage: no upper object ref id given.');
        }
        if (empty($edusharing->course) || !$edusharing->course) {
//            $edusharing->course = $course_id;//deprecated
        }
    }

    /**
     * Function importMetadata
     *
     * @param string $url
     * @return CurlResult
     */
    public function importMetadata(string $url): CurlResult {
        $curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT']
        ];
        return $this->authHelper->base->handleCurlRequest($url, $curlOptions);
    }

    /**
     * Function validateSession
     *
     * @param string $url
     * @param string $auth
     * @return CurlResult
     */
    public function validateSession(string $url, string $auth): CurlResult {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($auth)
        ];
        $url     = rtrim($url, '/') . '/rest/authentication/v1/validateSession';
        return $this->authHelper->base->handleCurlRequest($url, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers
        ]);
    }

    /**
     * Function registerPlugin
     *
     * @param string $url
     * @param string $delimiter
     * @param string $body
     * @param string $auth
     * @return CurlResult
     */
    public function registerPlugin(string $url, string $delimiter, string $body, string $auth): CurlResult {
        $registrationUrl = rtrim($url, '/') . '/rest/admin/v1/applications/xml';
        $headers         = [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($body),
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($auth)
        ];
        $this->authHelper->base->curlHandler->setMethod(EdusharingCurlHandler::METHOD_PUT);
        return $this->authHelper->base->handleCurlRequest($registrationUrl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body
        ]);
    }

    /**
     * Function sign
     *
     * @param string $input
     * @return string
     */
    public function sign(string $input): string {
        return $this->nodeHelper->base->sign($input);
    }

    /**
     * Function getRenderHtml
     *
     * @param string $url
     * @return string
     */
    public function getRenderHtml(string $url): string {
        $curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT']
        ];
        $result      = $this->authHelper->base->handleCurlRequest($url, $curlOptions);
        if ($result->error !== 0) {
            try {
                return 'Unexpected Error';
            } catch (Exception $exception) {
                return $exception->getMessage();
            }
        }
        return $result->content;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function getRendering2Url(): string {
        $about = $this->nodeHelper->base->getAboutCached();
        if (isset($about['renderingService2']['url'])) {
            return $about['renderingService2']['url'];
        }
        throw new Exception('Rendering Service 2 is not configured');
    }

    /**
     * hasRendering2
     *
     * @return bool
     */
    public function hasRendering2(): bool {
        try {
            $this->getRendering2Url();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function getSecuredNode(Usage $usage): SecuredNode {
        $securedNode = $this->nodeHelper->getSecuredNodeByUsage($usage, $this->utils->getAuthKey());
        $securedNode->previewUrl = ILIAS_HTTP_PATH . '/preview.php?resourceId=' . $usage->resourceId . '&containerId=' . $usage->containerId;
        $securedNode->signingAlgorithm = $this->get_signing_algorithm();
        return $securedNode;
    }

    public function getPreview(Usage $usage): CurlResult {
        return $this->nodeHelper->getPreview($usage);
    }

    /**
     * Function get_signing_algorithm
     *
     * @return string
     */
    public function get_signing_algorithm(): string {
        return $this->nodeHelper->base->getAlgorithm();
    }
}
