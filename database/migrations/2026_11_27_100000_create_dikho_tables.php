<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dikho core schema. Money is stored in paise (bigint), credits as integers.
// Every shop-owned row carries shop_id; balances are derived from append-only ledgers.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('purpose', 10); // login | signup
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // shuruaat | dukaan | badhao | dost
            $table->string('name');
            $table->unsignedInteger('price_paise');
            $table->unsignedInteger('credits');
            $table->string('blurb');
            $table->json('features');
            $table->unsignedTinyInteger('sort');
            $table->timestamps();
        });

        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('name_local')->nullable();
            $table->string('slug')->unique();
            $table->string('category', 20); // mobile | kirana | sweets | salon | clinic | coaching
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->string('address')->nullable();
            $table->string('landmark')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->string('weekly_off', 3)->nullable(); // mon..sun
            $table->json('languages')->nullable(); // ["mr","hi","en"]
            $table->unsignedSmallInteger('since_year')->nullable();
            $table->unsignedSmallInteger('delivery_km')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('goal')->nullable();
            $table->json('audience')->nullable();
            $table->json('brand')->nullable(); // colours, tagline, look
            $table->string('logo_path')->nullable();
            $table->string('package_id', 20)->default('shuruaat');
            $table->foreign('package_id')->references('id')->on('packages');
            $table->unsignedTinyInteger('setup_step')->default(1);
            $table->timestamp('setup_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('role', 10); // owner | manager | staff
            $table->json('permissions')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('package_id', 20);
            $table->foreign('package_id')->references('id')->on('packages');
            $table->string('status', 12); // active | cancelled | past_due
            $table->boolean('autopay')->default(false);
            $table->string('autopay_upi')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('next_package_id', 20)->nullable(); // change applies at renewal
            $table->timestamps();
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->integer('delta');
            $table->integer('balance_after');
            $table->string('reason', 20); // plan_grant | rollover | topup | spend | refund | expire
            $table->string('description');
            $table->nullableMorphs('subject');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['shop_id', 'created_at']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount_paise');
            $table->bigInteger('balance_after_paise');
            $table->string('reason', 20); // topup | whatsapp | sms | meta_ad | refund
            $table->string('description');
            $table->string('reference')->nullable(); // UPI ref
            $table->nullableMorphs('subject');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['shop_id', 'created_at']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('price_paise')->nullable();
            $table->unsignedBigInteger('mrp_paise')->nullable();
            $table->string('offer')->nullable();
            $table->string('photo_slot')->nullable();
            $table->boolean('is_hero')->default(false);
            $table->timestamps();
        });

        Schema::create('occasions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_hi')->nullable();
            $table->string('name_mr')->nullable();
            $table->string('kind', 12); // festival | local | season | national | moment
            $table->string('region', 30)->default('india'); // india | maharashtra | <city slug>
            $table->date('starts_on');
            $table->date('ends_on');
            $table->json('weights')->nullable(); // per category, 0..1
            $table->string('rule')->nullable(); // cultural rule shown to the owner
            $table->string('photo_slot')->nullable();
            $table->timestamps();
            $table->index('starts_on');
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->date('month'); // first day of month
            $table->string('status', 10); // draft | review | approved | live | ended
            $table->json('brief')->nullable();
            $table->json('strategy')->nullable();
            $table->unsignedInteger('credits_budget')->default(0);
            $table->unsignedBigInteger('wallet_budget_paise')->default(0);
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['shop_id', 'month', 'version']);
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occasion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('goal')->nullable();
            $table->string('offer')->nullable();
            $table->string('status', 10); // draft | scheduled | live | ended
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedBigInteger('budget_paise')->default(0);
            $table->unsignedBigInteger('spent_paise')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('enquiries')->default(0);
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedInteger('sales')->default(0);
            $table->timestamps();
        });

        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('occasion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16); // post | status | reel | carousel | ad | whatsapp | sms | google_post | print | review_reply
            $table->string('angle', 12)->nullable(); // offer | trust | newness | local | festival | tip
            $table->string('stage', 12)->nullable(); // awareness | trust | buying | return
            $table->string('language', 5)->default('en');
            $table->string('title');
            $table->string('hook');
            $table->string('subline')->nullable();
            $table->text('caption')->nullable();
            $table->string('cta')->nullable();
            $table->string('terms')->nullable();
            $table->string('ratio', 6)->default('4:5');
            $table->string('theme', 12)->default('brand'); // brand | light | dark | festive
            $table->string('photo_slot')->nullable();
            $table->string('status', 12); // draft | ready | approved | scheduled | posted | skipped
            $table->string('source', 8)->default('auto'); // auto | plan | manual
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('channels')->nullable();
            $table->unsignedSmallInteger('credits')->default(0);
            $table->unsignedTinyInteger('trust_score')->nullable();
            $table->json('trust_checks')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('chats')->default(0);
            $table->unsignedInteger('calls')->default(0);
            $table->unsignedInteger('qr_scans')->default(0);
            $table->timestamps();
            $table->index(['shop_id', 'scheduled_at']);
            $table->index(['shop_id', 'status']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('area')->nullable();
            $table->string('gender', 1)->nullable();
            $table->date('birthday')->nullable();
            $table->boolean('whatsapp_consent')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->string('stage', 10); // new | regular | at_risk | lapsed
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedBigInteger('spend_paise')->default(0);
            $table->date('first_visit_on')->nullable();
            $table->date('last_visit_on')->nullable();
            $table->string('last_purchase')->nullable();
            $table->string('source', 20)->nullable(); // walk_in | whatsapp | instagram | meta_ad | referral | google
            $table->string('photo_slot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['shop_id', 'phone']);
            $table->index(['shop_id', 'stage']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 10); // whatsapp | sms
            $table->string('name');
            $table->string('template')->nullable();
            $table->string('language', 5)->default('en');
            $table->text('body');
            $table->json('buttons')->nullable();
            $table->json('audience')->nullable();
            $table->string('status', 10); // draft | scheduled | sending | sent
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients')->default(0);
            $table->unsignedInteger('delivered')->default(0);
            $table->unsignedInteger('read')->default(0);
            $table->unsignedInteger('replies')->default(0);
            $table->unsignedBigInteger('cost_paise')->default(0);
            $table->timestamps();
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger', 20); // new_customer | birthday | lapsed | after_purchase | review | missed_call
            $table->string('description');
            $table->json('steps');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('channel', 16); // whatsapp_status | instagram | facebook | meta_ad | google | broadcast | walk_in
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('chats')->default(0);
            $table->unsignedInteger('calls')->default(0);
            $table->unsignedInteger('qr_scans')->default(0);
            $table->unsignedInteger('offer_codes')->default(0);
            $table->unsignedBigInteger('ad_spend_paise')->default(0);
            $table->unique(['shop_id', 'date', 'channel']);
        });

        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20); // whatsapp | instagram | facebook | google | meta_ads | upi
            $table->string('status', 12); // connected | pending | not_linked
            $table->string('account')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
            $table->unique(['shop_id', 'provider']);
        });

        Schema::create('notifications_feed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['notifications_feed', 'connections', 'daily_metrics', 'workflows', 'messages', 'customers', 'content_items',
            'campaigns', 'plans', 'occasions', 'products', 'wallet_transactions', 'credit_transactions', 'subscriptions',
            'shop_members', 'shops', 'packages', 'login_codes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
