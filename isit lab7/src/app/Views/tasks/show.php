<section><h1>Задача №<?= e($task['id']) ?>: <?= e($task['title']) ?></h1>
<dl><dt>Проект</dt><dd><a href="/projects/<?= e($project['id']) ?>"><?= e($project['title']) ?></a></dd>
<dt>Статус</dt><dd><?= e(config('statuses')[$task['status']]) ?></dd>
<dt>Приоритет</dt><dd><?= e(config('priorities')[$task['priority']]) ?></dd>
<dt>Срок выполнения</dt><dd><?= e($task['due_date']) ?></dd></dl>
<a href="/tasks">Вернуться к списку</a></section>
