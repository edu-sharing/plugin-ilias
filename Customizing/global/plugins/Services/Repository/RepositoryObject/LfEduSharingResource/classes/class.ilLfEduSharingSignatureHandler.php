<?php

use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\SignatureHandler;

class ilLfEduSharingSignatureHandler implements SignatureHandler
{
    private EduSharingNodeHelper $nodeHelper;

    public function __construct(EduSharingNodeHelper $nodeHelper) {
        $this->nodeHelper = $nodeHelper;
    }

    public function getAlgorithm(): string {
        try {
            $about = $this->nodeHelper->base->getAbout();
            if (isset($about['defaultSignatureAlgorithm'])) {
                return $about['defaultSignatureAlgorithm'];
            }
        } catch (Exception) {
            // Do nothing. Just use default
        }
        return $this->nodeHelper->base->defaultAlgorithm;
    }
}
