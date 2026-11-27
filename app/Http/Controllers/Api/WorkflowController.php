<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->shop($request)->workflows()->orderBy('id')->get()]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $workflow = $this->shop($request)->workflows()->findOrFail($id);
        $workflow->update($request->validate([
            'active' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:80'],
            'steps' => ['sometimes', 'array', 'min:1', 'max:6'],
            'steps.*.after' => ['required_with:steps', 'string', 'max:10'],
            'steps.*.action' => ['required_with:steps', 'in:whatsapp,sms'],
            'steps.*.text' => ['required_with:steps', 'string', 'max:500'],
        ]));

        return response()->json(['data' => $workflow]);
    }
}
