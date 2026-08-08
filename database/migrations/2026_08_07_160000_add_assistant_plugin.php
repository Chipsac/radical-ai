<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The assistant is a paid add-on, so access needs two independent gates that
 * answer different questions.
 *
 * The organisation-level entitlement is commercial: has this customer bought
 * the plugin. The per-user module grant is the existing access-control
 * question: should this particular person be able to open it. Buying the
 * add-on must not silently hand it to everyone in the workspace, and granting
 * a user the module must not bypass billing — so neither gate implies the
 * other, and both are checked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Null means never purchased. Timestamps rather than a boolean so
            // the billing history is legible without a separate audit table.
            $table->timestamp('assistant_enabled_at')->nullable()->after('onboarding_completed_at');
            $table->timestamp('assistant_expires_at')->nullable()->after('assistant_enabled_at');

            // A hard ceiling on spend per billing period. The assistant calls a
            // metered API, so an unbounded loop is a real cost, not a
            // hypothetical one — this is the backstop when a prompt goes wrong.
            $table->unsignedInteger('assistant_monthly_token_cap')->default(2_000_000);
        });

        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id', 'last_message_at']);
        });

        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_conversation_id')->constrained()->cascadeOnDelete();

            // user | assistant. Tool calls and results are kept in `blocks`
            // rather than as their own rows: the API requires them replayed in
            // exact order within a turn, and splitting them across rows invites
            // reassembling them wrongly.
            $table->string('role', 16);
            $table->json('blocks');

            // Per-message usage, so the add-on can be billed and a runaway
            // conversation can be spotted before the invoice arrives.
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);

            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['assistant_conversation_id', 'id']);
        });

        // Every write the assistant performs, recorded independently of the
        // conversation. If someone asks "why did this deal move", the answer
        // must not depend on chat history that a user can delete.
        Schema::create('assistant_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool');
            $table->json('arguments');
            $table->string('outcome', 32);          // executed | refused | failed
            $table->text('detail')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_actions');
        Schema::dropIfExists('assistant_messages');
        Schema::dropIfExists('assistant_conversations');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'assistant_enabled_at',
                'assistant_expires_at',
                'assistant_monthly_token_cap',
            ]);
        });
    }
};
