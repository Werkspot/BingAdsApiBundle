<?php

namespace Werkspot\BingAdsApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('werkspot_bing_ads_api');

        $rootNode
            ->children()
                ->scalarNode('cache_dir')
                    ->defaultValue('%kernel.cache_dir%')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('api_client_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('api_secret')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('redirect_uri')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('dev_token')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('refresh_token')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
