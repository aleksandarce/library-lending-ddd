<?php

declare(strict_types=1);

namespace App\Domain\Lending;

use App\Domain\Lending\Exception\LendingViolation;
use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\LoanId;
use DateTimeImmutable;

final class Loan
{
    private function __construct(
        public readonly LoanId $id,
        public readonly BookCopyId $copyId,
        public readonly DateTimeImmutable $borrowedAt,
        public readonly DateTimeImmutable $dueAt,
        private ?DateTimeImmutable $returnedAt,
    ) {
    }

    public static function start(
        LoanId $id,
        BookCopyId $copyId,
        DateTimeImmutable $borrowedAt,
        DateTimeImmutable $dueAt,
    ): self {
        return new self($id, $copyId, $borrowedAt, $dueAt, null);
    }

    public static function reconstitute(
        LoanId $id,
        BookCopyId $copyId,
        DateTimeImmutable $borrowedAt,
        DateTimeImmutable $dueAt,
        ?DateTimeImmutable $returnedAt,
    ): self {
        return new self($id, $copyId, $borrowedAt, $dueAt, $returnedAt);
    }

    public function isActive(): bool
    {
        return $this->returnedAt === null;
    }

    public function isOverdueAt(DateTimeImmutable $now): bool
    {
        return $this->isActive() && $now > $this->dueAt;
    }

    public function returnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function markReturned(DateTimeImmutable $now): void
    {
        if (! $this->isActive()) {
            throw LendingViolation::loanAlreadyReturned($this->id);
        }

        $this->returnedAt = $now;
    }
}
