<?php

/**
 * Contao Page Context
 *
 * @package    contao-page-context
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2018 netzmacht David Molineus.
 * @license    LGPL-3.0 https://github.com/netzmacht/contao-page-context/blob/master/LICENSE
 * @filesource
 */

declare(strict_types=1);

namespace Netzmacht\Contao\PageContext\DependencyInjection;

use OutOfBoundsException;
use PackageVersions\Versions;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use function explode;

/**
 * Class NetzmachtContaoPageContextExtension
 */
final class NetzmachtContaoPageContextExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.xml');
        $loader->load('listener.xml');

        try {
            $contaoVersion = Versions::getVersion('contao/core-bundle');
        } catch (OutOfBoundsException $e) {
            $contaoVersion = Versions::getVersion('contao/contao');
        }

        $container->setParameter(
            'netzmacht.contao_page_initializer.contao_core_version',
            explode('@', $contaoVersion, 2)[0]
        );
    }
}
