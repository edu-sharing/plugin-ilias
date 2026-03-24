<?php

/**
 * LfEduSharing Page Component GUI
 *
 * @ilCtrl_isCalledBy ilLfEduSharingPageComponentPluginGUI: ilPCPluggedGUI
 * @ilCtrl_isCalledBy ilLfEduSharingPageComponentPluginGUI: ilUIPluginRouterGUI
 */
use EduSharingApiClient\EduSharingHelperBase;
class ilLfEduSharingPageComponentPluginGUI extends ilPageComponentPluginGUI {

	protected ilLanguage $lng;

	protected ilCtrl $ctrl;

	protected ilGlobalPageTemplate $tpl;

	protected \ilPageComponentPlugin $plugin;

    private EduSharingService $service;

    private EduSharingUtilityFunctions $utils;

	public function __construct() {
		global $DIC;
		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();
		$this->tpl = $DIC['tpl'];
        $this->service = new EduSharingService();
        $this->utils = new EduSharingUtilityFunctions();
	}


	public function executeCommand(): void {
		$next_class = $this->ctrl->getNextClass();
		switch($next_class)
		{
			default:
				$cmd = $this->ctrl->getCmd();
				if (in_array($cmd, array("create", "edit", "update", "cancel"))) {
                    $this->$cmd();
                }
				break;
		}
	}

	/**
	 * Create new element
	 */
	public function insert(): void
    {
        global $DIC;
        $ticket = $this->getTicket();
        $stext = "";
        $re_url = ILIAS_HTTP_PATH . '/' . $DIC->ctrl()->getLinkTarget($this, "create", "", false, false);
        $reposearch = ilObjLfEduSharingResourceGUI::buildUrl("search", $ticket, $stext, $re_url, $DIC->user());

        $ilToolbar = $DIC->toolbar();
        $search_btn = ilLinkButton::getInstance();
        $search_btn->setCaption($this->plugin->txt("search_and_create"),false);
        $search_btn->setUrl($reposearch);
        $ilToolbar->addButtonInstance($search_btn);
        $ilToolbar->addSeparator();
        $form = new ilPropertyFormGUI();
        $section = new ilFormSectionHeaderGUI();
        $section->setTitle('Widget');
        $form->addItem($section);

        $area = new ilTextAreaInputGUI($this->plugin->txt("widget_info"), 'widget');
        $area->setRows(3);
        $area->setCols(40);
        $form->addItem($area);

        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle('');
        $form->addCommandButton('create', $this->plugin->txt('insert_widget'));

        $this->tpl->setContent($form->getHTML());
    }

