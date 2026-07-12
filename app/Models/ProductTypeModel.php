<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ProductTypeModel — replaces ItemTypeModel.
 * Uses table type_of_product with type_id PK and type column.
 */
class ProductTypeModel extends Model
{
    protected $table                 = 'type_of_product';
    protected $primaryKey            = 'type_id';
    protected $returnType            = 'array';
    protected $allowedFields         = ['type', 'user_office_id'];
    protected bool $allowEmptyInserts = false;

    public function orderedList(int $userOfficeId = 0): array
    {
        $builder = $this->orderBy('type', 'ASC');
        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }
        return $builder->findAll();
    }
}
