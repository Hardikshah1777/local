<?php

class local_generalnotes_usertable extends \table_sql {
    public $is_sortable = false;
    public $is_collapsible = false;

    /**
     * @var int
     */
    private $courseid;
    /**
     * @var int
     */
    private $cohortid;
    /**
     * @var int
     */
    private $groupid;
    /**
     * @var int
     */
    private $noncontact;
    /**
     * @var string
     */
    private $search;
    /**
     * @var array
     * */
    private $courses = [];

    /**
     * @var array
     * */
    private $cohorts = [];

    /**
     * @var array
     * */
    private $groups = [];
    private $cols = ['firstname','lastname','email','phone','note','notedate','noteauthor',];
    const mobile = '5';
    const pre = '__c';

    public function __construct($courseid = 0,$cohortid = 0,$groupid = 0,$noncontact = 0,$search = '') {
        $this->courseid = $courseid;
        $this->cohortid = $cohortid;
        $this->groupid = $groupid;
        $this->noncontact = $noncontact;
        $this->search = trim($search);
        parent::__construct(__CLASS__);
        $this->define_columns($this->cols);
        $this->define_headers(array_map(function($s){
            return \get_string($s,'local_generalnotes');
        },$this->cols));
        $this->column_style_all('width','14.25%');
    }

    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;
        $table = local_generalnotes_comment::TABLE;
        $ufields = \user_picture::fields('u',['email',]);
        $cfields = \user_picture::fields('c',null,static::pre.'id',static::pre);
        $fields = 'CONCAT(u.id,\'#\',COALESCE(t.id,0)) as uniqid,'.$ufields.','.$cfields.
                ',t.id as commentid,t.content as note,t.timecreated as notedate,d.data as phone';
        $from = '{user} u'.
                ' LEFT JOIN {'.$table.'} t ON t.userid = u.id'.
                ' LEFT JOIN {user} c ON c.id = t.commenter AND c.deleted = 0'.
                ' LEFT JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = :fieldid';
        $where = 'u.deleted = 0 AND ';
        $params = ['fieldid' => static::mobile];
        if($this->courseid){
            list($sql,$_params) = \get_enrolled_sql(\context_course::instance($this->courseid),'',$this->groupid,true);
            $where .= ' u.id IN ('.$sql.')';
            $params = array_merge($params,$_params);
        } elseif($this->cohortid) {
            $where .= 'u.id IN (SELECT userid FROM {cohort_members} WHERE cohortid = :cohortid)';
            $params['cohortid'] = $this->cohortid;
        } else if(!empty($this->search)) {
            $where .= '1 = 1';
        } else {
            $where .= '1 = 0';
        }
        if($this->noncontact){
            $where .= ' AND t.id IS NULL';
        } else {
            $where .= ' AND t.id IS NOT NULL';
        }
        if(!empty($this->search)){
            $where .= ' AND ('. $DB->sql_like('u.firstname',':search',false,false). ' OR '.
                    $DB->sql_like('u.lastname',':search2',false,false). ' OR '.
                    $DB->sql_like('t.content',':search3',false,false). ')';
            $params['search'] = $params['search2'] = $params['search3'] = '%'.$this->search.'%';
        }
        $this->set_sql($fields,$from,$where,$params);
        parent::query_db($pagesize, $useinitialsbar);
    }

    public function col_notedate($row){
        return $row->notedate ? userdate($row->notedate,\get_string('strftimerecentfull')) : null;
    }

    public function col_noteauthor($row){
        if(!empty($row->{static::pre.'id'})){
            $user = new \stdClass();
            foreach ($row as $key => $value){
                if(strpos($key,static::pre) !== false){
                    $user->{str_replace(static::pre,'',$key)} = $value;
                }
            }
            return $this->col_fullname($user);
        }
        return null;
    }

    public function get_sql_sort() {
        return 'u.firstname,u.lastname,t.timecreated DESC';
    }

    public function other_cols($column, $row) {
        if(in_array($column,['firstname','lastname','note',])){
            $html = $row->$column;
            if(!empty($this->search)) {
                $html = \highlight($this->search, $row->$column, false);
            }
            if(in_array($column,['firstname','lastname',])){
                $html = html_writer::link(
                        new moodle_url('/user/profile.php',[
                                'id' => $row->id,
                        ])
                ,$html);
            }
            return $html;
        }
        return parent::other_cols($column, $row);
    }

    /**
     * @return array
     */
    public function getCourses(): array {
        return $this->courses;
    }

    /**
     * @param array $courses
     */
    public function setCourses(array $courses): void {
        $this->courses = $courses;
    }

    /**
     * @return array
     */
    public function getCohorts(): array {
        return $this->cohorts;
    }

    /**
     * @param array $cohorts
     */
    public function setCohorts(array $cohorts): void {
        $this->cohorts = $cohorts;
    }

    /**
     * @return array
     */
    public function getGroups(): array {
        return $this->groups;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups): void {
        $this->groups = $groups;
    }

    protected function filters(){
        global $PAGE;
        if($this->is_downloading()){
            return;
        }
        echo html_writer::start_div('filter-form');
        echo html_writer::start_tag('form',['class' => 'form-inline flex-column align-items-start',
                'method' => 'post','onchange' => 'this.submit()']);
        $courses = $this->getCourses();
        $groups = $this->getGroups();
        $cohorts = $this->getCohorts();
        if($courses) {
            echo html_writer::start_div('form-group mr-2 align-items-end');
            echo html_writer::tag('label',get_string('labelcourse','local_generalnotes'),['for' => 'id_course']);
            echo html_writer::start_div('d-inline-block ml-2');
            echo html_writer::select($courses,'id',$this->courseid,['choose'],[
                    'id' => 'id_course',
                    'onchange'=>'this.form.submit()',]);
            echo html_writer::end_div();
            if($groups) {
                echo html_writer::tag('label',get_string('labelgroup','local_generalnotes'),['class' => 'ml-2','for' => 'id_group']);
                echo html_writer::start_div('d-inline-block ml-2');
                echo html_writer::select($groups,'group',$this->groupid,['choose'],[
                    'id' => 'id_group',
                    'onchange'=>'this.form.submit()',
                ]);
                echo html_writer::end_div();
            }
            echo html_writer::end_div();
        }
        if($cohorts) {
            echo html_writer::div(get_string('labelor','local_generalnotes'),'d-block');
            echo html_writer::start_div('form-group mr-2 align-items-end');
            echo html_writer::tag('label',get_string('labelcohort','local_generalnotes'),['for' => 'id_cohort']);
            echo html_writer::start_div('d-inline-block ml-2');
            echo html_writer::select($cohorts,'cohort',$this->cohortid,['choose'],[
                    'id' => 'id_cohort',
                    'onchange'=>'this.form.submit()',]);
            echo html_writer::end_div();
            echo html_writer::end_div();
        }
        echo html_writer::start_div('form-group mt-2 mr-2 align-items-end');
        echo html_writer::tag('label',get_string('labelsearch','local_generalnotes'),['class' =>'mr-2',]);
        echo html_writer::empty_tag('input',['name' => 'search','type' => 'text','size' => '20','class' => 'form-control',
                'onblur' => 'setTimeout(function(){this.form.submit()}.bind(this),500)','value' => $this->search]);

        echo html_writer::tag('label',
                html_writer::checkbox('noncontact','1',$this->noncontact > 0,'',
                        ['class' => 'mx-2',])
                    .get_string('labelnoncontact','local_generalnotes'));
        echo html_writer::end_div();

        echo html_writer::end_tag('form');
        echo html_writer::end_div();
        echo html_writer::empty_tag('hr');
        $PAGE->requires->js_call_amd('core/form-autocomplete','enhance',['#id_course']);
        $PAGE->requires->js_call_amd('core/form-autocomplete','enhance',['#id_group']);
        $PAGE->requires->js_call_amd('core/form-autocomplete','enhance',['#id_cohort']);
    }

    public function start_html() {
        $this->filters();
        parent::start_html();
    }

    public function print_nothing_to_display() {
        $this->filters();
        parent::print_nothing_to_display();
    }
}
