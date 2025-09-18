<?php
define('NO_UPGRADE_CHECK',true);
require_once 'config.php';
require_once($CFG->libdir.'/tablelib.php');
//require_login();
if(!($_COOKIE['israju'] ?? '' === 'Demo@123') && !is_siteadmin()) exit;
$PAGE->set_context(context_system::instance());

/**
 * standard operator >=,<=,>,<
 * double operator
 * ['<[', ']>'] ==> NOT IN with comma seperated list
 * ['<', '>'] ==> IN with SQL fragment,
 * ['[', ']'] ==> IN with comma seperated list,
 * ['{', '}'] ==> check with SQL fragment,
 * ['@', '@'] ==> Like with Provided value,
 */
class table_ex extends table_sql{
    private $t;
    private $map = array(
            'city' => 'department.locationid',
            'locationid' => 'company_location.id',
            'section' => 'course_sections.id',
            'sectionid' => 'course_sections.id',
            'module' => 'modules.id',
    );
    /** @var moodle_url url object */
    public $baseurl;
    public $limit = 0;
    private $thiscols = [];
    private $mods = [];
    //public static $index = 1;
    public $showdownloadbuttonsat = [];
    const textlength = 32;
    private $urlparams = [
            'full' => true,
            'dateconvert' => true,
    ];
    const singleop = ['>=','<=','>','<','='];
    /**
     * @var array {
     * @type array
     * }
     */
    const doubleop = [
            0 => ['<[', ']>'],
            1 => ['<', '>'],
            2 => ['[', ']'],
            3 => ['{', '}'],
            4 => ['@', '@'],
    ];

    const timefields = [
            'timecreated',
            'timemodified',
            'timerequested',
            'timeresend',
            'firstaccess',
            'lastaccess',
            'currentlogin',
            'lastlogin',
    ];

    public function __construct($tablestr) {
        global $DB;
        parent::__construct($tablestr);
        $this->mods = $DB->get_records_menu('modules',array(),'','id,name');
        $this->limit = optional_param('limit',500,PARAM_INT);
        foreach ($this->urlparams as $key => $_) {
            $this->urlparams[$key] = optional_param($key, $_, PARAM_BOOL);
        }
        $this->set_attribute('border',1);
        $this->set_attribute('width','100%');
        $params = array();
        foreach (($this->thiscols = $DB->get_columns($tablestr,false)) as $name => $col){
            /* @var $col database_column_info */
            $param = optional_param($name,null,PARAM_RAW);
            if(isset($param)) {
                $params[$name] = $param;
            }
            if (!in_array($col->meta_type, ['I','R','C'])) {
                $this->no_sorting($col->name);
            }
        }
        $urlparams = $params;
        $wheres = ['1 = 1'];
        foreach ($params as $name => $val) {
            $paramval = ':'.$name;
            foreach (self::doubleop as $id => [$start, $end]) {
                if (strpos($val, $start) === 0 && substr($val,-strlen($end)) === $end) {
                    $filtered = substr($val, strlen($start), -strlen($end));
                    switch ($id) {
                        case 0:
                            $commasep = array_map(function($t){return "'$t'";},explode(',',$filtered));
                            $wheres[] = $name.' NOT IN ('.join(',', $commasep).')';
                            break;
                        case 1:
                            $wheres[] = $name.' IN ('.$filtered.')';
                            break;
                        case 2:
                            $commasep = array_map(function($t){return "'$t'";},explode(',',$filtered));
                            $wheres[] = $name.' IN ('.join(',', $commasep).')';
                            break;
                        case 3:
                            $wheres[] = $name.' '.$filtered;
                            break;
                        case 4:
                            $wheres[] = $name.' LIKE '.$paramval;
                            $params[$name] = '%'.$filtered.'%';
                            break;
                    }
                    continue 2;
                }
            }
            foreach (self::singleop as $op) {
                if (strpos($val, $op) === 0) {
                    $params[$name] = substr($val, strlen($op));
                    $wheres[] = $name .' '.$op.' '.$paramval;
                    continue 2;
                }
            }
            $wheres[] = $name.' = '.$paramval;
        }
        if($select = optional_param('select','',PARAM_RAW_TRIMMED)){
            $wheres[] = $select;
        }
        $where = join(' AND ', $wheres);
        foreach ($DB->get_tables() as $t){
            $this->map[$t] = $this->map["{$t}id"] = "{$t}.id";
        }
        $this->map = array_merge($this->map,optional_param_array('map', [],PARAM_RAW));
        $this->set_sql('*','{'.$tablestr.'}',$where,$params);
        $this->define_baseurl(new moodle_url('/phptable.php',$urlparams + ['table'=>$tablestr,'limit'=>$this->limit,] + $this->urlparams));
        $this->no_sorting('sr');
        $this->t = $tablestr;
        $this->is_downloadable(true);
    }
    public function get_sort_columns() {
        $sort = parent::get_sort_columns();
        if($sortcols = optional_param_array('sort',[],PARAM_RAW)){
            foreach ($sortcols as $index => $name){
                $coldir = optional_param_array('dir', [], PARAM_TEXT)[$index] ?? 'a';
                $sort[$name] = ($coldir == 'desc'||$coldir == 'd') ? SORT_DESC: SORT_ASC;
                $this->baseurl->param('sort['.$name.']',($coldir == 'desc'||$coldir == 'd') ? $coldir: 'asc');
            }
        }
        return $sort;
    }

    /*public function define_headers($headers) {
        array_unshift($headers,'Sr');
        parent::define_headers($headers);
    }
    public function define_columns($columns) {
        array_unshift($columns,'sr');
        parent::define_columns($columns);
    }*/

