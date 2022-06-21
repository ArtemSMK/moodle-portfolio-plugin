<?php

require_once(__DIR__.'/inc.php');

$courseid = optional_param('courseid', 0, PARAM_INT);

block_exaport_require_login($courseid);

$context = context_system::instance();
$url = '/blocks/exaport/importexport.php';
$PAGE->set_url($url, ['courseid' => $courseid]);

global $DB;
$conditions = array("id" => $courseid);
if (!$course = $DB->get_record("course", $conditions)) {
    error("That's an invalid course id");
}

block_exaport_print_header("importexport");

echo "<br />";

echo "<div class='block_eportfolio_center'>";

$OUTPUT->box(text_to_html(get_string("explainexport", "block_exaport")));

if (has_capability('block/exaport:export', $context)) {
    echo "<p ><img src=\"{$CFG->wwwroot}/blocks/exaport/pix/export.png\" height=\"16\" width=\"16\" alt='".
            get_string("export", "block_exaport")."' /> <a title=\"".get_string("export", "block_exaport").
            "\" href=\"{$CFG->wwwroot}/blocks/exaport/export_scorm.php?courseid=".$courseid."\">".
            get_string("export", "block_exaport")."</a></p>";
}

if (has_capability('block/exaport:import', $context)) {
    echo "<p ><img src=\"{$CFG->wwwroot}/blocks/exaport/pix/import.png\" height=\"16\" width=\"16\" alt='".
            get_string("import", "block_exaport")."' /> <a title=\"".get_string("import", "block_exaport").
            "\" href=\"{$CFG->wwwroot}/blocks/exaport/import_file.php?courseid=".$courseid."\">".
            get_string("import", "block_exaport")."</a></p>";
}

if (has_capability('block/exaport:importfrommoodle', $context)) {
    $modassign = block_exaport_assignmentversion();
    $assignments = block_exaport_get_assignments_for_import($modassign);
    if ($assignments) {
        echo "<p ><img src=\"{$CFG->wwwroot}/blocks/exaport/pix/import.png\" height=\"16\" width=\"16\" alt='" .
            get_string("moodleimport", "block_exaport") . "' /> <a title=\"" . get_string("moodleimport", "block_exaport") .
            "\" href=\"{$CFG->wwwroot}/blocks/exaport/import_moodle.php?courseid=" . $courseid . "\">" .
            get_string("moodleimport", "block_exaport") . "</a></p>";
    }
}

echo "</div>";
echo block_exaport_wrapperdivend();
echo $OUTPUT->footer($course);
