<?php

declare(strict_types=1);

namespace SpipLeague\Component\Rector\CodeQuality\LangFileWithReturn;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\PhpParser\Node\CustomNode\FileWithoutNamespace;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class LangFileWithReturnRector extends AbstractRector
{
    public function __construct(
        private readonly LongArrayToShortArrayRector $longArrayToShortArrayRector
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FileWithoutNamespace::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): Node|int|null
    {
        if (! $this->isSpipLangFileDirectory($this->file->getFilePath())) {
            return null;
        }

        $is_updated = false;
        foreach ($node->stmts as $k => $stmt) {
            if (
                $stmt instanceof Expression
                && $stmt->expr instanceof Assign
                && $stmt->expr->var instanceof ArrayDimFetch
                && $stmt->expr->var->var instanceof Variable
                && $stmt->expr->var->var->name === 'GLOBALS'
                && $stmt->expr->var->dim instanceof ArrayDimFetch
                && $stmt->expr->var->dim->var instanceof Variable
                && $stmt->expr->var->dim->var->name === 'GLOBALS'
                && $stmt->expr->var->dim->dim instanceof String_
                && $stmt->expr->var->dim->dim->value === 'idx_lang'
                && $stmt->expr->expr instanceof Array_
            ) {
                $node->stmts[$k] = $this->refactorToReturn($stmt);
                $is_updated = true;
            }
        }

        return $is_updated ? $node : null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Update SPIP lang file with return instead of global',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$GLOBALS[$GLOBALS['idx_lang']] = array(
    '...' => '...',
);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
return [
    '...' => '...',
];
CODE_SAMPLE
                ),
            ]
        );
    }

    private function refactorToReturn(Expression $node): Node
    {
        assert($node->expr instanceof Assign);
        assert($node->expr->expr instanceof Array_);
        $array = $node->expr->expr;
        # Transformer array() en [] passe les items sur 1 ligne…
        # Du coup, laissons les gens le faire eux même, car ça oblige
        # à une passe de Ecs ou phpcbf pour réparer.
        # $array = $this->longArrayToShortArrayRector->refactor($array) ?? $array;

        return new Return_($array);
    }

    private function isSpipLangFileDirectory(string $path): bool
    {
        return \strtolower(\basename(\dirname($path))) === 'lang';
    }
}
