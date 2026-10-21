<?php

namespace Tests\Feature;

use Tests\TestCase;

class BandungPlannerTest extends TestCase
{
    public function test_planner_is_publicly_accessible_with_duration_options(): void
    {
        $response = $this->get(route('bandung.planner'));

        $response->assertStatus(200);
        $response->assertViewIs('ui.bandung-planner');
        $response->assertSee('3 hari, 3 malam');
        $response->assertSee('bandung.js');
    }
}
