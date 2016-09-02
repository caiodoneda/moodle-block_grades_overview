<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    block_grades_overview
 * @copyright  2016 Caio Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once($CFG->dirroot . '/lib/enrollib.php');
include_once($CFG->dirroot . '/lib/coursecatlib.php');


class block_grades_overview extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_grades_overview');
    }

    function has_config() {
        return true;
    }

    public function applicable_formats() {
        return array('my' => true);
    }

    function get_content() {
        global $CFG, $USER, $PAGE, $DB;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = "";
        $this->content->footer = '';
        
        if (!has_capability('block/grades_overview:view', context_system::instance())) {
            return $this->content;
        }

        $courses = $DB->get_records('course');

        $query = 'SELECT * FROM {course_categories} ORDER BY sortorder';
        $course_categories = $DB->get_records_sql($query);
        $top_categories = array();

        foreach ($course_categories as $cc) {
            if ($cc->parent != 0) {
                if (isset($course_categories[$cc->parent]->sub_ids)) {
                    $course_categories[$cc->parent]->sub_ids[$cc->id] = $cc->id;
                } else {
                    $course_categories[$cc->parent]->sub_ids = array();
                    $course_categories[$cc->parent]->sub_ids[$cc->id] = $cc->id;
                }
            } else {
                $top_categories[$cc->id] = $cc;
            }
        }

        $out = "";
        $out .= html_writer::start_tag('div', array('class'=>'admin-grades-general'));

        $out .= html_writer::start_tag('ul');

        foreach ($top_categories as $category) {
            if ($this->has_children($category->id, $course_categories[$category->id]->sub_ids, $courses)) {
                $out .= html_writer::start_tag('li');
                $out .= html_writer::empty_tag('input', 
                        array('type' => 'checkbox', 'class' => 'category-checkbox'));
                $out .= html_writer::tag('label', $category->name, array('class' => 'closed'));
                $out .= $this->draw_my_children($course_categories[$category->id], $course_categories, $courses);
                $out .= html_writer::end_tag('li');
            }
        }

        $out .= html_writer::end_tag('ul');

        $out .= html_writer::end_tag('div');

        $this->content->text .= $out;
        return $this->content;
    }

    function draw_my_children($category, $course_categories, $courses) {
        $out = "";
        $out .= html_writer::start_tag('ul');

        foreach ($courses as $c) {
            if ($c->category == $category->id) {
                $out .= html_writer::start_tag('li');
                $url = new moodle_url('/grade/report/grader/index.php', array('id'=>$c->id));
                $out .= html_writer::link($url, $c->fullname);
                $out .= html_writer::end_tag('li');
            }
        }

        foreach ($category->sub_ids as $sub_cat) {
            if ($this->has_children($sub_cat, $course_categories[$sub_cat]->sub_ids, $courses)) {
                $out .= html_writer::start_tag('li');
                $out .= html_writer::empty_tag('input', 
                        array('type' => 'checkbox', 'class' => 'category-checkbox'));
                $out .= html_writer::tag('label', '');
                $out .= html_writer::tag('html', $course_categories[$sub_cat]->name, 
                        array('class' => 'closed'));
                $out .= $this->draw_my_children($course_categories[$sub_cat], $course_categories, $courses);
                $out .= html_writer::end_tag('li');
            }
        }

        $out .= html_writer::end_tag('ul');

        return $out;
    }

    function has_children($catid, $sub_cat, $courses) {
        $has_subcategories = !empty($sub_cat);
        $has_subcourses = false;
        
        foreach ($courses as $c) {
            if ($c->category == $catid) {
                $has_subcourses = true;
                break;    
            }
        }
        
        return ($has_subcategories || $has_subcourses); 
    }
}


