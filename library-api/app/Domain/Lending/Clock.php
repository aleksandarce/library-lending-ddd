<?php

declare(strict_types=1);

namespace App\Domain\Lending;

use DateTimeImmutable;

interface Clock{
 public function now(): DateTimeImmutable;
}