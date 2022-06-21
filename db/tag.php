<?php

defined('MOODLE_INTERNAL') || die();

$tagareas = array(
    array(
        'itemtype' => 'block_exaportitem',
        'component' => 'block_exaport',
        'callback' => 'block_exaport_get_tagged_items',
        'callbackfile' => '/blocks/exaport/lib/lib.php',
    ),
);
