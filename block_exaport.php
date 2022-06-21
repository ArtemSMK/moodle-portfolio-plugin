<?php
defined('MOODLE_INTERNAL') || die();

require(__DIR__.'/inc.php');

class block_exaport extends block_list {

    public function init() {
        $this->title = get_string('blocktitle', 'block_exaport');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function instance_allow_config() {
        return false;
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $CFG, $COURSE, $OUTPUT, $USER;

        $context = context_system::instance();
        if (!has_capability('block/exaport:use', $context)) {
            $this->content = '';
            return $this->content;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $output = block_exaport_get_renderer();

        $context = context_course::instance($COURSE->id);
        $roles = get_user_roles($context, $USER->id, true);
        $role = key($roles);
        $rolename = $roles[$role]->shortname;

        if($rolename == 'manager'){
            $icon = '<img src="'.$output->image_url('myviews', 'block_exaport').'" class="icon" alt="" />';
            $this->content->items[] = '<a title="'.block_exaport_get_string('views').'" '.
                    ' href="'.$CFG->wwwroot.'/blocks/exaport/views_list.php?courseid='.$COURSE->id.'">'.
                    $icon.block_exaport_get_string('views').'</a>';
        }
        else{
            $icon = '<img src="'.$output->image_url('resume', 'block_exaport').'" class="icon" alt="" />';
        $this->content->items[] = '<a title="'.block_exaport_get_string('resume_my').'" '.
                ' href="'.$CFG->wwwroot.'/blocks/exaport/resume.php?courseid='.$COURSE->id.'">'.
                $icon.block_exaport_get_string('resume_my').'</a>';

        // $icon = '<img src="'.$output->image_url('my_portfolio', 'block_exaport').'" class="icon" alt="" />';
        // $this->content->items[] = '<a title="'.block_exaport_get_string('myportfoliotitle').'" '.
        //         ' href="'.$CFG->wwwroot.'/blocks/exaport/view_items.php?courseid='.$COURSE->id.'">'.
        //         $icon.block_exaport_get_string('myportfolio').'</a>';

         $icon = '<img src="'.$output->image_url('shared_views', 'block_exaport').'" class="icon" alt="" />';
         $this->content->items[] = '<a title="'.block_exaport_get_string('views').'" '.
                 ' href="'.$CFG->wwwroot.'/blocks/exaport/my_events_list.php?courseid='.$COURSE->id.'">'.
                 $icon.block_exaport_get_string('views').'</a>';
        }

        // $icon = '<img src="'.$output->image_url('resume', 'block_exaport').'" class="icon" alt="" />';
        // $this->content->items[] = '<a title="'.block_exaport_get_string('resume_my').'" '.
        //         ' href="'.$CFG->wwwroot.'/blocks/exaport/resume.php?courseid='.$COURSE->id.'">'.
        //         $icon.block_exaport_get_string('resume_my').'</a>';

        // // $icon = '<img src="'.$output->image_url('my_portfolio', 'block_exaport').'" class="icon" alt="" />';
        // // $this->content->items[] = '<a title="'.block_exaport_get_string('myportfoliotitle').'" '.
        // //         ' href="'.$CFG->wwwroot.'/blocks/exaport/view_items.php?courseid='.$COURSE->id.'">'.
        // //         $icon.block_exaport_get_string('myportfolio').'</a>';

        //  $icon = '<img src="'.$output->image_url('myviews', 'block_exaport').'" class="icon" alt="" />';
        //  $this->content->items[] = '<a title="'.block_exaport_get_string('views').'" '.
        //          ' href="'.$CFG->wwwroot.'/blocks/exaport/views_list.php?courseid='.$COURSE->id.'">'.
        //          $icon.block_exaport_get_string('views').'</a>';

        // $icon = '<img src="'.$output->image_url('shared_views', 'block_exaport').'" class="icon" alt="" />';
        // $this->content->items[] = '<a title="'.block_exaport_get_string('shared_views').'" '.
        //         ' href="'.$CFG->wwwroot.'/blocks/exaport/shared_views.php?courseid='.$COURSE->id.'">'.
        //         $icon.block_exaport_get_string('shared_views').'</a>';

        // $icon = '<img src="'.$output->image_url('shared_categories', 'block_exaport').'" class="icon" alt="" />';
        // $this->content->items[] = '<a title="'.block_exaport_get_string('shared_categories').'" '.
        //         ' href="'.$CFG->wwwroot.'/blocks/exaport/shared_categories.php?courseid='.$COURSE->id.'">'.
        //         $icon.block_exaport_get_string('shared_categories').'</a>';

        // $icon = '<img src="'.$output->image_url('importexport', 'block_exaport').'" class="icon" alt="" />';
        // $this->content->items[] = '<a title="'.block_exaport_get_string('importexport').'" '.
        //         ' href="'.$CFG->wwwroot.'/blocks/exaport/importexport.php?courseid='.$COURSE->id.'">'.
        //         $icon.block_exaport_get_string('importexport').'</a>';

        return $this->content;
    }
}
