<?php

namespace Cxalloy\Haystack;

use RuntimeException;
use InvalidArgumentException;

/**
 * HZincReader reads grids using the Zinc format.
 *
 * @see <a href='http://project-haystack.org/doc/Zinc'>Project Haystack</a>
 */
class HZincReader extends HGridReader
{
    private HaystackTokenizer $tokenizer;
    private HaystackToken $cur;
    private mixed $curVal;
    private int $curLine;
    private HaystackToken $peek;
    private mixed $peekVal;
    private int $peekLine;
    private int $version = 3;
    private bool $isTop = true;

    ////////////////////////////////////////////////////////////////////////
    // Construction
    ////////////////////////////////////////////////////////////////////////

    /** Read from UTF-8 input stream. */
    public function __construct($in)
    {
        try {
            $this->tokenizer = new HaystackTokenizer(new \BufferedReader(new \InputStreamReader($in, "UTF-8")));
            $this->init();
        } catch (\IOException $e) {
            throw $this->err("init failed", $e);
        }
    }

    /** Read from in-memory string. */
    public static function fromString(string $in): self
    {
        $instance = new self(null);
        $instance->tokenizer = new HaystackTokenizer(new \StringReader($in));
        $instance->init();
        return $instance;
    }

    private function init(): void
    {
        $this->consume();
        $this->consume();
    }

    ////////////////////////////////////////////////////////////////////////
    // Public
    ////////////////////////////////////////////////////////////////////////

    /** Close underlying input stream */
    public function close(): void
    {
        $this->tokenizer->close();
    }

    /** Read a value and auto-close the stream */
    public function readVal(bool $close = true): HVal
    {
        try {
            $val = null;
            if ($this->cur === HaystackToken::ID && $this->curVal === "ver") {
                $val = $this->parseGrid();
            } else {
                $val = $this->parseVal();
            }
            $this->verify(HaystackToken::EOF);
            return $val;
        } finally {
            if ($close) {
                $this->close();
            }
        }
    }

    /** Convenience for {@link #readVal} as Grid */
    public function readGrid(): HGrid
    {
        return $this->readVal(true);
    }

    /** Read a list of grids separated by blank line from stream */
    public function readGrids(): array
    {
        $acc = [];
        while ($this->cur === HaystackToken::ID) {
            $acc[] = $this->parseGrid();
        }
        return $acc;
    }

    /**
     * Read a set of tags as {@code name:val} pairs separated by space. The
     * tags may optionally be surrounded by '{' and '}'
     */
    public function readDict(): HDict
    {
        return $this->parseDict();
    }

    /**
     * Read scalar value: Bool, Int, Str, Uri, etc
     *
     * @deprecated Will be removed in future release.
     */
    public function readScalar(): HVal
    {
        return $this->parseVal();
    }

    ////////////////////////////////////////////////////////////////////////
    // Utils
    ////////////////////////////////////////////////////////////////////////

    private function parseVal(): HVal
    {
        // if it's an id
        if ($this->cur === HaystackToken::ID) {
            $id = $this->curVal;
            $this->consume(HaystackToken::ID);

            // check for coord or xstr
            if ($this->cur === HaystackToken::LPAREN) {
                if ($this->peek === HaystackToken::NUM) {
                    return $this->parseCoord($id);
                } else {
                    return $this->parseXStr($id);
                }
            }

            // check for keyword
            return match ($id) {
                "T" => HBool::TRUE,
                "F" => HBool::FALSE,
                "N" => null,
                "M" => HMarker::VAL,
                "NA" => HNA::VAL,
                "R" => HRemove::VAL,
                "NaN" => HNum::NaN,
                "INF" => HNum::POS_INF,
                default => throw $this->err("Unexpected identifier: " . $id),
            };
        }

        // literals
        if ($this->cur->isLiteral()) {
            return $this->parseLiteral();
        }

        // -INF
        if ($this->cur === HaystackToken::MINUS && $this->peekVal === "INF") {
            $this->consume(HaystackToken::MINUS);
            $this->consume(HaystackToken::ID);
            return HNum::NEG_INF;
        }

        // nested collections
        return match ($this->cur) {
            HaystackToken::LBRACKET => $this->parseList(),
            HaystackToken::LBRACE => $this->parseDict(),
            HaystackToken::LT2 => $this->parseGrid(),
            default => throw $this->err("Unexpected token: " . $this->curToStr()),
        };
    }

