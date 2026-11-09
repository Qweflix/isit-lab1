<?php
namespace App\Models;
class CategoryRepository extends PgRepository
{
    public function __construct() { parent::__construct('categories'); }
}
