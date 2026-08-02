<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tr_ca_collaborator', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tr_ca_id')
                ->constrained('tr_ca')
                ->cascadeOnDelete();

            $table->string('user_id');
            $table->string('username');

            $table->enum('role', [
                'owner',
                'collaborator',
                'viewer'
            ])->default('collaborator');

            $table->boolean('can_view')->default(true);
            $table->boolean('can_create_transaction')->default(false);
            $table->boolean('can_edit_transaction')->default(false);
            $table->boolean('can_delete_transaction')->default(false);

            $table->timestamps();

            $table->unique(['tr_ca_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_ca_collaborator');
    }
};