    private function parseCoord(string $id): HCoord
    {
        if ($id !== "C") {
            throw $this->err("Expecting 'C' for coord, not " . $id);
        }
        $this->consume(HaystackToken::LPAREN);
        $lat = $this->consumeNum();
        $this->consume(HaystackToken::COMMA);
        $lng = $this->consumeNum();
        $this->consume(HaystackToken::RPAREN);
        return HCoord::make($lat->val, $lng->val);
    }

    private function parseXStr(string $id): HVal
    {
        if (!ctype_upper($id[0])) {
            throw $this->err("Invalid XStr type: " . $id);
        }
        $this->consume(HaystackToken::LPAREN);
        if ($this->version < 3 && $id === "Bin") {
            return $this->parseBinObsolete();
        }
        $val = $this->consumeStr();
        $this->consume(HaystackToken::RPAREN);
        return HXStr::decode($id, $val);
    }

    private function parseBinObsolete(): HBin
    {
        $s = '';
        while ($this->cur !== HaystackToken::RPAREN && $this->cur !== HaystackToken::EOF) {
            $s .= $this->curVal ?? $this->cur->dis;
            $this->consume();
        }
        $this->consume(HaystackToken::RPAREN);
        return HBin::make($s);
    }

    private function parseLiteral(): HVal
    {
        $val = $this->curVal;
        if ($this->cur === HaystackToken::REF && $this->peek === HaystackToken::STR) {
            $val = HRef::make($val->val, $this->peekVal->val);
            $this->consume(HaystackToken::REF);
        }
        $this->consume();
        return $val;
    }

    private function parseList(): HList
    {
        $arr = [];
        $this->consume(HaystackToken::LBRACKET);
        while ($this->cur !== HaystackToken::RBRACKET && $this->cur !== HaystackToken::EOF) {
            $arr[] = $this->parseVal();
            if ($this->cur !== HaystackToken::COMMA) {
                break;
            }
            $this->consume(HaystackToken::COMMA);
        }
        $this->consume(HaystackToken::RBRACKET);
        return HList::make($arr);
    }

    private function parseDict(): HDict
    {
        $db = new HDictBuilder();
        $braces = $this->cur === HaystackToken::LBRACE;
        if ($braces) {
            $this->consume(HaystackToken::LBRACE);
        }
        while ($this->cur === HaystackToken::ID) {
            // tag name
            $id = $this->consumeTagName();
            if (!ctype_lower($id[0])) {
                throw $this->err("Invalid dict tag name: " . $id);
            }

            // tag value
            $val = HMarker::VAL;
            if ($this->cur === HaystackToken::COLON) {
                $this->consume(HaystackToken::COLON);
                $val = $this->parseVal();
            }
            $db->add($id, $val);
        }
        if ($braces) {
            $this->consume(HaystackToken::RBRACE);
        }
        return $db->toDict();
    }

