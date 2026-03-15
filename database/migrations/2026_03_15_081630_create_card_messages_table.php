<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('card_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            // кто отправил: external (внешний пользователь), manager (CRM), system
            $table->enum('sender_type', ['external', 'manager', 'system'])->default('external');
            // отображаемое имя, например "Пользователь 9999"
            $table->string('sender_label')->nullable();
            // сырые данные из API
            $table->json('payload')->nullable();
            // текст сообщения
            $table->text('message')->nullable();
            // вложения (файлы, изображения)
            $table->json('attachments')->nullable();
            // статус прочтения
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_messages');
    }
};
