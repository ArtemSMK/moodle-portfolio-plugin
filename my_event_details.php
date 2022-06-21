<?php

use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Dompdf\Exception;

require_once(__DIR__.'/inc.php');
require_once(__DIR__.'/blockmediafunc.php');

//  $access = optional_param('access', 0, PARAM_TEXT);

require_login(0, true);

// main content:
$generalContent = '';

$url = '/blocks/exaport/my_event_details.php';
$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

//  if (!$view = block_exaport_get_view_from_access($access)) {
//      print_error("viewnotfound", "block_exaport");
//  }

$isPdf = optional_param('ispdf', 0, PARAM_INT);

$conditions = array("id" => $view->userid);
// if (!$user = $DB->get_record("user", $conditions)) {
//     print_error("nouserforid", "block_exaport");
// }

$portfoliouser = block_exaport_get_user_preferences($user->id);

// Read blocks.
$query = "select b.*". // , i.*, i.id as itemid".
        " FROM {block_exaportviewblock} b".
        " WHERE b.viewid = ? ORDER BY b.positionx, b.positiony";

        $query2 = "select b.*". // , i.*, i.id as itemid".
        " FROM {block_exaportview} b".
         " WHERE b.id = ?";     

$blocks = $DB->get_records_sql($query, array($view->id));
$blocks2 = $DB->get_record_sql($query2, array($_GET['viewID']));



$badges = block_exaport_get_all_user_badges($view->userid);

// Read columns.
$columns = array();
foreach ($blocks as $block) {
    if (!isset($columns[$block->positionx])) {
        $columns[$block->positionx] = array();
    }

    if ($block->type == 'item') {
        $conditions = array("id" => $block->itemid);
        if ($item = $DB->get_record("block_exaportitem", $conditions)) {
            if (!$block->width) {
                $block->width = 320;
            }
            if (!$block->height) {
                $block->height = 240;
            }
            $item->intro = process_media_url($item->intro, $block->width, $block->height);
            // Add checking on sharable item.
            if ($sharable = block_exaport_can_user_access_shared_item($view->userid, $item->id) || $view->userid == $item->userid) {
                $block->item = $item;
            } else {
                continue; // Hide unshared items.
            }
        } else {
            $block->type = 'text';
        }
    }
    $columns[$block->positionx][] = $block;
}

block_exaport_init_js_css();

if (!$isPdf) {
    if ($view->access->request == 'intern') {
        block_exaport_print_header("shared_views");
    } else {
        $PAGE->requires->css('/blocks/exaport/css/shared_view.css');
        // $PAGE->set_title(get_string("externaccess", "block_exaport"));
        // $PAGE->set_heading(get_string("externaccess", "block_exaport")." ".fullname($user, $user->id));

        $generalContent .= $OUTPUT->header();
        $generalContent .= block_exaport_wrapperdivstart();
    }
}

if (!$isPdf) {
    ?>
    <script type="text/javascript">
        //<![CDATA[
        jQueryExaport(function ($) {
            $('.view-item').click(function (event) {
                if ($(event.target).is('a')) {
                    // ignore if link was clicked
                    return;
                }

                var link = $(this).find('.view-item-link a');
                if (link.length)
                    document.location.href = link.attr('href');
            });
        });
        //]]>
    </script>
    <?php
}

$comp = block_exaport_check_competence_interaction();

require_once(__DIR__.'/lib/resumelib.php');
$resume = block_exaport_get_resume_params($view->userid, true);

