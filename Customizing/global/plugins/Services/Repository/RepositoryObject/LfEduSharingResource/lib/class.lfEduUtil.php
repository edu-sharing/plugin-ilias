<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Edusharing utility class 
 *
 * @author Alex Killing <alex.killing@gmx.de>
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
	static function buildUrl($a_home_app_conf, $a_cmd, $a_ticket, $a_search_text = "",
		$a_re_url = "")
	{
		global $ilUser;
		
		$pars = array();
		
		$link = $a_home_app_conf->getEntry('cc_gui_url');
		if ($link == "")
		{
			die("No cc_gui_url given.");
		}
		
		switch ($a_cmd)
		{
			case "search":
				$path = '/components/search';
				if ($a_search_text != "")
				{
					$pars["p_startsearch"] = 1;
					$pars["p_searchtext"] = urlencode($a_search_text);
				}
				break;
				
			default:
                $path = '/components/workspace';
		}
		
		if ($a_re_url != "")
		{
			$pars["reurl"] = urlencode($a_re_url);
		}
		
		// build link to search
		$link .= $path;

		// todo: what if no email given!?
		$user = $ilUser->getEmail();
		$link .= '?user='.urlencode($user);

		// add ticket
		$link .= '&ticket='.urlencode($a_ticket);

		// add language
		$link .= '&locale='.urlencode($ilUser->getLanguage());
        $link .= '&language='.urlencode($ilUser->getLanguage());

		if (is_array($pars))
		{
			foreach ($pars  as $k => $v)
			{
				$link.= "&".$k."=".$v;
			}
		}
		
		return $link;
	}

	/**
	 * Get repository ID from URI
	 *
	 * @param string $a_uri URI
	 * @return string repository ID
	 */
	function getRepIdFromUri($a_uri)
	{
		$rep_id = parse_url($a_uri, PHP_URL_HOST);

		return $rep_id;
	}
	
	/**
	 * Get object ID from URI
	 *
	 * @param string $a_uri URI
	 * @return string object ID
	 */
	static function getObjectIdFromUri($a_uri)
	{
		$object_id = parse_url($a_uri, PHP_URL_PATH);
		$object_id = str_replace('/', '', $object_id);
		
		return $object_id;
	}
	
	/**
	 * Get render url
	 *
	 * @param
	 * @return
	 */
	static function getRenderUrl($a_obj)
	{
		global $ilUser;

		$home_app_conf = $a_obj -> getHomeAppConf();
		$url = $home_app_conf->getEntry("contenturl");
		$app_id = $home_app_conf->getEntry('appid');

		$url.= '?app_id='.urlencode($app_id);

		// problem: esrender does not allow many necessary characters
		$sess_id = session_id();
		$url.= '&session='.urlencode($sess_id);

		$rep_id = $home_app_conf->getEntry("homerepid");

		$url.= '&rep_id='.urlencode($rep_id);

		$res_ref = str_replace('/', '', parse_url($a_obj->getUri(), PHP_URL_PATH));
		if ($res_ref == "")
		{
			include_once("class.ilLfEduSharingLibException.php");
			throw new ilLfEduSharingLibException('Error parsing resource-url "'.$a_obj->getUri().'".');
		}
		
		$url.= '&obj_id='.urlencode($res_ref);
		
		$url.= '&resource_id='.$a_obj->getRefId();
		$url.= '&course_id='.$a_obj->getUpperCourse();

        $ES_KEY = $home_app_conf->getEntry("encrypt_key");
        $ES_IV = $home_app_conf->getEntry("encrypt_initvector");
		$url.= '&u=' . urlencode(base64_encode(mcrypt_cbc(MCRYPT_BLOWFISH, $ES_KEY, $ilUser->getEmail(), MCRYPT_ENCRYPT, $ES_IV)));
        
        $ts = round(microtime(true) * 1000);
        $url .= '&ts=' . $ts;
        $url .= '&display=window';
        
        $data = $app_id . $ts . $res_ref;
        $priv_key = $home_app_conf->getEntry('private_key');
        $pkeyid = openssl_get_privatekey($priv_key);      
        openssl_sign($data, $signature, $pkeyid);
        $signature = base64_encode($signature);
        openssl_free_key($pkeyid);    
        
        $url .= '&sig=' . urlencode($signature);
        $url .= '&signed=' . $data;


        $ticket = $a_obj->getTicket();
        $repoPublicKey = $home_app_conf->getEntry('repo_public_key');
        if(empty($repoPublicKey)) {
            include_once("class.ilLfEduSharingLibException.php");
            throw new ilLfEduSharingLibException('Error fetching repository public key.');
        }

        $encryptedTicket = '';
        $key = openssl_get_publickey($repoPublicKey);
        openssl_public_encrypt($ticket ,$encryptedTicket, $key);
        if($encryptedTicket === false) {
            include_once("class.ilLfEduSharingLibException.php");
            throw new ilLfEduSharingLibException('Error encryting ticket.');
        }

        $url .= '&ticket=' . $encryptedTicket;

		return $url;
	}
	
	/**
	 * Format exception
	 *
	 * @param
	 * @return
	 */
	function formatException($e)
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
