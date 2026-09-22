<?php

/* Copyright (c) 2023 internetlehrer GmbH, GPL */

use EduSharingApiClient\EduSharingHelperBase;

/**
 * User Interface class for edusharing resource repository object.
 *
 * User interface classes process GET and POST parameter and call
 * application classes to fulfill certain tasks.
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 *
 * $Id$
 *
 * @ilCtrl_isCalledBy ilObjLfEduSharingResourceGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjLfEduSharingResourceGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
 * @ilCtrl_Calls ilObjLfEduSharingResourceGUI: ilCommonActionDispatcherGUI
 *
 */
class ilObjLfEduSharingResourceGUI extends ilObjectPluginGUI
{

	protected ilPropertyFormGUI $form;
	/**
	 * Initialisation
	 */
	protected function afterConstructor() : void
	{
		// anything needed after object has been constructed
		// - example: append my_id GET parameter to each request
		//   $ilCtrl->saveParameter($this, array("my_id"));
	}

	/**
	 * Get type.
	 */
	final function getType() : string
	{
		return "xesr";
	}

	/**
	 * Handles all commmands of this class, centralizes permission checks
	 */
	public function performCommand($cmd) : void
	{
		switch ($cmd) {
			case "editProperties":        // list all commands that need write permission here
			case "updateProperties":
			case "browseResource":
			case "uploadResource":
			case "searchResource":
			case "setResource":
			case "registerUsage":
				$this->checkPermission("write");
				$this->$cmd();
				break;

			case "showContent":            // list all commands that need read permission here
				$this->checkPermission("read");
				$this->$cmd();
				break;
		}
	}

	/**
	 * After object has been created -> jump to this command
	 */
	public function getAfterCreationCmd() : string
	{
		return "editProperties";
	}

	/**
	 * Get standard command
	 */
	public function getStandardCmd() : string
	{
		return "infoScreen";//"showContent";
	}

//
// DISPLAY TABS
//

