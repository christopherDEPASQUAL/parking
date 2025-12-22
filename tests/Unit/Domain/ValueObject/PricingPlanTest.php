<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\PricingPlan;
use PHPUnit\Framework\TestCase;

final class PricingPlanTest extends TestCase
{
    public function testComputePriceWithTiersAndDefault(): void
    {
        $plan = new PricingPlan(
            [
                ['upToMinutes' => 30, 'pricePerStepCents' => 100],
            ],
            200
        );

        self::assertSame(200, $plan->computePriceCents(20));
        self::assertSame(400, $plan->computePriceCents(45));
    }

    public function testComputeOverstayAddsPenalty(): void
    {
        $plan = new PricingPlan([], 200, 2000);

        self::assertSame(2600, $plan->computeOverstayPriceCents(30, 45));
        self::assertSame(0, $plan->computeOverstayPriceCents(45, 0));
    }

    public function testInvalidStepThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PricingPlan([], 100, 2000, [], 10);
    }

    public function testInvalidTierThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PricingPlan([
            ['upToMinutes' => 20, 'pricePerStepCents' => 100],
        ], 200);
    }

    public function testSubscriptionPricesNormalization(): void
    {
        $plan = new PricingPlan([], 100, 2000, ['full' => 5000, 'invalid' => 100]);

        self::assertSame(5000, $plan->getSubscriptionPriceCents('FULL'));
        self::assertSame(0, $plan->getSubscriptionPriceCents('unknown'));

        $payload = $plan->toArray();
        $from = PricingPlan::fromArray($payload);
        self::assertSame($plan->getSubscriptionPrices(), $from->getSubscriptionPrices());
    }

    public function testGettersAndToArrayForEmptySubscriptions(): void
    {
        $tiers = [
            ['upToMinutes' => 30, 'pricePerStepCents' => 100],
        ];
        $plan = new PricingPlan($tiers, 200, 1500, []);

        self::assertSame(15, $plan->getStepMinutes());
        self::assertSame($tiers, $plan->getTiers());
        self::assertSame(200, $plan->getDefaultPricePerStepCents());
        self::assertSame(1500, $plan->getOverstayPenaltyCents());

        $payload = $plan->toArray();
        self::assertTrue(is_object($payload['subscriptionPrices']));
    }
}
