<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Lf edu usage. Stores local information about usages
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 * @ingroup 
 */
class lfEduUsage
{
	/**
	 * Get usages of object
	 *
	 * @param
	 * @return
	 */
	// static function getUsagesOfObject($a_obj_id)
	// {
		// global $DIC;		
		// $set = $DIC->database()->query("SELECT * FROM rep_robj_xesr_usage ".
			// " WHERE id = ".$DIC->database()->quote($a_obj_id, "integer")
			// );
		// $usages = array();
		// while ($rec  = $DIC->database()->fetchAssoc($set))
		// {
			// $usages[] = $rec;
		// }
		// return $usages;
	// }
	
	/**
	 * Add usage
	 *
	 * @param
	 * @return
	 */
	// static function addUsage($a_obj_id, $a_uri, $a_crs_ref_id)
	// {
		// global $DIC;		
		// $set = $DIC->database()->query("SELECT * FROM rep_robj_xesr_usage ".
			// " WHERE id = ".$DIC->database()->quote($a_obj_id, "integer").
			// " AND edus_uri = ".$DIC->database()->quote($a_uri, "text").
			// " AND crs_ref_id = ".$DIC->database()->quote($a_crs_ref_id, "integer")
			// );
		// $usages = array();
		// if (!$DIC->database()->fetchAssoc($set))
		// {
			// $DIC->database()->manipulate("INSERT INTO rep_robj_xesr_usage ".
				// "(id, edus_uri, crs_ref_id) VALUES (".
				// $DIC->database()->quote($a_obj_id, "integer").",".
				// $DIC->database()->quote($a_uri, "text").",".
				// $DIC->database()->quote($a_crs_ref_id, "integer").
				// ")");
		// }

	// }
	
	/**
	 * Delete usage
	 *
	 * @param
	 * @return
	 */
	// static function deleteUsage($a_obj_id, $a_uri, $a_crs_ref_id)
	// {
		// global $DIC;		
		// $DIC->database()->manipulate("DELETE FROM rep_robj_xesr_usage ".
			// " WHERE id = ".$DIC->database()->quote($a_obj_id, "integer").
			// " AND edus_uri = ".$DIC->database()->quote($a_uri, "text").
			// " AND crs_ref_id = ".$DIC->database()->quote($a_crs_ref_id, "integer")
			// );
		
	// }
	
}

?>
