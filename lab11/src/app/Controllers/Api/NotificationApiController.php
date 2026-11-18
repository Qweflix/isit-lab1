<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Database;

final class NotificationApiController
{
    public function index(): void
    {
        $user = Auth::user();
        $query = Database::connection()->prepare('SELECT id, job_id, ticket_id, body, created_at FROM notifications WHERE staff_id = ? ORDER BY id DESC');
        $query->execute([$user['id']]);
        json_response($query->fetchAll());
    }
}