$colslayout = array(
        "1" => 1, "2" => 2, "3" => 2, "4" => 2, "5" => 3, "6" => 3, "7" => 3, "8" => 4, "9" => 4, "10" => 5,
);
if (!isset($view->layout) || $view->layout == 0) {
    $view->layout = 2;
}
$generalContent .= '<div id="view">';
$generalContent .= '<table class="table_layout layout'.$view->layout.'""><tr>';
$dataForPdf = array();
for ($i = 1; $i <= $colslayout[$view->layout]; $i++) {
    $dataForPdf[$i] = array();
    $generalContent .= '<td class="view-column td'.$i.'">';
    if (isset($columns[$i])) {
        foreach ($columns[$i] as $block) {
            $blockForPdf = '<div class="view-block">';
            if ($block->text) {
                $block->text = file_rewrite_pluginfile_urls($block->text, 'pluginfile.php', context_user::instance($USER->id)->id,
                        'block_exaport', 'view_content', $access);
                $block->text = format_text($block->text, FORMAT_HTML);
            }
            $attachments = array();
            switch ($block->type) {
                case 'item':
                    $item = $block->item;
                    $competencies = null;

                    if ($comp) {
                        $comps = block_exaport_get_active_comps_for_item($item);
                        if ($comps && is_array($comps) && array_key_exists('descriptors', $comps)) {
                            $competencies = $comps['descriptors'];
                        } else {
                            $competencies = null;
                        }

                        if ($competencies) {
                            $competenciesoutput = "";
                            foreach ($competencies as $competence) {
                                $competenciesoutput .= $competence->title.'<br>';
                            }

                            // TODO: still needed?
                            $competenciesoutput = str_replace("\r", "", $competenciesoutput);
                            $competenciesoutput = str_replace("\n", "", $competenciesoutput);
                            $competenciesoutput = str_replace("\"", "&quot;", $competenciesoutput);
                            $competenciesoutput = str_replace("'", "&prime;", $competenciesoutput);

                            $item->competences = $competenciesoutput;
                        }

                    }

                    $href = 'shared_item.php?access=view/'.$access.'&itemid='.$item->id.'&att='.$item->attachment;

                    $generalContent .= '<div class="view-item view-item-type-'.$item->type.'">';
                    // Thumbnail of item.
                    $fileparams = '';
                    if ($item->type == "file") {
                        $select = "contextid='".context_user::instance($item->userid)->id."' ".
                                " AND component='block_exaport' AND filearea='item_file' AND itemid='".$item->id."' AND filesize>0 ";
                        if ($files = $DB->get_records_select('files', $select, null, 'id, filename, mimetype, filesize')) {
                            if (is_array($files)) {
                                $width = '';
                                if (count($files) > 5) {
                                    $width = 's35';
                                } elseif (count($files) > 3) {
                                    $width = 's40';
                                } elseif (count($files) > 2) {
                                    $width = 's50';
                                } elseif (count($files) > 1) {
                                    $width = 's75';
                                }

                                foreach ($files as $file) {
                                    if (strpos($file->mimetype, "image") !== false) {
                                        $imgsrc = $CFG->wwwroot."/pluginfile.php/".context_user::instance($item->userid)->id.
                                                "/".'block_exaport'."/".'item_file'."/view/".$access."/itemid/".$item->id."/".
                                                $file->filename;
                                        $generalContent .= '<div class="view-item-image"><img src="'.$imgsrc.'" class="'.$width.'" alt=""/></div>';
                                        if ($isPdf) {
                                            $imgsrc .= '/forPdf/'.$view->hash.'/'.$view->id.'/'.$USER->id;
                                        }
                                        $blockForPdf .= '<div class="view-item-image">
                                                            <img align = "right"
                                                                border = "0"
                                                                src = "'.$imgsrc.'" 
                                                                width = "'.((int)filter_var($width, FILTER_SANITIZE_NUMBER_INT) ?: '100').'" 
                                                                alt = ""/>
                                                         </div>';
                                    } else {
                                        // Link to file.
                                        $ffurl = s("{$CFG->wwwroot}/blocks/exaport/portfoliofile.php?access=view/".$access.
                                                "&itemid=".$item->id."&inst=".$file->pathnamehash);
                                        // Human filesize.
                                        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
                                        $power = $file->filesize > 0 ? floor(log($file->filesize, 1024)) : 0;
                                        $filesize = number_format($file->filesize / pow(1024, $power), 2, '.', ',').' '.$units[$power];
                                        // Fileinfo block.
                                        $fileparams = '<div class="view-item-file"><a href="'.$ffurl.'" >'.$file->filename.'</a> '.
                                                '<span class="filedescription">('.$filesize.')</span></div>';
                                        if (block_exaport_is_valid_media_by_filename($file->filename)) {
                                            $generalContent .= '<div class="view-item-image"><img height="60" src="'.$CFG->wwwroot.
                                                    '/blocks/exaport/pix/media.png" alt=""/></div>';
                                            $blockForPdf .= '<img height="60" src="'.$CFG->wwwroot. '/blocks/exaport/pix/media.png" align="right"/>';
                                        }
                                    };
                                }
                            }
                        };
                    } else if ($item->type == "link") {
                        $generalContent .= '<div class="picture" style="float:right; position: relative; height: 100px; width: 100px;"><a href="'.
                                $href.'"><img style="max-width: 100%; max-height: 100%;" src="'.$CFG->wwwroot.
                                '/blocks/exaport/item_thumb.php?item_id='.$item->id.'&access='.$access.'" alt=""/></a></div>';
                        $blockForPdf .= '<img align="right" 
                                                style="" height="100"
                                                src="'.$CFG->wwwroot.'/blocks/exaport/item_thumb.php?item_id='.$item->id.'&access='.$access.'&ispdf=1&vhash='.$view->hash.'&vid='.$view->id.'&uid='.$USER->id.'" 
                                                alt=""/>';
                    };
                    $generalContent .= '<div class="view-item-header" title="'.$item->type.'">'.$item->name;
                    // Falls Interaktion ePortfolio - competences aktiv und User ist Lehrer.
                    if ($comp && has_capability('block/exaport:competences', $context)) {
                        if ($competencies) {
                            $generalContent .= '<img align="right" src="'.$CFG->wwwroot.
                                    '/blocks/exaport/pix/application_view_tile.png" alt="competences"/>';
                        }
                    }
                    $generalContent .= '</div>';
                    $blockForPdf .= '<h4>'.$item->name.'</h4>';
                    $generalContent .= $fileparams;
                    $blockForPdf .= $fileparams;
                    $intro = file_rewrite_pluginfile_urls($item->intro, 'pluginfile.php', context_user::instance($item->userid)->id,
                            'block_exaport', 'item_content', 'view/'.$access.'/itemid/'.$item->id);
                    $intro = format_text($intro, FORMAT_HTML, ['noclean' => true]);
                    $generalContent .= '<div class="view-item-text">';
                    $blockForPdf .= '<div class="view-item-text">';
                    if ($item->url && $item->url != "false") {
                        // Link.
                        $generalContent .= '<a href="'.s($item->url).'" target="_blank">'.str_replace('http://', '', $item->url).'</a><br />';
                        $blockForPdf .= '<a href="'.s($item->url).'" target="_blank">'.str_replace('http://', '', $item->url).'</a><br />';
                    }
                    $generalContent .= $intro.'</div>';
                    $blockForPdf .= $intro.'</div>';
                    if ($competencies) {
                        $generalContent .= '<div class="view-item-competences">'.
                                '<script type="text/javascript" src="javascript/wz_tooltip.js"></script>'.
                                '<a onmouseover="Tip(\''.$item->competences.'\')" onmouseout="UnTip()">'.
                                '<img src="'.$CFG->wwwroot.'/blocks/exaport/pix/comp.png" class="iconsmall" alt="'.'competences'.'" />'.
                                '</a></div>';
                    }
                    $generalContent .= '<div class="view-item-link"><a href="'.s($href).'">'.block_exaport_get_string('show').'</a></div>';
                    $generalContent .= '</div>';
                    break;
                case 'personal_information':
                    $generalContent .= '<div class="header">'.$block->block_title.'</div>';
                    if ($block->block_title) {
                        $blockForPdf .= '<h4>'.$block->block_title.'</h4>';
                    }
                    $generalContent .= '<div class="view-personal-information">';
                    $blockForPdf .= '<div class="view-personal-information">';
                    if (isset($block->picture)) {
                        $generalContent .= '<div class="picture" style="float:right; position: relative;"><img src="'.$block->picture.
                                '" alt=""/></div>';
                        $blockForPdf .= '<img src="'.$block->picture.'" align="right" />';
                    }
                    $personInfo = '';
                    if (isset($block->firstname) or isset($block->lastname)) {
                        $personInfo .= '<div class="name">';
                        if (isset($block->firstname)) {
                            $personInfo .= $block->firstname;
                        }
                        if (isset($block->lastname)) {
                            $personInfo .= ' '.$block->lastname;
                        }
                        $personInfo .= '</div>';
                    };
                    if (isset($block->email)) {
                        $personInfo .= '<div class="email">'.$block->email.'</div>';
                    }
                    if (isset($block->text)) {
                        $personInfo .= '<div class="body">'.$block->text.'</div>';
                    }
                    $generalContent .= $personInfo;
                    $generalContent .= '</div>';
                    $blockForPdf .= $personInfo;
                    $blockForPdf .= '</div>';
                    break;
                case 'headline':
                    $generalContent .= '<div class="header view-header">'.nl2br($block->text).'</div>';
                    $blockForPdf .= '<h4>'.nl2br($block->text).'</h4>';
                    break;
                case 'media':
                    $generalContent .= '<div class="header view-header">'.nl2br($block->block_title).'</div>';
                    if ($block->block_title) {
                        $blockForPdf .= '<h4>'.nl2br($block->block_title).'</h4>';
                    }
                    $generalContent .= '<div class="view-media">';
                    if (!empty($block->contentmedia)) {
                        $generalContent .= $block->contentmedia;
                    }
                    $generalContent .= '</div>';
                    $blockForPdf .= '----media----';
                    $blockForPdf .= '</div>';
                    break;
                case 'badge':
                    if (count($badges) == 0) {
                        continue 2;
                    }
                    $badge = null;
                    foreach ($badges as $tmp) {
                        if ($tmp->id == $block->itemid) {
                            $badge = $tmp;
                            break;
                        };
                    };
                    if (!$badge) {
                        continue 2;
                    }
                    $generalContent .= '<div class="header">'.nl2br($badge->name).'</div>';
                    $blockForPdf .= '<h4>'.nl2br($badge->name).'</h4>';
                    $generalContent .= '<div class="view-text">';
                    $generalContent .= '<div style="float:right; position: relative; height: 100px; width: 100px;" class="picture">';
                    if (!$badge->courseid) { // For badges with courseid = NULL.
                        $badge->imageUrl = (string) moodle_url::make_pluginfile_url(1, 'badges', 'badgeimage',
                                                                                    $badge->id, '/', 'f1', false);
                    } else {
                        $context = context_course::instance($badge->courseid);
                        $badge->imageUrl = (string) moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage',
                                                                                    $badge->id, '/', 'f1', false);
                    }
                    $generalContent .= '<img src="'.$badge->imageUrl.'">';
                    $generalContent .= '</div>';
                    $generalContent .= '<div class="badge-description">';
                    $generalContent .= format_text($badge->description, FORMAT_HTML);
                    $generalContent .= '</div>';
                    $generalContent .= '</div>';
                    $blockForPdf .= '<p>'.format_text($badge->description, FORMAT_HTML).'</p>';
                    $blockForPdf .= '<img align="right" src="'.$badge->imageUrl.'">';
                    $blockForPdf .= '</div>';
                    break;
                case 'cv_information':
                    $bodyContent = '';
                    switch ($block->resume_itemtype) {
                        case 'edu':
                            if ($block->itemid && $resume && $resume->educations[$block->itemid]) {
                                $itemData = $resume->educations[$block->itemid];
                                $attachments = $itemData->attachments;
                                $description = '';
                                $description .= '<span class="edu_institution">'.$itemData->institution.':</span> ';
                                $description .= '<span class="edu_qualname">'.$itemData->qualname.'</span>';
                                if ($itemData->startdate != '' || $itemData->enddate != '') {
                                    $description .= ' (';
                                    if ($itemData->startdate != '') {
                                        $description .= '<span class="edu_startdate">'.$itemData->startdate.'</span>';
                                    }
                                    if ($itemData->enddate != '') {
                                        $description .= '<span class="edu_enddate"> - '.$itemData->enddate.'</span>';
                                    }
                                    $description .= ')';
                                }
                                if ($itemData->qualdescription != '') {
                                    $description .= '<span class="edu_qualdescription">'.$itemData->qualdescription.'</span>';
                                }
                                $bodyContent .= $description;
                            }
                            break;
                        case 'employ':
                            if ($block->itemid && $resume && $resume->employments[$block->itemid]) {
                                $itemData = $resume->employments[$block->itemid];
                                $attachments = $itemData->attachments;
                                $description = '';
                                $description .= '<span class="employ_jobtitle">'.$itemData->jobtitle.':</span> ';
                                $description .= '<span class="employ_employer">'.$itemData->employer.'</span>';
                                if ($itemData->startdate != '' || $itemData->enddate != '') {
                                    $description .= ' (';
                                    if ($itemData->startdate != '') {
                                        $description .= '<span class="employ_startdate">'.$itemData->startdate.'</span>';
                                    }
                                    if ($itemData->enddate != '') {
                                        $description .= '<span class="employ_enddate"> - '.$itemData->enddate.'</span>';
                                    }
                                    $description .= ')';
                                }
                                if ($itemData->positiondescription != '') {
                                    $description .= '<span class="employ_positiondescription">'.$itemData->positiondescription.'</span>';
                                }
                                $bodyContent .= $description;
                            }
                            break;
                        case 'certif':
                            if ($block->itemid && $resume && $resume->certifications[$block->itemid]) {
                                $itemData = $resume->certifications[$block->itemid];
                                $attachments = $itemData->attachments;
                                $description = '';
                                $description .= '<span class="certif_title">'.$itemData->title.'</span> ';
                                if ($itemData->date != '') {
                                    $description .= '<span class="certif_date">('.$itemData->date.')</span>';
                                }
                                if ($itemData->description != '') {
                                    $description .= '<span class="certif_description">'.$itemData->description.'</span>';
                                }
                                $bodyContent = $description;
                            }
                            break;
                        case 'public':
                            if ($block->itemid && $resume && $resume->publications[$block->itemid]) {
                                $itemData = $resume->publications[$block->itemid];
                                $attachments = $itemData->attachments;
                                $description = '';
                                $description .= '<span class="public_title">'.$itemData->title;
                                if ($itemData->contribution != '') {
                                    $description .= ' ('.$itemData->contribution.')';
                                }
                                $description .= '</span> ';
                                if ($itemData->date != '') {
                                    $description .= '<span class="public_date">('.$itemData->date.')</span>';
                                }
                                if ($itemData->contributiondetails != '' || $itemData->url != '') {
                                    $description .= '<span class="public_description">';
                                    if ($itemData->contributiondetails != '') {
                                        $description .= $itemData->contributiondetails;
                                    }
                                    if ($itemData->url != '') {
                                        $description .= '<br /><a href="'.$itemData->url.'" class="public_url" target="_blank">'.$itemData->url.'</a>';
                                    }
                                    $description .= '</span>';
                                }
                                $bodyContent = $description;
                            }
                            break;
                        case 'mbrship':
                            if ($block->itemid && $resume && $resume->profmembershipments[$block->itemid]) {
                                $itemData = $resume->profmembershipments[$block->itemid];
                                $attachments = $itemData->attachments;
                                $description = '';
                                $description .= '<span class="mbrship_title">'.$itemData->title.'</span> ';
                                if ($itemData->startdate != '' || $itemData->enddate != '') {
                                    $description .= ' (';
                                    if ($itemData->startdate != '') {
                                        $description .= '<span class="mbrship_startdate">'.$itemData->startdate.'</span>';
                                    }
                                    if ($itemData->enddate != '') {
                                        $description .= '<span class="mbrship_enddate"> - '.$itemData->enddate.'</span>';
                                    }
                                    $description .= ')';
                                }
                                if ($itemData->description != '') {
                                    $description .= '<span class="mbrship_description">'.$itemData->description.'</span>';
                                }
                                $bodyContent = $description;
                            }
                            break;
                        case 'goalspersonal':
                        case 'goalsacademic':
                        case 'goalscareers':
                        case 'skillspersonal':
                        case 'skillsacademic':
                        case 'skillscareers':
                            $attachments = @$resume->{$block->resume_itemtype.'_attachments'};
                            $description = '';
                            if ($resume && $resume->{$block->resume_itemtype}) {
                                $description .= '<span class="'.$block->resume_itemtype.'_text">'.$resume->{$block->resume_itemtype}.'</span> ';
                            }
                            $bodyContent = $description;
                            break;
                        case 'interests':
                            $description = '';
                            if ($resume->interests != '') {
                                $description .= '<span class="interests">'.$resume->interests.'</span> ';
                            }
                            $bodyContent = $description;
                            break;
                        default:
                            $generalContent .= '!!! '.$block->resume_itemtype.' !!!';
                    }

                    if ($attachments && is_array($attachments) && count($attachments) > 0 && $block->resume_withfiles) {
                        $bodyContent .= '<ul class="resume_attachments '.$block->resume_itemtype.'_attachments">';
                        foreach($attachments as $attachm) {
                            $bodyContent .= '<li><a href="'.$attachm['fileurl'].'" target="_blank">'.$attachm['filename'].'</a></li>';
                        }
                        $bodyContent .= '</ul>';
                    }

                    // if the resume item is empty - do not show
                    if ($bodyContent != '') {
                        $generalContent .= '<div class="view-cv-information">';
                        /*if (isset($block->picture)) {
                            echo '<div class="picture" style="float:right; position: relative;"><img src="'.$block->picture.
                                    '" alt=""/></div>';
                        }*/
                        $generalContent .= $bodyContent;
                        $generalContent .= '</div>';
                        $blockForPdf .= $bodyContent;
                    }
                    break;
                default:
                    // Text.
                    $generalContent .= '<div class="header">'.$block->block_title.'</div>';
                    $generalContent .= '<div class="view-text">';
                    $generalContent .= format_text($block->text, FORMAT_HTML);
                    $generalContent .= '</div>';
                    if ($block->block_title) {
                        $blockForPdf = '<h4>'.$block->block_title.'</h4>';
                    }
                    $blockForPdf .= '<div>'.format_text($block->text, FORMAT_HTML).'</div>';
            }
            $blockForPdf .= '</div>';
            $dataForPdf[$i][] = $blockForPdf;
        }
    }
    $generalContent .= '</td>';
}

