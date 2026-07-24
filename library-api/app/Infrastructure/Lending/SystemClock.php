<?php

declare(strict_types=1);

namespace App\Infrastructure\Lending;

use App\Domain\Lending\Clock;
use DateTimeImmutable;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
