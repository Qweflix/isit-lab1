<h1>Проекты</h1>
<?php foreach ($projects as $project): ?><article>
<h2><a href="/projects/<?= e($project['id']) ?>"><?= e($project['title']) ?></a></h2>
<p><?= e($project['description']) ?></p></article><?php endforeach; ?>
