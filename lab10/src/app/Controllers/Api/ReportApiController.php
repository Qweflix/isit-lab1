<?php
namespace App\Controllers\Api;
use App\Models\ReportRepository;
final class ReportApiController
{
    public function summary(): void { json_response((new ReportRepository())->summary()); }
}
