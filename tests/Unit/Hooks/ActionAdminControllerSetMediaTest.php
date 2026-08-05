<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Hooks;

use Packetery\Hooks\ActionAdminControllerSetMedia;
use Packetery\Module\VersionChecker;
use Packetery\Returns\PendingReturnsNotice;
use Packetery\Returns\PendingReturnsNotifier;
use PHPUnit\Framework\TestCase;

/**
 * PrestaShop 9 runs this hook twice per admin page, so execute() is called repeatedly here on purpose.
 * The controller double is deliberately NOT an AdminController — on migrated pages PrestaShop passes
 * a LegacyControllerContext, so any type hint against AdminController would break those pages.
 */
class ActionAdminControllerSetMediaTest extends TestCase
{
    private const NOTICE_HTML = '<notice count="2">';
    private const NOTICE_HTML_UPDATED = '<notice count="1">';
    private const FOREIGN_WARNING = 'warning from another module';

    protected function setUp(): void
    {
        \Tools::$values = ['controller' => 'AdminOrders'];
    }

    public function testAddsTheNoticeWhenReturnsAwaitApproval(): void
    {
        $controller = $this->createController();
        $hook = $this->createHook($controller, [self::NOTICE_HTML], [new PendingReturnsNotice(2, true)]);

        $hook->execute();

        $this->assertSame([self::NOTICE_HTML], $controller->warnings);
    }

    public function testReplacesTheStaleNoticeOnTheSecondRunInsteadOfAddingAnother(): void
    {
        $controller = $this->createController();
        $hook = $this->createHook(
            $controller,
            [self::NOTICE_HTML, self::NOTICE_HTML_UPDATED],
            [new PendingReturnsNotice(2, true), new PendingReturnsNotice(1, true)]
        );

        $hook->execute();
        $hook->execute();

        $this->assertSame([self::NOTICE_HTML_UPDATED], $controller->warnings);
    }

    public function testDropsTheNoticeWhenNothingAwaitsApprovalAnyMore(): void
    {
        $controller = $this->createController();
        $hook = $this->createHook($controller, [self::NOTICE_HTML], [new PendingReturnsNotice(2, true), null]);

        $hook->execute();
        $hook->execute();

        $this->assertSame([], $controller->warnings);
    }

    public function testKeepsWarningsPutUpByOtherModules(): void
    {
        $controller = $this->createController();
        $controller->warnings[] = self::FOREIGN_WARNING;
        $hook = $this->createHook(
            $controller,
            [self::NOTICE_HTML, self::NOTICE_HTML_UPDATED],
            [new PendingReturnsNotice(2, true), new PendingReturnsNotice(1, true)]
        );

        $hook->execute();
        $hook->execute();

        $this->assertSame([self::FOREIGN_WARNING, self::NOTICE_HTML_UPDATED], $controller->warnings);
    }

    public function testRegistersTheModuleAssetsOnlyOnceAcrossRepeatedRuns(): void
    {
        $controller = $this->createController();
        $hook = $this->createHook($controller, [], [null, null]);

        $hook->execute();
        $hook->execute();

        $this->assertCount(1, $controller->cssFiles);
        $this->assertCount(2, $controller->jsFiles);
        $this->assertStringContainsString('views/js/back.js', $controller->jsFiles[1]);
    }

    /**
     * Stands in for both shapes PrestaShop puts in the context: a legacy AdminController and, on
     * migrated pages, a LegacyControllerContext. Only the API the hook touches is implemented.
     */
    private function createController(): object
    {
        return new class {
            /** @var array<int, string|bool> */
            public $warnings = [];
            /** @var array<int, string> */
            public $cssFiles = [];
            /** @var array<int, string> */
            public $jsFiles = [];

            public function addCSS($uri, $mediaType = 'all', $offset = null, $checkPath = true): void
            {
                $this->cssFiles[] = $uri;
            }

            public function addJS($uri, $checkPath = true): void
            {
                $this->jsFiles[] = $uri;
            }
        };
    }

    /**
     * @param array<int, string> $renderedNotices what the template renders, in call order
     * @param array<int, PendingReturnsNotice|null> $notices what the notifier reports, in call order
     */
    private function createHook(object $controller, array $renderedNotices, array $notices): ActionAdminControllerSetMedia
    {
        $smarty = new class($renderedNotices) {
            /** @var array<int, string> */
            private $renderedNotices;

            public function __construct(array $renderedNotices)
            {
                $this->renderedNotices = $renderedNotices;
            }

            public function assign($variables): void
            {
            }

            public function fetch(string $template): string
            {
                return (string) array_shift($this->renderedNotices);
            }
        };

        $context = new \stdClass();
        $context->controller = $controller;
        $context->smarty = $smarty;

        $module = $this->createStub(\Packetery::class);
        $module->method('getContext')->willReturn($context);
        $module->method('getPathUri')->willReturn('/modules/packetery/');
        $module->method('getAdminLink')->willReturn('/admin/PacketeryReturnGrid');

        $notifier = $this->createStub(PendingReturnsNotifier::class);
        $notifier->method('getNotice')->willReturn(...$notices);

        return new ActionAdminControllerSetMedia($module, $this->createStub(VersionChecker::class), $notifier);
    }
}
