<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * HClient manages a logical connection to a HTTP REST haystack server.
 *
 * @see <a href='http://project-haystack.org/doc/Rest'>Project Haystack</a>
 */
class HClient extends HProj
{
    private string $uri;
    private AuthClientContext $auth;
    private int $connectTimeout = 60000;
    private int $readTimeout = 60000;
    private int $version = 2;
    private array $watches = [];
    private Client $httpClient;

    public function __construct(string $uri, string $user, string $pass)
    {
        $this->uri = $this->checkUri($uri);
        $this->auth = new AuthClientContext($this->uri . "about", $user, $pass);
        $this->httpClient = new Client([
            'base_uri' => $this->uri,
            'timeout' => $this->connectTimeout / 1000,
            'auth' => [$user, $pass]
        ]);
    }

    public static function open(string $uri, string $user, string $pass): HClient
    {
        return (new HClient($uri, $user, $pass))->open();
    }

    public static function openWithTimeouts(string $uri, string $user, string $pass, int $connectTimeout, int $readTimeout): HClient
    {
        return (new HClient($uri, $user, $pass))->setTimeouts($connectTimeout, $readTimeout)->open();
    }

    private function checkUri(string $uri): string
    {
        if (!str_starts_with($uri, "http://") && !str_starts_with($uri, "https://")) {
            throw new InvalidArgumentException("Invalid uri format: " . $uri);
        }
        if (!str_ends_with($uri, "/")) {
            $uri .= "/";
        }
        return $uri;
    }

    public function setConnectTimeout(int $timeout): self
    {
        if ($timeout < 0) {
            throw new InvalidArgumentException("Invalid timeout: " . $timeout);
        }
        $this->connectTimeout = $timeout;
        return $this;
    }

    public function setReadTimeout(int $timeout): self
    {
        if ($timeout < 0) {
            throw new InvalidArgumentException("Invalid timeout: " . $timeout);
        }
        $this->readTimeout = $timeout;
        return $this;
    }