$generalContent .= '</tr></table>';
$generalContent .= '</div>';

$generalContent .= "<br />";
$generalContent .= "<div class=''>\n";
$pdflink = $PAGE->url;
$pdflink->params(array(
        'courseid' => optional_param('courseid', 1, PARAM_TEXT),
        'access' => optional_param('access', 0, PARAM_TEXT),
        'ispdf' => 1
));
$resumeQuery = "select b.*".
"FROM {block_exaportresume} b WHERE b.user_id = ?";
$resume = $DB->get_record_sql($resumeQuery, array($USER->id));
$certifQuery = "select b.* FROM {block_exaportresume_certif} b WHERE b.resume_id = ?";
$certif = $DB->get_records_sql($certifQuery, array($resume->id));
$pubsQuery = "select b.*"."FROM {block_exaportresume_public} b WHERE b.resume_id = ?";
$publication = $DB->get_records_sql($pubsQuery, array($resume->id));



$generalContent .= "</div>\n";

$generalContent .= "<div class='block_eportfolio_center'>\n";

$generalContent .= "</div>\n";

// echo $generalContent;
print_r($view->id);

$generalContent .= "<h3 class='h3 mb-3'>Event details</h3>\n";
$generalContent .=  "<div class='row'>\n";
$generalContent .=  "<div class='bold col-sm-3 mb-2'>Event name:</div>\n";
$generalContent .=  "<div class='col-sm-9 mb-2'>".$blocks2->name."</div>\n";
$generalContent .= "<div class='bold col-sm-3'>Event description:</div>\n";
$generalContent .= "<div class='col-sm-9'>".$blocks2->description."</div>\n";
$generalContent .= "</div>\n";

 $generalContent .= "<button type='button' class='btn btn-primary' data-toggle='modal' data-target='#exampleModalLong' >Add achievment</button>\n";
 $generalContent .= "<hr/>\n";
