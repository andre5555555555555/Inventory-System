<?php

namespace App\Models;

use CodeIgniter\Model;

class EntityModel extends Model
{
    protected $table                 = 'entity';
    protected $primaryKey            = 'entity_id';
    protected $returnType            = 'array';
    protected $allowedFields         = ['entity_name', 'fund_cluster', 'user_office_id'];
    protected bool $allowEmptyInserts = false;

    public function orderedList(int $userOfficeId = 0): array
    {
        $builder = $this->orderBy('entity_name', 'ASC');
        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }
        return $builder->findAll();
    }
}
