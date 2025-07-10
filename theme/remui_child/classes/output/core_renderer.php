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

namespace theme_remui_child\output;

use coding_exception;
use html_writer;
use tabobject;
use tabtree;
use core_text;
use custom_menu_item;
use custom_menu;
use block_contents;
use navigation_node;
use action_link;
use stdClass;
use moodle_url;
use preferences_groups;
use action_menu;
use help_icon;
use single_button;
use paging_bar;
use context_course;
use pix_icon;
use action_menu_filler;
use context_system;
use moodle_page;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_remui_child
 * @copyright  2012 Bas Brands, www.basbrands.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class core_renderer extends \theme_remui\output\core_renderer {
  /**
   * Constructor
   *
   * @param moodle_page $page the page we are doing output for.
   * @param string $target one of rendering target constants
   */
  public function __construct(moodle_page $page, $target) {
    parent::__construct($page, $target);
    $this->themeconfig = array(\theme_config::load('remui'));
    $this->themeconfig = array_merge($this->themeconfig, array(\theme_config::load('remui_child')));
  }

  /**
     * Whether we should display the logo in the navbar.
     *
     * We will when there are no main logos, and we have compact logo.
     *
     * @return bool
     */
    public function should_display_logo() {
        global $SITE, $DB, $USER;

        $logoorsitename = \theme_remui\toolbox::get_setting('logoorsitename');
        $context = array('islogo' => false, 'issitename' => false, 'isiconsitename' => false);
        $checklogo = \theme_remui\toolbox::setting_file_url('logo', 'logo');
        $checklogomini = \theme_remui\toolbox::setting_file_url('logomini', 'logomini');

        if (!empty($checklogo)) {
            $logo = $checklogo;
        } else {
            $logo = \theme_remui\toolbox::image_url('logo','theme');
        }

        if (!empty($checklogomini)) {
            $logomini = $checklogomini;
        } else {
            $logomini = \theme_remui\toolbox::image_url('logomini','theme');
        }

        if ($logoorsitename == 'logo') {
            $context['islogo'] = true;
            $context['logourl'] = $logo;
            $context['logominiurl'] = $logomini;
        } else {
            $context['isiconsitename'] = true;
            $context['siteicon'] = \theme_remui\toolbox::get_setting('siteicon');
            $context['sitename'] = format_string($SITE->shortname);
        }

        if (!$companyid = optional_param('company',  false,  PARAM_INT)) {
            $usercompany = $DB->get_record('company_users', array('userid' => $USER->id), 'id,companyid');
            if ($usercompany) {
                $companyid = $usercompany->companyid;
            } else {
                $companyid = 0;
            }
        }

        /*if (!$companyid = optional_param('company',  false,  PARAM_INT)) {
            $usercompany = $DB->get_record('company_users', array('userid' => $USER->id), 'id,companyid');
            if ($usercompany) {
                $companyid = $usercompany->companyid;
            } else {
                $companyid = false;
            }
        }*/

        if ($companyrec = $DB->get_record('company', array('id' => $companyid))) {
            $context['sitename'] = $companyrec->name;
        }

        /*if ($logo = $this->get_iomad_logo($USER->id)) {
            $context['isiconsitename'] = '';
            $logomini = $this->get_compact_logo_url($USER->id);
            $context['islogo'] = true;
            $context['logourl'] = $logo;
            $context['logominiurl'] = $logomini;
        }*/
        return $context;
    }

    /**
     * Get the Iomad logo for the company
     * @return string logo url or false;
     */
    //protected function get_iomad_logo($userid = null, $maxwidth = 100, $maxheight = 100) {
      //  global $CFG, $DB, $USER;
/*
        $fs = get_file_storage();

        $clientlogo = '';
        if (!$companyid = optional_param('company',  false,  PARAM_INT)) {
            $usercompany = $DB->get_record('company_users', array('userid' => $USER->id), 'id,companyid');
            if ($usercompany) {
                $companyid = $usercompany->companyid;
            } else {
                $companyid = \iomad::is_company_user();
            }
        }*/

        /*if (!$companyid = optional_param('company',  false,  PARAM_INT)) {
            $usercompany = $DB->get_record('company_users', array('userid' => $USER->id), 'id,companyid');
            if ($usercompany) {
                $companyid = $usercompany->companyid;
            } else {
                $companyid = false;
            }
        }*/

        /*if (isset($companyid) && $companyid) {
            $context = \context_system::instance();
            $files = $fs->get_area_files($context->id, 'theme_iomad', 'companylogo', $companyid );
            if ($files) {
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';
                    if ($filename != '.') {
                        $clientlogo = $CFG->wwwroot . "/pluginfile.php/{$context->id}/theme_iomad/companylogo/$companyid/$filename";
                        return $clientlogo;
                    }
                }
            }
        }*/

     //   return false;
   // }

    /**
     * Get the compact logo URL.
     *
     * @return string
     */
    /*public function get_compact_logo_url($userid = null, $maxwidth = 100, $maxheight = 100) {
        global $CFG;

        if ($url = $this->get_iomad_logo($userid, $maxwidth, $maxheight)) {
            return $url;
        } else {

            // If that didn't work... try the original version
            return parent::get_compact_logo_url($maxwidth, $maxheight);
        }
    }*/

    /*
     * Overriding the custom_menu function ensures the custom menu is
     * always shown, even if no menu items are configured in the global
     * theme settings page.
     */
    public function custom_menu($custommenuitems = '') {
        global $CFG, $DB;

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }
        // Deal with company custom menu items.
        /*if ($companyid = \iomad::is_company_user()) {
            if ($companyrec = $DB->get_record('company', array('id' => $companyid))) {
                if (!empty($companyrec->custommenuitems)) {
                    $custommenuitems = $companyrec->custommenuitems;
                }
            }
        }*/
        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }

    /**
     * The standard tags that should be included in the <head> tag
     * including a meta description for the front page
     *
     * @return string HTML fragment.
     */
    public function standard_head_html() {
        global $SITE, $PAGE, $DB, $USER, $SESSION;

        // Inject additional 'live' css
        $css = '';

        // Get company colours
        /*if (!$companyid = optional_param('company',  false,  PARAM_INT)) {
            $usercompany = $DB->get_record('company_users', array('userid' => $USER->id), 'id,companyid');
            if ($usercompany) {
                $companyid = $usercompany->companyid;
            } else {
                $companyid = \iomad::is_company_user();
            }
        }*/

        /*if (!$companyid = optional_param('company',  false,  PARAM_INT)) {
            $usercompany = $DB->get_record('company_users', array('userid' => $USER->id), 'id,companyid');
            if ($usercompany) {
                $companyid = $usercompany->companyid;
            } else {
                $companyid = false;
            }
        }*/

        if ($companyid) {
            $company = $DB->get_record('company', array('id' => $companyid), '*', MUST_EXIST);
            $linkcolor = $company->linkcolor;
            if ($linkcolor) {
                $css .= 'a {color: ' . $linkcolor . ' !important;} ';
            }
            $headingcolor = $company->headingcolor;
            if ($headingcolor) {
                $css .= '.navbar {background-color: ' . $headingcolor . ' !important;} ';
                $css .= '#gotop.to-top{background-color: ' . $headingcolor . ' !important;}';
                $css .= 'table.dataTable tfoot th, table.dataTable thead th {background-color: ' . $headingcolor . ' !important;}';
            }
            $maincolor = $company->maincolor;
            if ($maincolor) {
                $darkcolor = $this->shadeColor($maincolor, .25);
            }
            if ($maincolor) {
                $css .= 'body, .site-menubar{background-color: ' .
                $maincolor . ' !important;} ';
                $css .= '.site-menu-item.active {background-color: ' . $darkcolor . ' !important; opacity: .8 !important;}';
                $css .= 'body::-webkit-scrollbar-thumb { background-color:'.$darkcolor.' !important; outline: 1px solid '.$darkcolor.' !important;}';
                $css .= '#gotop.to-top{background-color: ' . $darkcolor . ' !important;}';
                $css .= 'table.dataTable tfoot th, table.dataTable thead th {background-color: ' . $darkcolor . ' !important;}';
            }

            $css .= $company->customcss;
        }

        $output = parent::standard_head_html();
        if ($PAGE->pagelayout == 'frontpage') {
            $summary = s(strip_tags(format_text($SITE->summary, FORMAT_HTML)));
            if (!empty($summary)) {
                $output .= "<meta name=\"description\" content=\"$summary\" />\n";
            }
        }

        // Get the theme font from setting
        $fontname = ucwords(\theme_remui\toolbox::get_setting('fontname', 'Roboto'));
        if (empty($fontname)) {
            $fontname = 'Open Sans';
        }
        $output .= "<link href='https://fonts.googleapis.com/css?family=$fontname:300,400,500,600,700,300italic' rel='stylesheet' type='text/css'>";

        if ($css) {
            $output .= '<style>' . $css . '</style>';
        }

        // add google analytics code
        $ga_js_async = "<!-- Google Analytics --><script>window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;ga('create', 'UA-CODE-X', 'auto');ga('send', 'pageview');</script><script async src='https://www.google-analytics.com/analytics.js'></script><!-- End Google Analytics -->";

        $ga_tracking_code = trim(\theme_remui\toolbox::get_setting('googleanalytics'));
        if (!empty($ga_tracking_code)) {
            $output .= str_replace("UA-CODE-X", $ga_tracking_code, $ga_js_async);
        }

        return $output;
    }

    public function shadeColor($color, $percent) {
        $color = str_replace("#", "", $color);
        $t=$percent<0?0:255;
        $p=$percent<0?$percent*-1:$percent;
        $RGB = str_split($color, 2);
        $R=hexdec($RGB[0]);
        $G=hexdec($RGB[1]);
        $B=hexdec($RGB[2]);

        return '#'.substr(dechex(0x1000000+(round(($t-$R)*$p)+$R)*0x10000+(round(($t-$G)*$p)+$G)*0x100+(round(($t-$B)*$p)+$B)),1);
    }
}