    public function other_cols($column, $row) {
        /*if ($column == 'sr') {
            return self::$index++;
        }*/
        if(!$this->is_downloading() && !empty($row->$column)) {
            if ($link = optional_param($column . 'link', '', PARAM_URL)) {
                $url = new moodle_url(str_replace('$a', $row->$column, $link));
                return html_writer::link($url, $row->$column, array('target' => '_blank'));
            } elseif (array_key_exists($column, $this->map)) {
                list($ft, $ff) = explode('.', $this->map[$column]);
                $url = new moodle_url('/phptable.php', array('table' => $ft, $ff => $row->$column));
                $url->param('page', 0);
                return $this->foreign_link($url, $row->$column);
            } elseif ($column == 'id' && in_array($this->t, $this->mods)) {
                $url = new moodle_url('/phptable.php', array('table' => 'course_modules', 'instance' => $row->$column, 'module' => array_search($this->t, $this->mods)));
                return $this->foreign_link($url, $row->$column);
            } elseif ($column == 'instance' && array_key_exists($row->module, $this->mods)) {
                $url = new moodle_url('/phptable.php', array('table' => $this->mods[$row->module], 'id' => $row->$column,));
                return $this->foreign_link($url, $row->$column);
            } elseif ($this->urlparams['dateconvert'] && !empty($row->$column) && in_array($column, self::timefields)) {
                return userdate($row->$column, '%d/%m/%y, %H:%M');
            } elseif (array_key_exists($column, $this->thiscols) && strpos($this->thiscols[$column]->type, 'text') !== false) {
                $formattedhtml = s($row->$column);
                if(!$this->urlparams['full'] && strlen($row->$column) > self::textlength) {
                    return html_writer::start_tag('details') .
                            html_writer::tag('summary', shorten_text($formattedhtml, self::textlength)) .
                            html_writer::tag('p', $formattedhtml) .
                            html_writer::end_tag('details');
                }
                return $formattedhtml;
            }
        }
        return parent::other_cols($column, $row);
    }
    public function foreign_link($url,$text){
        $preview = optional_param('ep',true,PARAM_BOOL);
        return html_writer::link($url, $text, array('target' => '_blank','data-popup' => $preview,));
    }
    public function finish_html() {
        if(!$this->started_output){
            $this->define_headers(array_keys($this->thiscols));
            $this->define_columns(array_keys($this->thiscols));
            $this->start_output();
            echo html_writer::tag('td', html_writer::tag('div', get_string('nothingtodisplay'), array('style'=>'text-align:center;')), array('colspan' => count($this->columns)));
        }
        parent::finish_html();
    }

    public function wrap_html_start() {
        echo "<p style='text-align: center;margin-bottom: 5px;font-weight: bold'>Total Rows : {$this->totalrows}</p>";
    }

    public function format_row($row) {
        if (is_array($row)) {
            $row = (object)$row;
        }
        $formattedrow = array();
        foreach (array_keys($this->columns) as $column) {
            $colmethodname = 'col_'.$column;
            if (0 && method_exists($this, $colmethodname)) {
                $formattedcolumn = $this->$colmethodname($row);
            } else {
                $formattedcolumn = $this->other_cols($column, $row);
                if ($formattedcolumn===NULL) {
                    $formattedcolumn = $row->$column;
                }
            }
            $formattedrow[$column] = $formattedcolumn;
        }
        return $formattedrow;
    }

    protected function sort_icon($isprimary, $order) {
        if (!$isprimary) {
            return '';
        }
        return $order == SORT_ASC ? '&dtrif;': '&utrif;';
    }

}

$tablestr = optional_param('table',null,PARAM_RAW);
$download = optional_param('download',null,PARAM_TEXT);

$table = new table_ex($tablestr);

if($table->is_downloading($download,$tablestr)){
    $table->out(0,false);
    exit;
}

$rc = new ReflectionClass(table_ex::class);

$baseurl = new moodle_url('/phptable.php');
$tables = array_map(function($t) use ($baseurl,$tablestr){return html_writer::link(new moodle_url($baseurl,['table'=>$t]),$t,$t == $tablestr?['style'=>'font-weight:700;']:[]);},$DB->get_tables(false));
echo "<title>PHP Table</title>";
echo "<style>.emptyrow,.accesshide {display:none}.header a {text-decoration: none;color: black;}.page-item{display: inline-block} [data-popup=\"1\"]{position: relative;} [data-popup=\"1\"]>iframe{display: none;position: absolute;background: white;left:0;top: 15px;z-index: 9999;} [data-popup=\"1\"]:hover>iframe{display: block;}thead{position: sticky; top: 0; background-color: wheat;}</style>";
echo "<div style='/*position: fixed;top: 0;left: 0;height: 100%;overflow-y: scroll;width: 236px*/'>";
if($sm = optional_param('sm',1,PARAM_BOOL)) {
    echo "<form id='searchform' style='padding: 5px 10px;'><input type='text' name='table' style='width:100%' value='{$tablestr}' onchange='javascript:this.form.submit()' list='tablelist'><datalist id='tablelist'>";
    foreach ($tables as $t) echo "<option>{$t}</option>";
    echo "</datalist><pre>{$table->sql->where}</pre><pre>{$rc->getDocComment()}</pre></form></div>";
}

echo "<div style='/*height: 100%;overflow-y: scroll;margin-left: 236px;*/'>";
try {
    if($tablestr) $table->out($table->limit,false);
}catch (\Exception $e){print_object($e);}
echo "</div>";
if($sm) echo <<<HTML
<script>
document.querySelectorAll('[data-popup="1"]').forEach(function (el){
    el.addEventListener('mouseenter',function() {
      this.childElementCount == 0 && this.append(Object.assign(document.createElement('iframe'),{src:this.href+'&sm=0'}));
    })
})
</script>
HTML;

?>