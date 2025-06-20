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
 * Course overview block
 *
 * Currently, just a copy-and-paste from the old My Moodle.
 *
 * @package   blocks
 * @subpackage course_overview_sorting
 * @copyright  2012 Filip Benčo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

define('BLOCK_MY_COURSES_SORTING_BY_NAME','name');
define('BLOCK_MY_COURSES_SORTING_NONE','none');
define('BLOCK_MY_COURSES_SORTING_BY_TIMEMODIFIED','timemodified');
define('BLOCK_MY_COURSES_SORTING_BY_TIMECREATED','timecreated');

class block_my_courses extends block_base {
    /**
     * block initializations
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_my_courses');
    }

    /**
     * block contents
     *
     * @return object
     */
    public function get_content() {
        global $USER, $CFG, $PAGE;
        if($this->content !== NULL) {
            return $this->content;
        }
        
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $sorting = optional_param('sort', 'default', PARAM_TEXT);
        $desc = optional_param('desc', 0, PARAM_INT);
        $visible = optional_param('visible', 0, PARAM_INT);
        
        if($sorting == 'default') {
        	$sorting = get_user_preferences('block_my_courses_sorting',BLOCK_MY_COURSES_SORTING_NONE);
        	$desc = get_user_preferences('block_my_courses_desc', 0);
        	$visible = get_user_preferences('block_my_courses_visible',0);
        } else {
        	if($sorting != get_user_preferences('block_my_courses_sorting',BLOCK_MY_COURSES_SORTING_NONE)) {
        		set_user_preference('block_my_courses_sorting', $sorting);
        		set_user_preference('block_my_courses_desc', 0);
        		$desc = 0;
        	}
        	
        	if($visible != get_user_preferences('block_my_courses_visible',0)) 
        		set_user_preference('block_my_courses_visible', $visible);
        	
        	if($desc != get_user_preferences('block_my_courses_desc',0)) 
        		set_user_preference('block_my_courses_desc', $desc);
        	
        }
        
        $content = array();

        // limits the number of courses showing up
        $courses_limit = 21;

        if (isset($CFG->mycoursesperpage)) {
            $courses_limit = $CFG->mycoursesperpage;
        }
        
        $morecourses = false;
        if ($courses_limit > 0) {
            $courses_limit = $courses_limit + 1;
        }

        $sort_sql = '';
        if($visible != 1) 
        	$sort_sql .= 'visible DESC,';
        
        switch($sorting) {
        	case BLOCK_MY_COURSES_SORTING_NONE:
        		$sort_sql .= 'sortorder';
        		break;
        	case BLOCK_MY_COURSES_SORTING_BY_NAME:
        		$sort_sql .= 'fullname';
        		break;
        	case BLOCK_MY_COURSES_SORTING_BY_TIMECREATED:
        		$sort_sql .= 'timecreated';
        		break;
        	case BLOCK_MY_COURSES_SORTING_BY_TIMEMODIFIED:
        		$sort_sql .= 'timemodified';
        		break;
        }
        
        if($desc == 1) 
        	$sort_sql .= ' DESC';
        else 
        	$sort_sql .= ' ASC';
        
        $courses = enrol_get_my_courses('id, shortname, modinfo', $sort_sql, $courses_limit);
        $site = get_site();
        $course = $site; //just in case we need the old global $course hack

        if (is_enabled_auth('mnet')) {
            $remote_courses = get_my_remotecourses();
        }
        if (empty($remote_courses)) {
            $remote_courses = array();
        }

        if (($courses_limit > 0) && (count($courses)+count($remote_courses) >= $courses_limit)) {
            // get rid of any remote courses that are above the limit
            $remote_courses = array_slice($remote_courses, 0, $courses_limit - count($courses), true);
            if (count($courses) >= $courses_limit) {
                //remove the 'marker' course that we retrieve just to see if we have more than $courses_limit
                array_pop($courses);
            }
            $morecourses = true;
        }


        if (array_key_exists($site->id,$courses)) {
            unset($courses[$site->id]);
        }

        foreach ($courses as $c) {
            if (isset($USER->lastcourseaccess[$c->id])) {
                $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
            } else {
                $courses[$c->id]->lastaccess = 0;
            }
        }

        if (empty($courses) && empty($remote_courses)) {
            $content[] = get_string('nocourses','my');
        } else {
            ob_start();
            
            $this->print_courses_overview($courses, $remote_courses);

            $content[] = ob_get_contents();
            ob_end_clean();
        }

        // if more than 20 courses
        if ($morecourses) {
            $content[] = '<br />...';
        }        
        
        $this->content->text = $this->sorting_links($sorting, $desc, $visible) . implode($content);

        $module = array(
        		'name'      => 'block_my_courses', // chat gui's are not real plugins, we have to break the naming standards for JS modules here :-(
        		'fullpath'  => '/blocks/my_courses/module.js',
        		'requires'  => array('base', 'dom', 'event','io',)
        );

        $PAGE->requires->js_init_call('M.block_my_courses.init', array(array('hideString' => get_string('hide_news','block_my_courses'),'showString' => get_string('show_news','block_my_courses'))), false, $module);
        
        $PAGE->requires->js('/blocks/my_courses/module.js',true);
        
        return $this->content;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index'=>true);
    }
    
