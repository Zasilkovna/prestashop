<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Returns\ReturnApprovalPolicy;
use Packetery\Returns\ReturnRepository;
use Packetery\Returns\ReturnSettings;
use Packetery\Returns\ReturnSettingsFactory;
use Packetery\Tools\ConfigHelper;
use PHPUnit\Framework\TestCase;

class ReturnApprovalPolicyTest extends TestCase
{
    private const ORDER_ID = 42;

    public function testFirstReturnIsAutoProcessedWhenApprovalNotRequired(): void
    {
        $policy = $this->policy($this->settings(false), 0);

        $this->assertFalse($policy->needsApproval(self::ORDER_ID));
    }

    public function testFurtherReturnNeedsApprovalEvenWhenNotRequired(): void
    {
        // the order already has an earlier return (any state, incl. cancelled/rejected) -> the next
        // one waits (PES-3227 rule 9)
        $policy = $this->policy($this->settings(false), 1);

        $this->assertTrue($policy->needsApproval(self::ORDER_ID));
    }

    public function testApprovalRequiredForcesApprovalOnTheFirstReturn(): void
    {
        $policy = $this->policy($this->settings(true), 0);

        $this->assertTrue($policy->needsApproval(self::ORDER_ID));
    }

    private function policy(ReturnSettings $settings, int $existingCount): ReturnApprovalPolicy
    {
        $factory = $this->createStub(ReturnSettingsFactory::class);
        $factory->method('fromConfig')->willReturn($settings);

        $repository = $this->createStub(ReturnRepository::class);
        $repository->method('countByOrderId')->willReturn($existingCount);

        return new ReturnApprovalPolicy($factory, $repository);
    }

    private function settings(bool $approvalRequired): ReturnSettings
    {
        return (new ReturnSettingsFactory())->fromRawValues([
            ConfigHelper::KEY_RETURNS_APPROVE_FIRST => $approvalRequired ? '1' : '',
        ]);
    }
}
