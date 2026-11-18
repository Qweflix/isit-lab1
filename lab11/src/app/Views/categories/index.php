<h1>Категории</h1>
<?php foreach ($categories as $category): ?><article>
<h2><a href="/categories/<?= e($category['id']) ?>"><?= e($category['title']) ?></a></h2>
<p><?= e($category['description']) ?></p></article><?php endforeach; ?>
