<?php
namespace App\Controllers;
final class HomeController
{
    public function index(): void { redirect('/client/'); }
}
