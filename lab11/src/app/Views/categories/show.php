<section><h1><?= e($category['title']) ?></h1><p><?= e($category['description']) ?></p>
<h2>Заявки</h2>
<?php if ($tickets === []): ?><p>Записи не найдены.</p><?php else: ?><ul>
<?php foreach ($tickets as $ticket): ?><li><a href="/tickets/<?= e($ticket['id']) ?>"><?= e($ticket['title']) ?></a> — <?= e(config('statuses')[$ticket['status']]) ?></li><?php endforeach; ?>
</ul><?php endif; ?><a href="/categories">Все категории</a></section>