    public function setTimeouts(int $connectTimeout, int $readTimeout): self
    {
        return $this->setConnectTimeout($connectTimeout)->setReadTimeout($readTimeout);
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function open(): self
    {
        $this->auth->connectTimeout = $this->connectTimeout;
        $this->auth->readTimeout = $this->readTimeout;
        $this->auth->open();
        return $this;
    }

    public function close(): HGrid
    {
        return $this->call("close", HGrid::EMPTY);
    }

    public function about(): HDict
    {
        return $this->call("about", HGrid::EMPTY)->row(0);
    }

    public function ops(): HGrid
    {
        return $this->call("ops", HGrid::EMPTY);
    }

    public function formats(): HGrid
    {
        return $this->call("formats", HGrid::EMPTY);
    }

    protected function onReadById(HRef $id): ?HDict
    {
        $res = $this->readByIds([$id], false);
        if ($res->isEmpty()) {
            return null;
        }
        $rec = $res->row(0);
        if ($rec->missing("id")) {
            return null;
        }
        return $rec;
    }

    protected function onReadByIds(array $ids): HGrid
    {
        $b = new HGridBuilder();
        $b->addCol("id");
        foreach ($ids as $id) {
            $b->addRow([$id]);
        }
        $req = $b->toGrid();
        return $this->call("read", $req);
    }

    protected function onReadAll(string $filter, int $limit): HGrid
    {
        $b = new HGridBuilder();
        $b->addCol("filter");
        $b->addCol("limit");
        $b->addRow([HStr::make($filter), HNum::make($limit)]);
        $req = $b->toGrid();
        return $this->call("read", $req);
    }

    public function eval(string $expr): HGrid
    {
        $b = new HGridBuilder();
        $b->addCol("expr");
        $b->addRow([HStr::make($expr)]);
        $req = $b->toGrid();
        return $this->call("eval", $req);
    }

    public function evalAll(array $exprs, bool $checked = true): array
    {
        $b = new HGridBuilder();
        $b->addCol("expr");
        foreach ($exprs as $expr) {
            $b->addRow([HStr::make($expr)]);
        }
        return $this->evalAllGrids($b->toGrid(), $checked);
    }

    public function evalAllGrids(HGrid $req, bool $checked): array
    {
        $reqStr = HZincWriter::gridToString($req);
        $resStr = $this->postString($this->uri . "evalAll", $reqStr);
        $res = (new HZincReader($resStr))->readGrids();
        if ($checked) {
            foreach ($res as $grid) {
                if ($grid->isErr()) {
                    throw new CallErrException($grid);
                }
            }
        }
        return $res;
    }

    public function watchOpen(string $dis, HNum $lease): HWatch
    {
        return new HClientWatch($this, $dis, $lease);
    }

    public function watches(): array
    {
        return array_values($this->watches);
    }

    public function watch(string $id, bool $checked): ?HWatch
    {
        $w = $this->watches[$id] ?? null;
        if ($w !== null) {
            return $w;
        }
        if ($checked) {
            throw new UnknownWatchException($id);
        }
        return null;
    }

    public function watchSub(HClientWatch $w, array $ids, bool $checked): HGrid
    {
        if (empty($ids)) {
            throw new InvalidArgumentException("ids are empty");
        }
        if ($w->isClosed()) {
            throw new IllegalStateException("watch is closed");
        }

        $b = new HGridBuilder();
        if ($w->getId() !== null) {
            $b->meta()->add("watchId", $w->getId());
        }
        if ($w->getDesiredLease() !== null) {
            $b->meta()->add("lease", $w->getDesiredLease());
        }
        $b->meta()->add("watchDis", $w->getDis());

        $b->addCol("id");
        foreach ($ids as $id) {
            $b->addRow([$id]);
        }

        try {
            $req = $b->toGrid();
            $res = $this->call("watchSub", $req);
        } catch (CallErrException $e) {
            $this->watchClose($w, false);
            throw $e;
        }

        if ($w->getId() === null) {
            $w->setId($res->meta()->getStr("watchId"));
            $w->setLease($res->meta()->get("lease"));
            $this->watches[$w->getId()] = $w;
        }

        if ($checked) {
            if ($res->numRows() !== count($ids) && !empty($ids)) {
                throw new UnknownRecException($ids[0]);
            }
            foreach ($res->rows() as $i => $row) {
                if ($row->missing("id")) {
                    throw new UnknownRecException($ids[$i]);
                }
            }
        }
        return $res;
    }

    public function watchUnsub(HClientWatch $w, array $ids): void
    {
        if (empty($ids)) {
            throw new InvalidArgumentException("ids are empty");
        }
        if ($w->getId() === null) {
            throw new IllegalStateException("nothing subscribed yet");
        }
        if ($w->isClosed()) {
            throw new IllegalStateException("watch is closed");
        }

        $b = new HGridBuilder();
        $b->meta()->add("watchId", $w->getId());

        $b->addCol("id");
        foreach ($ids as $id) {
            $b->addRow([$id]);
        }

        $req = $b->toGrid();
        $this->call("watchUnsub", $req);
    }

    public function watchPoll(HClientWatch $w, bool $refresh): HGrid
    {
        if ($w->getId() === null) {
            throw new IllegalStateException("nothing subscribed yet");
        }
        if ($w->isClosed()) {
            throw new IllegalStateException("watch is closed");
        }

        $b = new HGridBuilder();
        $b->meta()->add("watchId", $w->getId());
        if ($refresh) {
            $b->meta()->add("refresh");
        }
        $b->addCol("empty");

        $req = $b->toGrid();
        try {
            return $this->call("watchPoll", $req);
        } catch (CallErrException $e) {
            $this->watchClose($w, false);
            throw $e;
        }
    }

    public function watchClose(HClientWatch $w, bool $send): void
    {
        if ($w->isClosed()) {
            return;
        }
        $w->setClosed(true);

        if ($w->getId() !== null) {
            unset($this->watches[$w->getId()]);
        }

        if ($send) {
            try {
                $b = new HGridBuilder();
                $b->meta()->add("watchId", $w->getId())->add("close");
                $b->addCol("id");
                $this->call("watchUnsub", $b->toGrid());
            } catch (Exception $e) {
                // Ignore exceptions during close
            }
        }
    }

    public function pointWrite(HRef $id, int $level, string $who, ?HVal $val, ?HNum $dur): HGrid
    {
        $b = new HGridBuilder();
        $b->addCol("id");
        $b->addCol("level");
        $b->addCol("who");
        $b->addCol("val");
        $b->addCol("duration");

        $b->addRow([$id, HNum::make($level), HStr::make($who), $val, $dur]);

        $req = $b->toGrid();
        return $this->call("pointWrite", $req);
    }

    public function pointWriteArray(HRef $id): HGrid
    {
        $b = new HGridBuilder();
        $b->addCol("id");
        $b->addRow([$id]);

        $req = $b->toGrid();
        return $this->call("pointWrite", $req);
    }

    public function hisRead(HRef $id, $range): HGrid
    {
        $b = new HGridBuilder();
        $b->addCol("id");
        $b->addCol("range");
        $b->addRow([$id, HStr::make((string)$range)]);
        $req = $b->toGrid();
        return $this->call("hisRead", $req);
    }

    public function hisWrite(HRef $id, array $items): void
    {
        $meta = (new HDictBuilder())->add("id", $id)->toDict();
        $req = HGridBuilder::hisItemsToGrid($meta, $items);
        $this->call("hisWrite", $req);
    }

    public function invokeAction(HRef $id, string $action, HDict $args): HGrid
    {
        $meta = (new HDictBuilder())->add("id", $id)->add("action", HStr::make($action))->toDict();
        $req = HGridBuilder::dictsToGridWithMeta($meta, [$args]);
        return $this->call("invokeAction", $req);
    }

    public function call(string $op, HGrid $req): HGrid
    {
        $res = $this->postGrid($op, $req);
        if ($res->isErr()) {
            throw new CallErrException($res);
        }
        return $res;
    }

    private function postGrid(string $op, HGrid $req): HGrid
    {
        $reqStr = HZincWriter::gridToString($req, $this->version);
        $resStr = $this->postString($this->uri . $op, $reqStr);
        return (new HZincReader($resStr))->readGrid();
    }

    private function postString(string $uriStr, string $req, ?string $mimeType = null): string
    {
        try {
            $response = $this->httpClient->post($uriStr, [
                'body' => $req,
                'headers' => [
                    'Content-Type' => $mimeType ?? 'text/zinc; charset=utf-8',
                    'Connection' => 'Close'
                ]
            ]);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                throw new CallHttpException($response->getStatusCode(), $response->getReasonPhrase());
            }
            throw new CallNetworkException($e);
        }
    }
}

