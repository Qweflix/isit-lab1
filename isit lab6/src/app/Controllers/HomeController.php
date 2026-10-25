<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\TaskRepository;
use App\Models\ProjectRepository;

final class HomeController
{
    public function index(): void
    {
        $tasks = (new TaskRepository())->all();
        $counts = array_fill_keys(array_keys(config('statuses')), 0);
        foreach ($tasks as $task) {
            $counts[$task['status']]++;
        }
        view('home', ['title' => 'Главная', 'counts' => $counts,
            'total' => count($tasks), 'projectCount' => count((new ProjectRepository())->all())]);
    }
}
