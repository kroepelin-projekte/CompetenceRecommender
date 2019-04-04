<#1>
<?php
?>
<#2>
<?php
$fields = array
(
    'id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'user_id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => false
    ),
    'name' => array(
        'type' => 'text',
        'length' => 200,
        'notnull' => true
    ),
    'value' => array(
        'type' => 'text',
        'length' => 200,
        'notnull' => true
    ),
);

$ilDB->createTable("ui_uihk_comprec_config", $fields);
$ilDB->addPrimaryKey("ui_uihk_comprec_config", array("id"));
$ilDB->createSequence("ui_uihk_comprec_config");
?>
<#3>
<?php
$ilDB->dropSequence("ui_uihk_comprec_config");
$ilDB->createSequence("ui_uihk_comprec_config", 1);
?>
