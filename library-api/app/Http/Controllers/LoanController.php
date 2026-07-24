<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Lending\BorrowBookCopy;
use App\Application\Lending\ReturnBookCopy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoanController extends Controller
{
    public function store(
        Request $request,
        string $memberId,
        BorrowBookCopy $borrow,
    ): JsonResponse {
        $validated = $request->validate([
            'copy_id' => ['required', 'string', 'max:64'],
        ]);

        $borrow($memberId, $validated['copy_id']);

        return response()->json(['status' => 'ok'], 201);
    }

    public function destroy(
        string $memberId,
        string $copyId,
        ReturnBookCopy $return,
    ): JsonResponse {
        $return($memberId, $copyId);

        return response()->json(['status' => 'ok']);
    }
}
