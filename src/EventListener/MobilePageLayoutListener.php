<?php

declare(strict_types=1);

namespace Netzmacht\Contao\PageContext\EventListener;

use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Environment;
use Contao\LayoutModel;
use Contao\PageModel;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * MobilePageLayoutListener initializes the mobile page layout for Contao < 4.8
 */
final class MobilePageLayoutListener
{
    /**
     * Contao framework.
     *
     * @var ContaoFramework
     */
    private $framework;

    /**
     * HTTP request stack.
     *
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * List of active bundles.
     *
     * @var array<string,mixed>
     */
    private $activeBundles;

    /**
     * @param ContaoFramework     $framework         Contao framework.
     * @param RequestStack        $requestStack      TTP request stack.
     * @param RepositoryManager   $repositoryManager Repository manager.
     * @param LoggerInterface     $logger            Logger.
     * @param array<string,mixed> $activeBundles     List of active kernel bundles.
     */
    public function __construct(
        ContaoFramework $framework,
        RequestStack $requestStack,
        RepositoryManager $repositoryManager,
        LoggerInterface $logger,
        array $activeBundles
    ) {
        $this->framework         = $framework;
        $this->requestStack      = $requestStack;
        $this->repositoryManager = $repositoryManager;
        $this->logger            = $logger;
        $this->activeBundles     = $activeBundles;
    }

    /**
     * Listen to the onGetPageLayout hook.
     *
     * @param PageModel   $pageModel   The page model.
     * @param LayoutModel $layoutModel The current layout model.
     *
     * @throws NoLayoutSpecifiedException When mobile layout could not be loaded.
     */
    public function onGetPageLayout(PageModel $pageModel, LayoutModel &$layoutModel): void
    {
        if (! $pageModel->mobileLayout || isset($this->activeBundles['ContaoMobilePageLayoutBundle'])) {
            $pageModel->isMobile = false;

            return;
        }

        $environment = $this->framework->getAdapter(Environment::class);
        $isMobile    = $environment->get('agent')->mobile;
        $request     = $this->requestStack->getMasterRequest();

        if ($request !== null && $request->cookies->has('TL_VIEW')) {
            $isMobile = $request->cookies->get('TL_VIEW') === 'mobile';
        }

        $pageModel->isMobile = $isMobile;
        if (! $isMobile) {
            return;
        }

        $layoutModel = $this->repositoryManager
            ->getRepository(LayoutModel::class)
            ->find((int) $pageModel->mobileLayout);

        if ($layoutModel === null) {
            $this->logger->log(
                LogLevel::ERROR,
                'Could not find mobile layout ID "' . $pageModel->mobileLayout . '"',
                ['contao' => new ContaoContext(__METHOD__, LogLevel::ERROR)]
            );

            throw new NoLayoutSpecifiedException('No layout specified');
        }
    }
}
