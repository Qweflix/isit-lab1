<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\CategoryRepository;
use App\Models\TicketRepository;

final class CategoryController
{
    public function index(): void
    {
        view('categories/index', ['title' => 'Категории', 'categories' => (new CategoryRepository())->all()]);
    }

    public function show(string $id): void
    {
        $category = (preg_match('/^[0-9]+$/D', $id) === 1) ? (new CategoryRepository())->find((int) $id) : null;
        if ($category === null) {
            notFound();
        }
        view('categories/show', ['title' => $category['title'], 'category' => $category,
            'tickets' => (new TicketRepository())->byCategory($category['id'])]);
    }
}
