<?php
namespace App\Controllers\Api;
final class CategoryApiController extends ResourceApiController
{
    protected string $resource = 'categories';

    public function tickets(string $id): void
    {
        if (!(new \App\Models\PgRepository('categories'))->find(record_id($id))) json_error('Запись не найдена',404);
        json_response((new \App\Models\PgRepository('tickets'))->all(['category_id'=>record_id($id)]));
    }
}
