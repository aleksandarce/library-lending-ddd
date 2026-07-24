<?php

declare(strict_types=1);

namespace App\Domain\Lending\Event;

use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\LoanId;
use App\Domain\Lending\ValueObject\MemberId;
use DateTimeImmutable;

final class BookReturned
{
    public function __construct(
        public readonly LoanId $loanId,
        public readonly MemberId $memberId,
        public readonly BookCopyId $copyId,
        public readonly DateTimeImmutable $returnedAt,
        public readonly bool $wasLate,
    ) {
    }
}
