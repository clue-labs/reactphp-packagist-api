<?php

use Clue\React\Packagist\Api\Client;
use React\Promise\Deferred;
use Clue\React\Buzz\Message\Response;
use Clue\React\Buzz\Message\Headers;
use Clue\React\Buzz\Message\Body;

class ClientTest extends TestCase
{
    private $browser;
    private $client;

    public function setUp()
    {
        $this->browser = $this->getMockBuilder('Clue\React\Buzz\Browser')->disableOriginalConstructor()->getMock();

        $this->client = new Client($this->browser);
    }

    public function testGet()
    {
        $this->setupBrowser('packages/clue/zenity-react.json', $this->createResponsePromise('{"package":{"name":"clue\/zenity-react", "versions": {}}}'));

        $this->expectPromiseResolve($this->client->get('clue/zenity-react'));
    }

    public function testAll()
    {
        $this->setupBrowser('packages/list.json', $this->createResponsePromise('{"packageNames":["a/a", "b/b"]}'));

        $this->expectPromiseResolve($this->client->all());
    }

    public function testAllVendor()
    {
        $this->setupBrowser('packages/list.json?vendor=a', $this->createResponsePromise('{"packageNames":["a/a"]}'));

        $this->expectPromiseResolve($this->client->all(array('vendor' => 'a')));
    }

    public function testSearch()
    {
        $this->setupBrowser('search.json?q=zenity', $this->createResponsePromise('{"results":[{"name":"clue\/zenity-react","description":"Build graphical desktop (GUI) applications in PHP","url":"https:\/\/packagist.org\/packages\/clue\/zenity-react","downloads":57,"favers":0,"repository":"https:\/\/github.com\/clue\/reactphp-zenity"}],"total":1}'));

        $this->expectPromiseResolve($this->client->search('zenity'));
    }

    public function testSearchPagination()
    {
        $this->browser->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls(
                $this->createResponsePromise('{"results":[{"name":"clue\/zenity-react","description":"Build graphical desktop (GUI) applications in PHP","url":"https:\/\/packagist.org\/packages\/clue\/zenity-react","downloads":57,"favers":0,"repository":"https:\/\/github.com\/clue\/reactphp-zenity"}],"total":2, "next": ""}'),
                $this->createResponsePromise('{"results":[{"name":"clue\/zenity-react","description":"Build graphical desktop (GUI) applications in PHP","url":"https:\/\/packagist.org\/packages\/clue\/zenity-react","downloads":57,"favers":0,"repository":"https:\/\/github.com\/clue\/reactphp-zenity"}],"total":2}')
            ));

        $this->expectPromiseResolve($this->client->search('zenity'));
    }

    public function testHttpError()
    {
        $this->setupBrowser('packages/clue/invalid.json', $this->createRejectedPromise(new RuntimeException('error')));

        $this->expectPromiseReject($this->client->get('clue/invalid'));
    }

    private function setupBrowser($expectedUrl, $promise)
    {
        $this->browser->expects($this->once())
             ->method('get')
             ->with($this->equalTo('https://packagist.org/' . $expectedUrl), array())
             ->will($this->returnValue($promise));
    }

    private function createResponsePromise($fakeResponseBody)
    {
        $d = new Deferred();
        $d->resolve(new Response('HTTP/1.0', 200, 'OK', new Headers(), new Body($fakeResponseBody)));
        return $d->promise();
    }

    private function createRejectedPromise($reason)
    {
        $d = new Deferred();
        $d->reject($reason);
        return $d->promise();
    }
}
