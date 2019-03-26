<?php
/**
 * Copyright © 2019 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

use PhpParser\Node;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Owebia_Shipping2_Helper_Evaluator extends Mage_Core_Helper_Data
{

    const UNDEFINED_INDEX = 301;

    /**
     * @var boolean
     */
    protected $debug = false;

    /**
     * @var array
     */
    protected $debugOutput = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var integer
     */
    protected $counter = 1;

    /**
     * @var array
     */
    protected $allowedFunctions = [
        // Deprecated
        'exp',
        'log',
        'pi',
        'pow',
        'rand',
        'sqrt',
        // Math Functions
        'abs',
        'ceil',
        'floor',
        'max',
        'min',
        'round',
        // String Functions
        'explode',
        'implode',
        'strlen',
        'strpos',
        'strtolower',
        'strtoupper',
        'substr',
        // Multibyte String Functions
        'mb_strlen',
        'mb_strpos',
        'mb_strtolower',
        'mb_strtoupper',
        'mb_substr',
        // PCRE Functions
        'preg_match',
        'preg_replace',
        // Date/Time Functions
        'date',
        'strtotime',
        'time',
        // Array Functions
        'array_filter',
        'array_intersect',
        'array_keys',
        'array_map',
        'array_reduce',
        'array_search',
        'array_sum',
        'array_unique',
        'array_values',
        'count',
        'in_array',
        'range',
    ];

    /**
     * @var Owebia_Shipping2_Model_Registry
     */
    protected $registry = null;

    /**
     * @var Owebia_Shipping2_Model_ConfigParser
     */
    protected $callbackHandler = null;

    /**
     * @var \PhpParser\PrettyPrinter\Standard
     */
    protected $prettyPrinter = null;

    /**
     * @return string
     */
    public function getDebugOutput()
    {
        return implode("\n", $this->debugOutput);
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param string $msg
     * @param mixed $expr
     * @throws \Exception
     */
    protected function error($msg, $expr)
    {
        $trace = debug_backtrace(false);
        $this->errors[] = [
            'level' => 'ERROR',
            'msg' => $msg,
            // 'code' => $this->prettyPrint($expr),
            'expression' => $expr,
            'line' => $trace[0]['line']
        ];
        throw new \Exception(__($msg));
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        $msg = [];
        foreach ($this->errors as $error) {
            $msg[] = $error['msg'];
        }
        return implode('<br/>', $msg);
    }

    public function initialize()
    {
        $this->debugOutput = [];
        $this->errors = [];
        $this->counter = 1;
    }

    /**
     * @param mixed $node
     * @param mixed $result
     * @return mixed
     */
    protected function debug($node, $result, $wrap = true)
    {
        if ($this->debug) {
            $right = $this->prettyPrint($result);
            $left = $this->prettyPrint($node);
            $uid = 'p' . uniqid();
            if ($left !== $right) {
                $this->debugOutput[] = '<div data-target="#' . $uid . '"><pre class=php>'
                        . htmlspecialchars($left)
                    . '</pre>'
                    . '<div class="hidden target" id="' . $uid . '"><pre class="php result">'
                        . htmlspecialchars("// Result\n$right")
                    . '</pre></div></div>';
            }
        }
        return $wrap ? $this->wrap($result) : $result;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function wrap($data)
    {
        return $this->registry->wrap($data);
    }

    /**
     * @return \PhpParser\PrettyPrinter\Standard
     */
    public function getPrettyPrinter()
    {
        if (!isset($this->prettyPrinter)) {
            $this->prettyPrinter = new \PhpParser\PrettyPrinter\Standard([
                'shortArraySyntax' => true
            ]);
        }
        return $this->prettyPrinter;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function prettyPrint($value)
    {
        if (!isset($value) || is_bool($value) || is_int($value) || is_string($value)) {
            return var_export($value, true);
        } elseif (is_float($value)) {
            return (string) $value;
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                if (is_object($item) || is_array($item)) {
                    return 'array(size:' . count($value) . ')';
                }
            }
            // return $this->getPrettyPrinter()->pExpr_Array(new \PhpParser\Node\Expr\Array_($value));
            return var_export($value, true);
        } elseif (is_object($value)) {
            if ($value instanceof Node) {
                if ($value->hasAttribute('comments')) {
                    $value->setAttribute('comments', []);
                }
                return rtrim($this->getPrettyPrinter()->prettyPrint([
                    $value
                ]), ';');
            } else {
                return "/** @var " . get_class($value) . " \$obj */ \$obj";
            }
        } else {
            return $value;
        }
    }

    /**
     * @param array $stmts
     * @return mixed
     */
    public function evaluateStmts($stmts)
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_) {
                return $stmt;
            }

            $result = $this->evaluate($stmt);
            if (is_array($result) && $this->doesArrayContainOnly($result, \PhpParse\AbstractNode::class)) {
                $result = $this->evaluateStmts($result);
            }
            if ($result instanceof Node\Stmt\Return_) {
                return $result;
            }
        }
        return null;
    }

    protected function doesArrayContainOnly($data, $className)
    {
        foreach ($data as $item) {
            if (!$item instanceof $className) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param \PhpParser\Node\Expr\Closure $expression
     * @return \Closure|null
     * @throws \Exception
     */
    protected function closure(Node\Expr\Closure $expression)
    {
        if ($expression->static !== false) {
            return $this->error("Unsupported code - closure \$expression->static !== false", $expression);
        }
        if ($expression->byRef !== false) {
            return $this->error("Unsupported code - closure \$expression->byRef !== false", $expression);
        }

        $evaluator = $this;

        return function () use ($expression, $evaluator) {
            $args = func_get_args();
            $evaluator->registry->createScope();
            try {
                foreach ($expression->params as $param) {
                    $value = empty($args) ? $evaluator->evaluate($param) : array_shift($args);
                    $evaluator->registry->register(isset($param->var->name) ? $param->var->name : $param->name, $this->wrap($value)); // v.3 $param->name, v.4 $param->var->name
                }
                
                $result = $evaluator->evaluateStmts($expression->stmts);
                if ($result instanceof Node\Stmt\Return_) {
                    $result = $evaluator->evaluate($result);
                }
            } catch (\Exception $e) {
                $evaluator->registry->deleteScope();
                throw $e;
            }
            $evaluator->registry->deleteScope();
            return $result;
        };
    }

    /**
     * @param Owebia_Shipping2_Model_Registry $registry
     * @return $this
     */
    public function setRegistry(Owebia_Shipping2_Model_Registry $registry)
    {
        $this->registry = $registry;
        return $this;
    }

    /**
     * @param Owebia_Shipping2_Model_ConfigParser $callbackHandler
     * @return $this
     */
    public function setCallbackManager(Owebia_Shipping2_Model_ConfigParser $callbackHandler)
    {
        $this->callbackHandler = $callbackHandler;
        return $this;
    }

    /**
     * @param mixed $expression
     * @return mixed
     * @throws \Exception
     */
    public function evaluate($expression)
    {
        return $this->evl($expression);
    }

    /**
     * @param mixed $expr
     * @return mixed
     * @throws \Exception
     */
    protected function evl($expr)
    {
        if (is_string($expr)) {
            return $expr;
        }
        if (is_array($expr)) {
            return $expr;
        }
        
        $className = get_class($expr);
        switch ($className) {
            // nikic/php-parser:4.*
            // Don't use ::class to keep compatibility with nikic/php-parser:3.*
            case 'PhpParser\\Node\\Stmt\\Expression':
                return $this->debug($expr, $this->evl($expr->expr));
            case 'PhpParser\\Node\\Identifier':
                return $this->debug($expr, (string) $expr);

            case Node\Scalar\DNumber::class:
            case Node\Scalar\LNumber::class:
            case Node\Scalar\String_::class:
                return $this->debug($expr, $expr->value);
            
            // Arithmetic Operators
            case Node\Expr\UnaryMinus::class:
                return $this->debug($expr, - $this->evl($expr->expr));
            case Node\Expr\BinaryOp\Plus::class:
                return $this->debug($expr, $this->evl($expr->left) + $this->evl($expr->right));
            case Node\Expr\BinaryOp\Minus::class:
                return $this->debug($expr, $this->evl($expr->left) - $this->evl($expr->right));
            case Node\Expr\BinaryOp\Mul::class:
                return $this->debug($expr, $this->evl($expr->left) * $this->evl($expr->right));
            case Node\Expr\BinaryOp\Div::class:
                return $this->debug($expr, $this->evl($expr->left) / $this->evl($expr->right));
            case Node\Expr\BinaryOp\Mod::class:
                return $this->debug($expr, $this->evl($expr->left) % $this->evl($expr->right));
            case Node\Expr\BinaryOp\Pow::class: // Operator ** Introduced in PHP 5.6
                return $this->debug($expr, pow($this->evl($expr->left), $this->evl($expr->right)));
            
            // Bitwise Operators
            case Node\Expr\BinaryOp\BitwiseAnd::class:
                return $this->debug($expr, $this->evl($expr->left) & $this->evl($expr->right));
            case Node\Expr\BinaryOp\BitwiseOr::class:
                return $this->debug($expr, $this->evl($expr->left) | $this->evl($expr->right));
            case Node\Expr\BinaryOp\BitwiseXor::class:
                return $this->debug($expr, $this->evl($expr->left) ^ $this->evl($expr->right));
            case Node\Expr\BitwiseNot::class:
                return $this->debug($expr, ~ $this->evl($expr->expr));
            case Node\Expr\BinaryOp\ShiftLeft::class:
                return $this->debug($expr, $this->evl($expr->left) << $this->evl($expr->right));
            case Node\Expr\BinaryOp\ShiftRight::class:
                return $this->debug($expr, $this->evl($expr->left) >> $this->evl($expr->right));
            
            // Comparison Operators
            case Node\Expr\BinaryOp\Equal::class:
                return $this->debug($expr, $this->evl($expr->left) == $this->evl($expr->right));
            case Node\Expr\BinaryOp\Identical::class:
                return $this->debug($expr, $this->evl($expr->left) === $this->evl($expr->right));
            case Node\Expr\BinaryOp\NotEqual::class:
                return $this->debug($expr, $this->evl($expr->left) != $this->evl($expr->right));
            case Node\Expr\BinaryOp\NotIdentical::class:
                return $this->debug($expr, $this->evl($expr->left) !== $this->evl($expr->right));
            case Node\Expr\BinaryOp\Smaller::class:
                return $this->debug($expr, $this->evl($expr->left) < $this->evl($expr->right));
            case Node\Expr\BinaryOp\Greater::class:
                return $this->debug($expr, $this->evl($expr->left) > $this->evl($expr->right));
            case Node\Expr\BinaryOp\SmallerOrEqual::class:
                return $this->debug($expr, $this->evl($expr->left) <= $this->evl($expr->right));
            case Node\Expr\BinaryOp\GreaterOrEqual::class:
                return $this->debug($expr, $this->evl($expr->left) >= $this->evl($expr->right));
            
            // Logical Operators
            case Node\Expr\BinaryOp\LogicalAnd::class:
                return $this->debug($expr, $this->evl($expr->left) and $this->evl($expr->right));
            case Node\Expr\BinaryOp\LogicalOr::class:
                return $this->debug($expr, $this->evl($expr->left) or $this->evl($expr->right));
            case Node\Expr\BinaryOp\LogicalXor::class:
                return $this->debug($expr, $this->evl($expr->left) xor $this->evl($expr->right));
            case Node\Expr\BooleanNot::class:
                return $this->debug($expr, !$this->evl($expr->expr));
            case Node\Expr\BinaryOp\BooleanAnd::class:
                return $this->debug($expr, $this->evl($expr->left) && $this->evl($expr->right));
            case Node\Expr\BinaryOp\BooleanOr::class:
                return $this->debug($expr, $this->evl($expr->left) || $this->evl($expr->right));
            
            // Casting
            case Node\Expr\Cast\String_::class:
                return $this->debug($expr, (string) $this->evl($expr->expr));
            case Node\Expr\Cast\Int_::class:
                return $this->debug($expr, (int) $this->evl($expr->expr));
            case Node\Expr\Cast\Bool_::class:
                return $this->debug($expr, (bool) $this->evl($expr->expr));
            case Node\Expr\Cast\Double::class:
                return $this->debug($expr, (double) $this->evl($expr->expr));
            case Node\Expr\Cast\Object_::class:
                return $this->debug($expr, (object) $this->evl($expr->expr));
            case Node\Expr\Cast\Array_::class:
                return $this->debug($expr, (array) $this->evl($expr->expr));
            
            // String Operators
            case Node\Expr\BinaryOp\Concat::class:
                return $this->debug($expr, $this->evl($expr->left) . $this->evl($expr->right));
            
            case Node\Expr\BinaryOp\Coalesce::class: // Operator ?? Introduced in PHP 7
                try {
                    $left = $this->evl($expr->left);
                } catch (\OutOfBoundsException $e) {
                    $left = null;
                }
                return $this->debug($expr, null !== $left ? $left : $this->evl($expr->right));
            
            case Node\Expr\Ternary::class:
                return $this->debug($expr, $this->evl($expr->cond)
                    ? $this->evl($expr->if)
                    : $this->evl($expr->else));
            
            case Node\Expr\Isset_::class:
                try {
                    $result = $this->evl($expr->vars[0]);
                } catch (\OutOfBoundsException $e) {
                    $result = null;
                }
                return $this->debug($expr, $result  !== null);

            case Node\Expr\MethodCall::class:
                return $this->evaluateMethodCall($expr);

            case Node\Expr\ArrayDimFetch::class:
                $propertyName = $this->evl($expr->dim);
                $variable = $this->evl($expr->var);
                if ($variable instanceof Node\Expr\ArrayItem) {
                    $variable = $this->evl($variable->value);
                }
                if ($variable instanceof Node\Expr\Array_) {
                    $variable = $this->evl($variable);
                }
                // var_export($variable);
                if (!is_array($variable)) {
                    $variableName = isset($expr->var->name) ? $expr->var->name : '';
                    return $this->error("Unsupported ArrayDimFetch expression"
                        . " - Variable \${$variableName} is not an array", $expr);
                } elseif (is_array($variable) && isset($variable[$propertyName])) {
                    return $this->debug($expr, $variable[$propertyName]);
                } elseif (is_array($variable) && !isset($variable[$propertyName])) {
                    $this->debug($expr, null);
                    throw new \OutOfBoundsException("Undefined index: $propertyName", $this::UNDEFINED_INDEX);
                }
                return $this->error("Unsupported ArrayDimFetch expression", $expr);
            case Node\Expr\StaticPropertyFetch::class:
                $className = $this->evl($expr->class);
                if (true) { // StaticPropertyFetch is forbidden
                    return $this->error("Unsupported StaticPropertyFetch expression", $expr);
                }
                $propertyName = $this->evl($expr->name);
                $result = $this->registry->getGlobal($propertyName);
                return $this->debug($expr, $result);
            case Node\Expr\PropertyFetch::class:
                return $this->evaluatePropertyFetch($expr);
            case Node\Expr\Variable::class:
                return $this->debug($expr, $this->registry->get($expr->name));
            case Node\Expr\Array_::class:
                $items = [];
                foreach ($expr->items as $item) {
                    $value = $this->evl($item->value);
                    if (isset($item->key)) {
                        $items[$this->evl($item->key)] = $value;
                    } else {
                        $items[] = $value;
                    }
                }
                return $this->debug($expr, $items);
            case Node\Expr\ConstFetch::class:
                return $this->debug($expr, constant($expr->name->parts[0]));
            case Node\Expr\FuncCall::class:
                return $this->evaluateFuncCall($expr);
            case Node\Expr\Closure::class:
                return $this->debug($expr, $this->closure($expr));
            case Node\Stmt\Return_::class:
                return $this->debug($expr, $this->evl($expr->expr));
            case Node\Stmt\Global_::class:
                foreach ($expr->vars as $var) {
                    $variableName = $var->name;
                    $value = $this->registry->getGlobal($variableName);
                    $this->registry->declareGlobalAtCurrentScope($variableName);
                }
                return $this->debug($expr, null);
            case Node\Expr\Assign::class:
                if (!isset($expr->var->name)
                    || !isset($expr->expr)
                    || !($expr->var instanceof Node\Expr\Variable)
                ) {
                    return $this->error("Unsupported Assign expression", $expr);
                }
                $variableName = $expr->var->name;
                $value = $this->evl($expr->expr);
                $this->registry->register($variableName, $value, true);
                return $this->debug($expr, $value);
            case Node\Stmt\If_::class:
                $cond = $this->evl($expr->cond);
                if ($cond) {
                    return $this->debug($expr, $this->evaluateStmts($expr->stmts), $wrap = false);
                }
                
                if (isset($expr->elseifs)) {
                    foreach ($expr->elseifs as $elseif) {
                        $cond = $this->evl($elseif->cond);
                        if ($cond) {
                            return $this->debug($expr, $this->evaluateStmts($elseif->stmts), $wrap = false);
                        }
                    }
                }
                if (isset($expr->else)) {
                    return $this->debug($expr, $this->evaluateStmts($expr->else->stmts), $wrap = false);
                }
                return $this->debug($expr, null);
            case Node\Stmt\Foreach_::class:
                $exp = $this->evl($expr->expr);
                $valueVar = $this->evl($expr->valueVar->name);
                $keyVar = $expr->keyVar ? $this->evl($expr->keyVar->name) : null;
                if (!is_array($exp)) {
                    return $this->error("Unsupported Foreach_ expression - Undefined variable", $expr);
                }
                foreach ($exp as $key => $value) {
                    $this->registry->register($valueVar, $this->wrap($value), true);
                    if ($keyVar) {
                        $this->registry->register($keyVar, $this->wrap($key), true);
                    }
                    $result = $this->evaluateStmts($expr->stmts);
                    if ($result instanceof Node\Stmt\Return_) {
                        return $this->debug($expr, $result);
                    }
                }
                return $this->debug($expr, null);
            case Node\Name::class:
                if (!isset($expr->parts) || count($expr->parts) != 1) {
                    return $this->error("Unsupported Name expression", $expr);
                }
                return $this->debug($expr, $expr->parts[0]);
            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param type $expr
     * @return mixed
     */
    protected function evaluatePropertyFetch($expr)
    {
        $propertyName = $this->evl($expr->name);
        $variable = $this->evl($expr->var);
        if ($variable instanceof Node\Expr\ArrayItem) {
            $variable = $this->evl($variable->value);
        }
        if ($variable instanceof Node\Expr\Array_) {
            $variable = $this->evl($variable);
        }
        if (!isset($variable) && isset($expr->var->name) && is_string($expr->var->name)) {
            return $this->error("Unknown variable \${$expr->var->name}", $expr);
        }
        
        if (is_array($variable) && isset($variable[$propertyName])) {
            return $this->debug($expr, $variable[$propertyName]);
        } elseif (is_object($variable) && isset($variable->{$propertyName})) {
            return $this->debug($expr, $variable->{$propertyName});
        } elseif (is_object($variable)) {
            return $this->error("Unsupported PropertyFetch expression - " . get_class($variable), $expr);
        }
        return $this->error("Unsupported PropertyFetch expression", $expr);
    }

    /**
     * @param type $expr
     * @return mixed
     */
    protected function evaluateMethodCall($expr)
    {
        $methodName = $this->evl($expr->name);
        $variable = $this->evl($expr->var);
        if ($variable instanceof Node\Expr\ArrayItem) {
            $variable = $this->evl($variable->value);
        }
        if ($variable instanceof Node\Expr\Array_) {
            $variable = $this->evl($variable);
        }

        $method = null;
        $variableName = isset($expr->var->name) ? $expr->var->name : '';
        if (!isset($variable)) {
            return $this->error("Unsupported MethodCall expression"
                . " - Unkown variable \${$variableName}", $expr);
        }
        if (is_object($variable) && isset($variable->{$methodName}) && is_callable($variable->{$methodName})) {
            $method = $variable->{$methodName};
        } elseif (is_array($variable) && isset($variable[$methodName]) && is_callable($variable[$methodName])) {
            $method = $variable[$methodName];
        }
        if (!$method) {
            return $this->error("Unsupported MethodCall expression - Unkown method" . ( is_callable([
                $variable,
                $methodName
            ]) ? '1' : '0'), $expr);
        }
        $args = $this->evaluateArgs($expr);
        $result = $this->callFunction($method, $args);
        $result = $this->wrap($result);
        return $this->debug($expr, $result);
    }

    /**
     * @param mixed $method
     * @param array $args
     * @return type
     */
    protected function callFunction($method, $args = [])
    {
        return call_user_func_array($method, $args);
    }

    /**
     * @param type $expr
     * @return type
     */
    protected function evaluateFuncCall($expr)
    {
        if (isset($expr->name->parts)) {
            if (count($expr->name->parts) != 1) {
                return $this->error("Unsupported FuncCall expression", $expr);
            }

            $functionName = $expr->name->parts[0];
            $map = [];
            $isFunctionAllowed = in_array($functionName, $this->allowedFunctions)
                || in_array($functionName, array_keys($map));
            if (preg_match('@Callback$@', $functionName) && method_exists($this->callbackHandler, $functionName)) {
                $functionName = [ $this->callbackHandler, $functionName ];
            } else {
                if (!$isFunctionAllowed && function_exists($functionName)) {
                    return $this->error("Unauthorized function '{$functionName}'", $expr);
                } elseif (!$isFunctionAllowed) {
                    return $this->error("Unknown function '{$functionName}'", $expr);
                }

                if (isset($map[$functionName])) {
                    $functionName = $map[$functionName];
                }
            }

            $args = $this->evaluateArgs($expr);
            $result = $this->callFunction($functionName, $args);
            return $this->debug($expr, $result);
        } elseif ($expr->name instanceof Node\Expr\Variable) {
            $variable = $this->registry->get($expr->name->name);
            if (!isset($variable)) {
                return $this->error("Unsupported FuncCall expression - Unkown function", $expr);
            }

            if (!is_callable($variable)) {
                return $this->error("Unsupported FuncCall expression - Variable is not a function", $expr);
            }

            $args = $this->evaluateArgs($expr);
            $result = $this->callFunction($variable, $args);
            return $this->debug($expr, $result);
        } else {
            return $this->error("Unsupported FuncCall expression", $expr);
        }
    }

    /**
     * @param type $expr
     * @return array
     */
    protected function evaluateArgs($expr)
    {
        $args = [];
        foreach ($expr->args as $arg) {
            $args[] = $this->evl($arg->value);
        }
        return $args;
    }
}
