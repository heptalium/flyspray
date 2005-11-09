<?php

class Tpl
{
    var $_uses  = array();
    var $_vars  = array();
    var $_theme = '';

    function uses()
    {
        $args = func_get_args();
        $this->_uses = array_merge($this->_uses, $args);
    }

    function assign($arg0 = null, $arg1 = null)
    {
        if (is_string($arg0)) {
            $this->_vars[$arg0] = $arg1;
        }
        if (is_array($arg0)) {
            $this->_vars += $arg0;
        }
        if (is_object($arg0)) {
            $this->_vars += get_object_vars($arg0);
        }
    }

    function setTheme($theme)
    {
        $this->_theme = str_replace('//', '/', $theme.'/');
    }

    function themeUrl()
    {
        global $baseurl;
        return $baseurl.'themes/'.$this->_theme;
    }

    function compile(&$item)
    {
        if (strncmp($item, '<?', 2)) {
            $item = preg_replace( '/{!([^\s&][^{}]*)}(\n?)/', '<?php echo \1; ?>\2\2', $item);
            $item = preg_replace( '/{([^\s&][^{}]*)}(\n?)/',
                    '<?php echo htmlspecialchars(\1, ENT_QUOTES, "utf-8"); ?>\2\2', $item);
        }
    }

    function display($_tpl, $_arg0 = null, $_arg1 = null)
    {
        // theming part
        $_basedir = dirname(dirname(__FILE__)).'/';
        if (file_exists($_basedir.$this->_theme.$_tpl)) {
            $_tpl_data = file_get_contents($_basedir.$this->_theme.$_tpl);
        } else {
            $_tpl_data = file_get_contents($_basedir.'templates/'.$_tpl);
        }

        // compilation part
        $_tpl_data = preg_split('!(<\?php.*\?>)!sU', $_tpl_data, -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        array_walk($_tpl_data, array($this, 'compile'));
        $_tpl_data = join('', $_tpl_data);

        $_tpl_data = str_replace('&lbrace;', '{', $_tpl_data);
        $_tpl_data = str_replace('&rbrace;', '}', $_tpl_data);

        // variables part
        if (!is_null($_arg0)) {
            $this->assign($_arg0, $_arg1);
        }

        foreach ($this->_uses as $_var) {
            global $$_var;
        }
        extract($this->_vars);
        eval( '?>'.$_tpl_data );
    }

    function fetch($tpl, $arg0 = null, $arg1 = null)
    {
        ob_start();
        $this->display($tpl, $arg0, $arg1);
        return ob_get_clean();
    }
}

class FSTpl extends Tpl
{
    var $_uses = array('fs', 'conf', 'baseurl', 'language', 'proj', 'user');
}

// {{{ costful templating functions, TODO: optimize them

function tpl_tasklink($text, $id, $attr = null)
{
    global $fs, $details_text;

    $details = $fs->GetTaskDetails($id);

    if ($details['is_closed'] == '1') {
        $status = $details['resolution_name'];
    } else {
        $status = $details['status_name'];
    }
    $title = $status . ': '
           .  htmlspecialchars(substr($details['item_summary'], 0, 64), ENT_QUOTES, 'utf-8');
    $link  = sprintf('<a href="%s" title="%s" %s>%s</a>',
            $fs->CreateURL('details', $id), $title, join_attrs($attr), $text);

    if ($details['is_closed']) {
        $link = "<del>&#160;".$link."&#160;</del>";
    }
    return $link;
}

function tpl_userlink($uid)
{
    global $db, $fs;
    global $details_text;

    static $cache = array();

    if (empty($cache[$uid])) {
        $sql = $db->Query("SELECT user_name, real_name FROM {users} WHERE user_id = ?",
                array($uid));
        if ($db->countRows($sql)) {
            list($uname, $rname) = $db->fetchRow($sql);
            $cache[$uid] = '<a href="'.$fs->createUrl('user', $uid).'">'
                .htmlspecialchars($rname, ENT_QUOTES, 'utf-8').' ('
                .htmlspecialchars($uname, ENT_QUOTES, 'utf-8').')</a>';
        } else {
            $cache[$uid] = $details_text['anonymous'];
        }
    }

    return $cache[$uid];
}

function tpl_fast_tasklink($arr)
{
    return tpl_tasklink($arr[0], $arr[1]);
}

// }}}
// {{{ some useful plugins

function join_attrs($attr = null) {
    if (is_array($attr)) {
        $arr = array();
        foreach ($attr as $key=>$val) {
            $arr[] = $key.'="'.htmlspecialchars($val, ENT_QUOTES, "utf-8").'"';
        }
        return ' '.join(' ', $arr);
    }
    return '';
}

function tpl_options($options, $selected = null, $labelIsValue = false, $attr = null)
{
    $html = '';

    // force $selected to be an array.
    // this allows multi-selects to have multiple selected options.
    settype($selected, 'array');
    settype($options, 'array');

    foreach ($options as $value=>$label)
    {
        if (is_array($label)) {
            list($value, $label) = $label;
        }
        $label = htmlspecialchars($label, ENT_QUOTES, "utf-8");
        $value = $labelIsValue ? $label
                               : htmlspecialchars($value, ENT_QUOTES, "utf-8");

        $html .= '<option value="'.$value.'"';
        if (in_array($value, $selected)) {
            $html .= ' selected="selected"';
        }
        $html .= join_attrs($attr).'>'.$label.'</option>';
    }

    return $html;
}

function tpl_double_select($name, $options, $selected = null, $labelIsValue = false)
{
    static $_id = 0;
    static $tpl = null;
    
    if (!$tpl) {
        // poor man's cache
        $tpl = new FSTpl();
    }

    settype($selected, 'array');
    settype($options, 'array');

    $tpl->assign('id', '_task_id_'.($_id++));
    $tpl->assign('name', $name);
    $tpl->assign('selected', $selected);
    $html = $tpl->fetch('common.dualselect.tpl');

    $selectedones = array();

    $opt1 = '';
    foreach ($options as $value => $label) {
        if (is_array($label)) {
            list($value, $label) = $label;
        }
        if ($labelIsValue) {
            $value = $label;
        }
        if (in_array($value, $selected)) {
            $selectedones[$value] = $label;
            continue;
        }
        $label = htmlspecialchars($label, ENT_QUOTES, "utf-8");
        $value = htmlspecialchars($value, ENT_QUOTES, "utf-8");

        $opt1 .= sprintf('<option value="%s">%s</option>', $value, $label);
    }

    $opt2 = '';
    foreach ($selected as $value) {
        $label = htmlspecialchars($selectedones[$value], ENT_QUOTES, "utf-8");
        $value = htmlspecialchars($value, ENT_QUOTES, "utf-8");

        $opt2 .= sprintf('<option value="%s">%s</option>', $value, $label);
    }

    return sprintf($html, $opt1, $opt2);
}

function tpl_checkbox($name, $checked = false, $id = null, $value = 1, $attr = null)
{
    $name  = htmlspecialchars($name,  ENT_QUOTES, "utf-8");
    $value = htmlspecialchars($value, ENT_QUOTES, "utf-8");
    $html  = '<input type="checkbox" name="'.$name.'" value="'.$value.'" ';
    if ($id) {
        $html .= 'id="'.htmlspecialchars($id, ENT_QUOTES, "utf-8").'" ';
    }
    if ($checked) {
        $html .= 'checked="checked" ';
    }

    return $html.join_attrs($attr).' />';
}

function tpl_img($src, $alt)
{
    global $baseurl;
    if (file_exists(dirname(dirname(__FILE__)).'/'.$src)) {
        return '<img src="'.$baseurl
            .htmlspecialchars($src, ENT_QUOTES,'utf-8').'" alt="'
            .htmlspecialchars($alt, ENT_QUOTES,'utf-8').'" />';
    }
}

function tpl_formattext($text)
{
    $text = nl2br(htmlspecialchars($text));

    // Change URLs into hyperlinks
    $text = ereg_replace('[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]','<a href="\0">\0</a>', $text);

    // Change FS#123 into hyperlinks to tasks
    return preg_replace_callback("/\b(?:FS#|bug )(\d+)\b/",
            'tpl_fast_tasklink', $text);
}

function tpl_draw_perms($perms)
{
    global $language;
    $perm_fields = array('is_admin', 'manage_project', 'view_tasks',
            'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
            'view_comments', 'add_comments', 'edit_comments', 'delete_comments',
            'view_attachments', 'create_attachments', 'delete_attachments',
            'view_history', 'close_own_tasks', 'close_other_tasks',
            'assign_to_self', 'assign_others_to_self', 'view_reports',
            'global_view');

    // FIXME: colours should be set in the stylesheet instead of the template class
    $yesno = array(
            '<td style="color: red;">No</td>',
            '<td style="color: green;">Yes</td>');

    // FIXME: html belongs in a template, not in the template class
    $html = '<table border="1">';

    foreach ($perms as $key => $val) {
        if (!is_numeric($key) && in_array($key, $perm_fields)) {
            $html .= '<tr><td>' . str_replace('_', ' ', $key) . '</td>';
            $html .= $yesno[(bool)$val].'</tr>';
        }
    }
    return $html . '</table>';
}

function tpl_disableif($if)
{
    if ($if) {
        return 'disabled="disabled"';
    }
}

// }}}

?>
