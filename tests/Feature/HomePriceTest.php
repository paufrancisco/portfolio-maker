<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
    }

    public function test_home_page_shows_the_configured_portfolio_price(): void
    {
        config(['portfolio.price' => 500]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('portfolioPrice', 500));
    }

    public function test_price_is_hidden_when_not_set(): void
    {
        config(['portfolio.price' => 0]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('portfolioPrice', null));
    }
}
