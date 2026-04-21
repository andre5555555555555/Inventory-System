<?php

namespace App\Models;

use CodeIgniter\Model;

class UnitModel extends Model
{
    protected $table                 = 'unit';
    protected $primaryKey            = 'unit_id';
    protected $returnType            = 'array';
    protected $allowedFields         = ['unit', 'user_office_id'];
    protected bool $allowEmptyInserts = false;

    public function orderedList(int $userOfficeId = 0): array
    {
        $builder = $this->orderBy('unit', 'ASC');
        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }
        return $builder->findAll();
    }
}
