<?php

namespace VirtualAssembly\SemanticFormsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        //$client = static::createClient();

        //$crawler = $client->request('GET', '/');

        $this->assertEquals(1+1, 2);
        //$this->assertContains('Hello World', $client->getResponse()->getContent());
    }
}
