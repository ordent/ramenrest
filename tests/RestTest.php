<?php
namespace Ordent\RamenRest\Tests;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RestTest extends TestCase{
/**
     * A basic test example.
     *
     * @return void
     */
    public function testGetCollection()
    {
        $response = $this->get('/users');
        $response->assertStatus(200);
    }

    protected function getPackageProviders($app)
    {
        return ['Ordent\RamenRest\Providers\RamenRestProvider'];
    }
}