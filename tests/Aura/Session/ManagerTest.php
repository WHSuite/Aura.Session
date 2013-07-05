<?php
namespace Aura\Session;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    // the session object
    protected $session;

    protected function setUp()
    {
        session_set_save_handler(new MockSessionHandler);
        $this->session = $this->newSession();
    }

    protected function newSession(array $cookies = array())
    {
        return new Manager(
            new SegmentFactory,
            new CsrfTokenFactory(new Randval(new Phpfunc)),
            $cookies
        );
    }

    public function teardown()
    {
        session_unset();
        if (session_id() !== '') {
            session_destroy();
        }
    }

    public function testStart()
    {
        $this->session->start();
        $this->assertTrue($this->session->isStarted());
    }

    public function testClear()
    {
        // get a test segment and set some data
        $segment = $this->session->newSegment('test');
        $segment->foo = 'bar';
        $segment->baz = 'dib';

        $expect = array('test' => array('foo' => 'bar', 'baz' => 'dib'));
        $this->assertSame($expect, $_SESSION);

        // now clear it
        $this->session->clear();
        $this->assertSame(array(), $_SESSION);
    }

    public function testDestroy()
    {
        // get a test segment and set some data
        $segment = $this->session->newSegment('test');
        $segment->foo = 'bar';
        $segment->baz = 'dib';

        $expect = array('test' => array('foo' => 'bar', 'baz' => 'dib'));
        $this->assertSame($expect, $_SESSION);

        // now destroy it
        $this->session->destroy();
        $this->assertFalse($this->session->isStarted());
    }

    public function testCommit()
    {
        $this->session->commit();
        $this->assertFalse($this->session->isStarted());
    }

    public function testNewSegment()
    {
        $segment = $this->session->newSegment('test');
        $this->assertInstanceof('Aura\Session\Segment', $segment);
    }

    public function testGetCsrfToken()
    {
        $actual = $this->session->getCsrfToken();
        $expect = 'Aura\Session\CsrfToken';
        $this->assertInstanceOf($expect, $actual);
    }

    public function testisAvailable()
    {
        // should not look active
        $this->assertFalse($this->session->isAvailable());

        // fake a cookie
        $cookies = array(
            $this->session->getName() => 'fake-cookie-value',
        );
        $this->session = $this->newSession($cookies);

        // now it should look active
        $this->assertTrue($this->session->isAvailable());
    }

    public function testGetAndRegenerateId()
    {
        $this->session->start();
        $old_id = $this->session->getId();
        $this->session->regenerateId();
        $new_id = $this->session->getId();
        $this->assertTrue($old_id != $new_id);

        // check the csrf token as well
        $old_value = $this->session->getCsrfToken()->getValue();
        $this->session->regenerateId();
        $new_value = $this->session->getCsrfToken()->getValue();
        $this->assertTrue($old_value != $new_value);
    }

    public function testSetAndGetName()
    {
        $expect = 'new_name';
        $this->session->setName($expect);
        $actual = $this->session->getName();
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetSavePath()
    {
        $expect = '/new/save/path';
        $this->session->setSavePath($expect);
        $actual = $this->session->getSavePath();
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetCookieParams()
    {
        $expect = $this->session->getCookieParams();
        $expect['lifetime'] = '999';
        $this->session->setCookieParams($expect);
        $actual = $this->session->getCookieParams();
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetCacheExpire()
    {
        $expect = 123;
        $this->session->setCacheExpire($expect);
        $actual = $this->session->getCacheExpire();
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetCacheLimiter()
    {
        $expect = 'private_no_cache';
        $this->session->setCacheLimiter($expect);
        $actual = $this->session->getCacheLimiter();
        $this->assertSame($expect, $actual);
    }

    public function testGetStatus()
    {
        $expect = 0;
        $actual = $this->session->getStatus();
        $this->assertSame($expect, $actual);

        $expect = 1;
        $this->session->start();
        $actual = $this->session->getStatus();
        $this->assertSame($expect, $actual);
    }
}
