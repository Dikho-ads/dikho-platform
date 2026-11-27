<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the signed-in account's shop on the request. Controllers read it from here and never
 * accept a shop id from input, so one shop can never reach another shop's rows.
 * With the "ready" argument the shop must also have finished setup.
 */
class ResolveShop
{
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $shop = $request->user()?->currentShop();

        if ($shop === null) {
            return response()->json(['message' => 'Create your shop first.', 'code' => 'no_shop'], 409);
        }

        if ($mode === 'ready' && ! $shop->isSetupComplete()) {
            return response()->json(['message' => 'Finish setting up your shop first.', 'code' => 'setup_incomplete'], 409);
        }

        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
