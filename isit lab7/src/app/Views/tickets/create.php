<section><h1>Создать заявку</h1>
<?php if ($errors !== []): ?><div class="error-box" role="alert">Исправьте ошибки. Введённые значения сохранены.</div><?php endif; ?>
<form method="post" action="/tickets" novalidate>
<label for="title">Название (не менее 3 символов)</label>
<input id="title" name="title" value="<?= e($old['title']) ?>" required>
<?php if (isset($errors['title'])): ?><p class="error"><?= e($errors['title']) ?></p><?php endif; ?>
<label for="category_id">Категория</label><select id="category_id" name="category_id" required>
<option value="">Выберите категорию</option>
<?php foreach ($categories as $category): ?>
<option value="<?= e($category['id']) ?>" <?= (string) $category['id'] === $old['category_id'] ? 'selected' : '' ?>><?= e($category['title']) ?></option>
<?php endforeach; ?></select>
<?php if (isset($errors['category_id'])): ?><p class="error"><?= e($errors['category_id']) ?></p><?php endif; ?>
<?php foreach (['status' => ['Статус', 'statuses'], 'priority' => ['Приоритет', 'priorities']] as $field => [$label, $setting]): ?>
<label for="<?= e($field) ?>"><?= e($label) ?></label><select id="<?= e($field) ?>" name="<?= e($field) ?>" required>
<?php foreach (config($setting) as $value => $caption): ?>
<option value="<?= e($value) ?>" <?= $old[$field] === $value ? 'selected' : '' ?>><?= e($caption) ?></option>
<?php endforeach; ?></select>
<?php if (isset($errors[$field])): ?><p class="error"><?= e($errors[$field]) ?></p><?php endif; ?>
<?php endforeach; ?>
<label for="description">Описание</label>
<textarea id="description" name="description" required><?= e($old['description']) ?></textarea>
<?php if (isset($errors['description'])): ?><p class="error"><?= e($errors['description']) ?></p><?php endif; ?>
<button type="submit">Сохранить</button> <a href="/tickets">Отмена</a></form></section>