	/**
	 * Save new element
	 */
	public function create(): void {
		global $DIC;
        $widget = '';
        if ($DIC->http()->wrapper()->post()->has('widget')) {
            $widget = $DIC->http()->wrapper()->post()->retrieve(
                'widget',
                $DIC->refinery()->kindlyTo()->string()
            );
        }
        $widgetMode = false;
        if (!empty( $widget )) {
            $widgetMode = true;
        }
		$properties = $this->getProperties();

        $this->plugin->setResId($this->plugin->addUsage(""));
        $properties['resId'] = $this->plugin->getResId();
        $resId = $properties['resId'];

        if (!isset($resId)) {
            $resId = ilUtil::stripSlashes($DIC->http()->wrapper()->query()->retrieve("resId", $DIC->refinery()->kindlyTo()->int()));
        }
        $this->plugin->setResId($resId);

        if (!$widgetMode) {
            $eduuri = ilUtil::stripSlashes($DIC->http()->wrapper()->query()->retrieve("nodeId", $DIC->refinery()->kindlyTo()->string()));
            $this->plugin->setUri($eduuri);

            $this->plugin->setMimetype($DIC->http()->wrapper()->query()->retrieve("mimeType", $DIC->refinery()->kindlyTo()->string()));
            $this->plugin->setObjectVersion($DIC->http()->wrapper()->query()->retrieve("v", $DIC->refinery()->kindlyTo()->string()));
            $this->plugin->setWindowWidthOrg($DIC->http()->wrapper()->query()->retrieve("w", $DIC->refinery()->kindlyTo()->int()));
            $this->plugin->setWindowHeightOrg($DIC->http()->wrapper()->query()->retrieve("h", $DIC->refinery()->kindlyTo()->int()));
            $this->plugin->setWindowWidth($DIC->http()->wrapper()->query()->retrieve("w", $DIC->refinery()->kindlyTo()->int()));
            $this->plugin->setWindowHeight($DIC->http()->wrapper()->query()->retrieve("h", $DIC->refinery()->kindlyTo()->int()));

            if ($this->plugin->updateUsage($resId) == true) {
                $DIC->ui()->mainTemplate()->setOnScreenMessage('success', $this->lng->txt("msg_obj_created"), true);
            }
            $this->createElement($properties);

            $service = new EduSharingService();
            $eduObj = new ilObjLfEduSharingResource();
            $eduObj->setUri($this->plugin->getUri());
            $eduObj->setId($resId);
            $eduObj->setRefId($this->plugin->getRefId());
            $usageResult = $service->addInstance($eduObj);
            if ($usageResult == false) {
                $DIC->ui()->mainTemplate()->setOnScreenMessage('failure', "Create failed (usageResult = false)", true);
                $this->returnToParent();
            }
            $this->edit();
        } else {
            // Widget mode
            $widgetAttributes = $this->parseWidgetAttributes($widget);
            if (!empty($widgetAttributes)) {
                $this->plugin->setWidget(json_encode($widgetAttributes, JSON_THROW_ON_ERROR));
                $this->plugin->setUri($widgetAttributes['nodeId'] ?? '');
                $this->plugin->setMimetype('widget');
                $this->plugin->setObjectVersion($widgetAttributes['version'] ?? '');
                $this->plugin->setWindowWidthOrg((int)($widgetAttributes['width'] ?? 0));
                $this->plugin->setWindowHeightOrg((int)($widgetAttributes['height'] ?? 0));
                $this->plugin->setWindowWidth((int)($widgetAttributes['width'] ?? 0));
                $this->plugin->setWindowHeight((int)($widgetAttributes['height'] ?? 0));

                if ($this->plugin->updateUsage($resId)) {
                    $DIC->ui()->mainTemplate()->setOnScreenMessage('success', $this->lng->txt("msg_obj_created"), true);
                }
                $this->createElement($properties);

            } else {
                $DIC->ui()->mainTemplate()->setOnScreenMessage('failure', "Failed to parse widget attributes", true);
            }
            $this->returnToParent();
        }
    }

