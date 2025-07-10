<?php

namespace theme_remui\output\core;
global $CFG;
require_once($CFG->dirroot . '/badges/renderer.php');

use component_action;
use core_badges\output\issued_badge;
use core_badges_renderer;
use core_text;
use moodle_url;
use badge;
use html_writer;

class badges_renderer extends core_badges_renderer {
    protected function render_issued_badge(issued_badge $ibadge) {
        global $USER, $CFG, $DB, $SITE;
        $issued = $ibadge->issued;
        $userinfo = $ibadge->recipient;
        $badgeclass = $ibadge->badgeclass;
        $badge = new badge($ibadge->badgeid);
        $now = time();
        $expiration = isset($issued['expires']) ? $issued['expires'] : $now + 86400;
        $badgeimage = is_array($badgeclass['image']) ? $badgeclass['image']['id'] : $badgeclass['image'];
        $languages = get_string_manager()->get_list_of_languages();

        $output = '';
        $output .= html_writer::start_tag('div', array('id' => 'badge'));
        $output .= html_writer::start_tag('div', array('id' => 'badge-image'));
        $output .= html_writer::empty_tag('img', array('src' => $badgeimage, 'alt' => $badge->name, 'width' => '100'));
        if ($expiration < $now) {
            $output .= $this->output->pix_icon('i/expired',
                    get_string('expireddate', 'badges', userdate($issued['expires'])),
                    'moodle',
                    array('class' => 'expireimage'));
        }

        if ($USER->id == $userinfo->id && !empty($CFG->enablebadges)) {
            $output .= $this->output->single_button(
                    new moodle_url('/badges/badge.php', array('hash' => $ibadge->hash, 'bake' => true)),
                    get_string('download'),
                    'POST');
                    
            $linkedin_url = new moodle_url('/badges/badge.php', array('hash' => $ibadge->hash));
            if (!empty($CFG->badges_allowexternalbackpack) && ($expiration > $now) && badges_user_has_backpack($USER->id)) {

                if (badges_open_badges_backpack_api() == OPEN_BADGES_V1) {
                    $assertion = new moodle_url('/badges/assertion.php', array('b' => $ibadge->hash));
                    $action = new component_action('click', 'addtobackpack', array('assertion' => $assertion->out(false)));
                    $attributes = array(
                            'type'  => 'button',
                            'class' => 'btn btn-secondary m-1',
                            'id'    => 'addbutton',
                            'value' => get_string('addtobackpack', 'badges'));
                    $tobackpack = html_writer::tag('input', '', $attributes);
                    $this->output->add_action_handler($action, 'addbutton');
                    $output .= $tobackpack;
                } else {
                    $assertion = new moodle_url('/badges/backpack-add.php', array('hash' => $ibadge->hash));
                    $attributes = ['class' => 'btn btn-secondary m-1', 'role' => 'button'];
                    $tobackpack = html_writer::link($assertion, get_string('addtobackpack', 'badges'), $attributes);
                    $output .= $tobackpack;
                }
            }
        }

        //linkedin

        $exdate = ($ibadge->issued['expires']);
        if($exdate) {
            $exdate = explode('-', $exdate);
            $exyear = $exdate[0];
            $exmounth = $exdate[1];
        }
        $max = ($ibadge->issued['issuedOn']);
        
        $max = explode('-', $max);
        $year = $max[0];
        $mounth = $max[1];
        $nex = $issued['verify']['url'];

        /*$output .= $this->output->single_button(new moodle_url("https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name={$badge->name}&organizationId=3293205&issueYear={$year}&issueMonth={$mounth}&expirationYear={$exyear}&expirationMonth={$exmounth}&certUrl={$nex}&certId={$badge->id}"),
                get_string('linkedin','theme_remui'), 'GET');*/
        if($USER->id == $userinfo->id){
            $url = new moodle_url("https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name={$badge->name}&organizationId=3293205&issueYear={$year}&issueMonth={$mounth}&expirationYear={$exyear}&expirationMonth={$exmounth}&certUrl={$linkedin_url}&certId={$badge->id}");        
            $imgurl = new moodle_url('/theme/remui/pix/ln.png');
            $output .= '<a href="'.$url.'"><img style="padding-left: 5px; padding-top: 5px;" src="'.$imgurl.'"></a>';
            
        
        }       
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('id' => 'badge-details'));
        // Recipient information.
        $output .= $this->output->heading(get_string('recipientdetails', 'badges'), 3);
        $dl = array();
        if ($userinfo->deleted) {
            $strdata = new stdClass();
            $strdata->user = fullname($userinfo);
            $strdata->site = format_string($SITE->fullname, true, array('context' => context_system::instance()));

            $dl[get_string('name')] = get_string('error:userdeleted', 'badges', $strdata);
        } else {
            $dl[get_string('name')] = fullname($userinfo);
        }
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('issuerdetails', 'badges'), 3);
        $dl = array();
        $dl[get_string('issuername', 'badges')] = $badge->issuername;
        if (isset($badge->issuercontact) && !empty($badge->issuercontact)) {
            $dl[get_string('contact', 'badges')] = obfuscate_mailto($badge->issuercontact);
        }
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('badgedetails', 'badges'), 3);
        $dl = array();
        $dl[get_string('name')] = $badge->name;
        if (!empty($badge->version)) {
            $dl[get_string('version', 'badges')] = $badge->version;
        }
        if (!empty($badge->language)) {
            $dl[get_string('language')] = $languages[$badge->language];
        }
        $dl[get_string('description', 'badges')] = $badge->description;
        if (!empty($badge->imageauthorname)) {
            $dl[get_string('imageauthorname', 'badges')] = $badge->imageauthorname;
        }
        if (!empty($badge->imageauthoremail)) {
            $dl[get_string('imageauthoremail', 'badges')] =
                    html_writer::tag('a', $badge->imageauthoremail, array('href' => 'mailto:' . $badge->imageauthoremail));
        }
        if (!empty($badge->imageauthorurl)) {
            $dl[get_string('imageauthorurl', 'badges')] =
                    html_writer::link($badge->imageauthorurl, $badge->imageauthorurl, array('target' => '_blank'));
        }
        if (!empty($badge->imagecaption)) {
            $dl[get_string('imagecaption', 'badges')] = $badge->imagecaption;
        }

        if ($badge->type == BADGE_TYPE_COURSE && isset($badge->courseid)) {
            $coursename = $DB->get_field('course', 'fullname', array('id' => $badge->courseid));
            $dl[get_string('course')] = $coursename;
        }
        $dl[get_string('bcriteria', 'badges')] = self::print_badge_criteria($badge);
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('issuancedetails', 'badges'), 3);
        $dl = array();
        if (!is_numeric($issued['issuedOn'])) {
            $issued['issuedOn'] = strtotime($issued['issuedOn']);
        }
        $dl[get_string('dateawarded', 'badges')] = userdate($issued['issuedOn']);
        if (isset($issued['expires'])) {
            if (!is_numeric($issued['expires'])) {
                $issued['expires'] = strtotime($issued['expires']);
            }
            if ($issued['expires'] < $now) {
                $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']) . get_string('warnexpired', 'badges');

            } else {
                $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']);
            }
        }

        // Print evidence.
        $agg = $badge->get_aggregation_methods();
        $evidence = $badge->get_criteria_completions($userinfo->id);
        $eids = array_map(function($o) {
            return $o->critid;
        }, $evidence);
        unset($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]);

        $items = array();
        foreach ($badge->criteria as $type => $c) {
            if (in_array($c->id, $eids)) {
                if (count($c->params) == 1) {
                    $items[] = get_string('criteria_descr_single_' . $type , 'badges') . $c->get_details();
                } else {
                    $items[] = get_string('criteria_descr_' . $type , 'badges',
                                    core_text::strtoupper($agg[$badge->get_aggregation_method($type)])) . $c->get_details();
                }
            }
        }

        $dl[get_string('evidence', 'badges')] = get_string('completioninfo', 'badges') . html_writer::alist($items, array(), 'ul');
        $output .= $this->definition_list($dl);
        $endorsement = $badge->get_endorsement();
        if (!empty($endorsement)) {
            $output .= self::print_badge_endorsement($badge);
        }

        $relatedbadges = $badge->get_related_badges(true);
        $items = array();
        foreach ($relatedbadges as $related) {
            $relatedurl = new moodle_url('/badges/overview.php', array('id' => $related->id));
            $items[] = html_writer::link($relatedurl->out(), $related->name, array('target' => '_blank'));
        }
        if (!empty($items)) {
            $output .= $this->heading(get_string('relatedbages', 'badges'), 3);
            $output .= html_writer::alist($items, array(), 'ul');
        }

        $alignments = $badge->get_alignments();
        if (!empty($alignments)) {
            $output .= $this->heading(get_string('alignment', 'badges'), 3);
            $items = array();
            foreach ($alignments as $alignment) {
                $items[] = html_writer::link($alignment->targeturl, $alignment->targetname, array('target' => '_blank'));
            }
            $output .= html_writer::alist($items, array(), 'ul');
        }
        $output .= html_writer::end_tag('div');

        return $output;
    }


}