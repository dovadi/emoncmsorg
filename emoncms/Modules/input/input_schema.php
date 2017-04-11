<?php

$schema['input'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
    'nodeid' => array('type' => 'varchar(16)'),
    'name' => array('type' => 'varchar(16)'),
    'description' => array('type' => 'text','default'=>''),
    'processList' => array('type' => 'text'),
    'time' => array('type' => 'datetime'),
    'value' => array('type' => 'float')
);
