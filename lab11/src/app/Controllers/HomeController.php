<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\TicketRepository;
use App\Models\CategoryRepository;

final class HomeController
{
    public function index(): void
    {
        $tickets = (new TicketRepository())->all();
        $counts = array_fill_keys(array_keys(config('statuses')), 0);
        foreach ($tickets as $ticket) {
            $counts[$ticket['status']]++;
        }
        view('home', ['title' => 'Главная', 'counts' => $counts,
            'total' => count($tickets), 'categoryCount' => count((new CategoryRepository())->all())]);
    }
}