class HClientWatch extends HWatch
{
    private HClient $client;
    private string $dis;
    private HNum $desiredLease;
    private ?string $id = null;
    private ?HNum $lease = null;
    private bool $closed = false;

    public function __construct(HClient $client, string $dis, HNum $desiredLease)
    {
        $this->client = $client;
        $this->dis = $dis;
        $this->desiredLease = $desiredLease;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLease(): ?HNum
    {
        return $this->lease;
    }

    public function getDis(): string
    {
        return $this->dis;
    }

    public function getDesiredLease(): HNum
    {
        return $this->desiredLease;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setLease(HNum $lease): void
    {
        $this->lease = $lease;
    }

    public function setClosed(bool $closed): void
    {
        $this->closed = $closed;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function sub(array $ids, bool $checked): HGrid
    {
        return $this->client->watchSub($this, $ids, $checked);
    }

    public function unsub(array $ids): void
    {
        $this->client->watchUnsub($this, $ids);
    }

    public function pollChanges(): HGrid
    {
        return $this->client->watchPoll($this, false);
    }

    public function pollRefresh(): HGrid
    {
        return $this->client->watchPoll($this, true);
    }

    public function close(): void
    {
        $this->client->watchClose($this, true);
    }

    public function isOpen(): bool
    {
        return !$this->closed;
    }
}
