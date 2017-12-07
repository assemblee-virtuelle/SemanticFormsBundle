<?php

namespace VirtualAssembly\SemanticFormsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('semantic_forms');
				$rootNode
					->children()
						->arrayNode('fields_aliases')
						->end() // twitter
						->scalarNode('domain')
						->end() // twitter.
						->variableNode('login')
						->end() // twitter
						->variableNode('password')
						->end() // twitter
						->integerNode('timeout')
						->end() // twitter
						->scalarNode('base_url_form')
						->end() // twitter
					->end()
				;
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
