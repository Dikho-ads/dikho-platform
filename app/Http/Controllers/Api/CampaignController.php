<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return CampaignResource::collection(
            $this->shop($request)->campaigns()->with('occasion:id,name')->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'scheduled' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")->latest('starts_on')->get()
        );
    }

    public function show(Request $request, int $id): CampaignResource
    {
        return new CampaignResource(
            $this->shop($request)->campaigns()->with(['occasion:id,name', 'items' => fn ($q) => $q->orderBy('scheduled_at'), 'messages'])->findOrFail($id)
        );
    }

    public function store(Request $request): CampaignResource
    {
        $campaign = $this->shop($request)->campaigns()->create($request->validate($this->rules(true)) + ['status' => 'draft']);

        return new CampaignResource($campaign);
    }

    public function update(Request $request, int $id): CampaignResource
    {
        $campaign = $this->shop($request)->campaigns()->findOrFail($id);
        $campaign->update($request->validate($this->rules(false) + ['status' => ['sometimes', Rule::in(['draft', 'scheduled', 'live', 'ended'])]]));

        return new CampaignResource($campaign);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:80'],
            'goal' => ['sometimes', 'nullable', 'string', 'max:120'],
            'offer' => ['sometimes', 'nullable', 'string', 'max:160'],
            'occasion_id' => ['sometimes', 'nullable', 'integer', Rule::exists('occasions', 'id')],
            'starts_on' => [$required, 'date'],
            'ends_on' => [$required, 'date', 'after_or_equal:starts_on'],
            'budget_paise' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