    /**
	 * Edit
	 */
	public function edit(): void {
		$properties = $this->getProperties();
        $this->plugin->setVars((int) $properties['resId']);
        $widgetMode = !empty($this->plugin->getWidget());
        if ($widgetMode) {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('widget_no_edit'), true);
            $this->returnToParent();
        } else {
            $form = $this->editform();
            $this->tpl->setContent($form->getHTML());
        }
	}

	/**
	 * Update
	 */
	public function update()
	{
        global $DIC;

		$resId = 0;
        if ($DIC->http()->wrapper()->post()->has('resId')) {
            $resId = $DIC->http()->wrapper()->post()->retrieve('resId', $DIC->refinery()->kindlyTo()->int());
        }
		$this->plugin->setVars($resId);
		if ($this->plugin->getWindowWidthOrg() > 0) {
			$this->plugin->setWindowWidth($DIC->http()->wrapper()->post()->retrieve('window_width', $DIC->refinery()->kindlyTo()->int()));
			$scaleFactor = $this->plugin->getWindowWidth() / $this->plugin->getWindowWidthOrg();
			$newHeight = round($scaleFactor * $this->plugin->getWindowHeightOrg());
			$this->plugin->setWindowHeight($newHeight);
		}
		$this->plugin->setWindowFloat($DIC->http()->wrapper()->post()->retrieve('window_float', $DIC->refinery()->kindlyTo()->string()));
        if ($DIC->http()->wrapper()->post()->has('object_version_use_exact')) {
            $this->plugin->setObjectVersionUseExact($DIC->http()->wrapper()->post()->retrieve('object_version_use_exact',
                $DIC->refinery()->kindlyTo()->int()));
        }
		if ($this->plugin->updateUsage($resId) == true) {
            $DIC->ui()->mainTemplate()->setOnScreenMessage('success', $this->lng->txt("msg_obj_modified"), true);
		}
        $form = $this->editform();
		$this->tpl->setContent($form->getHTML());
	}
	
	
	/**
	 * Init editing form
	 *
	 */
	protected function editform() : ilPropertyFormGUI
    {
		global $DIC;
        $resId = $this->plugin->getResId();

		$form = new ilPropertyFormGUI();
		$ne = new ilNonEditableValueGUI($this->plugin->txt("uri"), "uri");
		$ne->setValue($this->plugin->getUri());
		$form->addItem($ne);

		$ne = new ilNonEditableValueGUI($this->plugin->txt("mimetype"), "mimetype");
		$ne->setValue($this->plugin->getMimetype());
		$form->addItem($ne);

		$ne = new ilNonEditableValueGUI($this->plugin->txt("resId"), "resId");
		$ne->setValue($resId);
		$form->addItem($ne);
		
		if ($this->plugin->getWindowWidthOrg() > 0) {
			$ni = new ilNumberInputGUI($this->plugin->txt("window_width"), "window_width");
			$ni->setMaxLength(4);
			$ni->setSize(4);
			$ni->setRequired(true);
			$ni->setInfo(
				sprintf(
					$this->plugin->txt("window_width_info"),
					$this->plugin->getWindowWidthOrg(),
					$this->plugin->getWindowHeightOrg()
				)
			);
			$ni->setValue($this->plugin->getWindowWidth());
			$form->addItem($ni);
		}
		$cb = new ilCheckboxInputGUI($this->plugin->txt("object_version_use_exact"), "object_version_use_exact");
		$cb->setValue("1");
		$cb->setChecked($this->plugin->getObjectVersionUseExact());
		$cb->setInfo($this->plugin->txt("object_version_use_exact_info").' '.$this->plugin->getObjectVersion());
		$form->addItem($cb);

		$radg = new ilRadioGroupInputGUI($this->plugin->txt("window_float"), "window_float");
		$op0 = new ilRadioOption($this->plugin->txt("no_float"), "no");
		$radg->addOption($op0);
		$op1 = new ilRadioOption($this->plugin->txt("float_left"), "left");
		$radg->addOption($op1);
		$op2 = new ilRadioOption($this->plugin->txt("float_right"), "right");
		$radg->addOption($op2);
		$radg->setValue($this->plugin->getWindowFloat());
		$radg->setRequired(true);
		$form->addItem($radg);

		$form->addCommandButton("update", $this->lng->txt("update"));

		$form->setTitle($this->lng->txt("settings"));

//        $this->ctrl->setCmd('update');

		$form->setFormAction($this->ctrl->getFormAction($this));

		return $form;
	}

	/**
	 * Cancel
	 */
	public function cancel()
	{
		$this->returnToParent();
	}

	/**
	 * Get HTML for element
	 * @param string $a_mode //(edit, presentation, print, preview, offline)
	 * @return string   html code
	 */
	public function getElementHTML(string $a_mode, array $a_properties, string $plugin_version): string
	{
        global $DIC;
        static $widgetLoaded = false;
        static $renderingLoaded = false;
        $this->plugin->setResId($a_properties['resId']);
        $this->plugin->setVars($a_properties['resId']);
        $widget = $this->plugin->getWidget();
        $repoUrl = $this->utils->getInternalUrl();
        if (!empty($widget)) {
            $widgetScripts = '';
            $widgetStyles = '';
            if (!$widgetLoaded) {
                if (!$renderingLoaded) {
                    $inlineEnv = "window.__env = {EDU_SHARING_API_URL: `${repoUrl}/rest`};";
                    $inlineScript = '<script type="text/javascript">' . $inlineEnv . '</script>';
                    $widgetScripts .= $inlineScript;
                }
                $widgetJs = '<script type="text/javascript" src="./Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/js/widget.js"></script>';
                $widgetScripts .= $widgetJs;
                $polyfills = '<script type="module" src="' . $repoUrl . '/web-components/app/polyfills.js"></script>';
                $widgetScripts .= $polyfills;
                $webComponent = '<script type="module" src="' . $repoUrl . '/web-components/app/main.js"></script>';
                $widgetScripts .= $webComponent;
                $webComponentCss = $repoUrl . '/web-components/app/styles.css';
                $widgetStyles .= '<link rel="stylesheet" href="' . $webComponentCss . '">';
                $widgetLoaded = true;
            }
            return $widgetScripts
    . $widgetStyles
    . '<div class="edu-widget" data-widget="' . htmlspecialchars($widget, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"></div>';
        }

        if ($this->service->hasRendering2()) {
            $scripts = '';
            $styles = '';
            if (!$renderingLoaded) {
                // Inject JS and css only once
                if (!$widgetLoaded) {
                    $inlineEnv = "window.__env = {EDU_SHARING_API_URL: `${repoUrl}/rest`};";
                    $inlineScript = '<script type="text/javascript">' . $inlineEnv . '</script>';
                    $scripts .= $inlineScript;
                }
                $webComponentUrl = $repoUrl . '/web-components/rendering-service/main.js';
                $scripts .= '<script type="module" src="' . $webComponentUrl . '"></script>';
                $eduJs = '<script type="text/javascript" src="./Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/js/edu.js"></script>';
                $scripts .= $eduJs;
                $webComponentCss = $repoUrl . '/web-components/rendering-service/styles.css';
                $styles .= '<link rel="stylesheet" href="' . $webComponentCss . '">';
                $renderingLoaded = true;
            }
            $resourceId = $this->plugin->getResId();
            $nodeId = $this->utils->getObjectIdFromUrl($this->plugin->getUri());
            $version = $this->plugin->getObjectVersion();
            $refId = $DIC->http()->wrapper()->query()->retrieve('ref_id', $DIC->refinery()->kindlyTo()->string());
            $redirectUrl = ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/inlineHelper.php?resId=" . $resourceId . '&ref_id=' . $refId;
            $nodeEndpoint = ILIAS_HTTP_PATH . '/Customizing/global/plugins/Services/Repository/RepositoryObject/LfEduSharingResource/securedNode.php';
            $serviceWorker = ILIAS_HTTP_PATH . '/Customizing/global/plugins/Services/Repository/RepositoryObject/LfEduSharingResource/serviceWorker.php';
            $float = $this->plugin->getWindowFloat() != 'no' ? 'style="float:'.$this->plugin->getWindowFloat().'"' : "";
            $container = <<<HTML
            <div data-refid="{$refId}" data-redirecturl="{$redirectUrl}" data-endpoint="{$nodeEndpoint}" data-service-worker="{$serviceWorker}" data-resourceId="{$resourceId}" data-repo="{$repoUrl}" data-nodeId="{$nodeId}" data-version="{$version}" {$float} data-type="esObject"></div>
            HTML;
            return $scripts . $styles . $container;
        }

        $counter = $this->plugin->getCounter($a_properties['resId']);
        $this->plugin->setResId($a_properties['resId']);
		$this->plugin->setVars($a_properties['resId']);
		if ($this->plugin->getUri() == "") return $this->plugin->txt("failure_create");
		$html = "";

		$settings = new ilSetting("xedus");
        $eduObj = new ilObjLfEduSharingResource();
        $eduObj->setUri($this->plugin->getUri());
        $eduObj->setId($this->plugin->getResId());
        $eduObj->setRefId($this->plugin->getRefId());

        $eduSharingService = new EduSharingService();
        $displaymode = 'inline';

        $utils = new EduSharingUtilityFunctions();

        $redirectUrl = $utils->getRedirectUrl($eduObj, $displaymode);
        $ts = $timestamp = round(microtime(true) * 1000);
        $redirectUrl .= '&ts=' . $ts;
        $data = $settings->get('application_appid') . $ts . $utils->getObjectIdFromUrl($this->plugin->getUri());
        $baseHelper = new EduSharingHelperBase(
            $settings->get('application_cc_gui_url'),
            $settings->get('application_private_key'),
            $settings->get('application_appid')
        );
        $redirectUrl .= '&sig=' . urlencode($baseHelper->sign($data));
        $redirectUrl .= '&signed=' . urlencode($data);

        $ticket = $eduSharingService->getTicket();
        $redirectUrl .= '&ticket=' . urlencode(base64_encode($utils->encryptWithRepoKey($ticket)));

		$html .= '<div';
		if ($this->plugin->getWindowFloat() != 'no') $html .= ' style="float:'.$this->plugin->getWindowFloat().'"';
		$html .= '>'.$this->filter_edusharing_get_render_html($redirectUrl).'</div>';
		$html = $this->filter_edusharing_display($html);

		if ($counter == 0) $html .= '<script type="text/javascript" src="./node_modules/jquery/dist/jquery.min.js"></script><script type="text/javascript" src="./Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/js/eduLegacy.js"></script>';

		return $html;
	}

    // /**
	 // *
	 // */
	public function delete() {
		// TODO Delete LfEduSharing content on page component delete
		// $properties = $this->getProperties();
		// die(a_properties['resId']);
		// $LfEduSharing_content = ilLfEduSharingContent::getContentById($properties["content_id"]);

		// if ($LfEduSharing_content !== NULL) {
			// $this->LfEduSharing->show_editor()->deleteContent($LfEduSharing_content);
		// }
		
	}
	// public function copy() {
		// // TODO Copy LfEduSharing content on page component copy
		// $this->plugin->addInstanceAfterCopy();
	// }

	// public function paste() {
		// // TODO Paste LfEduSharing content on page component paste
	// }

    /**
     * Get rendered object via curl
     * @param string $url
     * @return string
     * @throws Exception
     */
    public function filter_edusharing_get_render_html(string $url) : string
    {
        global $DIC;
		$inline = "";
        try {
            $curlhandle = curl_init($url);
            $proxy = ilProxySettings::_getInstance();
            if ($proxy->isActive()) {
                curl_setopt($curlhandle, CURLOPT_HTTPPROXYTUNNEL,  1);
                curl_setopt($curlhandle, CURLOPT_PROXY, $proxy->getHost());
                curl_setopt($curlhandle, CURLOPT_PROXYPORT, $proxy->getPort());
            }
            curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curlhandle, CURLOPT_HEADER, 0);
            // DO NOT RETURN HTTP HEADERS
            curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, 1);
            // RETURN THE CONTENTS OF THE CALL
            curl_setopt($curlhandle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            curl_setopt($curlhandle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curlhandle, CURLOPT_SSL_VERIFYHOST, false);
			$inline = curl_exec($curlhandle);
			if($inline === false) {
				ilLoggerFactory::getLogger('xesp')->warning(curl_error($curlhandle));
                $DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->plugin->txt("not_visible_now") . ' ' . curl_error($curlhandle), true);
                $inline = "";
			}
        } catch (Exception $e) {
			ilLoggerFactory::getLogger('xesp')->warning($e->getMessage());
            $DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->plugin->txt("not_visible_now") . ' ' . $e->getMessage(), true);
        }
        curl_close($curlhandle);
        return $inline;
    }

    /**
     * Prepare rendered object for display
     */
    public function filter_edusharing_display(string $html): string
    {
        global $DIC;

		$resid = $this->plugin->getResId();
		
		$html = str_replace(array("\n", "\r", "\n"), '', $html);
        $html = str_replace('width:0px','width:'.$this->plugin->getWindowWidth().'px',$html);//; height:'.$this->plugin->getWindowHeight().'px
        /*
         * replaces {{{LMS_INLINE_HELPER_SCRIPT}}}
         */
        $html = str_replace(
            '{{{LMS_INLINE_HELPER_SCRIPT}}}',
            ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/inlineHelper.php?resId=" . $resid .
            "&ref_id=" . $DIC->http()->wrapper()->query()->retrieve('ref_id', $DIC->refinery()->kindlyTo()->string()),
            $html);

        $html = str_replace(
            '<div class="license" style="max-width: 100%">',
            '<div class="license" style="max-width: 70%">',
            $html
        );

        return $html;
    }

	protected function getTicket() : string
    {
        $eduSharingService = new EduSharingService();
        return $eduSharingService->getTicket();
	}

	/**
	 * Parse widget HTML tag and extract attributes
     *
     * @param string $widget HTML string containing the custom tag
     * @return array Associative array of attribute names and values
     */
    protected function parseWidgetAttributes(string $widget): array {
        $attributes = [];

        // Use DOMDocument to parse the HTML
        $dom = new DOMDocument();
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $widget, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Get the first element (should be the widget tag)
        $element = $dom->documentElement;

        if ($element && $element->hasAttributes()) {
            foreach ($element->attributes as $attr) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }
        }

        return $attributes;
    }

}
