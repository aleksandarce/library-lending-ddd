<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('member_id');
            $table->string('copy_id');
            $table->dateTime('borrowed_at');
            $table->dateTime('due_at');
            $table->dateTime('returned_at')->nullable();
            $table->timestamps();

            $table->foreign('member_id')
                ->references('id')->on('members')
                ->cascadeOnDelete();

            $table->index(['member_id', 'returned_at']);
        });

        // THE cross-aggregate invariant (rule 4), enforced where it can
        // actually be guaranteed: the database.
        //
        // A partial unique index — unique on copy_id, but only across rows
        // where returned_at IS NULL. So a copy can have many *returned*
        // loans in its history, but only one *active* one, ever, across all
        // members. No application code can violate this, including buggy
        // application code, including two requests racing.
        //
        // Works on SQLite and PostgreSQL. MySQL has no partial indexes —
        // there you'd need a generated column or a different modelling
        // approach. Worth knowing before you promise it in a design doc.
        DB::statement(
            'CREATE UNIQUE INDEX one_active_loan_per_copy
             ON loans (copy_id) WHERE returned_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
        Schema::dropIfExists('members');
    }
};
