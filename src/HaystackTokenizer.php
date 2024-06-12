<?php
declare(strict_types=1);
namespace Cxalloy\Haystack;

use Cxalloy\Haystack\Latest;
use InvalidArgumentException;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Exception\TransferException;
use \Exception;
use GuzzleHttp\Psr7\Utils;


/**
 * Stream based tokenizer for Haystack formats such as Zinc and Filters
 */
class HaystackTokenizer
{
    public HaystackToken $tok; // current token type
    public mixed $val;         // token literal or identifier
    public int $line = 1;      // current line number

    private Stream $in;       // underlying stream
    private int $cur;          // current char
    private int $peek;         // next char
    private const EOF = -1;

    ////////////////////////////////////////////////////////////////////////
    // Construction
    ////////////////////////////////////////////////////////////////////////

    public function __construct(Stream $in)
    {
        $this->in = $in;
        $this->tok = HaystackToken::$eof;
        $this->consume();
        $this->consume();
    }

    public function close(): bool
    {
        try {
            $this->in->close();
            return true;
        } catch (TransferException $e) {
            return false;
        }
    }

    ////////////////////////////////////////////////////////////////////////
    // Tokenizing
    ////////////////////////////////////////////////////////////////////////

    public function next(): HaystackToken
    {
        // reset
        $this->val = null;

        // skip non-meaningful whitespace and comments
        while (true) {
            // treat space, tab, non-breaking space as whitespace
            if ($this->cur === ' ' || $this->cur === '\t' || $this->cur === 0xa0) {
                $this->consume();
                continue;
            }

            // comments
            if ($this->cur === '/') {
                if ($this->peek === '/') {
                    $this->skipCommentsSL();
                    continue;
                }
                if ($this->peek === '*') {
                    $this->skipCommentsML();
                    continue;
                }
            }
            break;
        }

        // newlines
        if ($this->cur === '\n' || $this->cur === '\r') {
            if ($this->cur === '\r' && $this->peek === '\n') {
                $this->consume('\r');
            }
            $this->consume();
            ++$this->line;
            return $this->tok = HaystackToken::$nl;
        }

        // handle various starting chars
        if ($this->isIdStart($this->cur)) {
            return $this->tok = $this->id();
        }
        if ($this->cur === '"') {
            return $this->tok = $this->str();
        }
        if ($this->cur === '@') {
            return $this->tok = $this->ref();
        }
        if ($this->cur === '^') {
            return $this->tok = $this->symbol();
        }
        if ($this->isDigit($this->cur)) {
            return $this->tok = $this->num();
        }
        if ($this->cur === '`') {
            return $this->tok = $this->uri();
        }
        if ($this->cur === '-' && $this->isDigit($this->peek)) {
            return $this->tok = $this->num();
        }
        return $this->tok = $this->operator();
    }

    ////////////////////////////////////////////////////////////////////////
    // Token Productions
    ////////////////////////////////////////////////////////////////////////

    private function id(): HaystackToken
    {
        $s = '';
        while ($this->isIdPart($this->cur)) {
            $s .= chr($this->cur);
            $this->consume();
        }
        $this->val = $s;
        return HaystackToken::$id;
    }


    private static function isIdStart(int $cur): bool
    {
        return ('a' <= $cur && $cur <= 'z') || ('A' <= $cur && $cur <= 'Z');
    }

    private static function isIdPart(int $cur): bool
    {
        return self::isIdStart($cur) || self::isDigit($cur) || $cur === '_';
    }

    private static function isDigit(int $cur): bool
    {
        return '0' <= $cur && $cur <= '9';
    }

    private function num(): HaystackToken
    {
        // hex number (no unit allowed)
        $isHex = $this->cur === '0' && $this->peek === 'x';
        if ($isHex) {
            $this->consume('0');
            $this->consume('x');
            $s = '';
            while (true) {
                if ($this->isHex($this->cur)) {
                    $s .= chr($this->cur);
                    $this->consume();
                    continue;
                }
                if ($this->cur === '_') {
                    $this->consume();
                    continue;
                }
                break;
            }
            $this->val = Latest\HNum::make(hexdec($s));
            return HaystackToken::$num;
        }

        // consume all things that might be part of this number token
        $s = '';
        $s .= chr($this->cur);
        $this->consume();
        $colons = 0;
        $dashes = 0;
        $unitIndex = 0;
        $exp = false;
        while (true) {
            if (!ctype_digit(chr($this->cur))) {
                if ($exp && ($this->cur === '+' || $this->cur === '-')) {
                } elseif ($this->cur === '-') {
                    ++$dashes;
                } elseif ($this->cur === ':' && ctype_digit(chr($this->peek))) {
                    ++$colons;
                } elseif (($exp || $colons >= 1) && $this->cur === '+') {
                } elseif ($this->cur === '.') {
                    if (!ctype_digit(chr($this->peek))) {
                        break;
                    }
                } elseif (($this->cur === 'e' || $this->cur === 'E') && ($this->peek === '-' || $this->peek === '+' || ctype_digit(chr($this->peek)))) {
                    $exp = true;
                } elseif (ctype_alpha(chr($this->cur)) || $this->cur === '%' || $this->cur === '|' || $this->cur === '/' || $this->cur > 128) {
                    if ($unitIndex === 0) {
                        $unitIndex = strlen($s);
                    }
                } elseif ($this->cur === '_') {
                    if ($unitIndex === 0 && ctype_digit(chr($this->peek))) {
                        $this->consume();
                        continue;
                    } else {
                        if ($unitIndex === 0) {
                            $unitIndex = strlen($s);
                        }
                    }
                } else {
                    break;
                }
            }
            $s .= chr($this->cur);
            $this->consume();
        }

        if ($dashes === 2 && $colons === 0) {
            return $this->date($s);
        }
        if ($dashes === 0 && $colons >= 1) {
            return $this->time($s, $colons === 1);
        }
        if ($dashes >= 2) {
            return $this->dateTime($s);
        }
        return $this->number($s, $unitIndex);
    }


