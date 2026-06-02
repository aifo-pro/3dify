<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('model_creation');
            $table->string('status')->default('pending_review')->index();
            $table->string('title');
            $table->longText('description');
            $table->decimal('budget_amount', 12, 2)->nullable();
            $table->boolean('budget_is_negotiable')->default(true);
            $table->date('deadline_at')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('material')->nullable();
            $table->string('color')->nullable();
            $table->string('delivery_service')->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('extra_comment')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('UAH');
            $table->unsignedInteger('delivery_days')->nullable();
            $table->text('offer_description')->nullable();
            $table->text('offer_terms')->nullable();
            $table->decimal('escrow_amount', 12, 2)->default(0);
            $table->decimal('platform_fee_amount', 12, 2)->default(0);
            $table->decimal('author_amount', 12, 2)->default(0);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->timestamp('auto_complete_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('custom_order_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('buyer');
            $table->longText('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('custom_order_messages')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose')->default('attachment');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('custom_order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('aifo');
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('UAH');
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_order_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_order_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('tracking_added')->index();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_order_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('custom_order_shipments')->cascadeOnDelete();
            $table->string('status');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('happened_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_order_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open')->index();
            $table->string('reason');
            $table->longText('description');
            $table->text('resolution_note')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_order_disputes');
        Schema::dropIfExists('custom_order_tracking_events');
        Schema::dropIfExists('custom_order_shipments');
        Schema::dropIfExists('custom_order_milestones');
        Schema::dropIfExists('custom_order_payments');
        Schema::dropIfExists('custom_order_status_logs');
        Schema::dropIfExists('custom_order_files');
        Schema::dropIfExists('custom_order_messages');
        Schema::dropIfExists('custom_orders');
    }
};
