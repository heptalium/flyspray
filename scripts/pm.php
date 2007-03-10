<?php

  /********************************************************\
  | Project Managers Toolbox                               |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                               |
  | This script is for Project Managers to modify settings |
  | for their project, including general permissions,      |
  | members, group permissions, and dropdown list items.   |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}


class FlysprayDoPm extends FlysprayDo
{
    var $default_handler = 'prefs';

    // **********************
    // Begin all area_ functions
    // **********************

    function area_pendingreq()
    {
        global $db, $page, $proj;

        $sql = $db->Execute("SELECT  *
                             FROM  {admin_requests} ar
                        LEFT JOIN  {tasks} t ON ar.task_id = t.task_id
                        LEFT JOIN  {users} u ON ar.submitted_by = u.user_id
                            WHERE  ar.project_id = ? AND resolved_by = 0
                         ORDER BY  ar.time_submitted ASC", array($proj->id));

        $page->assign('pendings', $sql->GetArray());
    }

    function area_prefs()     {}
    function area_editgroup() { return FlysprayDoAdmin::area_editgroup(); }
    function area_groups()    { return FlysprayDoAdmin::area_groups(); }
    function area_users()     { return FlysprayDoAdmin::area_users(); }
    function area_fields()    { return FlysprayDoAdmin::area_fields(); }
    function area_lists()     { return FlysprayDoAdmin::area_lists(); }
    function area_list()      { return FlysprayDoAdmin::area_list(); }

    // **********************
    // End of area_ functions
    // **********************

    // **********************
    // Begin all action_ functions
    // **********************

    function action_updateproject()
    {
        global $proj, $db, $baseurl;

        if (Post::val('delete_project')) {
            $url = (Post::val('move_to')) ? CreateURL('pm', 'prefs', Post::val('move_to')) : $baseurl;

            if (Backend::delete_project($proj->id, Post::val('move_to'))) {
                return array(SUBMIT_OK, L('projectdeleted'), $url);
            } else {
                return array(ERROR_INPUT, L('projectnotdeleted'), $url);
            }
        }

        if (!Post::val('project_title')) {
            return array(ERROR_RECOVER, L('emptytitle'));
        }

        $cols = array( 'project_title', 'theme_style', 'lang_code', 'default_task', 'default_entry',
                'intro_message', 'others_view', 'anon_open', 'send_digest', 'anon_view_tasks',
                'notify_email', 'notify_jabber', 'notify_subject', 'notify_reply', 'roadmap_field',
                'feed_description', 'feed_img_url', 'comment_closed', 'auto_assign');
        $args = array_map('Post_to0', $cols);
        $cols[] = 'notify_types';
        $args[] = implode(' ', Post::val('notify_types'));
        $cols[] = 'last_updated';
        $args[] = time();
        $cols[] = 'default_cat_owner';
        $args[] =  Flyspray::username_to_id(Post::val('default_cat_owner'));
        $args[] = $proj->id;

        $db->Execute("UPDATE  {projects}
                         SET  ".join('=?, ', $cols)."=?
                       WHERE  project_id = ?", $args);

        $db->Execute('UPDATE {projects} SET visible_columns = ? WHERE project_id = ?',
                      array(trim(Post::val('visible_columns')), $proj->id));

        return array(SUBMIT_OK, L('projectupdated'));
    }

    function action_add_field()       { return FlysprayDoAdmin::action_add_field(); }
    function action_update_fields()   { return FlysprayDoAdmin::action_update_fields(); }
    function action_add_list()        { return FlysprayDoAdmin::action_add_list(); }
    function action_update_lists()    { return FlysprayDoAdmin::action_update_lists(); }
    function action_add_category()    { return FlysprayDoAdmin::action_update_lists(); }
    function action_update_category() { return FlysprayDoAdmin::action_update_category(); }
    function action_newgroup()        { return FlysprayDoAdmin::action_newgroup(); }
    function action_addusertogroup()  { return FlysprayDoAdmin::action_addusertogroup(); }
    function action_add_to_list()     { return FlysprayDoAdmin::action_add_to_list(); }
    function action_editgroup()       { return FlysprayDoAdmin::action_editgroup(); }

    // **********************
    // End of action_ functions
    // **********************

	function _show($area = null)
	{
		global $page, $fs, $db, $proj;

        $page->pushTpl('pm.menu.tpl');

        $this->handle('area', $area);

		$page->setTitle($fs->prefs['page_title'] . L('pmtoolbox'));
		$page->pushTpl('pm.'.$area.'.tpl');
	}

	function _onsubmit()
	{
        global $fs, $db, $proj, $user;

        list($type, $msg, $url) = $this->handle('action', Post::val('action'));
        if ($type != NO_SUBMIT) {
        	$proj = new Project($proj->id);
        }

        return array($type, $msg, $url);
	}

	function is_accessible()
	{
		global $user, $proj;
		return $user->perms('manage_project') && $proj->id;
	}
}

?>