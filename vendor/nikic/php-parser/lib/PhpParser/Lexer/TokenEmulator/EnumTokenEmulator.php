<?php

declare (strict_types=1);
namespace PhpParser\Lexer\TokenEmulator;

use PhpParser\PhpVersion;
final class EnumTokenEmulator extends \PhpParser\Lexer\TokenEmulator\KeywordEmulator
{
    public function getPhpVersion() : PhpVersion
    {
        return PhpVersion::fromComponents(8, 1);
    }
    public function getKeywordString() : string
    {
        return 'enum';
    }
    public function getKeywordToken() : int
    {
        return \T_ENUM;
    }
    protected function isKeywordContext(array $tokens, int $pos) : bool
    {
        return parent::isKeywordContext($tokens, $pos) && isset($tokens[$pos + 2]) && (\is_array($tokens[$pos + 1]) ? $tokens[$pos + 1][0] : $tokens[$pos + 1]) === \T_WHITESPACE && (\is_array($tokens[$pos + 2]) ? $tokens[$pos + 2][0] : $tokens[$pos + 2]) === \T_STRING;
    }
}
