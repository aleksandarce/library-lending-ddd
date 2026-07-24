<?php

declare(strict_types=1);

namespace App\Application\Lending;

use App\Domain\Lending\Clock;
use App\Domain\Lending\Exception\LendingViolation;
use App\Domain\Lending\MemberRepository;
use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\MemberId;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class BorrowBookCopy
{
    public function __construct(
        private MemberRepository $members,
        private Dispatcher $events,
        private Clock $clock,
    ) {
    }

    public function __invoke(string $memberId, string $copyId): void
    {
        $copy = BookCopyId::fromString($copyId);
        $member = $this->members->get(MemberId::fromString($memberId));

        // Friendly pre-check for the cross-aggregate rule. NOT the guarantee
        // (see the unique index in the migration) — this exists so the user
        // gets "that copy is already out" instead of a raw SQL error.
        if ($this->members->isCopyOnLoan($copy)) {
            throw LendingViolation::copyAlreadyOnLoan($copy);
        }

        $member->borrow($copy, $this->clock->now());

        $this->members->save($member);

        // Dispatch only AFTER a successful save. Announcing before would
        // broadcast things that could still roll back.
        foreach ($member->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }
    }
}
