<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        'block/exaport:use' => array(
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'legacy' => array(
                        'user' => CAP_ALLOW
                )
        ),
        'block/exaport:export' => array(
                'captype' => 'read',
                'contextlevel' => CONTEXT_SYSTEM,
                'legacy' => array(
                        'user' => CAP_ALLOW
                )
        ),
        'block/exaport:import' => array(
                'riskbitmask' => RISK_SPAM | RISK_XSS,
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'legacy' => array(
                        'user' => CAP_ALLOW
                )
        ),
        'block/exaport:importfrommoodle' => array(
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'legacy' => array(
                        'user' => CAP_ALLOW
                )
        ),
        'block/exaport:shareintern' => array(
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'legacy' => array(
                        'user' => CAP_ALLOW
                )
        ),
        'block/exaport:shareextern' => array(
                'riskbitmask' => RISK_SPAM,
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'legacy' => array(
                        'user' => CAP_ALLOW
                )
        ),
        'block/exaport:allowposts' => array(
                'riskbitmask' => RISK_SPAM,
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'legacy' => array(
                        'user' => CAP_ALLOW
                )
        ),
        'block/exaport:competences' => array(
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'legacy' => array(
                        'coursecreator' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                )
        ),
        'block/exaport:myaddinstance' => array(
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array(
                        'user' => CAP_ALLOW
                ),
                'clonepermissionsfrom' => 'moodle/my:manageblocks'
        ),
        'block/exaport:addinstance' => array(
                'captype' => 'write',
                'contextlevel' => CONTEXT_BLOCK,
                'archetypes' => array(
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                ),
                'clonepermissionsfrom' => 'moodle/site:manageblocks'
        )
);
