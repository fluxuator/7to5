<?php

namespace Spatie\Php7to5\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class StrictTypesDeclarationRemover extends NodeVisitorAbstract
{
    /**
     * {@inheritdoc}
     */
    public function leaveNode(Node $node)
    {
        if (!$node instanceof DeclareDeclare) {
            return null;
        }

        if ($node->key === 'strict_type') {
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
