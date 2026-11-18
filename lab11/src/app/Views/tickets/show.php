<section><h1>Заявка №<?= e($ticket['id']) ?>: <?= e($ticket['title']) ?></h1>
<dl><dt>Категория</dt><dd><a href="/categories/<?= e($category['id']) ?>"><?= e($category['title']) ?></a></dd>
<dt>Статус</dt><dd><?= e(config('statuses')[$ticket['status']]) ?></dd>
<dt>Приоритет</dt><dd><?= e(config('priorities')[$ticket['priority']]) ?></dd>
<dt>Описание</dt><dd><?= e($ticket['description']) ?></dd></dl>
<a href="/tickets">Вернуться к списку</a></section>
