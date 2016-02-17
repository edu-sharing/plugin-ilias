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

