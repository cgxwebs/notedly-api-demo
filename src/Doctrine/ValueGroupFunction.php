<?php

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * ValueGroupFunction ::=
 *      "VALUE_GROUP" "("...StateFieldPathExpression | InputParameter [","] ")".
 */
class ValueGroupFunction extends FunctionNode
{
    /**
     * @var string Field | Input
     */
    private $type = '';

    private $bucket = [];

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $walked = array_map(function ($item) use ($sqlWalker) {
            return $item->dispatch($sqlWalker);
        }, $this->bucket);

        return '('.implode(', ', $walked).')';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $lexer = $parser->getLexer();

        do {
            // Make sure types are congruent
            if ('' == $this->type) {
                if ($lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
                    $this->type = 'Input';
                } else {
                    $this->type = 'Field';
                }
            }

            if ('Input' == $this->type) {
                $this->bucket[] = $parser->InputParameter();
            } else {
                $this->bucket[] = $parser->SingleValuedPathExpression();
            }

            if ($lexer->isNextToken(Lexer::T_COMMA)) {
                $parser->match(Lexer::T_COMMA);
            }
        } while (!$lexer->isNextToken(Lexer::T_CLOSE_PARENTHESIS));

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
