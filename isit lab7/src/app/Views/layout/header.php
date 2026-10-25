<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? '') ?> — <?= e(config('name')) ?></title>
<link rel="stylesheet" href="/css/style.css"></head><body>
<header><strong><?= e(config('name')) ?></strong><nav aria-label="Основная навигация">
<a href="/">Главная</a><a href="/tasks">Задачи</a><a href="/projects">Проекты</a>
<a href="/tasks/create">Создать задачу</a></nav></header><main>
