<?php

declare(strict_types=1);

// Minimal PSR-4 autoloader: App\ -> app/
// Note: NO Composer, NO Laravel. That is the entire point.
spl_autoload_register(function (string $class): void {
    if (! str_starts_with($class, 'App\\')) {
        return;
    }
    $path = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Domain\Lending\Event\BookBorrowed;
use App\Domain\Lending\Event\BookReturned;
use App\Domain\Lending\Exception\LendingViolation;
use App\Domain\Lending\Member;
use App\Domain\Lending\ValueObject\BookCopyId;
use App\Domain\Lending\ValueObject\MemberId;

$passed = 0;
$failed = 0;

function check(string $label, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  PASS  {$label}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  FAIL  {$label}\n        " . $e->getMessage() . "\n";
        $failed++;
    }
}

function assertTrue(bool $cond, string $msg = 'expected true'): void
{
    if (! $cond) {
        throw new RuntimeException($msg);
    }
}

function assertViolation(string $expectedCode, callable $fn): void
{
    try {
        $fn();
    } catch (LendingViolation $e) {
        if ($e->errorCode === $expectedCode) {
            return;
        }
        throw new RuntimeException("expected {$expectedCode}, got {$e->errorCode}");
    }
    throw new RuntimeException("expected {$expectedCode}, nothing thrown");
}

function assertThrows(string $exceptionClass, callable $fn): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $exceptionClass) {
            return;
        }
        throw new RuntimeException("expected {$exceptionClass}, got " . $e::class);
    }
    throw new RuntimeException("expected {$exceptionClass}, nothing thrown");
}

$now = new DateTimeImmutable('2026-07-23 16:00:00');

$freshMember = fn () => Member::register(MemberId::generate(), 'Test Member');
$copy = fn (string $s) => BookCopyId::fromString($s);

echo "\nDomain layer verification (plain PHP, zero framework)\n";
echo str_repeat('-', 55) . "\n";

check('borrowing sets a due date 14 days out', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $loan = $m->borrow($copy('COPY-1'), $now);
    assertTrue(
        $loan->dueAt->format('Y-m-d') === '2026-08-06',
        'due date was ' . $loan->dueAt->format('Y-m-d')
    );
});

check('member may hold at most 3 active loans', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $m->borrow($copy('C1'), $now);
    $m->borrow($copy('C2'), $now);
    $m->borrow($copy('C3'), $now);
    assertTrue(count($m->activeLoans()) === 3);
    assertViolation('loan_limit_reached', fn () => $m->borrow($copy('C4'), $now));
});

check('returning a copy frees allowance again', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $m->borrow($copy('C1'), $now);
    $m->borrow($copy('C2'), $now);
    $m->borrow($copy('C3'), $now);
    assertTrue($m->remainingAllowance() === 0);
    $m->returnCopy($copy('C2'), $now->modify('+2 days'));
    assertTrue($m->remainingAllowance() === 1);
    $m->borrow($copy('C4'), $now->modify('+2 days'));
    assertTrue(count($m->activeLoans()) === 3);
});

check('a member cannot borrow the same copy twice', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $m->borrow($copy('C1'), $now);
    assertViolation('copy_already_on_loan', fn () => $m->borrow($copy('C1'), $now));
});

check('overdue loans block all new borrowing', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $m->borrow($copy('C1'), $now);
    $later = $now->modify('+20 days');
    assertTrue($m->hasOverdueLoanAt($later));
    assertViolation('member_has_overdue_loans', fn () => $m->borrow($copy('C2'), $later));
});

check('a loan is not overdue on its exact due date', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $loan = $m->borrow($copy('C1'), $now);
    assertTrue(! $loan->isOverdueAt($loan->dueAt), 'should not be overdue at due moment');
    assertTrue($loan->isOverdueAt($loan->dueAt->modify('+1 second')));
});

check('cannot return a copy that is not on loan', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    assertViolation('copy_not_on_loan', fn () => $m->returnCopy($copy('GHOST'), $now));
});

check('cannot return the same copy twice', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $m->borrow($copy('C1'), $now);
    $m->returnCopy($copy('C1'), $now->modify('+1 day'));
    assertViolation('copy_not_on_loan', fn () => $m->returnCopy($copy('C1'), $now->modify('+2 days')));
});

check('a late return is flagged as late', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $m->borrow($copy('C1'), $now);
    $m->releaseEvents();
    $m->returnCopy($copy('C1'), $now->modify('+30 days'));
    $events = $m->releaseEvents();
    assertTrue(count($events) === 1);
    assertTrue($events[0] instanceof BookReturned);
    assertTrue($events[0]->wasLate === true, 'wasLate should be true');
});

check('events are recorded then released exactly once', function () use ($freshMember, $copy, $now) {
    $m = $freshMember();
    $m->borrow($copy('C1'), $now);
    $first = $m->releaseEvents();
    assertTrue(count($first) === 1, 'expected 1 event, got ' . count($first));
    assertTrue($first[0] instanceof BookBorrowed);
    assertTrue(count($m->releaseEvents()) === 0, 'events should not be replayed');
});

check('value objects compare by value, not identity', function () use ($copy) {
    assertTrue($copy('C1')->equals($copy('C1')));
    assertTrue(! $copy('C1')->equals($copy('C2')));
});

check('identifiers reject empty strings', function () {
    assertThrows(InvalidArgumentException::class, fn () => BookCopyId::fromString('   '));
});

check('reconstitute rebuilds state without firing events', function () use ($copy, $now) {
    $m = Member::register(MemberId::generate(), 'X');
    $m->borrow($copy('C1'), $now);
    $loans = $m->allLoans();

    $rebuilt = Member::reconstitute($m->id, $m->name, $loans);
    assertTrue(count($rebuilt->activeLoans()) === 1);
    assertTrue(count($rebuilt->releaseEvents()) === 0, 'hydration must not emit events');
});

echo str_repeat('-', 55) . "\n";
echo "{$passed} passed, {$failed} failed\n\n";

exit($failed === 0 ? 0 : 1);