    private function parseGrid(): HGrid
    {
        $nested = $this->cur === HaystackToken::LT2;
        if ($nested) {
            $this->consume(HaystackToken::LT2);
            if ($this->cur === HaystackToken::NL) {
                $this->consume(HaystackToken::NL);
            }
        }

        // ver:"3.0"
        if ($this->cur !== HaystackToken::ID || $this->curVal !== "ver") {
            throw $this->err("Expecting grid 'ver' identifier, not " . $this->curToStr());
        }
        $this->consume();
        $this->consume(HaystackToken::COLON);
        $this->version = $this->checkVersion($this->consumeStr());

        // grid meta
        $gb = new HGridBuilder();
        if ($this->cur === HaystackToken::ID) {
            $gb->meta()->add($this->parseDict());
        }
        $this->consume(HaystackToken::NL);

        // column definitions
        $numCols = 0;
        while ($this->cur === HaystackToken::ID) {
            ++$numCols;
            $name = $this->consumeTagName();
            $colMeta = HDict::EMPTY;
            if ($this->cur === HaystackToken::ID) {
                $colMeta = $this->parseDict();
            }
            $gb->addCol($name)->add($colMeta);
            if ($this->cur !== HaystackToken::COMMA) {
                break;
            }
            $this->consume(HaystackToken::COMMA);
        }
        if ($numCols === 0) {
            throw $this->err("No columns defined");
        }
        $this->consume(HaystackToken::NL);

        // grid rows
        while (true) {
            if ($this->cur === HaystackToken::NL || $this->cur === HaystackToken::EOF || ($nested && $this->cur === HaystackToken::GT2)) {
                break;
            }

            // read cells
            $cells = array_fill(0, $numCols, null);
            for ($i = 0; $i < $numCols; ++$i) {
                if ($this->cur === HaystackToken::COMMA || $this->cur === HaystackToken::NL || $this->cur === HaystackToken::EOF) {
                    $cells[$i] = null;
                } else {
                    $cells[$i] = $this->parseVal();
                }
                if ($i + 1 < $numCols) {
                    $this->consume(HaystackToken::COMMA);
                }
            }
            $gb->addRow($cells);

            // newline or end
            if ($nested && $this->cur === HaystackToken::GT2) {
                break;
            }
            if ($this->cur === HaystackToken::EOF) {
                break;
            }
            $this->consume(HaystackToken::NL);
        }

        if ($this->cur === HaystackToken::NL) {
            $this->consume(HaystackToken::NL);
        }
        if ($nested) {
            $this->consume(HaystackToken::GT2);
        }
        return $gb->toGrid();
    }

    private function checkVersion(string $s): int
    {
        return match ($s) {
            "3.0" => 3,
            "2.0" => 2,
            default => throw $this->err("Unsupported version " . $s),
        };
    }

    ////////////////////////////////////////////////////////////////////////
    // Token Reads
    ////////////////////////////////////////////////////////////////////////

    private function consumeTagName(): string
    {
        $this->verify(HaystackToken::ID);
        $id = $this->curVal;
        if (empty($id) || !ctype_lower($id[0])) {
            throw $this->err("Invalid dict tag name: " . $id);
        }
        $this->consume(HaystackToken::ID);
        return $id;
    }

    private function consumeNum(): HNum
    {
        $this->verify(HaystackToken::NUM);
        $num = $this->curVal;
        $this->consume(HaystackToken::NUM);
        return $num;
    }

    private function consumeStr(): string
    {
        $this->verify(HaystackToken::STR);
        $val = $this->curVal->val;
        $this->consume(HaystackToken::STR);
        return $val;
    }

    private function verify(HaystackToken $expected): void
    {
        if ($this->cur !== $expected) {
            throw $this->err("Expected " . $expected . " not " . $this->curToStr());
        }
    }

    private function curToStr(): string
    {
        return $this->curVal !== null ? $this->cur . " " . $this->curVal : (string)$this->cur;
    }

    private function consume(?HaystackToken $expected = null): void
    {
        if ($expected !== null) {
            $this->verify($expected);
        }

        $this->cur = $this->peek;
        $this->curVal = $this->peekVal;
        $this->curLine = $this->peekLine;

        $this->peek = $this->tokenizer->next();
        $this->peekVal = $this->tokenizer->val;
        $this->peekLine = $this->tokenizer->line;
    }

    private function err(string $msg, ?\Exception $e = null): ParseException
    {
        return new ParseException($msg . " [line " . $this->curLine . "]", $e);
    }
}
