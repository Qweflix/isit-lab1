<h1><?= e(config('name')) ?></h1>
<p>Всего заявок: <strong><?= e($total) ?></strong></p>
<div class="stats">
<?php foreach (config('statuses') as $value => $label): ?>
<article><b><?= e($counts[$value]) ?></b><a href="/tickets?status=<?= e($value) ?>"><?= e($label) ?></a></article>
<?php endforeach; ?>
<article><b><?= e($categoryCount) ?></b><a href="/categories">Категории</a></article></div>
<section><h2>Работа с системой</h2><p>Откройте список, выберите статус или создайте новую запись.</p>
<a class="button" href="/tickets">Все заявки</a> <a href="/tickets/create">Создать заявку</a></section>
