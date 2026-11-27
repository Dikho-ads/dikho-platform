<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown when a shop tries to spend more credits or wallet money than it has.
 */
class InsufficientBalanceException extends RuntimeException
{
    public function __construct(
        public readonly string $balance,
        public readonly int $needed,
        public readonly int $available,
    ) {
        $label = $balance === 'credits' ? 'credits' : 'wallet balance';
        parent::__construct("Not enough {$label}: this needs {$needed}, you have {$available}.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->balance === 'credits'
                ? "This needs {$this->needed} credits and you have {$this->available}. Top up credits or pick a smaller format."
                : 'Your wallet needs ₹'.number_format($this->needed / 100, 2).' and has ₹'.number_format($this->available / 100, 2).'. Add money to the wallet first.',
            'code' => $this->balance === 'credits' ? 'insufficient_credits' : 'insufficient_wallet',
            'needed' => $this->needed,
            'available' => $this->available,
        ], 402);
    }
}
