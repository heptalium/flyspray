<?php foreach($data as $milestone): ?>

<script type="text/javascript">
allTasks = [<?php foreach($milestone['open_tasks'] as $task): echo $task['task_id'] . ','; endforeach; ?>];

function hideAll()
{
    for (i = 0; i < allTasks.length; i++) {
        if (!allTasks[i]) continue;
        hidestuff('dd'+ allTasks[i]);
        hidestuff('hide'+ allTasks[i]);
        showstuff('expand'+ allTasks[i], 'inline');
    }
}

function showAll()
{
    for (i = 0; i < allTasks.length; i++) {
        if (!allTasks[i]) continue;
        showstuff('dd'+ allTasks[i]);
        hidestuff('expand'+ allTasks[i]);
        showstuff('hide'+ allTasks[i], 'inline');
    }
}
</script>

<div class="box roadmap">
<h3>{L('roadmapfor')} {$milestone['name']}
    <?php if (count($milestone['open_tasks'])): ?>
    <small class="DoNotPrint">
      <a href="javascript:showAll()">{L('expandall')}</a> |
      <a href="javascript:hideAll()">{L('collapseall')}</a>
    </small>
    <?php endif; ?>
</h3>

<div class="taskpercent roadmapp"><div style="width:{round($milestone['percent_complete']/10)*10}%"> </div></div>

<p>{$milestone['percent_complete']}{L('of')}
   <a href="{$this->url(array('index', 'proj' . $proj->id), array('field' . $proj->prefs['roadmap_field'] => array($milestone['id']), 'status' => array('open', 'closed')))}">
     {count($milestone['all_tasks'])} {L('tasks')}
   </a> {L('completed')}
   <?php if(count($milestone['open_tasks'])): ?>
   <a href="{$this->url(array('index', 'proj' . $proj->id), array('field' . $proj->prefs['roadmap_field'] => array($milestone['id']), 'status[]' => 'open'))}">{count($milestone['open_tasks'])} {L('opentasks')}:</a>
   <?php endif; ?>
</p>

<?php if(count($milestone['open_tasks'])): ?>
<dl class="roadmap">
    <?php foreach($milestone['open_tasks'] as $task): ?>
      <dt class="task {$fs->GetColorCssClass($task)}">
        {!tpl_tasklink($task['task_id'])}
        <small class="DoNotPrint">
          <a id="expand{$task['task_id']}" href="javascript:showstuff('dd{$task['task_id']}');hidestuff('expand{$task['task_id']}');showstuff('hide{$task['task_id']}', 'inline')">{L('expand')}</a>
          <a class="hide" id="hide{$task['task_id']}" href="javascript:hidestuff('dd{$task['task_id']}');hidestuff('hide{$task['task_id']}');showstuff('expand{$task['task_id']}', 'inline')">{L('collapse')}</a>
        </small>
      </dt>
      <dd id="dd{$task['task_id']}" >
        {!$this->text->render($task['detailed_desc'], false, 'rota', $task['task_id'], $task['content'])}
        <br style="position:absolute;" />
      </dd>
    <?php endforeach; ?>
</dl>

<?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (!count($data)): ?>
<div class="box roadmap">
<p><em>{L('noroadmap')}</em></p>
</div>
<?php else: ?>
<p><a href="{$this->url(array('roadmap', 'proj' . $proj->id), array('txt' => 'true'))}"><img src="{$this->get_image('mime/text')}" alt="" /> {L('textversion')}</a></p>
<?php endif; ?>
