<?php

declare(strict_types=1);

namespace App\Application\Lending;

use App\Domain\Lending\Clock;
use App\Domain\Lending\MemberRepository;
use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\MemberId;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class ReturnBookCopy
{
    public function __construct(
        private MemberRepository $members,
        private Dispatcher $events,
        private Clock $clock,
    ) {
    }

    public function __invoke(string $memberId, string $copyId): void
    {
        $member = $this->members->get(MemberId::fromString($memberId));

        $member->returnCopy(BookCopyId::fromString($copyId), $this->clock->now());

        $this->members->save($member);

        foreach ($member->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }
    }
}