    private static function isHex(int $cur): bool
    {
        $cur = strtolower(chr($cur));
        return ('a' <= $cur && $cur <= 'f') || self::isDigit(ord($cur));
    }

    private function date(string $s): HaystackToken
    {
        try {
            $this->val = HDate::make($s);
            return HaystackToken::$date;
        } catch (\ParseException $e) {
            throw $this->err($e->getMessage());
        }
    }

    /** we don't require hour to be two digits and we don't require seconds */
    private function time(\StringBuffer $s, bool $addSeconds): HaystackToken
    {
        try {
            if ($s->charAt(1) === ':') {
                $s->insert(0, '0');
            }
            if ($addSeconds) {
                $s->append(":00");
            }
            $this->val = HTime::make($s->toString());
            return HaystackToken::$time;
        } catch (\ParseException $e) {
            throw $this->err($e->getMessage());
        }
    }

    private function dateTime(\StringBuffer $s): HaystackToken
    {
        // xxx timezone
        if ($this->cur !== ' ' || !ctype_upper(chr($this->peek))) {
            if ($s->charAt($s->length() - 1) === 'Z') {
                $s->append(" UTC");
            } else {
                throw $this->err("Expecting timezone");
            }
        } else {
            $this->consume();
            $s->append(' ');
            while ($this->isIdPart($this->cur)) {
                $s->append(chr($this->cur));
                $this->consume();
            }
            // handle GMT+xx or GMT-xx
            if (($this->cur === '+' || $this->cur === '-') && str_ends_with($s->toString(), "GMT")) {
                $s->append(chr($this->cur));
                $this->consume();
                while ($this->isDigit($this->cur)) {
                    $s->append(chr($this->cur));
                    $this->consume();
                }
            }
        }

        try {
            $this->val = HDateTime::make($s->toString());
            return HaystackToken::$dateTime;
        } catch (\ParseException $e) {
            throw $this->err($e->getMessage());
        }
    }

    private function number(string $s, int $unitIndex): HaystackToken
    {
        try {
            if ($unitIndex === 0) {
                $this->val = HNum::make((float)$s);
            } else {
                $doubleStr = substr($s, 0, $unitIndex);
                $unitStr = substr($s, $unitIndex);
                $this->val = HNum::make((float)$doubleStr, $unitStr);
            }
        } catch (\Exception $e) {
            throw $this->err("Invalid Number literal: " . $s);
        }
        return HaystackToken::$num;
    }

    private function str(): HaystackToken
    {
        $this->consume('"');
        $s = new \StringBuffer();
        while (true) {
            if ($this->cur === self::EOF) {
                throw $this->err("Unexpected end of str");
            }
            if ($this->cur === '"') {
                $this->consume('"');
                break;
            }
            if ($this->cur === '\\') {
                $s->append($this->escape());
                continue;
            }
            $s->append(chr($this->cur));
            $this->consume();
        }
        $this->val = HStr::make($s->toString());
        return HaystackToken::$str;
    }

    private function symbol(): HaystackToken
    {
        $this->consume('^');
        $s = new \StringBuffer();
        while (true) {
            if (HRef::isIdChar(chr($this->cur))) {
                $s->append(chr($this->cur));
                $this->consume();
            } else {
                break;
            }
        }
        if ($s->length() === 0) {
            throw $this->err("Invalid empty symbol");
        }
        $this->val = HSymbol::make($s->toString());
        return HaystackToken::$symbol;
    }

    private function ref(): HaystackToken
    {
        $this->consume('@');
        $s = new \StringBuffer();
        while (true) {
            if (HRef::isIdChar(chr($this->cur))) {
                $s->append(chr($this->cur));
                $this->consume();
            } else {
                break;
            }
        }
        $this->val = HRef::make($s->toString(), null);
        return HaystackToken::$ref;
    }

