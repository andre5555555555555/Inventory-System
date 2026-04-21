<?php

namespace App\Models;

use CodeIgniter\Model;

class AdjustmentReasonModel extends Model
{
    protected $table                 = 'adjustment_reason';
    protected $primaryKey            = 'reason_id';
    protected $returnType            = 'array';
    protected $allowedFields         = ['reason_name'];
    protected bool $allowEmptyInserts = false;

    public function orderedList(): array
    {
        return $this->orderBy('reason_name', 'ASC')->findAll();
    }
}
