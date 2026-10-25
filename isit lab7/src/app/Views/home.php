<h1><?= e(config('name')) ?></h1>
<p>Всего задач: <strong><?= e($total) ?></strong></p>
<div class="stats">
<?php foreach (config('statuses') as $value => $label): ?>
<article><b><?= e($counts[$value]) ?></b><a href="/tasks?status=<?= e($value) ?>"><?= e($label) ?></a></article>
<?php endforeach; ?>
<article><b><?= e($projectCount) ?></b><a href="/projects">Проекты</a></article></div>
<section><h2>Работа с системой</h2><p>Откройте список, выберите статус или создайте новую запись.</p>
<a class="button" href="/tasks">Все задачи</a> <a href="/tasks/create">Создать задачу</a></section>
