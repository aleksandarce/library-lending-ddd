<?php

namespace App\Providers;

use App\Domain\Lending\Clock;
use App\Domain\Lending\MemberRepository;
use App\Infrastructure\Lending\EloquentMemberRepository;
use App\Infrastructure\Lending\SystemClock;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MemberRepository::class, EloquentMemberRepository::class);
        $this->app->bind(Clock::class, SystemClock::class);
    }

    public function boot(): void
    {
        //
    }
}
