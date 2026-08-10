<?php

namespace Tests\Unit;

use App\Livewire\Dashboard;
use ReflectionClass;
use Tests\TestCase;

/**
 * Regressionstest fuers Livewire-Self-Refresh-Antipattern (siehe Memory
 * "Livewire — Komponente darf nicht auf eigenes dispatched Event hoeren"):
 * Dashboard.php dispatchte 'environment-changed' in changeEnvironment() UND
 * stand gleichzeitig in $listeners mit 'environment-changed' => '$refresh'.
 * Das erzeugte zwei render()-Aufrufe pro Environment-Wechsel -> zwei
 * charts-data-Events -> der Donut-Chart wurde doppelt gezeichnet.
 *
 * Reine Reflection, keine Datenbank noetig: prueft nur, dass sich die Mengen
 * "dispatchte Events" und "gehoerte Events" nicht ueberschneiden.
 */
class DashboardListenerConfigTest extends TestCase
{
    public function test_dashboard_hoert_auf_kein_event_das_es_selbst_dispatched(): void
    {
        $source = file_get_contents((new ReflectionClass(Dashboard::class))->getFileName());
        preg_match_all("/\\\$this->dispatch\('([a-zA-Z0-9\-_]+)'/", $source, $matches);
        $dispatchedEvents = array_unique($matches[1]);

        $this->assertNotEmpty($dispatchedEvents, 'Erwartete mindestens ein dispatch() in Dashboard.php zu finden');

        $reflection = new ReflectionClass(Dashboard::class);
        $property = $reflection->getProperty('listeners');
        $property->setAccessible(true);
        $listenedEvents = array_keys($property->getValue(new Dashboard()));

        $this->assertEmpty(
            array_intersect($dispatchedEvents, $listenedEvents),
            'Dashboard darf auf kein Event hoeren, das es selbst dispatched (erzeugt doppelten Render-Zyklus): ' .
            implode(', ', array_intersect($dispatchedEvents, $listenedEvents))
        );
    }
}
