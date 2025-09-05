<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Make all Feature & Unit tests extend Laravel's TestCase (gives you $this->getJson(), app boot, etc.)
uses(TestCase::class)->in('Feature', 'Unit');

// Auto-apply RefreshDatabase to Feature tests (runs migrations per test)
uses(RefreshDatabase::class)->in('Feature');
