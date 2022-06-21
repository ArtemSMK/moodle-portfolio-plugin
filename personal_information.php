<?php
require_once(__DIR__.'/inc.php');
require_once(__DIR__.'/lib/information_edit_form.php');

$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

block_exaport_require_login($courseid);

block_exaport_setup_default_categories();

$url = '/blocks/exaport/personal_information.php';
$PAGE->set_url($url, ['courseid' => $courseid]);

$userpreferences = block_exaport_get_user_preferences();
$description = $userpreferences->description;

$textfieldoptions = array('trusttext' => true, 'subdirs' => true, 'maxfiles' => 99, 'context' => context_user::instance($USER->id));

require_sesskey();

$informationform = new block_exaport_personal_information_form();

if ($informationform->is_cancelled()) {
    redirect('resume.php?courseid='.$courseid);
    exit;
} else if ($fromform = $informationform->get_data()) {
    $fromform = file_postupdate_standard_editor($fromform,
                                                'description',
                                                $textfieldoptions,
                                                context_user::instance($USER->id),
                                                'block_exaport',
                                                'personal_information',
                                                $USER->id);
    block_exaport_set_user_preferences(array('description' => $fromform->description, 'persinfo_timemodified' => time()));

    redirect('resume.php?courseid='.$courseid);
    exit;
}
$data = new stdClass();
$data->courseid = $courseid;
$data->description = $description;
$data->descriptionformat = FORMAT_HTML;
$data->cataction = 'save';
$data->edit = 1;

$data = file_prepare_standard_editor($data,
                                    'description',
                                    $textfieldoptions,
                                    context_user::instance($USER->id),
                                    'block_exaport',
                                    'personal_information',
                                    $USER->id);
$informationform->set_data($data);

block_exaport_print_header("resume_my");

echo "<div class='block_eportfolio_center'><h2>";
echo $OUTPUT->box(text_to_html(get_string("explainpersonal", "block_exaport")), 'center');
echo "</h2></div>";

$informationform->display();

echo block_exaport_print_footer();
