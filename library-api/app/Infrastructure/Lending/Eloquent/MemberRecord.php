<?php

declare(strict_types=1);

namespace App\Infrastructure\Lending\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MemberRecord extends Model
{
    protected $table = 'members';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    public function loans(): HasMany
    {
        return $this->hasMany(LoanRecord::class, 'member_id');
    }
}
