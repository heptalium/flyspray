<?php
  /*********************************************************\
  | Show the roadmap                                        |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoChangelog extends FlysprayDo
{
    function is_accessible()
    {
        global $proj, $user;

        return (bool) $proj->id && $user->can_view_project($proj->id);
    }

    function is_projectlevel() {
        return true;
    }

    function show()
    {
        global $page, $db, $fs, $proj, $user;

        $page->setTitle($fs->prefs['page_title'] . L('changelog'));

        // Get milestones
        $list_id = $db->x->GetOne('SELECT list_id FROM {fields} WHERE field_id = ?',
                                   null, $proj->prefs['roadmap_field']);
        if (!$list_id) {
            trigger_error('Roadmap / changelog has not been configured in the project management area.', E_USER_ERROR);
        }
        
        $milestones = $db->x->getAll('SELECT list_item_id AS version_id, item_name AS version_name
                                    FROM {list_items} li
                                   WHERE list_id = ? AND (version_tense = 1 OR version_tense = 2) AND show_in_list = 1
                                ORDER BY list_position ASC', null, $list_id);

        $data = array();
        $reasons = implode(',', explode(' ', $proj->prefs['changelog_reso']));

        while ((list(, $row) = each($milestones)) && $reasons) {
            $tasks = $db->x->getAll('SELECT t.task_id, percent_complete, item_summary, detailed_desc, mark_private, fs.field_value AS field' . $fs->prefs['color_field'] . ',
                                          opened_by, task_token, t.project_id, prefix_id, li.item_name AS res_name, li.list_item_id AS res_id
                                   FROM {tasks} t
                              LEFT JOIN {field_values} f ON f.task_id = t.task_id
                              LEFT JOIN {field_values} fs ON (fs.task_id = t.task_id AND fs.field_id = ?)
                              LEFT JOIN {list_items} li ON t.resolution_reason = li.list_item_id
                                  WHERE f.field_value = ? AND f.field_id = ? AND t.project_id = ? AND is_closed = 1
                                        AND t.resolution_reason IN (' . $reasons . ')
                               ORDER BY t.resolution_reason DESC', null, 
                                 array($fs->prefs['color_field'], $row['version_id'], $proj->prefs['roadmap_field'], $proj->id));
            $tasks = array_filter($tasks, array($user, 'can_view_task'));

            if (count($tasks)) {
                $resolutions = array();
                foreach ($tasks as $task) {
                    $resolutions[$task['res_name']] = isset($resolutions[$task['res_name']]) ? $resolutions[$task['res_name']] + 1 : 1;
                }

                $data[] = array('tasks' => $tasks, 'name' => $row['version_name'], 'resolutions' => $resolutions);
            }
        }

        if (Get::val('txt')) {
            $page = new FSTpl;
            header('Content-Type: text/plain; charset=UTF-8');
            $page->assign('data', $data);
            $page->display('changelog.text.tpl');
            exit();
        } else {
            $page->assign('data', $data);
            $page->pushTpl('changelog.tpl');
        }
    }
}


?>
