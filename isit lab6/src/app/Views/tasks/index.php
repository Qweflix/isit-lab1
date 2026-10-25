<h1>Задачи</h1><a class="button" href="/tasks/create">Создать задачу</a>
<form method="get" action="/tasks"><label for="status">Фильтр по статусу</label>
<select id="status" name="status"><option value="">Все статусы</option>
<?php foreach (config('statuses') as $value => $label): ?>
<option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?></select><button type="submit">Применить</button></form>
<p>Найдено записей: <?= e(count($tasks)) ?></p>
<?php if ($tasks === []): ?><p>Записи не найдены.</p><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Название</th><th>Проект</th><th>Статус</th><th>Приоритет</th></tr></thead><tbody>
<?php foreach ($tasks as $task): ?>
<tr data-status="<?= e($task['status']) ?>"><td><?= e($task['id']) ?></td>
<td><a href="/tasks/<?= e($task['id']) ?>"><?= e($task['title']) ?></a></td>
<td><a href="/projects/<?= e($task['project_id']) ?>"><?= e($projects[$task['project_id']]['title']) ?></a></td>
<td><span class="badge"><?= e(config('statuses')[$task['status']]) ?></span></td>
<td><?= e(config('priorities')[$task['priority']]) ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?>
