<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_chat_id')->unique();
            $table->string('customer_name')->nullable();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'escalated', 'closed'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['customer', 'bot', 'system']);
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
        Schema::dropIfExists('telegram_conversations');
    }
};
