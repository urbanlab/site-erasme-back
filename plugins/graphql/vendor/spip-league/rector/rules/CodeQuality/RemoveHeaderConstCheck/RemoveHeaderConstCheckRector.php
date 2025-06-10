<?php

declare(strict_types=1);

namespace SpipLeague\Component\Rector\CodeQuality\RemoveHeaderConstCheck;

use PhpParser\Node;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\NodeTraverser;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PhpParser\Node\CustomNode\FileWithoutNamespace;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RemoveHeaderConstCheckRector extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [If_::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): Node|int|null
    {
        if (
            // return;
            count($node->stmts) === 1
            && $node->stmts[0] instanceof Return_
            && $node->stmts[0]->expr === null
            // !defined('')
            && $node->cond instanceof BooleanNot
            && $node->cond->expr instanceof FuncCall
            && $this->isName($node->cond->expr->name, 'defined')
            && $node->cond->expr->getArgs()[0]
                ->value instanceof String_
                            && $node->cond->expr->getArgs()[0]
                                ->value->value === '_ECRIRE_INC_VERSION'
        ) {
            if ($this->hasCodeExecution($this->file->getNewStmts(), [$node])) {
                return null;
            }

            return $this->removeNodeAndKeepComments($node);
        }

        return null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove SPIP Test Header in files',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'

CODE_SAMPLE
                ),
            ]
        );
    }

    private function removeNodeAndKeepComments(Node $node): int|Node
    {
        if ($node->getComments() !== []) {
            $nop = new Nop();
            $nop->setAttribute(AttributeKey::COMMENTS, $node->getComments());
            return $nop;
        }
        return NodeTraverser::REMOVE_NODE;
    }

    private function hasCodeExecution(array $stmts, array $exclude_nodes = []): bool
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_ || $stmt instanceof FileWithoutNamespace) {
                if ($this->hasCodeExecution($stmt->stmts, $exclude_nodes)) {
                    return true;
                }
                continue;
            }
            if (in_array($stmt, $exclude_nodes)) {
                continue;
            }

            // define() ou include_spip()
            if (
                $this->isFuncCall($stmt, 'define')
                || $this->isFuncCall($stmt, 'include_spip')
            ) {
                continue;
            }
            // require_once ou include
            if (
                $stmt instanceof Expression
                && $stmt->expr instanceof Include_
            ) {
                continue;
            }

            if (
                $stmt instanceof Switch_
                || $stmt instanceof If_
                || $stmt instanceof Expression
                || $stmt instanceof Echo_
            ) {
                return true;
            }
        }
        return false;
    }

    private function isFuncCall(Stmt $stmt, string $name): bool
    {
        if (
            $stmt instanceof Expression
            && $stmt->expr instanceof FuncCall
            && $this->isName($stmt->expr->name, $name)
        ) {
            return true;
        }
        return false;
    }
}
