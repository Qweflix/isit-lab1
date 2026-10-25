<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\ProjectRepository;
use App\Models\TaskRepository;

final class ProjectController
{
    public function index(): void
    {
        view('projects/index', ['title' => 'Проекты', 'projects' => (new ProjectRepository())->all()]);
    }

    public function show(string $id): void
    {
        $project = (preg_match('/^[0-9]+$/D', $id) === 1) ? (new ProjectRepository())->find((int) $id) : null;
        if ($project === null) {
            notFound();
        }
        view('projects/show', ['title' => $project['title'], 'project' => $project,
            'tasks' => (new TaskRepository())->byProject($project['id'])]);
    }
}
