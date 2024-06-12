<?php

namespace Cxalloy\Haystack;

class HaystackToken
{
    public static HaystackToken $eof;
    public static HaystackToken $id;
    public static HaystackToken $num;
    public static HaystackToken $str;
    public static HaystackToken $ref;
    public static HaystackToken $symbol;
    public static HaystackToken $uri;
    public static HaystackToken $date;
    public static HaystackToken $time;
    public static HaystackToken $dateTime;
    public static HaystackToken $colon;
    public static HaystackToken $comma;
    public static HaystackToken $semicolon;
    public static HaystackToken $minus;
    public static HaystackToken $eq;
    public static HaystackToken $notEq;
    public static HaystackToken $lt;
    public static HaystackToken $lt2;
    public static HaystackToken $ltEq;
    public static HaystackToken $gt;
    public static HaystackToken $gt2;
    public static HaystackToken $gtEq;
    public static HaystackToken $lbracket;
    public static HaystackToken $rbracket;
    public static HaystackToken $lbrace;
    public static HaystackToken $rbrace;
    public static HaystackToken $lparen;
    public static HaystackToken $rparen;
    public static HaystackToken $arrow;
    public static HaystackToken $slash;
    public static HaystackToken $assign;
    public static HaystackToken $bang;
    public static HaystackToken $nl;

    public string $dis;
    public bool $literal;

    public function __construct(string $dis, bool $literal = false)
    {
        $this->dis = $dis;
        $this->literal = $literal;
    }

    public function equals($o): bool
    {
        if ($this === $o) {
            return true;
        }
        if ($o === null || get_class($this) !== get_class($o)) {
            return false;
        }

        $that = $o;
        if ($this->literal !== $that->literal) {
            return false;
        }
        return $this->dis === $that->dis;
    }

    public function hashCode(): int
    {
        $result = hash('crc32', $this->dis);
        $result = 31 * $result + ($this->literal ? 1 : 0);
        return $result;
    }

    public function __toString(): string
    {
        return $this->dis;
    }
}

// Initialize static properties
HaystackToken::$eof = new HaystackToken("eof");
HaystackToken::$id = new HaystackToken("identifier");
HaystackToken::$num = new HaystackToken("Number", true);
HaystackToken::$str = new HaystackToken("Str", true);
HaystackToken::$ref = new HaystackToken("Ref", true);
HaystackToken::$symbol = new HaystackToken("Symbol", true);
HaystackToken::$uri = new HaystackToken("Uri", true);
HaystackToken::$date = new HaystackToken("Date", true);
HaystackToken::$time = new HaystackToken("Time", true);
HaystackToken::$dateTime = new HaystackToken("DateTime", true);
HaystackToken::$colon = new HaystackToken(":");
HaystackToken::$comma = new HaystackToken(",");
HaystackToken::$semicolon = new HaystackToken(";");
HaystackToken::$minus = new HaystackToken("-");
HaystackToken::$eq = new HaystackToken("==");
HaystackToken::$notEq = new HaystackToken("!=");
HaystackToken::$lt = new HaystackToken("<");
HaystackToken::$lt2 = new HaystackToken("<<");
HaystackToken::$ltEq = new HaystackToken("<=");
HaystackToken::$gt = new HaystackToken(">");
HaystackToken::$gt2 = new HaystackToken(">>");
HaystackToken::$gtEq = new HaystackToken(">=");
HaystackToken::$lbracket = new HaystackToken("[");
HaystackToken::$rbracket = new HaystackToken("]");
HaystackToken::$lbrace = new HaystackToken("{");
HaystackToken::$rbrace = new HaystackToken("}");
HaystackToken::$lparen = new HaystackToken("(");
HaystackToken::$rparen = new HaystackToken(")");
HaystackToken::$arrow = new HaystackToken("->");
HaystackToken::$slash = new HaystackToken("/");
HaystackToken::$assign = new HaystackToken("=");
HaystackToken::$bang = new HaystackToken("!");
HaystackToken::$nl = new HaystackToken("newline");
