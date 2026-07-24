<?php

declare(strict_types=1);

namespace App\Domain\Lending;

use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\MemberId;

interface MemberRepository
{
    public function get(MemberId $id): Member;

    public function save(Member $member): void;

    /**
     * Cross-aggregate check: is this copy out to ANY member?
     *
     * Compromise, deliberately. This question spans aggregates, so it
     * doesn't really belong on a Member repository — in a larger system
     * it'd be its own read port. It's here because the alternative is
     * another interface + another binding for one method, and the real
     * guarantee is the DB unique index anyway (see the migration).
     *
     * This pre-check exists to produce a good error message, not to be
     * correct under concurrency. It cannot be: another request can insert
     * between this call and the save.
     */
    public function isCopyOnLoan(BookCopyId $copyId): bool;
}
