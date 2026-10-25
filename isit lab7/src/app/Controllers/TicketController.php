<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\TicketRepository;
use App\Models\CategoryRepository;

final class TicketController
{
    private TicketRepository $tickets;
    private CategoryRepository $categories;

    public function __construct()
    {
        $this->tickets = new TicketRepository();
        $this->categories = new CategoryRepository();
    }

    public function index(): void
    {
        $status = input($_GET, 'status');
        $tickets = $status === '' ? $this->tickets->all() : $this->tickets->byStatus($status);
        view('tickets/index', ['title' => 'Заявки', 'tickets' => $tickets, 'status' => $status,
            'categories' => array_column($this->categories->all(), null, 'id')]);
    }

    public function show(string $id): void
    {
        $ticket = (preg_match('/^[0-9]+$/D', $id) === 1) ? $this->tickets->find((int) $id) : null;
        if ($ticket === null) {
            notFound();
        }
        view('tickets/show', ['title' => $ticket['title'], 'ticket' => $ticket,
            'category' => $this->categories->find($ticket['category_id'])]);
    }

    public function create(): void
    {
        $this->form(['title' => '', 'category_id' => '', 'status' => 'new',
            'priority' => 'medium', 'description' => ''], []);
    }

    private function form(array $old, array $errors): void
    {
        view('tickets/create', ['title' => 'Создать заявку', 'old' => $old,
            'errors' => $errors, 'categories' => $this->categories->all()]);
    }

    public function store(): void
    {
        $old = [];
        foreach (['title', 'category_id', 'status', 'priority', 'description'] as $field) {
            $old[$field] = input($_POST, $field);
        }
        $errors = [];
        if (preg_match_all('/./us', $old['title']) < 3) {
            $errors['title'] = 'Введите название длиной не менее 3 символов.';
        }
        if (preg_match('/^[0-9]+$/D', $old['category_id']) !== 1 || $this->categories->find((int) $old['category_id']) === null) {
            $errors['category_id'] = 'Выберите существующую категорию.';
        }
        if (!array_key_exists($old['status'], config('statuses'))) {
            $errors['status'] = 'Выберите допустимый статус.';
        }
        if (!array_key_exists($old['priority'], config('priorities'))) {
            $errors['priority'] = 'Выберите допустимый приоритет.';
        }
        if ($old['description'] === '') {
            $errors['description'] = 'Опишите проблему.';
        }
        if ($errors !== []) {
            http_response_code(422);
            $this->form($old, $errors);
            return;
        }
        $old['category_id'] = (int) $old['category_id'];
        $this->tickets->add($old);
        redirect('/tickets');
    }
}
