<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Services\AccountSummary;
use App\Services\CreditService;
use App\Services\PlanBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function show(Request $request, string $month): JsonResponse
    {
        abort_unless((bool) preg_match('/^\d{4}-\d{2}$/', $month), 404);
        $plan = $this->shop($request)->plans()
            ->whereDate('month', CarbonImmutable::parse($month.'-01'))
            ->latest('version')
            ->with(['items' => fn ($q) => $q->orderBy('scheduled_at')])
            ->first();

        return response()->json(['plan' => $plan ? new PlanResource($plan) : null]);
    }

    public function find(Request $request, int $id): PlanResource
    {
        return new PlanResource($this->shop($request)->plans()->with(['items' => fn ($q) => $q->orderBy('scheduled_at')])->findOrFail($id));
    }

    public function store(Request $request, PlanBuilder $builder): PlanResource
    {
        $shop = $this->shop($request);
        $brief = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'goal' => ['nullable', 'string', 'max:30'],
            'goal_label' => ['nullable', 'string', 'max:60'],
            'current_enquiries' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'target' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'audience' => ['nullable', 'array'],
            'areas' => ['nullable', 'array'],
            'languages' => ['nullable', 'array'],
            'products' => ['nullable', 'array'],
            'occasions' => ['nullable', 'array'],
            'channels' => ['nullable', 'array'],
            'tone' => ['nullable', 'array'],
            'credits' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'wallet_paise' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return new PlanResource($builder->build($shop, $brief));
    }

    /**
     * Approving a plan schedules every piece in it and spends the credits once.
     */
    public function approve(Request $request, int $id, CreditService $credits, AccountSummary $summary): JsonResponse
    {
        $shop = $this->shop($request);
        $plan = $shop->plans()->findOrFail($id);
        abort_if($plan->status === 'approved', 422, 'This plan is already approved.');

        DB::transaction(function () use ($shop, $plan, $credits): void {
            $items = $plan->items()->where('status', 'draft')->get();
            $due = (int) $items->sum('credits');
            if ($due > $shop->creditBalance()) {
                throw new InsufficientBalanceException('credits', $due, $shop->creditBalance());
            }
            if ($due > 0) {
                $credits->spend($shop, $due, $plan->month->format('F Y').' plan · '.$items->count().' pieces', $plan);
            }
            // Credits for the whole plan were spent on the plan row; zero them per item so approving
            // an item later does not charge again.
            $plan->items()->where('status', 'draft')->update(['status' => 'scheduled', 'credits' => 0]);
            $plan->update(['status' => 'approved', 'approved_at' => now()]);
        });

        return response()->json([
            'plan' => new PlanResource($plan->refresh()->load(['items' => fn ($q) => $q->orderBy('scheduled_at')])),
            'account' => $summary->for($request->user()),
        ]);
    }
}