    private function uri(): HaystackToken
    {
        $this->consume('`');
        $s = new \StringBuffer();
        while (true) {
            if ($this->cur === '`') {
                $this->consume('`');
                break;
            }
            if ($this->cur === self::EOF || $this->cur === '\n') {
                throw $this->err("Unexpected end of uri");
            }
            if ($this->cur === '\\') {
                switch ($this->peek) {
                    case ':':
                    case '/':
                    case '?':
                    case '#':
                    case '[':
                    case ']':
                    case '@':
                    case '\\':
                    case '&':
                    case '=':
                    case ';':
                        $s->append(chr($this->cur));
                        $s->append(chr($this->peek));
                        $this->consume();
                        $this->consume();
                        break;
                    default:
                        $s->append($this->escape());
                }
            } else {
                $s->append(chr($this->cur));
                $this->consume();
            }
        }
        $this->val = HUri::make($s->toString());
        return HaystackToken::$uri;
    }

    private function escape(): string
    {
        $this->consume('\\');
        return match ($this->cur) {
            'b' => $this->consume() && '\b',
            'f' => $this->consume() && '\f',
            'n' => $this->consume() && '\n',
            'r' => $this->consume() && '\r',
            't' => $this->consume() && '\t',
            '"' => $this->consume() && '"',
            '$' => $this->consume() && '$',
            '\'' => $this->consume() && '\'',
            '`' => $this->consume() && '`',
            '\\' => $this->consume() && '\\',
            'u' => $this->unicodeEscape(),
            default => throw $this->err("Invalid escape sequence: " . chr($this->cur)),
        };
    }

    private function unicodeEscape(): string
    {
        $esc = new \StringBuffer();
        $this->consume('u');
        for ($i = 0; $i < 4; ++$i) {
            $esc->append(chr($this->cur));
            $this->consume();
        }
        try {
            return chr(hexdec($esc->toString()));
        } catch (\NumberFormatException $e) {
            throw new \ParseException("Invalid unicode escape: " . $esc->toString());
        }
    }

    /** parse a symbol token (typically into an operator). */
    private function operator(): HaystackToken
    {
        $c = $this->cur;
        $this->consume();
        return match ($c) {
            ',' => HaystackToken::$comma,
            ':' => HaystackToken::$colon,
            ';' => HaystackToken::$semicolon,
            '[' => HaystackToken::$lbracket,
            ']' => HaystackToken::$rbracket,
            '{' => HaystackToken::$lbrace,
            '}' => HaystackToken::$rbrace,
            '(' => HaystackToken::$lparen,
            ')' => HaystackToken::$rparen,
            '<' => match ($this->cur) {
                '<' => $this->consume('<<') && HaystackToken::$lt2,
                '=' => $this->consume('=') && HaystackToken::$ltEq,
                default => HaystackToken::$lt,
            },
            '>' => match ($this->cur) {
                '>' => $this->consume('>>') && HaystackToken::$gt2,
                '=' => $this->consume('=') && HaystackToken::$gtEq,
                default => HaystackToken::$gt,
            },
            '-' => match ($this->cur) {
                '>' => $this->consume('>') && HaystackToken::$arrow,
                default => HaystackToken::$minus,
            },
            '=' => match ($this->cur) {
                '=' => $this->consume('=') && HaystackToken::$eq,
                default => HaystackToken::$assign,
            },
            '!' => match ($this->cur) {
                '=' => $this->consume('=') && HaystackToken::$notEq,
                default => HaystackToken::$bang,
            },
            '/' => HaystackToken::$slash,
            self::EOF => HaystackToken::$eof,
            default => throw $this->err("Unexpected symbol: '" . chr($c) . "' (0x" . dechex($c) . ")"),
        };
    }

    ////////////////////////////////////////////////////////////////////////
    // Comments
    ////////////////////////////////////////////////////////////////////////

    private function skipCommentsSL(): void
    {
        $this->consume('/');
        $this->consume('/');
        while (true) {
            if ($this->cur === '\n' || $this->cur === self::EOF) {
                break;
            }
            $this->consume();
        }
    }

    private function skipCommentsML(): void
    {
        $this->consume('/');
        $this->consume('*');
        $depth = 1;
        while (true) {
            if ($this->cur === '*' && $this->peek === '/') {
                $this->consume('*');
                $this->consume('/');
                $depth--;
                if ($depth <= 0) {
                    break;
                }
            }
            if ($this->cur === '/' && $this->peek === '*') {
                $this->consume('/');
                $this->consume('*');
                $depth++;
                continue;
            }
            if ($this->cur === '\n') {
                ++$this->line;
            }
            if ($this->cur === self::EOF) {
                throw $this->err("Multi-line comment not closed");
            }
            $this->consume();
        }
    }

    ////////////////////////////////////////////////////////////////////////
    // Error Handling
    ////////////////////////////////////////////////////////////////////////

    private function err(string $msg): \ParseException
    {
        return new \ParseException($msg . " [line " . $this->line . "]");
    }

    ////////////////////////////////////////////////////////////////////////
    // Char
    ////////////////////////////////////////////////////////////////////////

    private function consume(int $expected = null): void
    {
        if ($expected !== null && $this->cur !== $expected) {
            throw $this->err("Expected " . chr($expected));
        }
        $this->consumeChar();
    }

    private function consumeChar(): void
    {
        try {
            $this->cur = $this->peek;
            $this->peek = $this->in->read();
        } catch (\IOException $e) {
            $this->cur = self::EOF;
            $this->peek = self::EOF;
        }
    }
}
