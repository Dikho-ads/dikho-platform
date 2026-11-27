<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OccasionResource;
use App\Models\Occasion;
use App\Services\StarterContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OccasionController extends Controller
{
    /**
     * Occasions for the shop's region in a date window, most relevant to its category first
     * within each day, with how many pieces are already planned for each.
     */
    public function index(Request $request, StarterContent $starter): AnonymousResourceCollection
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $shop = $this->shop($request);
        $from = now()->parse($data['from'] ?? now()->toDateString())->startOfDay();
        $to = now()->parse($data['to'] ?? now()->addMonths(4)->toDateString())->endOfDay();

        $occasions = Occasion::query()
            ->whereIn('region', $starter->regions($shop))
            ->whereDate('ends_on', '>=', $from)
            ->whereDate('starts_on', '<=', $to)
            ->orderBy('starts_on')
            ->get();

        $planned = $shop->contentItems()
            ->whereIn('occasion_id', $occasions->pluck('id'))
            ->whereNot('status', 'skipped')
            ->selectRaw('occasion_id, count(*) as n')
            ->groupBy('occasion_id')
            ->pluck('n', 'occasion_id');

        $occasions->each(fn (Occasion $o) => $o->setAttribute('planned_items', (int) ($planned[$o->id] ?? 0)));

        return OccasionResource::collection($occasions);
    }
}
