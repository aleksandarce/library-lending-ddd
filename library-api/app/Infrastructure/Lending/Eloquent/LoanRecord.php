<?php

declare(strict_types=1);

namespace App\Infrastructure\Lending\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class LoanRecord extends Model
{
    protected $table = 'loans';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];
}
