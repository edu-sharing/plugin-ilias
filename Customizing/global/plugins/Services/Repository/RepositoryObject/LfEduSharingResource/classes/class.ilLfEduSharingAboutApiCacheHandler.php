<?php

use EduSharingApiClient\AboutApiCacheHandler;
use EduSharingApiClient\EduSharingNodeHelper;
use ILIAS\Cache\Container\Request;
use ILIAS\Refinery\Custom\Transformation;

/**
 * Class ilLfEduSharingAboutApiCacheHandler
 *
 * Caches the repository /rest/_about response. The payload is small and
 * identical for every user, so it is stored in the ILIAS caching service
 * (cross-request when the global cache is activated). A per-request static
 * copy additionally avoids duplicate _about calls within a single request,
 * even when the global cache service is deactivated (VoidContainer).
 *
 * The class doubles as its own cache container Request; isForced() returns
 * true so the container is active without requiring per-container setup
 * activation, as recommended for plugins by the ILIAS Cache service.
 */
class ilLfEduSharingAboutApiCacheHandler implements AboutApiCacheHandler, Request
{
    private const CACHE_KEY = 'about';
    private const TTL       = 3600;

    private static ?array $requestCache = null;

    private EduSharingNodeHelper $nodeHelper;

    public function __construct(EduSharingNodeHelper $nodeHelper) {
        $this->nodeHelper = $nodeHelper;
    }

    /**
     * Cache container namespace for this plugin (matches the plugin id "xesr").
     */
    public function getContainerKey(): string {
        return 'xesr_about';
    }

    /**
     * Force the container active regardless of per-container setup activation.
     */
    public function isForced(): bool {
        return true;
    }

    /**
     * Returns the repository _about response, cached at application level.
     *
     * On a cache miss (or after the TTL expires) the live /rest/_about
     * endpoint is queried once and the result is stored for subsequent calls.
     *
     * @return array
     * @throws JsonException
     */
    public function getAboutApiCache(): array {
        if (self::$requestCache !== null) {
            return self::$requestCache;
        }
        global $DIC;
        $cache = $DIC->globalCache()->get($this);
        if ($cache->has(self::CACHE_KEY)) {
            $cached = $cache->get(self::CACHE_KEY, new Transformation(static fn($data) => $data));
            if (is_array($cached)) {
                self::$requestCache = $cached;
                return $cached;
            }
        }
        $about = $this->nodeHelper->base->getAbout();
        $cache->set(self::CACHE_KEY, $about, self::TTL);
        self::$requestCache = $about;
        return $about;
    }
}
