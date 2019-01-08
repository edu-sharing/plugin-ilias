<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Edusharing utility class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 * @ingroup
 */
class lfEduUtil
{
    /**
     * Build edusharing url
     *
     * @param
     * @return
     */
    static function buildUrl($a_cmd, $a_ticket, $a_search_text = "", $a_re_url = "", $ilUser) {
		$settings = new ilSetting("xedus");
		require_once('class.locallib.php');
		require_once('class.cclib.php');
		$cclib = new mod_edusharing_web_service_factory();
		$ticket = $cclib->edusharing_authentication_get_ticket(); 

		$ccresourcesearch = trim($settings->get('application_cc_gui_url'), '/');
		if ($a_cmd == "search") {
			if(version_compare($settings->get('repository_version'), '4' ) >= 0) {
				$ccresourcesearch .= '/components/search';
				$ccresourcesearch .= '?locale=' . $ilUser->getLanguage();
				if ($a_search_text != "") {
					$ccresourcesearch .= "&query=" . urlencode($a_search_text);
				}
			} else {
				$ccresourcesearch .= "/?mode=0";
				$ccresourcesearch .= "&user=" . urlencode(edusharing_get_auth_key());
				$ccresourcesearch .= "&locale=" . $ilUser->getLanguage();
				if ($a_search_text != "") {
					$ccresourcesearch .= "&p_startsearch=1&p_searchtext=" . urlencode($a_search_text);
				}
			}
		} else {
			$ccresourcesearch .= "?locale=" . $ilUser->getLanguage();
		}
		$ccresourcesearch .= '&ticket='.$ticket;
		$ccresourcesearch .= '&applyDirectories=true'; // used in 4.2 or higher
		// $ccresourcesearch .= "&reurl=".urlencode($CFG->wwwroot."/mod/edusharing/makelink.php");
		if ($a_re_url != "") $ccresourcesearch .= "&reurl=".urlencode($a_re_url);
		//$ccresourcesearch = $CFG->wwwroot .'/mod/edusharing/selectResourceHelper.php?sesskey='.sesskey().'&rurl=' . urlencode($ccresourcesearch);
		return $ccresourcesearch;
	}

    /**
     * Get repository ID from URI
     *
     * @param string $a_uri URI
     * @return string repository ID
     */
    // public static function getRepIdFromUri($a_uri)
    // {
        // $rep_id = parse_url($a_uri, PHP_URL_HOST);

        // return $rep_id;
    // }

    /**
     * Get object ID from URI
     *
     * @param string $a_uri URI
     * @return string object ID
     */
    // static function getObjectIdFromUri($a_uri)
    // {
        // $object_id = parse_url($a_uri, PHP_URL_PATH);
        // $object_id = str_replace('/', '', $object_id);

        // return $object_id;
    // }

    /**
     * Get render url
     *
     * @param obj $edusharing
	 * @param string @displaymode (window, inline)
     * @return
     */
    static function getRenderUrl($edusharing, $displaymode) {
		//view.php
		$settings = new ilSetting("xedus");
		require_once('class.locallib.php');
		$redirecturl = edusharing_get_redirect_url($edusharing, $displaymode);
		$ts = $timestamp = round(microtime(true) * 1000);
		$redirecturl .= '&ts=' . $ts;
		$data = $settings->get('application_appid') . $ts . edusharing_get_object_id_from_url($edusharing->getUri());//object_url
		$redirecturl .= '&sig=' . urlencode(edusharing_get_signature($data));
		$redirecturl .= '&signed=' . urlencode($data);

		$backAction = '&closeOnBack=true';
		// if (empty($edusharing->popup_window)) {
			// $backAction = '&backLink=' . urlencode($CFG->wwwroot . '/course/view.php?id=' . $courseid);
		// }
		// if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'modedit.php') !== false) {
		// if (!empty($_SERVER['HTTP_REFERER'])) {
			// $backAction = '&backLink=' . urlencode($_SERVER['HTTP_REFERER']);
		// }

		if ($displaymode != "inline") $redirecturl .= $backAction;

		require_once('class.cclib.php');
		$cclib = new mod_edusharing_web_service_factory();
		$redirecturl .= '&ticket=' . urlencode(base64_encode(edusharing_encrypt_with_repo_public($cclib -> edusharing_authentication_get_ticket())));
		return $redirecturl;
	}


    /**
     * Format exception
     *
     * @param
     * @return
     */
    static function formatException($e)
    {
        $mess = "Sorry. An error occured when processing your edu-sharing request.";

        $mess.= "<br />".$e->getMessage()." (".$e->getCode()." / ".get_class($e).")";

        if (!empty($e->detail))
        {
            if (!empty($e->detail->fault))
            {
                if (!empty($e->detail->fault->message))
                {
                    $mess.= "<br />".$e->detail->fault->message;
                }
            }
            if (!empty($e->detail->exceptionName))
            {
                $mess.= "<br />exception name: ".$e->detail->exceptionName;
            }
            if (!empty($e->detail->hostname))
            {
                $mess.= "<br />hostname: ".$e->detail->hostname;
            }

//			$mess.= "<br />".print_r($e->detail, true);
        }

        $mess.= "<br /><br />".nl2br($e->getTraceAsString());


        return $mess;
    }

}

?>
