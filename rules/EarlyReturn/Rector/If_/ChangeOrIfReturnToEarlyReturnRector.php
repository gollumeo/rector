<?php

declare (strict_types=1);
namespace Rector\EarlyReturn\Rector\If_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use Rector\Core\NodeManipulator\IfManipulator;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\EarlyReturn\Rector\If_\ChangeOrIfReturnToEarlyReturnRector\ChangeOrIfReturnToEarlyReturnRectorTest
 */
final class ChangeOrIfReturnToEarlyReturnRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @var \Rector\Core\NodeManipulator\IfManipulator
     */
    private $ifManipulator;
    public function __construct(\Rector\Core\NodeManipulator\IfManipulator $ifManipulator)
    {
        $this->ifManipulator = $ifManipulator;
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Changes if || with return to early return', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($a, $b)
    {
        if ($a || $b) {
            return null;
        }

        return 'another';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($a, $b)
    {
        if ($a) {
            return null;
        }
        if ($b) {
            return null;
        }

        return 'another';
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Stmt\If_::class];
    }
    /**
     * @param \PhpParser\Node $node
     */
    public function refactor($node) : ?\PhpParser\Node
    {
        if (!$this->ifManipulator->isIfWithOnly($node, \PhpParser\Node\Stmt\Return_::class)) {
            return null;
        }
        if (!$node->cond instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            return null;
        }
        if ($this->isInstanceofCondOnly($node->cond)) {
            return null;
        }
        /** @var Return_ $return */
        $return = $node->stmts[0];
        $ifs = $this->createMultipleIfs($node->cond, $return, []);
        foreach ($ifs as $key => $if) {
            if ($key === 0) {
                $this->mirrorComments($if, $node);
            }
            $this->addNodeBeforeNode($if, $node);
        }
        $this->removeNode($node);
        return $node;
    }
    /**
     * @param If_[] $ifs
     * @return If_[]
     * @param \PhpParser\Node\Stmt\Return_ $return
     */
    private function createMultipleIfs(\PhpParser\Node\Expr $expr, $return, array $ifs) : array
    {
        while ($expr instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $ifs = \array_merge($ifs, $this->collectLeftBooleanOrToIfs($expr, $return, $ifs));
            $ifs[] = $this->createIf($expr->right, $return);
            $expr = $expr->right;
        }
        return $ifs + [$this->createIf($expr, $return)];
    }
    /**
     * @param If_[] $ifs
     * @return If_[]
     */
    private function collectLeftBooleanOrToIfs(\PhpParser\Node\Expr\BinaryOp\BooleanOr $booleanOr, \PhpParser\Node\Stmt\Return_ $return, array $ifs) : array
    {
        $left = $booleanOr->left;
        if (!$left instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            return [$this->createIf($left, $return)];
        }
        return $this->createMultipleIfs($left, $return, $ifs);
    }
    private function createIf(\PhpParser\Node\Expr $expr, \PhpParser\Node\Stmt\Return_ $return) : \PhpParser\Node\Stmt\If_
    {
        return new \PhpParser\Node\Stmt\If_($expr, ['stmts' => [$return]]);
    }
    private function isInstanceofCondOnly(\PhpParser\Node\Expr\BinaryOp\BooleanOr $booleanOr) : bool
    {
        $currentNode = $booleanOr;
        if ($currentNode->left instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            return $this->isInstanceofCondOnly($currentNode->left);
        }
        if ($currentNode->right instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            return $this->isInstanceofCondOnly($currentNode->right);
        }
        if (!$currentNode->right instanceof \PhpParser\Node\Expr\Instanceof_) {
            return \false;
        }
        return $currentNode->left instanceof \PhpParser\Node\Expr\Instanceof_;
    }
}