	protected function setTabs() : void
	{
		global $DIC;

		// tab for the "show content" command
		if ($DIC->access()->checkAccess("read", "", $this->object->getRefId())) {
			$DIC->tabs()->addTab("content", $this->txt("content"), $DIC->ctrl()->getLinkTarget($this, "showContent")
				, "_blank");
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($DIC->access()->checkAccess("write", "", $this->object->getRefId())) {
			$DIC->tabs()->addTab("properties", $this->txt("properties"),
				$DIC->ctrl()->getLinkTarget($this, "editProperties"));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}

	/**
	 * Edit Properties. This commands uses the form class to display an input form.
	 */
	protected function editProperties()
	{
		global $DIC;
		$ilToolbar = $DIC->toolbar();
		// toolbar
		$ilToolbar->setFormAction($DIC->ctrl()->getFormAction($this));
		if ($this->object->getUri() == "") {
			// //set parent_obj for edu-sharing
			// $this->object->afterCreateSetParentObj();
			$ilToolbar->addText($this->plugin->txt("select_resource"));
			$ti = new ilHiddenInputGUI("edus_svalue");
//			$ti->setMaxLength(200);
//			$ti->setSize(30);
			$ilToolbar->addInputItem($ti, false);
			$ilToolbar->addFormButton($this->plugin->txt("search"), "searchResource");

			$settings = new ilSetting("xedus");
			$guestoption = $settings->get('edu_guest_option');
			if (empty($settings->get('edu_guest_option'))) {
				$ilToolbar->addSeparator();
				$ilToolbar->addFormButton($this->plugin->txt("upload"), "uploadResource");
			}

		}

		// check whether upper course is given
		if ($this->object->getUri() != "" && $this->object->getUpperCourse() == 0) {
			$DIC->ui()->mainTemplate()->setOnScreenMessage('failure',
				$this->plugin->txt("not_usable_no_parent_object"));
		}
		// else if ($this->object->getUri() == "" && !$this->object->checkRegisteredUsage())
		// {
		// ilUtil::sendFailure($this->plugin->txt("failure after copy"));
		// }
		else {
			// check whether usage is registered
			if ($this->object->getUri() != "" && !$this->object->checkRegisteredUsage()) {
				$DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->plugin->txt("usage_not_registered"));
				if ($this->object->getUpperCourse() > 0) {
					$ilToolbar->addFormButton($this->plugin->txt("register_usage"), "registerUsage");
				}
			}
		}

		$DIC->tabs()->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$DIC->ui()->mainTemplate()->setContent($this->form->getHTML());
	}

	/**
	 * Init  form.
	 * @param int $a_mode Edit Mode
	 */
	public function initPropertiesForm()
	{
		global $DIC;

		$this->form = new ilPropertyFormGUI();

		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);

		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);

		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);

		// version setting; not available for published copies and collection references
		if (!$this->object->getVersionRestricted()) {
			$cb = new ilCheckboxInputGUI($this->plugin->txt("object_version_use_exact"), "object_version_use_exact");
			$cb->setValue("1");
			$cb->setInfo($this->plugin->txt("object_version_use_exact_info") . ' ' . $this->object->getObjectVersion());
			$this->form->addItem($cb);
		}

		// uri
		$ne = new ilNonEditableValueGUI($this->lng->txt("uri"), "uri");
		$ne->setValue($this->object->getUri());
		$this->form->addItem($ne);

		$this->form->addCommandButton("updateProperties", $this->txt("save"));

		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($DIC->ctrl()->getFormAction($this));
	}

	/**
	 * Get values for edit properties form
	 */
	protected function getPropertiesValues() : void
	{
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["uri"] = $this->object->getUri();
		$values["online"] = $this->object->getOnline();
		$values["object_version_use_exact"] = $this->object->getObjectVersionUseExact();
		$this->form->setValuesByArray($values);
	}

	/**
	 * Update properties
	 */
	public function updateProperties()
	{
		global $DIC;

		$this->initPropertiesForm();
		if ($this->form->checkInput()) {
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setOnline((int) $this->form->getInput("online"));
			$this->object->setObjectVersionUseExact(
				$this->object->getVersionRestricted() ? 0 : (int) $this->form->getInput("object_version_use_exact")
			);
			$this->object->update();
			$DIC->ui()->mainTemplate()->setOnScreenMessage('success', $this->lng->txt("msg_obj_modified"), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$DIC->ui()->mainTemplate()->setContent($this->form->getHtml());
	}

	/**
	 * Search resource
	 */
	protected function searchResource()
	{
		global $DIC;

		try {
			$ticket = $this->object->getTicket();
			$stext = ilUtil::stripSlashes($DIC->http()->wrapper()->post()->retrieve("edus_svalue", $DIC->refinery()->kindlyTo()->string()));
			$re_url = ILIAS_HTTP_PATH . '/' . $DIC->ctrl()->getLinkTarget($this, "setResource", "", false, false);
			$url = $this->buildUrl("search", $ticket, $stext, $re_url, $DIC->user());
			ilUtil::redirect($url);
		} catch (Exception $e) {
			$DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}
	}

	/**
	 * Browse for a resource
	 */
	protected function browseResource()
	{
		global $DIC;

		try {
//			$this->plugin->includeClass("../lib/class.lfEduUtil.php");
			$ticket = $this->object->getTicket();
			$re_url = ILIAS_HTTP_PATH . '/' . $DIC->ctrl()->getLinkTarget($this, "setResource", "", false, false);
			$url = $this->buildUrl("browse", $ticket, "", $re_url, $DIC->user());
		} catch (Exception $e) {
			$DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}

		ilUtil::redirect($url);
	}

	/**
	 * Upload resource //needed anymore?
	 */
	function uploadResource()
	{
		global $DIC;

		// see mod/mod_form.php 114

		try {
			$ticket = $this->object->getTicket();
			$re_url = ILIAS_HTTP_PATH . '/' . $DIC->ctrl()->getLinkTarget($this, "setResource", "", false, false);
			$url = $this->buildUrl("upload", $ticket, "", $re_url, $DIC->user());
		} catch (Exception $e) {
			$DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}

		ilUtil::redirect($url);
	}

	/**
	 * Set resource
	 * @param
	 */
	public function setResource() : void
	{
		global $DIC;

		try {
			$new_uri = ilUtil::stripSlashes($_REQUEST["nodeId"]);
			$this->object->setUri($new_uri);
			$version = ilUtil::stripSlashes($_REQUEST["v"] ?? '');//query, absent for nodes without version
			$this->object->setObjectVersion($version);
			$service = new EduSharingService();
			$utils = new EduSharingUtilityFunctions();
			if ($service->isVersioningRestricted($utils->getObjectIdFromUrl($new_uri), $version)) {
				$this->object->setVersionRestricted(1);
				$this->object->setObjectVersionUseExact(0);
			} else {
				$this->object->setVersionRestricted(0);
			}
			$this->object->update();
		} catch (Exception $e) {
			$DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}
		$DIC->ctrl()->redirect($this, "editProperties");
	}

	/**
	 * Register usage
	 */
	public function registerUsage(): void
	{
		global $DIC;

		try {
//$this->object->deleteAllUsages();
			$this->object->setUsage();
		} catch (Exception $e) {
			$DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->formatException($e), true);
			$DIC->ctrl()->redirect($this, "editProperties");
		}

		$DIC->ctrl()->redirect($this, "editProperties");
	}

	/**
	 * Show content
	 */
	protected function showContent()
	{
		global $DIC;
		$DIC->tabs()->activateTab("content");

		if ($this->object->getUri() == "") {
			$DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->plugin->txt("no_resource_set"), true);
			return;
		}

		if (!$this->object->checkRegisteredUsage()) {
			$DIC->ui()->mainTemplate()->setOnScreenMessage('failure', $this->plugin->txt("not_visible_now"), true);
			return;
		}

		$edusharing = $this->object; //new stdClass();

		$displaymode = 'window';

		$settings = new ilSetting("xedus");

		$eduSharingService = new EduSharingService();
		$utils = new EduSharingUtilityFunctions();
		$redirectUrl = $utils->getRedirectUrl($edusharing);
		$ts = $timestamp = round(microtime(true) * 1000);
		$redirectUrl .= '&ts=' . $ts;
		$data = $settings->get('application_appid') . $ts . $utils->getObjectIdFromUrl($edusharing->getUri());
		$baseHelper = new EduSharingHelperBase(
			$settings->get('application_cc_gui_url'),
			$settings->get('application_private_key'),
			$settings->get('application_appid')
		);
		$redirectUrl .= '&sig=' . urlencode($baseHelper->sign($data));

		$redirectUrl .= '&signed=' . urlencode($data);

		$backAction = '&closeOnBack=true';
		// if (empty($edusharing->popup_window)) {
		// $backAction = '&backLink=' . urlencode($CFG->wwwroot . '/course/view.php?id=' . $courseid);
		// }
		// if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'modedit.php') !== false) {
//		 if (!empty($_SERVER['HTTP_REFERER'])) {
//		$backAction = '&backLink=' . urlencode($_SERVER['HTTP_REFERER']);
//	}
		if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'ilobjectcopygui') !== false) {
			$backAction = '';//'&backLink=' . urlencode('http://172.18.0.1/release_7/'.$DIC->ctrl()->getLinkTarget($this, "editProperties"));
		}

		if ($displaymode != "inline") {
			$redirectUrl .= $backAction;
		}

		$ticket = $eduSharingService->getTicket();
		$redirectUrl .= '&ticket=' . urlencode(base64_encode($utils->encryptWithRepoKey($ticket)));

		ilUtil::redirect($redirectUrl);
	}

	public static function buildUrl(string $a_cmd, string $a_ticket, string $a_search_text, ?string $a_re_url, $ilUser): string
	{
		$settings = new ilSetting("xedus");

		$ccresourcesearch = trim($settings->get('application_cc_gui_url'), '/');
		if ($a_cmd == "search") {
			if (version_compare($settings->get('repository_version'), '4') >= 0) {
				$ccresourcesearch .= '/components/search';
				$ccresourcesearch .= '?locale=' . $ilUser->getLanguage();
				if ($a_search_text != "") {
					$ccresourcesearch .= "&query=" . urlencode($a_search_text);
				}
			} else {
				$ccresourcesearch .= "/?mode=0";
//				$ccresourcesearch .= "&user=" . urlencode(edusharing_get_auth_key());
				$ccresourcesearch .= "&locale=" . $ilUser->getLanguage();
				if ($a_search_text != "") {
					$ccresourcesearch .= "&p_startsearch=1&p_searchtext=" . urlencode($a_search_text);
				}
			}
		} else {
			$ccresourcesearch .= "?locale=" . $ilUser->getLanguage();
		}
		$ccresourcesearch .= '&ticket=' . $a_ticket;
		$ccresourcesearch .= '&applyDirectories=true'; // used in 4.2 or higher
		// $ccresourcesearch .= "&reurl=".urlencode($CFG->wwwroot."/mod/edusharing/makelink.php");
		if ($a_re_url != "") {
			$ccresourcesearch .= "&reurl=" . urlencode($a_re_url);
		}
		//$ccresourcesearch = $CFG->wwwroot .'/mod/edusharing/selectResourceHelper.php?sesskey='.sesskey().'&rurl=' . urlencode($ccresourcesearch);
		return $ccresourcesearch;
	}

	public static function formatException(Exception $e) : string
	{
		$mess = "Sorry. An error occured when processing your edu-sharing request.";

		$mess .= "<br />" . $e->getMessage() . " (" . $e->getCode() . " / " . get_class($e) . ")";

		if (!empty($e->detail)) {
			if (!empty($e->detail->fault)) {
				if (!empty($e->detail->fault->message)) {
					$mess .= "<br />" . $e->detail->fault->message;
				}
			}
			if (!empty($e->detail->exceptionName)) {
				$mess .= "<br />exception name: " . $e->detail->exceptionName;
			}
			if (!empty($e->detail->hostname)) {
				$mess .= "<br />hostname: " . $e->detail->hostname;
			}

//			$mess.= "<br />".print_r($e->detail, true);
		}

		$mess .= "<br /><br />" . nl2br($e->getTraceAsString());

		return $mess;
	}

}
?>
