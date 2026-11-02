<?php

namespace Tests\Feature;

use Tests\TestCase;

class SampleResumeTest extends TestCase
{
    public function test_each_sample_resume_renders_for_guests(): void
    {
        foreach (['classic', 'modern', 'minimal'] as $key) {
            $this->get('/samples/resume/'.$key)->assertOk()->assertSee('Maria Santos');
        }
    }

    public function test_unknown_sample_template_is_404(): void
    {
        $this->get('/samples/resume/nope')->assertNotFound();
    }
}