// $ul_str="<ul>";
// foreach ($students as $student){
//     $ul_str.= "<li>".$student->name."</li>";
// }
// $ul_str.="</ul>";
// echo $ul_str;
$viewId = $blocks2->id;
$resumeId = $resume->id;
    $data = new stdClass();
    $data->viewid = $viewId;
    $data->resume_id = $resumeId;
    // print_r($data);
    // $DB->insert_record('block_exaportviewachievement', $data);
    

    $generalContent .=  "<div class='modal fade' id='exampleModalLong' tabindex='-1' role='dialog' aria-labelledby='exampleModalLongTitle' aria-hidden='true'>
  <div class='modal-dialog' role='document'>
    <div class='modal-content'>
      <div class='modal-header'>
        <h5 class='modal-title' id='exampleModalLongTitle'>Your Achievements</h5>
        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
          <span aria-hidden='true'>&times;</span>
        </button>
      </div>
      <div class='modal-body'>
      <div class='col-xl-4 col-md-6 col-sm-12'>
      <div class='card' style =' width:360px'>
          <div class='card-body'>
          <h5 class='card-title'>Resume</h5> 
          <p class='card-text'>".(strlen($resume->cover) > 70 ? mb_substr($resume->cover, 0, 70).'...' : $resume->cover)."</p> 
          <form action='".s($CFG->wwwroot.'/blocks/exaport/my_event_details.php?viewID='.$_GET['viewID'].'')."' method='post'>
          <input type='submit' name='button1' value='Add Achievement' class='btn btn-primary'/>
          </form>
          </div>
      </div>
    </div>
    <hr/>\n";
  foreach($certif as $cert){
    $generalContent .=   " <div class='col-xl-4 col-md-6 col-sm-12'>
      <div class='card' style =' width:360px'>
          <div class='card-body'>
          <h5 class='card-title'>Certificates and awards</h5> 
          <h6 class='card-text'>".(strlen($cert->title) > 70 ? mb_substr($cert->title, 0, 70).'...' : $cert->title)."</h6>
          <p class='card-text'>".(strlen($cert->description) > 70 ? mb_substr($cert->description, 0, 70).'...' : $cert->description)."</p>  
          <form action='".s($CFG->wwwroot.'/blocks/exaport/my_event_details.php?viewID='.$_GET['viewID'].'')."' method='post'>
          <input type='submit' name='button2' value='Add Achievement' class='btn btn-primary'/>
          <input type='hidden' name='certifID' value='$cert->id' />

          </form>
          </div>
      </div>
    </div>
    <hr/>\n";}
    foreach($publication as $publ){
    $generalContent .= " <div class='col-xl-4 col-md-6 col-sm-12'>
      <div class='card' style =' width:360px'>
          <div class='card-body'>
          <h5 class='card-title'>Certificates and awards</h5> 
          <h6 class='card-text'>".(strlen($publ->title) > 70 ? mb_substr($publ->title, 0, 70).'...' : $publ->title)."</h6>
          <p class='card-text'>".(strlen($publ->contribution) > 70 ? mb_substr($publ->contribution, 0, 70).'...' : $publ->contribution)."</p>  
          <p class='card-text'>".(strlen($publ->contributiondetails) > 70 ? mb_substr($publ->contributiondetails, 0, 70).'...' : $publ->contributiondetails)."</p>  
          <form action='".s($CFG->wwwroot.'/blocks/exaport/my_event_details.php?viewID='.$_GET['viewID'].'')."' method='post'>
          <input type='submit' name='button3' value='Add Achievement' class='btn btn-primary'/>
          <input type='hidden' name='publID' value='$publ->id' />
          </form>
          </div>
      </div>
    </div>
    \n";}

    



    $generalContent .= "</div>
      <div class='modal-footer'>
        <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
        <button type='button' class='btn btn-primary'>Save changes</button>
      </div>
    </div>
  </div>
</div>\n";

$connectionsQuery ="select b.*"."FROM {block_exaportviewachievement} b WHERE b.viewid = ?";
$connections = $DB->get_record_sql($connectionsQuery, array($_GET['viewID']));


$pubsQuerys = "select b.*"."FROM {block_exaportresume_public} b WHERE b.resume_id = ?";
$publications = $DB->get_records_sql($pubsQuerys, array($resume->id));


if($connections->public_id == $publications->id){

    $generalContent .= 
    "<div class='col-xl-4 col-md-6 col-sm-12'>
    <div class='card'>
        <div class='card-body'>
        <h6 class='card-title'>Publication</h5> 
            <h5 class='card-title'>".$publications[0]->title."</h5> 
            <p class='card-text'>".htmlspecialchars(strlen(strip_tags($publications[0]->contributiondetails)) > 70 ? mb_substr(strip_tags($publications[0]->contributiondetails), 0, 70).'...' : strip_tags($publications[0]->contributiondetails))."</p>
        </div>
    </div>
</div>\n";
}


if ($_SERVER['REQUEST_METHOD']=="POST" and isset($_POST["button1"])){
    $query = "select b.*"."FROM {block_exaportviewachievement} b";
    $values = $DB->get_records_sql($query);
    $isAlreadyHere = false;
    foreach($values as $value){
        print_r($data->resume_id);
        if ($value->viewid == $data->viewid and $value->resume_id == $data->resume_id){
            $isAlreadyHere = true;
            break;
        }
    }
    if(!$isAlreadyHere){
        $DB->insert_record('block_exaportviewachievement', $data);
    }
}

if ($_SERVER['REQUEST_METHOD']=="POST" and isset($_POST["button3"])){
   $query = "select b.*"."FROM {block_exaportviewachievement} b";
    $values = $DB->get_records_sql($query);
    $isAlreadyHere = false;
    $publNew = new stdClass();
    $publNew->viewid = $viewId;
    $publNew->public_id = $_POST["publID"];
    foreach($values as $value){
        if ($value->viewid == $publNew->viewid and $value->public_id == $publNew->public_id){
            $isAlreadyHere = true;
            break;
        }
    }
    if(!$isAlreadyHere){
        $DB->insert_record('block_exaportviewachievement', $publNew);
    }

}

if ($_SERVER['REQUEST_METHOD']=="POST" and isset($_POST["button2"])){
    $query = "select b.*"."FROM {block_exaportviewachievement} b";
    $values = $DB->get_records_sql($query);
    $isAlreadyHere = false;
    $certifNew = new stdClass();
    $certifNew->viewid = $viewId;
    $certifNew->certif_id = $_POST["certifID"];
    foreach($values as $value){
        if ($value->viewid == $certifNew->viewid and $value->certif_id == $certifNew->certif_id){
            $isAlreadyHere = true;
            break;
        }
    }
    if(!$isAlreadyHere){
        $DB->insert_record('block_exaportviewachievement', $certifNew);
    }
 }

if (!$isPdf) {
    $generalContent .= block_exaport_wrapperdivend();
    $generalContent .= $OUTPUT->footer();
}

echo $generalContent;


// if ($isPdf) {
//     require_once __DIR__.'/lib/classes/dompdf/autoload.inc.php';
//     $options = new \Dompdf\Options();
//     $options->set('isRemoteEnabled', true);
//     $options->set('defaultFont', 'dejavu sans');
//     $dompdf = new Dompdf($options);
//     $dompdf->setPaper('A4', 'landscape');
//     /*$context = stream_context_create([
//             'ssl' => [
//                     'verify_peer' => FALSE,
//                     'verify_peer_name' => FALSE,
//                     'allow_self_signed'=> TRUE
//             ]
//     ]);
//     $dompdf->setHttpContext($context);*/
//     $generalContent = pdfView($view, $colslayout, $dataForPdf);
// //    echo $generalContent;exit;
//     $dompdf->loadHtml($generalContent);
//     $dompdf->render();
//     $dompdf->stream('view.pdf'); //To popup pdf as download
//     exit;
//     print_r($viewId);
// }





