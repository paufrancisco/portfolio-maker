<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SamplePortfolioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_each_sample_portfolio_renders_for_guests(): void
    {
        foreach (['professional', 'pink', 'blue', 'showcase', 'minimal', 'editorial'] as $key) {
            $this->get('/samples/portfolio/'.$key)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Portfolios/Preview')
                ->where('portfolio.template_key', $key)
                ->where('portfolio.data.name', 'Maria Santos')
                ->where('watermark', false)
                ->where('templates', null));
        }
    }

    public function test_unknown_sample_portfolio_is_404(): void
    {
        $this->get('/samples/portfolio/nope')->assertNotFound();
    }
}
