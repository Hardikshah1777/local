<?php
defined('MOODLE_INTERNAL') || die();

class local_generalnotes_comment {
    const cap = 'local/generalnotes:add';
    const TABLE = 'local_generalnotes';
    const perpage = 10;
    /** @var bool Use non-javascript UI */
    private static $nonjs = false;
    /** @var bool If set to true then comment sections won't be able to be opened and closed instead they will always be visible. */
    protected $notoggle = true;
    /** @var bool If set to true comments are automatically loaded as soon as the page loads. */
    protected $autostart = true;
    /** @var bool If set to true the total count of comments is displayed when displaying comments. */
    protected $displaytotalcount = false;
    /** @var bool If set to true a cancel button will be shown on the form used to submit comments. */
    protected $displaycancel = false;
    /** @var int The number of comments associated with this comments params */
    protected $totalcommentcount = null;
    /**
     * Set to true to remove the col attribute from the textarea making it full width.
     *
     * @var bool
     */
    protected $fullwidth = true;
    /** @var int there may be several comment box in one page so we need a client_id to recognize them */
    private $cid;
    /** @var string this html snippet will be used as a template to build comment content */
    private $template;
    /** @var int The context id for comments */
    private $contextid;
    /** @var stdClass The context itself */
    private $context;
    /** @var string This is calculated by normalising the component */
    private $pluginname;
    /** @var string This is calculated by normalising the component */
    private $plugintype;
    /** @var bool Whether the user has the required capabilities/permissions to view comments. */
    private $viewcap = false;
    /** @var bool Whether the user has the required capabilities/permissions to post comments. */
    private $postcap = false;
    /** @var string to customize link text */
    private $linktext;
    private $formatoptions = array('overflowdiv' => true, 'blanktarget' => true);

    /**
     * Construct function of comment class, initialise
     * class members
     *
     * @param stdClass $options {
     *            context => context context to use for the comment [required]
     *            client_id => string an unique id to identify comment area
     *            autostart => boolean automatically expend comments
     *            showcount => boolean display the number of comments
     *            displaycancel => boolean display cancel button
     *            notoggle => boolean don't show/hide button
     *            linktext => string title of show/hide button
     * }
     */
    public function __construct(stdClass $options) {
        $this->viewcap = false;
        $this->postcap = false;

        // setup client_id
        if (!empty($options->client_id)) {
            $this->cid = $options->client_id;
        } else {
            $this->cid = uniqid();
        }

        // setup context
        if (!empty($options->context)) {
            $this->context = $options->context;
            $this->contextid = $this->context->id;
        } else if (!empty($options->contextid)) {
            $this->contextid = $options->contextid;
            $this->context = context::instance_by_id($this->contextid);
        } else {
            print_error('invalidcontext');
        }

        if (!$this->context instanceof context_user) {
            print_error('invalidcontext');
        }

        // setup customized linktext
        if (!empty($options->linktext)) {
            $this->linktext = $options->linktext;
        } else {
            $this->linktext = get_string('comments');
        }

        // setup options for callback functions
        $this->comment_param = new stdClass();
        $this->comment_param->context = $this->context;

        // setting post and view permissions
        $this->check_permissions();

        // load template
        $this->template = html_writer::start_tag('div', array('class' => 'profile-comment-message row border no-gutters p-2'));

        $this->template .= html_writer::tag('span', '___picture___', array('class' => 'picture h-100'));

        $this->template .= html_writer::start_tag('div', array('class' => 'profile-comment-message-meta d-inline-block ml-2'));
        $this->template .= html_writer::tag('div', get_string('createdby', 'local_generalnotes') . '___name___',
                array('class' => 'user'));
        $this->template .= html_writer::tag('div', get_string('datecreated', 'local_generalnotes') . '___time___',
                array('class' => 'time'));

        $this->template .= html_writer::tag('div', '___content___', array('class' => 'text'));
        $this->template .= html_writer::end_tag('div'); // .comment-message-meta

        $this->template .= html_writer::end_tag('div'); // .comment-message

        unset($options);
    }

    /**
     * check posting comments permission
     * It will check based on user roles and ask modules
     * If you need to check permission by modules, a
     * function named $pluginname_check_comment_post must be implemented
     */
    private function check_permissions() {
        $canadd = $this->can_add();
        $this->postcap = $canadd;
        $this->viewcap = $canadd;
    }

