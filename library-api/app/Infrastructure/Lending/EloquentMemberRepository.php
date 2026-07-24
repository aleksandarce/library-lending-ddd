<?php

declare(strict_types=1);

namespace App\Infrastructure\Lending;

use App\Domain\Lending\Loan;
use App\Domain\Lending\Member;
use App\Domain\Lending\MemberRepository;
use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\LoanId;
use App\Domain\Lending\ValueObject\MemberId;
use App\Infrastructure\Lending\Eloquent\LoanRecord;
use App\Infrastructure\Lending\Eloquent\MemberRecord;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class EloquentMemberRepository implements MemberRepository
{
    public function get(MemberId $id): Member
    {
        $record = MemberRecord::with('loans')->find($id->value);

        if ($record === null) {
            throw (new ModelNotFoundException())
                ->setModel(MemberRecord::class, [$id->value]);
        }

        $loans = $record->loans
            ->map(fn (LoanRecord $row) => Loan::reconstitute(
                id: LoanId::fromString($row->id),
                copyId: BookCopyId::fromString($row->copy_id),
                borrowedAt: new DateTimeImmutable($row->borrowed_at),
                dueAt: new DateTimeImmutable($row->due_at),
                returnedAt: $row->returned_at === null
                    ? null
                    : new DateTimeImmutable($row->returned_at),
            ))
            ->all();

        return Member::reconstitute($id, $record->name, $loans);
    }

    public function save(Member $member): void
    {
        DB::transaction(function () use ($member) {
            MemberRecord::updateOrCreate(
                ['id' => $member->id->value],
                ['name' => $member->name],
            );

            foreach ($member->allLoans() as $loan) {
                LoanRecord::updateOrCreate(
                    ['id' => $loan->id->value],
                    [
                        'member_id' => $member->id->value,
                        'copy_id' => $loan->copyId->value,
                        'borrowed_at' => $loan->borrowedAt->format('Y-m-d H:i:s'),
                        'due_at' => $loan->dueAt->format('Y-m-d H:i:s'),
                        'returned_at' => $loan->returnedAt()?->format('Y-m-d H:i:s'),
                    ],
                );
            }
        });
    }

    public function isCopyOnLoan(BookCopyId $copyId): bool
    {
        return LoanRecord::query()
            ->where('copy_id', $copyId->value)
            ->whereNull('returned_at')
            ->exists();
    }
}
