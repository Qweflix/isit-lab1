<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\TaskRepository;
use App\Models\ProjectRepository;

final class TaskController
{
    private TaskRepository $tasks;
    private ProjectRepository $projects;

    public function __construct()
    {
        $this->tasks = new TaskRepository();
        $this->projects = new ProjectRepository();
    }

    public function index(): void
    {
        $status = input($_GET, 'status');
        $tasks = $status === '' ? $this->tasks->all() : $this->tasks->byStatus($status);
        view('tasks/index', ['title' => 'Задачи', 'tasks' => $tasks, 'status' => $status,
            'projects' => array_column($this->projects->all(), null, 'id')]);
    }

    public function show(string $id): void
    {
        $task = (preg_match('/^[0-9]+$/D', $id) === 1) ? $this->tasks->find((int) $id) : null;
        if ($task === null) {
            notFound();
        }
        view('tasks/show', ['title' => $task['title'], 'task' => $task,
            'project' => $this->projects->find($task['project_id'])]);
    }

    public function create(): void
    {
        $this->form(['title' => '', 'project_id' => '', 'status' => 'new',
            'priority' => 'medium', 'due_date' => ''], []);
    }

    private function form(array $old, array $errors): void
    {
        view('tasks/create', ['title' => 'Создать задачу', 'old' => $old,
            'errors' => $errors, 'projects' => $this->projects->all()]);
    }

    public function store(): void
    {
        $old = [];
        foreach (['title', 'project_id', 'status', 'priority', 'due_date'] as $field) {
            $old[$field] = input($_POST, $field);
        }
        $errors = [];
        if (preg_match_all('/./us', $old['title']) < 3) {
            $errors['title'] = 'Введите название длиной не менее 3 символов.';
        }
        if (preg_match('/^[0-9]+$/D', $old['project_id']) !== 1 || $this->projects->find((int) $old['project_id']) === null) {
            $errors['project_id'] = 'Выберите существующий проект.';
        }
        if (!array_key_exists($old['status'], config('statuses'))) {
            $errors['status'] = 'Выберите допустимый статус.';
        }
        if (!array_key_exists($old['priority'], config('priorities'))) {
            $errors['priority'] = 'Выберите допустимый приоритет.';
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $old['due_date']);
        if ($date === false || $date->format('Y-m-d') !== $old['due_date']) {
            $errors['due_date'] = 'Укажите корректную дату.';
        }
        if ($errors !== []) {
            http_response_code(422);
            $this->form($old, $errors);
            return;
        }
        $old['project_id'] = (int) $old['project_id'];
        $this->tasks->add($old);
        redirect('/tasks');
    }
}
