<?php

declare(strict_types=1);

namespace App\Domain\Lending;

use App\Domain\Lending\Event\BookBorrowed;
use App\Domain\Lending\Event\BookReturned;
use App\Domain\Lending\Exception\LendingViolation;
use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\LoanId;
use App\Domain\Lending\ValueObject\MemberId;
use DateTimeImmutable;

final class Member
{
    public const MAX_ACTIVE_LOANS = 3;

    public const LOAN_PERIOD_DAYS = 14;

    /** @var list<object> */
    private array $recordedEvents = [];

    /** @param list<Loan> $loans */
    private function __construct(
        public readonly MemberId $id,
        public readonly string $name,
        private array $loans,
    ) {
    }

    public static function register(MemberId $id, string $name): self
    {
        return new self($id, $name, []);
    }

    /** @param list<Loan> $loans */
    public static function reconstitute(MemberId $id, string $name, array $loans): self
    {
        return new self($id, $name, $loans);
    }

    public function borrow(BookCopyId $copyId, DateTimeImmutable $now): Loan
    {
        if ($this->hasOverdueLoanAt($now)) {
            throw LendingViolation::memberHasOverdueLoans($this->id);
        }

        if (count($this->activeLoans()) >= self::MAX_ACTIVE_LOANS) {
            throw LendingViolation::loanLimitReached($this->id, self::MAX_ACTIVE_LOANS);
        }

        if ($this->activeLoanFor($copyId) !== null) {
            throw LendingViolation::copyAlreadyOnLoan($copyId);
        }

        $loan = Loan::start(
            id: LoanId::generate(),
            copyId: $copyId,
            borrowedAt: $now,
            dueAt: $now->modify('+' . self::LOAN_PERIOD_DAYS . ' days'),
        );

        $this->loans[] = $loan;

        $this->recordedEvents[] = new BookBorrowed(
            loanId: $loan->id,
            memberId: $this->id,
            copyId: $copyId,
            dueAt: $loan->dueAt,
        );

        return $loan;
    }

    public function returnCopy(BookCopyId $copyId, DateTimeImmutable $now): Loan
    {
        $loan = $this->activeLoanFor($copyId);

        if ($loan === null) {
            throw LendingViolation::copyNotOnLoan($copyId);
        }

        $wasLate = $loan->isOverdueAt($now);
        $loan->markReturned($now);

        $this->recordedEvents[] = new BookReturned(
            loanId: $loan->id,
            memberId: $this->id,
            copyId: $copyId,
            returnedAt: $now,
            wasLate: $wasLate,
        );

        return $loan;
    }

    public function hasOverdueLoanAt(DateTimeImmutable $now): bool
    {
        foreach ($this->loans as $loan) {
            if ($loan->isOverdueAt($now)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<Loan> */
    public function activeLoans(): array
    {
        return array_values(
            array_filter($this->loans, fn (Loan $loan) => $loan->isActive())
        );
    }

    /** @return list<Loan> */
    public function allLoans(): array
    {
        return $this->loans;
    }

    public function remainingAllowance(): int
    {
        return self::MAX_ACTIVE_LOANS - count($this->activeLoans());
    }

    private function activeLoanFor(BookCopyId $copyId): ?Loan
    {
        foreach ($this->loans as $loan) {
            if ($loan->isActive() && $loan->copyId->equals($copyId)) {
                return $loan;
            }
        }

        return null;
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
