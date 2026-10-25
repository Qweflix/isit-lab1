<section><h1><?= e($project['title']) ?></h1><p><?= e($project['description']) ?></p>
<h2>Задачи</h2>
<?php if ($tasks === []): ?><p>Записи не найдены.</p><?php else: ?><ul>
<?php foreach ($tasks as $task): ?><li><a href="/tasks/<?= e($task['id']) ?>"><?= e($task['title']) ?></a> — <?= e(config('statuses')[$task['status']]) ?></li><?php endforeach; ?>
</ul><?php endif; ?><a href="/projects">Все проекты</a></section>
