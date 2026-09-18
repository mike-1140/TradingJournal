<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Das eigentliche Sicherheitsnetz sitzt in AppServiceProvider::boot() -- muss WAEHREND
    // des App-Boots laufen (Teil von parent::setUp()/refreshApplication()), nicht erst
    // hier in setUp() selbst: setUpTraits()/RefreshDatabase feuert noch innerhalb
    // desselben parent::setUp()-Aufrufs, ein Check hier danach kaeme zu spaet (siehe
    // AppServiceProvider-Kommentar fuer Details zum Vorfall).
}
