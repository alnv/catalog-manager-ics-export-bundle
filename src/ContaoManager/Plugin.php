<?php

namespace Alnv\CatalogManagerIcsExportBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Alnv\CatalogManagerIcsExportBundle\AlnvCatalogManagerIcsExportBundle;


class Plugin implements BundlePluginInterface, RoutingPluginInterface
{


    public function getBundles(ParserInterface $parser)
    {

        return [
            BundleConfig::create(AlnvCatalogManagerIcsExportBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['catalog-manager-ics-export']),
        ];
    }


    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {

        return $resolver
            ->resolve(__DIR__ . '/../Resources/config/routing.yml')
            ->load(__DIR__ . '/../Resources/config/routing.yml');
    }
}