<?php

declare(strict_types=1);

namespace App\Domain\Lending\Exception;

use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\LoanId;
use App\Domain\Lending\ValueObject\MemberId;
use DomainException;

final class LendingViolation extends DomainException
{
    private function __construct(
        string $message,
        public readonly string $errorCode,
    ) {
        parent::__construct($message);
    }

    public static function loanLimitReached(MemberId $memberId, int $limit): self
    {
        return new self(
            "Member {$memberId} already holds the maximum of {$limit} loans.",
            'loan_limit_reached',
        );
    }

    public static function memberHasOverdueLoans(MemberId $memberId): self
    {
        return new self(
            "Member {$memberId} has overdue books and cannot borrow.",
            'member_has_overdue_loans',
        );
    }

    public static function copyAlreadyOnLoan(BookCopyId $copyId): self
    {
        return new self(
            "Copy {$copyId} is already on loan.",
            'copy_already_on_loan',
        );
    }

    public static function copyNotOnLoan(BookCopyId $copyId): self
    {
        return new self(
            "Copy {$copyId} is not on loan to this member.",
            'copy_not_on_loan',
        );
    }

    public static function loanAlreadyReturned(LoanId $loanId): self
    {
        return new self(
            "Loan {$loanId} was already returned.",
            'loan_already_returned',
        );
    }
}
