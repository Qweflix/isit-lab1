<section><h1>Создать задачу</h1>
<?php if ($errors !== []): ?><div class="error-box" role="alert">Исправьте ошибки. Введённые значения сохранены.</div><?php endif; ?>
<form method="post" action="/tasks" novalidate>
<label for="title">Название (не менее 3 символов)</label>
<input id="title" name="title" value="<?= e($old['title']) ?>" required>
<?php if (isset($errors['title'])): ?><p class="error"><?= e($errors['title']) ?></p><?php endif; ?>
<label for="project_id">Проект</label><select id="project_id" name="project_id" required>
<option value="">Выберите проект</option>
<?php foreach ($projects as $project): ?>
<option value="<?= e($project['id']) ?>" <?= (string) $project['id'] === $old['project_id'] ? 'selected' : '' ?>><?= e($project['title']) ?></option>
<?php endforeach; ?></select>
<?php if (isset($errors['project_id'])): ?><p class="error"><?= e($errors['project_id']) ?></p><?php endif; ?>
<?php foreach (['status' => ['Статус', 'statuses'], 'priority' => ['Приоритет', 'priorities']] as $field => [$label, $setting]): ?>
<label for="<?= e($field) ?>"><?= e($label) ?></label><select id="<?= e($field) ?>" name="<?= e($field) ?>" required>
<?php foreach (config($setting) as $value => $caption): ?>
<option value="<?= e($value) ?>" <?= $old[$field] === $value ? 'selected' : '' ?>><?= e($caption) ?></option>
<?php endforeach; ?></select>
<?php if (isset($errors[$field])): ?><p class="error"><?= e($errors[$field]) ?></p><?php endif; ?>
<?php endforeach; ?>
<label for="due_date">Срок выполнения</label>
<input id="due_date" name="due_date" type="date" value="<?= e($old['due_date']) ?>" required>
<?php if (isset($errors['due_date'])): ?><p class="error"><?= e($errors['due_date']) ?></p><?php endif; ?>
<button type="submit">Сохранить</button> <a href="/tasks">Отмена</a></form></section>
