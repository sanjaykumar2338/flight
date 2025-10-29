<?php

namespace App\Services\Pricing;

use App\Support\Money;

class PricingResult
{
    /**
     * @param array<int, array<string, mixed>> $rulesApplied
     * @param array<string, mixed> $ndcBreakdown
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly Money $baseAmount,
        private readonly Money $startingTotal,
        private readonly Money $finalTotal,
        private readonly array $rulesApplied,
        private readonly array $ndcBreakdown = [],
        private readonly array $context = []
    ) {
    }

    public function base(): Money
    {
        return $this->baseAmount;
    }

    public function startingTotal(): Money
    {
        return $this->startingTotal;
    }

    public function finalTotal(): Money
    {
        return $this->finalTotal;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rulesApplied(): array
    {
        return $this->rulesApplied;
    }

    /**
     * @return array<string, mixed>
     */
    public function ndc(): array
    {
        return $this->ndcBreakdown;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function displayAmount(): Money
    {
        return $this->finalTotal;
    }

    public function payableTotal(): Money
    {
        return $this->finalTotal;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ndc' => $this->ndcBreakdown,
            'rules_applied' => $this->rulesApplied,
            'display_amount' => $this->displayAmount()->toFloat(),
            'payable_total' => $this->payableTotal()->toFloat(),
            'context' => $this->context,
        ];
    }
}
