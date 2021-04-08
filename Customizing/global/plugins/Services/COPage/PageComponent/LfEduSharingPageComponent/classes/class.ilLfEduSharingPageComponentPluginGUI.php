<?php
include_once("./Services/COPage/classes/class.ilPageComponentPluginGUI.php");

/**
 * LfEduSharing Page Component GUI
 *
 * @ilCtrl_isCalledBy ilLfEduSharingPageComponentPluginGUI: ilPCPluggedGUI
 */
class ilLfEduSharingPageComponentPluginGUI extends ilPageComponentPluginGUI {

	/** @var  ilLanguage $lng */
	protected $lng;

	/** @var  ilCtrl $ctrl */
	protected $ctrl;

	/** @var  ilTemplate $tpl */
	protected $tpl;
	/**
	 * Fix autocomplete (Defined in parent)
	 *
	 * @var ilLfEduSharingPageComponentPlugin
	 */
	protected $plugin;


	/**
	 *
	 */
	public function __construct() {
		global $DIC;

		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();
		$this->tpl = $DIC['tpl'];
		// $this->LfEduSharing = ilLfEduSharing::getInstance();
	}


	/**
	 *
	 */
	public function executeCommand() {
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
	public function insert() {
		$this->getTicket();
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$ti = new ilTextInputGUI($this->lng->txt("search"), "edus_svalue");
		$ti->setMaxLength(200);
		$ti->setSize(30);
		$form->addItem($ti);
		$form->addCommandButton("create", $this->plugin->txt("search_and_create"));
		$form->setTitle($this->plugin->txt("search_and_create_form_title"));//$this->getPCGUI()->getHierId()
		$form->setFormAction($this->ctrl->getFormAction($this));

		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * Save new element
	 */
	public function create() {
		global $DIC;
		$properties = $this->getProperties();
		
		if (isset($_POST["edus_svalue"])) {
			$this->plugin->setResId($this->plugin->addUsage(""));
			$properties['resId'] = $this->plugin->getResId();
			$this->createElement($properties);

			$a_search = $properties['search'];
			$this->plugin->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduUtil.php");
			// try
			// {
				// $this->plugin->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.cclib.php');
				// $cclib = new mod_edusharing_web_service_factory();
				// $ticket = $cclib->edusharing_authentication_get_ticket();
				$ticket = $this->getTicket();

				$stext = ilUtil::stripSlashes($_POST["edus_svalue"]);
				$re_url = ILIAS_HTTP_PATH.'/'.$DIC->ctrl()->getLinkTarget($this, "create", "", false, false).'&resId='.$properties['resId'];
				// $re_url = str_replace(array("hier_id=pg"), 'hier_id=1', $re_url);
				$url = lfEduUtil::buildUrl("search", $ticket, $stext, $re_url, $DIC->user());
			// }
			// catch (Exception $e)
			// {
				// ilUtil::sendFailure(lfEduUtil::formatException($e), true);
				// $DIC->ctrl()->redirect($this, "edit");
			// }
			ilUtil::redirect($url);
		} else {
			$resId = $properties['resId'];
			if ($resId == "") $resId = $_REQUEST["resId"]; //Problem in 5.2 nur bei erstem Eintrag! Datenbankeintrag vorhanden.
			$eduuri = ilUtil::stripSlashes($_REQUEST["nodeId"]);
			$this->plugin->setResId($resId);
			$this->plugin->setUri($eduuri);
			$this->plugin->setMimetype($_REQUEST["mimeType"]);
			$this->plugin->setObjectVersion($_REQUEST["v"]);
			$this->plugin->setWindowWidthOrg($_REQUEST["w"]);
			$this->plugin->setWindowHeightOrg($_REQUEST["h"]);
			$this->plugin->setWindowWidth($_REQUEST["w"]);
			$this->plugin->setWindowHeight($_REQUEST["h"]);
			
			if ($this->plugin->updateUsage($resId) == true) {
				ilUtil::sendSuccess($this->lng->txt("msg_obj_created"), true);
			}
			
			$this->plugin->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lib.php');
			edusharing_add_instance($this->plugin);
			
			$this->returnToParent();
			// $this->edit();
		}
	}
	
	/**
	 * Edit
	 */
	public function edit() {
		$properties = $this->getProperties();
		$this->plugin->setVars($properties['resId']);
        $form = $this->editform();
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * Update
	 */
	public function update()
	{
		$resId = 0;
		$resId = $_POST["resId"];
		$this->plugin->setVars($resId);
		if ($this->plugin->getWindowWidthOrg() > 0) {
			$this->plugin->setWindowWidth($_POST["window_width"]);
			$scaleFactor = $this->plugin->getWindowWidth() / $this->plugin->getWindowWidthOrg();
			$newHeight = round($scaleFactor * $this->plugin->getWindowHeightOrg());
			$this->plugin->setWindowHeight($newHeight);
		}
		$this->plugin->setWindowFloat($_POST["window_float"]);
		$this->plugin->setObjectVersionUseExact($_POST["object_version_use_exact"]);
		if ($this->plugin->updateUsage($resId) == true) {
			ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
		}
        $form = $this->editform();
		$this->tpl->setContent($form->getHTML());
	}
	
	
	/**
	 * Init editing form
	 *
	 */
	protected function editform() {
		global $DIC;
		if (isset($_REQUEST["resId"])) $resId = $_REQUEST["resId"];
		else {
			$properties = $this->getProperties();
			$resId = $properties['resId'];
		}
		
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		// check whether usage is registered
		// if ($this->plugin->getUri() != "" && !$this->plugin->checkRegisteredUsage())//$properties['esresource']!=$this->plugin->getUri()
		// {
			// ilUtil::sendFailure($this->plugin->txt("usage_not_registered"));
			// $ilToolbar->addFormButton($this->plugin->txt("register_usage"), "registerUsage");
		// }
		$ne = new ilNonEditableValueGUI($this->plugin->txt("uri"), "uri");
		$ne->setValue($this->plugin->getUri());
		$form->addItem($ne);
		// $ne = new ilNonEditableValueGUI($this->plugin->txt("version"), "version");
		// $ne->setValue($this->plugin->getObjectVersion());
		// $form->addItem($ne);
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
	 *
	 * @param string    page mode (edit, presentation, print, preview, offline)
	 * @return string   html code
	 */
	public function getElementHTML($a_mode, array $a_properties, $a_plugin_version)
	{
		$this->plugin->setResId($a_properties['resId']);
		$this->plugin->setVars($a_properties['resId']);
		if ($this->plugin->getUri() == "") return $this->plugin->txt("failure_create");
		$counter = $this->plugin->getCounter($a_properties['resId']);
		$html = "";
			// $this->plugin->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lib.php');
			// edusharing_add_instance($this->plugin);

		$this->plugin->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.locallib.php');
		//see proxy php
		// show properties stores in the page
		$this->plugin->includeClass("../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.lfEduUtil.php");
		
		$settings = new ilSetting("xedus");
		// $redirecturl = edusharing_get_redirect_url($this->plugin, 'window');
		// $ts = $timestamp = round(microtime(true) * 1000);
		// $redirecturl .= '&ts=' . $ts;
		// $data = $settings->get('application_appid') . $ts . edusharing_get_object_id_from_url($this->plugin->getUri());//object_url
		// $redirecturl .= '&sig=' . urlencode(edusharing_get_signature($data));
		// $redirecturl .= '&signed=' . urlencode($data);
		// // '&videoFormat=' . optional_param('videoFormat', '', PARAM_TEXT);
		// // $redirecturl .= '&closeOnBack=true';
		// $this->plugin->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.cclib.php');
		// $cclib = new mod_edusharing_web_service_factory();
		// $redirecturl .= '&ticket=' . urlencode(base64_encode(edusharing_encrypt_with_repo_public($cclib -> edusharing_authentication_get_ticket())));
		// $redirecturlInline = $redirecturl;

		$redirecturlInline = lfEduUtil::getRenderUrl($this->plugin, 'inline'); //4.1
		// $redirecturlInline = lfEduUtil::getRenderUrl($this->plugin, 'window');
// echo('<a href="'.$redirecturlInline.'">test</a>\r\n');
		$html .= '<div';
		if ($this->plugin->getWindowFloat() != 'no') $html .= ' style="float:'.$this->plugin->getWindowFloat().'"';
		$html .= '>'.$this->filter_edusharing_get_render_html($redirecturlInline).'</div>';
		$html = $this->filter_edusharing_display($html);
		if ($counter == 0) $html .= '<script type="text/javascript" src="./Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/js/edu.js"></script>';

		return $html;
	}


	// /**
	 // *
	 // */
	public function delete() {
		// TODO Delete LfEduSharing content on page component delete

		// The LfEduSharing page component contents will be deleted with the LfEduSharing cronjob
		
		// $properties = $this->getProperties();
		// die(a_properties['resId']);
		// $LfEduSharing_content = ilLfEduSharingContent::getContentById($properties["content_id"]);

		// if ($LfEduSharing_content !== NULL) {
			// $this->LfEduSharing->show_editor()->deleteContent($LfEduSharing_content);
		// }
		
	}


	// /**
	 // *
	 // */
	// public function copy() {
		// // TODO Copy LfEduSharing content on page component copy
		// $this->plugin->addInstanceAfterCopy();
	// }


	// /**
	 // *
	 // */
	// public function paste() {
		// // TODO Paste LfEduSharing content on page component paste
	// }






	/**
	 * @return ilLfEduSharingEditContentFormGUI
	 */
    /**
     * Get rendered object via curl
     *
     * @param string $url
     * @return string
     * @throws Exception
     */
    public function filter_edusharing_get_render_html($url) {
		$inline = "";
        try {
            $curlhandle = curl_init($url);
            curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curlhandle, CURLOPT_HEADER, 0);
            // DO NOT RETURN HTTP HEADERS
            curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, 1);
            // RETURN THE CONTENTS OF THE CALL
            curl_setopt($curlhandle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            curl_setopt($curlhandle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curlhandle, CURLOPT_SSL_VERIFYHOST, false);
            // fau: eduProxy - use proxy for eduSharing connection
            $proxy = ilProxySettings::_getInstance();
            if ($proxy->isActive()) {
                curl_setopt($curlhandle,CURLOPT_HTTPPROXYTUNNEL, true);
                if (!empty($proxy->getHost())) {
                    curl_setopt($curlhandle, CURLOPT_PROXY, $proxy->getHost());
                }
                if (!empty($proxy->getPort())) {
                    curl_setopt($curlhandle, CURLOPT_PROXYPORT, $proxy->getPort());
                }
            }
            // fau.
            $inline = curl_exec($curlhandle);
			if($inline === false) {
				ilLoggerFactory::getLogger('xesp')->warning(curl_error($curlhandle));
				ilUtil::sendFailure($this->plugin->txt("not_visible_now").' '.curl_error($curlhandle), true);
			}
        } catch (Exception $e) {
			ilLoggerFactory::getLogger('xesp')->warning($e->getMessage());
			ilUtil::sendFailure($this->plugin->txt("not_visible_now").' '.$e->getMessage(), true);
        }
        curl_close($curlhandle);
        return $inline;
    }
    /**
     * Prepare rendered object for display
     *
     * @param string $html
     */
    public function filter_edusharing_display($html) {

		$resid = $this->plugin->getResId();
		
		$html = str_replace(array("\n", "\r", "\n"), '', $html);

        /*
         * replaces {{{LMS_INLINE_HELPER_SCRIPT}}}
         */
        $html = str_replace("{{{LMS_INLINE_HELPER_SCRIPT}}}",
                ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/COPage/PageComponent/LfEduSharingPageComponent/inlineHelper.php?resId=" . $resid . "&ref_id=" . $_GET['ref_id'], $html);
		$customTitle = true;
        /*
         * For images, audio and video show a capture underneath object
         */
        // $mimetypes = array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'video', 'audio');
		$mimetypes = array('image', 'video', 'audio'); //4.2
        $addCaption = false;
        // foreach ($mimetypes as $mimetype) {
            // // if (strpos(optional_param('mimetype', '', PARAM_TEXT), $mimetype) !== false) {
            // if (strpos($this->plugin->getMimetype(), $mimetype) !== false) {
                // $addCaption = true;
				// $customTitle = false;
            // }
        // }
        // // if(strpos(optional_param('mediatype', '', PARAM_TEXT), 'tool_object') !== false) {
        // if(strpos($this->plugin->getMediaType(), 'tool_object') !== false) {
            // $addCaption = true;
			// $customTitle = false;
		// }
		// if($customTitle)
			// $html = preg_replace("/<es:title[^>]*>.*<\/es:title>/Uims", utf8_decode($this->plugin->getTitle())), $html);
        // if($addCaption)
            // $html .= '<p class="caption">' . $this->plugin->getTitle() . '</p>';

        return $html;
        // exit();
    }

	/**
	 * Get ticket
	 *
	 * @param
	 * @return
	 */
	function getTicket() {
		// include_once('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.cclib.php');
		$this->plugin->includeClass('../../../../Repository/RepositoryObject/LfEduSharingResource/lib/class.cclib.php');
		$cclib = new mod_edusharing_web_service_factory();
		return $cclib->edusharing_authentication_get_ticket();
	}

}
