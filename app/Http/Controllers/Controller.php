<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * The signed-in account's shop, set by the ResolveShop middleware.
     */
    protected function shop(Request $request): Shop
    {
        return $request->attributes->get('shop');
    }

    /**
     * Converts an incoming date-time (any offset, e.g. "…Z" from a browser) to the app timezone.
     * Eloquent keeps only the wall-clock part of a string, so it must be converted first.
     */
    protected function localTime(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value)->setTimezone(config('app.timezone'));
    }
}