    public function instance_allow_multiple() {
    	return false;
    }
    
    /**
     * Print sorting action links
     * 
     * @param $sorting
     * @param $desc
     * @param $visible
     * @return unknown
     */
    protected function sorting_links($sorting, $desc, $visible) {
    	global $OUTPUT, $PAGE;
    	
    	$sort = $OUTPUT->box_start('block_my_courses');
     	
    	$sort .= $OUTPUT->box(get_string('sorting','block_my_courses'),'leftfloat');
    	$sort .= $OUTPUT->box($this->get_sorting_action(BLOCK_MY_COURSES_SORTING_NONE,$sorting, $desc, $visible),'leftfloat');
    	$sort .= $OUTPUT->box($this->get_sorting_action(BLOCK_MY_COURSES_SORTING_BY_NAME,$sorting, $desc, $visible),'leftfloat');
    	$sort .= $OUTPUT->box($this->get_sorting_action(BLOCK_MY_COURSES_SORTING_BY_TIMECREATED,$sorting, $desc, $visible),'leftfloat');
    	$sort .= $OUTPUT->box($this->get_sorting_action(BLOCK_MY_COURSES_SORTING_BY_TIMEMODIFIED,$sorting, $desc, $visible),'leftfloat');

    	$url = clone $PAGE->url;
    	
    	$params = array('sort' => $sorting);
    	if($desc == 1)
  			$url->param('desc',1);
    	
    	if($visible == 1) {
    		$sort .= $OUTPUT->box($OUTPUT->action_link($url, get_string('visible_exclude','block_my_courses')),'rightfloat');
    	} else {
    		$url->param('visible',1);
    		$sort .= $OUTPUT->box($OUTPUT->action_link($url, get_string('visible_include','block_my_courses')),'rightfloat');
    	}
    	$sort .= $OUTPUT->box('','clearfix');
    	$sort .= $OUTPUT->box_end();
    	
    	return $sort;
    }
    
    /**
     * function returns action link for given sorting
     * 
     * @param $sorting
     * @param $currentsorting
     * @param $desc
     * @param $visible
     * @return string
     */
    protected function get_sorting_action($sorting, $currentsorting, $desc, $visible) {
    	global $OUTPUT, $PAGE;
    	$url = clone $PAGE->url;
    	$url->param('sort',$sorting);
    	if($visible == 1)
    		$url->param('visible',1);
    	
    	if($sorting == $currentsorting) {
    		$link = '';
    		if($desc == 1) {
    			$link .= $OUTPUT->action_link($url, get_string('sorting_'.$sorting,'block_my_courses'));
    			$link .= ' ' . html_writer::empty_tag('img',array('src' => $OUTPUT->pix_url('t/up'), 'alt' => get_string('desc')));
    		} else {
    			$url->param('desc',1);
    			$link .= $OUTPUT->action_link($url, get_string('sorting_'.$sorting,'block_my_courses'));
    			$link .= ' ' . html_writer::empty_tag('img',array('src' => $OUTPUT->pix_url('t/down'), 'alt' => get_string('asc')));
    		}
    		return $link;
    	} else {
    		return $OUTPUT->action_link($url, get_string('sorting_'.$sorting,'block_my_courses'));
    	}
    }
    
    /**
     * Modified function from courselib
     * 
     * @param $courses
     * @param $remote_courses
     */
    protected function print_courses_overview($courses, $remote_courses) {
    	global $CFG, $USER, $DB, $OUTPUT;
    	
    	$item = 0;
    	
    	foreach ($courses as $course) {
    		$fullname = format_string($course->fullname, true, array('context' => get_context_instance(CONTEXT_COURSE, $course->id)));
    		echo $OUTPUT->box_start('coursebox');
    		$attributes = array('title' => s($fullname));
    		if (empty($course->visible)) {
    			$attributes['class'] = 'dimmed';
    		}
    		echo $OUTPUT->heading(html_writer::link(
    				new moodle_url('/course/view.php', array('id' => $course->id)), $fullname, $attributes), 3);
    		echo html_writer::empty_tag('input',array('id'=>'block-course-'.$item,'type'=>'hidden','value'=>$course->id));
    		echo $OUTPUT->box($OUTPUT->action_link(new moodle_url('#'), get_string('show_news','block_my_courses'),null,array('onclick'=>'return block_my_courses_expand(\''.$item.'\');')),'show-hide','block-course-action-'.$item);
    		echo $OUTPUT->box('','','block-course-expanded-'.$item);
    		echo $OUTPUT->box_end();
    		$item++;
    	}
    	
    	if (!empty($remote_courses)) {
    		echo $OUTPUT->heading(get_string('remotecourses', 'mnet'));
    	}
    	foreach ($remote_courses as $course) {
    		echo $OUTPUT->box_start('coursebox');
    		$attributes = array('title' => s($course->fullname));
    		echo $OUTPUT->heading(html_writer::link(
    				new moodle_url('/auth/mnet/jump.php', array('hostid' => $course->hostid, 'wantsurl' => '/course/view.php?id='.$course->remoteid)),
    				format_string($course->shortname),
    				$attributes) . ' (' . format_string($course->hostname) . ')', 3);
    		echo $OUTPUT->box_end();
    	}
    }
}
?>
