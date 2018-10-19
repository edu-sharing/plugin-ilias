<#1>
<?php

$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'edus_uri' => array(
		'type' => 'text',
		'length' => 1000,
		'fixed' => false,
		'notnull' => false
	)
);

$ilDB->createTable("rep_robj_xesr_data", $fields);
$ilDB->addPrimaryKey("rep_robj_xesr_data", array("id"));

?>
<#2>
<?php

$ilDB->addTableColumn("rep_robj_xesr_data", "is_online", array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
	);

?>
<#3>
<?php

$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'edus_uri' => array(
		'type' => 'text',
		'length' => 1000,
		'fixed' => false,
		'notnull' => false
	),
	'crs_ref_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);

$ilDB->createTable("rep_robj_xesr_usage", $fields);
$ilDB->addIndex("rep_robj_xesr_usage", array("id"), "i1");

?>
<#4>
<?php

$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'edus_uri' => array(
		'type' => 'text',
		'length' => 1000,
		'fixed' => false,
		'notnull' => false
	),
	'obj_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
);

$ilDB->createTable("rep_robj_xesp_usage", $fields);
?>
<#5>
<?php
	$ilDB->addPrimaryKey("rep_robj_xesp_usage", array("id"));
?>
<#6>
<?php
	$ilDB->createSequence("rep_robj_xesp_usage");
?>
<#7>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'active')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'active', 
		array('type' => 'integer', 'length' => 1, 'notnull' => true, 'default' => 1) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'mimetype')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'mimetype', 
		array('type' => 'text', 'length' => 100, 'notnull' => false) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'object_version')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'object_version', 
		array('type' => 'text', 'length' => 32, 'notnull' => false) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'object_version_use_exact')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'object_version_use_exact', 
		array('type' => 'integer', 'length' => 1, 'notnull' => true, 'default' => 1) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'window_float')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'window_float', 
		array('type' => 'text', 'length' => 6, 'notnull' => false) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'window_width_org')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'window_width_org', 
		array('type' => 'integer', 'length' => 2, 'notnull' => false) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'window_height_org')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'window_height_org', 
		array('type' => 'integer', 'length' => 2, 'notnull' => false) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'window_width')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'window_width', 
		array('type' => 'integer', 'length' => 2, 'notnull' => false) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'window_height')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'window_height', 
		array('type' => 'integer', 'length' => 2, 'notnull' => false) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'timecreated')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'timecreated', 
		array('type' => 'timestamp', 'notnull' => true) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesp_usage', 'timemodified')) {
	$ilDB->addTableColumn('rep_robj_xesp_usage', 'timemodified', 
		array('type' => 'timestamp', 'notnull' => true) );
}

?>
<#8>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xesr_usage', 'object_version')) {
	$ilDB->addTableColumn('rep_robj_xesr_usage', 'object_version', 
		array('type' => 'text', 'length' => 32, 'notnull' => false) );
}

?>
<#9>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xesr_usage', 'object_version_use_exact')) {
	$ilDB->addTableColumn('rep_robj_xesr_usage', 'object_version_use_exact', 
		array('type' => 'integer', 'length' => 1, 'notnull' => true, 'default' => 1) );
}
?>
<#10>
<?php
if( $ilDB->indexExistsByFields('rep_robj_xesr_usage', array('id')) ) {
	$ilDB->dropIndexByFields('rep_robj_xesr_usage',array('id'));
}

if(!$ilDB->tableColumnExists('rep_robj_xesr_usage', 'parent_obj_id')) {
	$ilDB->addTableColumn('rep_robj_xesr_usage', 'parent_obj_id', 
		array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0) );
}
if(!$ilDB->tableColumnExists('rep_robj_xesr_usage', 'is_online')) {
	$ilDB->addTableColumn('rep_robj_xesr_usage', 'is_online', 
		array('type' => 'integer', 'length' => 1, 'notnull' => true, 'default' => 1) );
}

?>
<#11>
<?php
if($ilDB->tableColumnExists('rep_robj_xesr_usage', 'parent_obj_id')) {
	$ilDB->manipulate("DELETE FROM rep_robj_xesr_usage WHERE id=0");

	$ilDB->addPrimaryKey("rep_robj_xesr_usage", array("id","parent_obj_id"));
}
?>
<#12>
<?php
if(!$ilDB->tableColumnExists('rep_robj_xesr_usage', 'timecreated')) {
	$ilDB->addTableColumn('rep_robj_xesr_usage', 'timecreated', 
		array('type' => 'timestamp', 'notnull' => true) );
}

if(!$ilDB->tableColumnExists('rep_robj_xesr_usage', 'timemodified')) {
	$ilDB->addTableColumn('rep_robj_xesr_usage', 'timemodified', 
		array('type' => 'timestamp', 'notnull' => true) );
}

?>
