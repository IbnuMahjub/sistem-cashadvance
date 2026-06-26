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
        Schema::create('tr_ca_transaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tr_ca_id')->constrained('tr_ca')->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('jenis', ['penerimaan', 'pengeluaran']);
            $table->text('deskripsi')->nullable();
            $table->string('bukti')->nullable();
            $table->string('kategori')->nullable();
            $table->decimal('jumlah', 15, 2);
            $table->decimal('saldo_setelah', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_ca_transactions');
    }
};
