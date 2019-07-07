<?php

namespace Spatie\Php7to5\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;

class GroupUseReplacer extends NodeVisitorAbstract
{
    /**
     * {@inheritdoc}
     */
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Stmt\GroupUse) {
            return null;
        }

        $nodePrefixParts = $node->prefix->parts;

        $separateUseStatements = array_map(function ($useNode) use ($nodePrefixParts) {
            return $this->createUseNode($nodePrefixParts, $useNode);
        }, $node->uses);

        return $separateUseStatements;
    }

    protected function createUseNode(array $nodePrefixParts, Node $useNode)
    {
        $fullClassName = array_merge($nodePrefixParts, [$useNode->name]);

        $nameNode = new Node\Name($fullClassName);

        $alias = ($useNode->alias === $useNode->name->toString()) ? null : $useNode->alias;

        return new Node\Stmt\Use_([new UseUse($nameNode, $alias)], $useNode->type);
    }
}
