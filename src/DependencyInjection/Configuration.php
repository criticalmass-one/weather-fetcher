<?php declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('caldera_weatherbundle');

        $rootNode
            ->children()
            ->scalarNode('weather_class')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('owm_app_id')
                ->isRequired()
                ->cannotBeEmpty()
            ->end();

        return $treeBuilder;
    }
}
