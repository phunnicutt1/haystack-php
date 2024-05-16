<?php
namespace Cxalloy\Haystack;

/*use HBin;
use HBool;
use HClient;
use HCol;
use HCoord;
use HDate;
use HDateTime;
use HDateTimeRange;
use HDict;
use HDictBuilder;
use HFilter;
use HGrid;
use HGridBuilder;
use HGridFormat;
use HHisItem;
use HJsonReader;
use HJsonWriter;
use HMarker;
use HNum;
use HProj;
use HRef;
use HRemove;
use HRow;
use HStr;
use HTime;
use HTimeZone;
use HUri;
use HVal;
use HWatch;
use HZincReader;
use HZincWriter;*/

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved class and variable names as much as possible.
 * 3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 4. Replaced JavaScript's `require` statements with PHP's `use` statements for class imports.
 * 5. Replaced JavaScript's object literal syntax with PHP's class instantiation syntax.
 * 6. Replaced JavaScript's `null` with PHP's `null`.
 * 7. Replaced JavaScript's `undefined` with PHP's `null`.
 */


class Haystack
{
    public static $HBin = \Cxalloy\Haystack\HBin::class;
    public static $HBool = \Cxalloy\Haystack\HBool::class;
    public static $HCol = \Cxalloy\Haystack\HCol::class;
    public static $HCoord = \Cxalloy\Haystack\HCoord::class;
    public static $HDate = \Cxalloy\Haystack\HDate::class;
    public static $HDateTime = \Cxalloy\Haystack\HDateTime::class;
    public static $HDateTimeRange = \Cxalloy\Haystack\HDateTimeRange::class;
    public static $HDictBuilder = \Cxalloy\Haystack\HDictBuilder::class;
    public static $HDict = \Cxalloy\Haystack\HDict::class;
    public static $HFilter = \Cxalloy\Haystack\HFilter::class;
    public static $HGridBuilder = \Cxalloy\Haystack\HGridBuilder::class;
    public static $HGrid = \Cxalloy\Haystack\HGrid::class;
    public static $HHisItem = \Cxalloy\Haystack\HHisItem::class;
    public static $HMarker = \Cxalloy\Haystack\HMarker::class;
    public static $HNum = \Cxalloy\Haystack\HNum::class;
    public static $HProj = \Cxalloy\Haystack\HProj::class;
    public static $HRef = \Cxalloy\Haystack\HRef::class;
    public static $HRemove = \Cxalloy\Haystack\HRemove::class;
    public static $HRow = \Cxalloy\Haystack\HRow::class;
    public static $HStr = \Cxalloy\Haystack\HStr::class;
    public static $HTime = \Cxalloy\Haystack\HTime::class;
    public static $HTimeZone = \Cxalloy\Haystack\HTimeZone::class;
    public static $HUri = \Cxalloy\Haystack\HUri::class;
    public static $HVal = \Cxalloy\Haystack\HVal::class;
    public static $HWatch = \Cxalloy\Haystack\HWatch::class;
    public static $HClient = \Cxalloy\Haystack\HClient::class;
    //public static $HCsvWriter = HCsvWriter::class;
    public static $HGridFormat = \Cxalloy\Haystack\HGridFormat::class;
    public static $HJsonReader = \Cxalloy\Haystack\HJsonReader::class;
    public static $HJsonWriter = \Cxalloy\Haystack\HJsonWriter::class;
    public static $HZincReader = \Cxalloy\Haystack\HZincReader::class;
    public static $HZincWriter = \Cxalloy\Haystack\HZincWriter::class;

	public function say_hello() {
		echo 'Hello!';
	}
}
