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
	static function getUsagesOfObject($a_obj_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xesr_usage ".
			" WHERE id = ".$ilDB->quote($a_obj_id, "integer")
			);
		$usages = array();
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$usages[] = $rec;
		}
		return $usages;
	}
	
	/**
	 * Add usage
	 *
	 * @param
	 * @return
	 */
	static function addUsage($a_obj_id, $a_uri, $a_crs_ref_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xesr_usage ".
			" WHERE id = ".$ilDB->quote($a_obj_id, "integer").
			" AND edus_uri = ".$ilDB->quote($a_uri, "text").
			" AND crs_ref_id = ".$ilDB->quote($a_crs_ref_id, "integer")
			);
		$usages = array();
		if (!$ilDB->fetchAssoc($set))
		{
			$ilDB->manipulate("INSERT INTO rep_robj_xesr_usage ".
				"(id, edus_uri, crs_ref_id) VALUES (".
				$ilDB->quote($a_obj_id, "integer").",".
				$ilDB->quote($a_uri, "text").",".
				$ilDB->quote($a_crs_ref_id, "integer").
				")");
		}

	}
	
	/**
	 * Delete usage
	 *
	 * @param
	 * @return
	 */
	static function deleteUsage($a_obj_id, $a_uri, $a_crs_ref_id)
	{
		global $ilDB;
		
		$ilDB->manipulate("DELETE FROM rep_robj_xesr_usage ".
			" WHERE id = ".$ilDB->quote($a_obj_id, "integer").
			" AND edus_uri = ".$ilDB->quote($a_uri, "text").
			" AND crs_ref_id = ".$ilDB->quote($a_crs_ref_id, "integer")
			);
		
	}
	
}

?>
