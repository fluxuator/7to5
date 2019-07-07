<?php

namespace Spatie\Php7to5\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/*
 * Converts define() arrays into const arrays
 */

class DefineArrayReplacer extends NodeVisitorAbstract
{
    /**
     * {@inheritdoc}
     */
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Expr\FuncCall) {
            return null;
        }

        if ((string) $node->name !== 'define') {
            return null;
        }

        $nameNode = $node->args[0]->value;
        $valueNode = $node->args[1]->value;

        if (!$valueNode instanceof Node\Expr\Array_) {
            return null;
        }

        $constNode = new Node\Const_(
            'const '.$nameNode->value,
            $valueNode
        );

        return $constNode;
    }
}
