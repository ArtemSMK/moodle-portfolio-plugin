<?php
require_once(__DIR__.'/inc.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
redirect('resume.php?courseid='.$courseid);
