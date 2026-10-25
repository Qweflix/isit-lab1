<h1>Заявки</h1><a class="button" href="/tickets/create">Создать заявку</a>
<form method="get" action="/tickets"><label for="status">Фильтр по статусу</label>
<select id="status" name="status"><option value="">Все статусы</option>
<?php foreach (config('statuses') as $value => $label): ?>
<option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?></select><button type="submit">Применить</button></form>
<p>Найдено записей: <?= e(count($tickets)) ?></p>
<?php if ($tickets === []): ?><p>Записи не найдены.</p><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Название</th><th>Категория</th><th>Статус</th><th>Приоритет</th></tr></thead><tbody>
<?php foreach ($tickets as $ticket): ?>
<tr data-status="<?= e($ticket['status']) ?>"><td><?= e($ticket['id']) ?></td>
<td><a href="/tickets/<?= e($ticket['id']) ?>"><?= e($ticket['title']) ?></a></td>
<td><a href="/categories/<?= e($ticket['category_id']) ?>"><?= e($categories[$ticket['category_id']]['title']) ?></a></td>
<td><span class="badge"><?= e(config('statuses')[$ticket['status']]) ?></span></td>
<td><?= e(config('priorities')[$ticket['priority']]) ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?>