    public function can_add() {
        global $USER;
        $usercontext = $this->context;
        $userid = $this->context->instanceid;
        if ($userid == $USER->id) {
            return false;
        }
        if (has_capability(self::cap, $usercontext)) {
            return true;
        }
        $userscourses = \enrol_get_all_users_courses($userid);
        if (empty($userscourses)) {
            return false;
        }
        foreach ($userscourses as $userscourse) {
            context_helper::preload_from_record($userscourse);
            $coursecontext = context_course::instance($userscourse->id);
            if (has_capability(self::cap, $coursecontext)) {
                if (!groups_user_groups_visible($userscourse, $userid)) {
                    continue;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Prepare comment code in html
     *
     * @param boolean $return
     * @return string|void
     */
    public function output($return = true) {
        global $PAGE, $OUTPUT;
        static $template_printed;

        $this->initialise_javascript($PAGE);

        if (!empty(self::$nonjs)) {
            // return non js comments interface
            return $this->print_comments(0, $return, true);
        }

        $html = '';

        // print html template
        // Javascript will use the template to render new comments
        if (empty($template_printed) && $this->can_view()) {
            $html .= html_writer::tag('div', $this->template, array('style' => 'display:none', 'id' => 'cmt-tmpl'));
            $template_printed = true;
        }

        if ($this->can_view()) {
            // print commenting icon and tooltip
            $html .= html_writer::start_tag('div', array('class' => 'mdl-left'));
            $html .= html_writer::link($this->get_nojslink($PAGE), get_string('showcommentsnonjs'),
                    array('class' => 'showcommentsnonjs'));

            if (!$this->notoggle) {
                // If toggling is enabled (notoggle=false) then print the controls to toggle
                // comments open and closed
                $countstring = '';
                if ($this->displaytotalcount) {
                    $countstring = '(' . $this->count() . ')';
                }
                $collapsedimage = 't/collapsed';
                if (right_to_left()) {
                    $collapsedimage = 't/collapsed_rtl';
                } else {
                    $collapsedimage = 't/collapsed';
                }
                $html .= html_writer::start_tag('a', array(
                                'class' => 'comment-link',
                                'id' => 'comment-link-' . $this->cid,
                                'href' => '#',
                                'role' => 'button',
                                'aria-expanded' => 'false')
                );
                $html .= $OUTPUT->pix_icon($collapsedimage, $this->linktext);
                $html .= html_writer::tag('span', $this->linktext . ' ' . $countstring,
                        array('id' => 'comment-link-text-' . $this->cid));
                $html .= html_writer::end_tag('a');
            }

            $html .= html_writer::start_tag('div', array('id' => 'comment-ctrl-' . $this->cid, 'class' => 'comment-ctrl mx-3'));

            if ($this->autostart) {
                // If autostart has been enabled print the comments list immediatly
                $html .= html_writer::start_tag('ul',
                        array('id' => 'comment-list-' . $this->cid, 'class' => 'comment-list comments-loaded'));
                $html .= html_writer::tag('li', '', array('class' => 'first'));
                $html .= $this->print_comments(0, true, false);
                $html .= html_writer::end_tag('ul'); // .comment-list
                $html .= $this->get_pagination(0);
            } else {
                $html .= html_writer::start_tag('ul', array('id' => 'comment-list-' . $this->cid, 'class' => 'comment-list'));
                $html .= html_writer::tag('li', '', array('class' => 'first'));
                $html .= html_writer::end_tag('ul'); // .comment-list
                $html .= html_writer::tag('div', '',
                        array('id' => 'comment-pagination-' . $this->cid, 'class' => 'comment-pagination'));
            }

            if ($this->can_post()) {
                // print posting textarea
                $textareaattrs = array(
                        'name' => 'content',
                        'rows' => 2,
                        'id' => 'dlg-content-' . $this->cid,
                        'aria-label' => get_string('addcomment')
                );
                $textareaattrs['class'] = 'form-control';
                if (!$this->fullwidth) {
                    $textareaattrs['cols'] = '20';
                } else {
                    $textareaattrs['class'] .= ' fullwidth';
                }

                $html .= html_writer::start_tag('div', array('class' => 'profile-comment-area w-full'));
                $html .= html_writer::start_tag('div', array('class' => 'db mt-1'));
                $html .= html_writer::tag('textarea', '', $textareaattrs);
                $html .= html_writer::end_tag('div'); // .db

                $html .= html_writer::start_tag('div', array('class' => 'fd mt-2 text-right', 'id' => 'comment-action-' . $this->cid));
                $html .= html_writer::link('#', get_string('savenote','local_generalnotes'),
                        array('id' => 'comment-action-post-' . $this->cid, 'class' => 'btn btn-primary'));

                if ($this->displaycancel) {
                    //$html .= html_writer::tag('span', ' | ');
                    $html .= html_writer::link('#', get_string('cancel'),
                            array('id' => 'comment-action-cancel-' . $this->cid, 'class' => 'btn btn-secondary'));
                }

                $html .= html_writer::end_tag('div'); // .fd
                $html .= html_writer::end_tag('div'); // .comment-area
                $html .= html_writer::tag('div', '', array('class' => 'clearer'));
            }

            $html .= html_writer::end_tag('div'); // .comment-ctrl
            $html .= html_writer::end_tag('div'); // .mdl-left
        } else {
            $html = '';
        }

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
     * Initialises the JavaScript that enchances the comment API.
     *
     * @param moodle_page $page The moodle page object that the JavaScript should be
     *                          initialised for.
     */
    public function initialise_javascript(moodle_page $page) {

        $options = new stdClass;
        $options->client_id = $this->cid;
        $options->page = 0;
        $options->contextid = $this->contextid;
        $options->notoggle = $this->notoggle;
        $options->autostart = $this->autostart;

        $page->requires->js_call_amd('local_generalnotes/comment', 'init', array($options));
        $page->requires->strings_for_js(array(
                'addcomment',
                'comments',
                'commentscount',
                'commentsrequirelogin',
                'deletecommentbyon'
        ),
                'moodle'
        );

        return true;
    }

    /**
     * Print comments
     *
     * @param int $page
     * @param bool $return return comments list string or print it out
     * @param bool $nonjs print nonjs comments list or not?
     * @return string|void
     */
    public function print_comments($page = 0, $return = true, $nonjs = true) {
        global $DB, $CFG, $PAGE;

        if (!$this->can_view()) {
            return '';
        }
        $page = $page ?? 0;
        $comments = $this->get_comments($page);

        $html = '';
        if ($nonjs) {
            $html .= html_writer::tag('h3', \get_string('comments'));
            $html .= html_writer::start_tag('ul', array('id' => 'comment-list-' . $this->cid, 'class' => 'comment-list'));
        }
        // Reverse the comments array to display them in the correct direction
        foreach ($comments as $cmt) {
            $html .= html_writer::tag('li', $this->print_comment($cmt, $nonjs),
                    array('id' => 'comment-' . $cmt->id . '-' . $this->cid,'class'=>'mx-0 px-0'));
        }
        if ($nonjs) {
            $html .= html_writer::end_tag('ul');
            $html .= $this->get_pagination($page);
        }
        if ($nonjs && $this->can_post()) {
            // Form to add comments
            $html .= html_writer::start_tag('form',
                    array('method' => 'post', 'action' => new moodle_url('/comment/comment_post.php')));
            // Comment parameters
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'contextid', 'value' => $this->contextid));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'add'));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'returnurl', 'value' => $PAGE->url));
            // Textarea for the actual comment
            $html .= html_writer::tag('textarea', '', array('name' => 'content', 'rows' => 2));
            // Submit button to add the comment
            $html .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('submit')));
            $html .= html_writer::end_tag('form');
        }
        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    public function can_view() {
        $this->validate();
        return !empty($this->viewcap);
    }

    protected function validate($params = array()) {
        foreach ($params as $key => $value) {
            $this->comment_param->$key = $value;
        }
        return true;
    }

    /**
     * Return matched comments
     *
     * @param int $page
     * @param str $sortdirection sort direction, ASC or DESC
     * @return array
     */
    public function get_comments($page = '', $sortdirection = 'DESC') {
        global $DB, $CFG, $USER, $OUTPUT;
        if (!$this->can_view()) {
            return false;
        }
        if (!is_numeric($page)) {
            $page = 0;
        }
        $params = array();
        $perpage = self::perpage;
        $start = $page * $perpage;
        $ufields = user_picture::fields('u');

        $sortdirection = ($sortdirection === 'ASC') ? 'ASC' : 'DESC';
        $sql = "SELECT $ufields, c.id AS cid, c.content AS ccontent, c.timecreated AS ctimecreated
                  FROM {" . self::TABLE . "} c
                  JOIN {user} u ON u.id = c.commenter
                 WHERE c.userid = :userid
              ORDER BY c.timecreated $sortdirection, c.id $sortdirection";
        $params['userid'] = $this->context->instanceid;

        $comments = array();

        $rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
        foreach ($rs as $u) {
            $c = new stdClass();
            $c->id = $u->cid;
            $c->content = $u->ccontent;
            $c->timecreated = $u->ctimecreated;
            $c->strftimeformat = get_string('strftimerecentfull', 'langconfig');
            $url = new moodle_url('/user/view.php', array('id' => $u->id,));
            $c->profileurl = $url->out(false); // URL should not be escaped just yet.
            $c->content = format_text($c->content, FORMAT_MOODLE, $this->formatoptions);
            $c->fullname = fullname($u);
            $c->time = userdate($c->timecreated, $c->strftimeformat);
            $c->avatar = $OUTPUT->user_picture($u, array('size' => 30));
            $c->userid = $u->id;

            if ($this->can_delete($c)) {
                $c->delete = true;
            }
            $comments[] = $c;
        }
        $rs->close();

        if (!empty($this->plugintype)) {
            // moodle module will filter comments
            $comments = plugin_callback($this->plugintype, $this->pluginname, 'comment', 'display',
                    array($comments, $this->comment_param), $comments);
        }

        return $comments;
    }

    public function can_delete() {
        return false;
    }

    /**
     * Returns an array containing comments in HTML format.
     *
     * @param stdClass $cmt {
     *          id => int comment id
     *          content => string comment content
     *          format  => int comment text format
     *          timecreated => int comment's timecreated
     *          profileurl  => string link to user profile
     *          fullname    => comment author's full name
     *          avatar      => string user's avatar
     *          delete      => boolean does user have permission to delete comment?
     * }
     * @param bool $nonjs
     * @return string
     * @global core_renderer $OUTPUT
     */
    public function print_comment($cmt, $nonjs = true) {
        global $OUTPUT;
        $patterns = array();
        $replacements = array();

        if (!empty($cmt->delete) && empty($nonjs)) {
            $strdelete = get_string('deletecommentbyon', 'moodle', (object) ['user' => $cmt->fullname, 'time' => $cmt->time]);
            $deletelink = html_writer::start_tag('div', array('class' => 'comment-delete'));
            $deletelink .= html_writer::start_tag('a', array('href' => '#', 'id' => 'comment-delete-' . $this->cid . '-' . $cmt->id,
                    'title' => $strdelete));

            $deletelink .= $OUTPUT->pix_icon('t/delete', get_string('delete'));
            $deletelink .= html_writer::end_tag('a');
            $deletelink .= html_writer::end_tag('div');
            $cmt->content = $deletelink . $cmt->content;
        }
        $patterns[] = '___picture___';
        $patterns[] = '___name___';
        $patterns[] = '___content___';
        $patterns[] = '___time___';
        $replacements[] = $cmt->avatar;
        $replacements[] = html_writer::link($cmt->profileurl, $cmt->fullname);
        $replacements[] = $cmt->content;
        $replacements[] = $cmt->time;

        // use html template to format a single comment.
        return str_replace($patterns, $replacements, $this->template);
    }

    public function get_pagination($page = 0) {
        global $OUTPUT;
        $count = $this->count();
        $perpage = self::perpage;
        $pages = (int) ceil($count / $perpage);
        if ($pages == 1 || $pages == 0) {
            return html_writer::tag('div', '',
                    array('id' => 'comment-pagination-' . $this->cid, 'class' => 'profile-comment-pagination'));
        }
        if (!empty(self::$nonjs)) {
            // used in non-js interface
            return $OUTPUT->paging_bar($count, $page, $perpage, $this->get_nojslink(), 'comment_page');
        } else {
            // return ajax paging bar
            $str = '<nav aria-label="Page" class="pagination justify-content-center">';
            $str .= '<ul class="profile-comment-paging pagination flex-wrap" id="comment-pagination-' . $this->cid . '">';
            for ($p = 0; $p < $pages; $p++) {
                if ($p == $page) {
                    $class = 'curpage btn-primary';
                } else {
                    $class = 'pageno btn-secondary';
                }
                $str .= '<li class="page-item ' . ($p == $page ? 'active' : '') . '">';
                $str .= '<a href="#" class="' . $class . ' page-link" id="comment-page-' . $this->cid . '-' . $p . '">' . ($p + 1) .
                        '</a> ';
                $str .= '</li>';
            }
            $str .= '</ul>';
            $str .= '</nav>';
        }
        return $str;
    }

    public function count() {
        global $DB;
        if ($this->totalcommentcount === null) {
            $where = 'userid = :userid';
            $params = array(
                    'userid' => $this->context->instanceid,
            );

            $this->totalcommentcount = $DB->count_records_select(self::TABLE, $where, $params);
        }
        return $this->totalcommentcount;
    }

    /**
     * Gets a link for this page that will work with JS disabled.
     *
     * @param moodle_page $page
     * @return moodle_url
     * @global moodle_page $PAGE
     */
    public function get_nojslink(moodle_page $page = null) {
        if ($page === null) {
            global $PAGE;
            $page = $PAGE;
        }

        $link = new moodle_url($page->url, array(
                'nonjscomment' => true,
        ));
        $link->remove_params(array('comment_page'));
        return $link;
    }

    public function can_post() {
        $this->validate();
        return isloggedin() && !empty($this->postcap);
    }

    /**
     * Add a new comment
     *
     * @param string $content
     * @param int $format
     * @return stdClass
     * @global moodle_database $DB
     */
    public function add($content, $format = FORMAT_MOODLE) {
        global $DB, $USER, $OUTPUT;
        if (!$this->can_post()) {
            throw new comment_exception('nopermissiontocomment');
        }
        $now = time();
        $newcmt = new stdClass;
        $newcmt->userid = $this->context->instanceid;
        $newcmt->commenter = $USER->id;
        $newcmt->content = $content;
        $newcmt->timecreated = $now;

        $cmt_id = $DB->insert_record(self::TABLE, $newcmt);
        if (!empty($cmt_id)) {
            $newcmt->id = $cmt_id;
            $newcmt->strftimeformat = get_string('strftimerecentfull', 'langconfig');
            $newcmt->fullname = fullname($USER);
            $url = new moodle_url('/user/view.php', array('id' => $USER->id,));
            $newcmt->profileurl = $url->out();
            $newcmt->content = format_text($newcmt->content, FORMAT_MOODLE, $this->formatoptions);
            $newcmt->avatar = $OUTPUT->user_picture($USER, array('size' => 30));

            $commentlist = array($newcmt);

            if (!empty($this->plugintype)) {
                // Call the display callback to allow the plugin to format the newly added comment.
                $commentlist = plugin_callback($this->plugintype,
                        $this->pluginname,
                        'comment',
                        'display',
                        array($commentlist, $this->comment_param),
                        $commentlist);
                $newcmt = $commentlist[0];
            }
            $newcmt->time = userdate($newcmt->timecreated, $newcmt->strftimeformat);

            return $newcmt;
        } else {
            throw new comment_exception('dbupdatefailed');
        }
    }

    /**
     * Delete a comment
     *
     * @param int|stdClass $comment The id of a comment, or a comment record.
     * @return bool
     */
    public function delete($comment) {
        global $DB;
        if (is_object($comment)) {
            $commentid = $comment->id;
        } else {
            $commentid = $comment;
            $comment = $DB->get_record(self::TABLE, ['id' => $commentid]);
        }

        if (!$comment) {
            throw new comment_exception('dbupdatefailed');
        }
        if (!$this->can_delete($comment)) {
            throw new comment_exception('nopermissiontocomment');
        }
        $DB->delete_records(self::TABLE, array('id' => $commentid));
        return true;
    }

}
