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


	public function __construct() {
		global $DIC;

		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();
		$this->tpl = $DIC['tpl'];
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
	}


	/**
	 * Save new element
	 */
	public function create(): void {
		global $DIC;
		$properties = $this->getProperties();
		
//		if (isset($_POST["edus_svalue"])) {
//			$this->plugin->setResId($this->plugin->addUsage(""));
//			$properties['resId'] = $this->plugin->getResId();
//			$this->createElement($properties);
//
//			$a_search = $properties['search'];
//			try {
//				$ticket = $this->getTicket();
//				$stext = ilUtil::stripSlashes($_POST["edus_svalue"]);
//				$re_url = ILIAS_HTTP_PATH.'/'.$DIC->ctrl()->getLinkTarget($this, "create", "", false, false).'&resId='.$properties['resId'];
//				// $re_url = str_replace(array("hier_id=pg"), 'hier_id=1', $re_url);
//				$url = ilObjLfEduSharingResourceGUI::buildUrl("search", $ticket, $stext, $re_url, $DIC->user());
//			 } catch (Exception $e) {
//				ilUtil::sendFailure("Create failed", true);
//                $DIC->ctrl()->redirect($this, "edit");
//            }
//			ilUtil::redirect($url);
//		} else {
            $this->plugin->setResId($this->plugin->addUsage(""));
            $properties['resId'] = $this->plugin->getResId();
			$resId = $properties['resId'];

			if (!isset($resId)) {
//                $resId = $_REQUEST["resId"];//Problem in 5.2 nur bei erstem Eintrag! Datenbankeintrag vorhanden.
                $resId = ilUtil::stripSlashes($DIC->http()->wrapper()->query()->retrieve("resId", $DIC->refinery()->kindlyTo()->int()));
            }
//			$eduuri = ilUtil::stripSlashes($_REQUEST["nodeId"]);
        $eduuri = ilUtil::stripSlashes($DIC->http()->wrapper()->query()->retrieve("nodeId", $DIC->refinery()->kindlyTo()->string()));
			$this->plugin->setResId($resId);
			$this->plugin->setUri($eduuri);

//			$this->plugin->setMimetype($_REQUEST["mimeType"]);
        $this->plugin->setMimetype($DIC->http()->wrapper()->query()->retrieve("mimeType", $DIC->refinery()->kindlyTo()->string()));
//			$this->plugin->setObjectVersion($_REQUEST["v"]);
        $this->plugin->setObjectVersion($DIC->http()->wrapper()->query()->retrieve("v", $DIC->refinery()->kindlyTo()->string()));
//			$this->plugin->setWindowWidthOrg($_REQUEST["w"]);
        $this->plugin->setWindowWidthOrg($DIC->http()->wrapper()->query()->retrieve("w", $DIC->refinery()->kindlyTo()->int()));
//			$this->plugin->setWindowHeightOrg($_REQUEST["h"]);
        $this->plugin->setWindowHeightOrg($DIC->http()->wrapper()->query()->retrieve("h", $DIC->refinery()->kindlyTo()->int()));
//			$this->plugin->setWindowWidth($_REQUEST["w"]);
        $this->plugin->setWindowWidth($DIC->http()->wrapper()->query()->retrieve("w", $DIC->refinery()->kindlyTo()->int()));
//			$this->plugin->setWindowHeight($_REQUEST["h"]);
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
                //delete
                $DIC->ui()->mainTemplate()->setOnScreenMessage('failure', "Create failed (usageResult = false)", true);
                $this->returnToParent();
            }

			$this->edit();
//		}
	}
	
	/**
	 * Edit
	 */
	public function edit(): void {
		$properties = $this->getProperties();
        $this->plugin->setVars((int) $properties['resId']);
        $form = $this->editform();
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * Update
	 */
	public function update()
	{
        global $DIC;

		$resId = 0;
//		$resId = $_POST["resId"];
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
		// check whether usage is registered
		//if ($this->plugin->getUri() != "" && !$this->plugin->checkRegisteredUsage())//$properties['esresource']!=$this->plugin->getUri()
		// {
			// ilUtil::sendFailure($this->plugin->txt("usage_not_registered"));
			// $ilToolbar->addFormButton($this->plugin->txt("register_usage"), "registerUsage");
		// }
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

			// $ni = new ilNumberInputGUI($this->plugin->txt("window_height"), "window_height");
			// $ni->setMaxLength(4);
			// $ni->setSize(4);
			// $ni->setRequired(true);
			// $ni->setInfo($this->plugin->txt("window_height_info").' '.$this->plugin->getWindowHeightOrg());
			// $ni->setValue($this->plugin->getWindowHeight());
			// $form->addItem($ni);
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
		$this->plugin->setResId($a_properties['resId']);
		$this->plugin->setVars($a_properties['resId']);
		if ($this->plugin->getUri() == "") return $this->plugin->txt("failure_create");
		$counter = $this->plugin->getCounter($a_properties['resId']);
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
//		if ($counter == 0) $html .= '<script type="text/javascript" src="./Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/js/edu.js"></script>';

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

        return $html;
    }

	protected function getTicket() : string
    {
        $eduSharingService = new EduSharingService();
        return $eduSharingService->getTicket();
	}

}
