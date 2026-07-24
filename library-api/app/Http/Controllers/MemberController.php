<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Lending\Member;
use App\Infrastructure\Lending\Eloquent\LoanRecord;
use App\Infrastructure\Lending\Eloquent\MemberRecord;
use Illuminate\Http\JsonResponse;

final class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        $members = MemberRecord::query()
            ->orderBy('name')
            ->get()
            ->map(fn (MemberRecord $m) => [
                'id' => $m->id,
                'name' => $m->name,
            ]);

        return response()->json(['members' => $members]);
    }

    public function show(string $memberId): JsonResponse
    {
        $member = MemberRecord::query()->findOrFail($memberId);

        $loans = LoanRecord::query()
            ->where('member_id', $memberId)
            ->whereNull('returned_at')
            ->orderBy('due_at')
            ->get();

        $now = now();

        return response()->json([
            'id' => $member->id,
            'name' => $member->name,
            'max_loans' => Member::MAX_ACTIVE_LOANS,
            'allowance_left' => Member::MAX_ACTIVE_LOANS - $loans->count(),
            'has_overdue' => $loans->contains(
                fn (LoanRecord $l) => $now->gt($l->due_at)
            ),
            'loans' => $loans->map(fn (LoanRecord $l) => [
                'id' => $l->id,
                'copy_id' => $l->copy_id,
                'borrowed_at' => $l->borrowed_at,
                'due_at' => $l->due_at,
                'is_overdue' => $now->gt($l->due_at),
            ]),
        ]);
    }
}
