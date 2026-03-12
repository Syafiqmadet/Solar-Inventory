<?php
namespace App\Exports;

use App\Models\Zone;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ZoneExport implements WithMultipleSheets
{
    public function __construct(private ?int $zoneId = null, private ?int $projectId = null) {}

    public function sheets(): array
    {
        $sheets = [new ZoneSummarySheet($this->zoneId, $this->projectId)];

        // One transactions sheet per zone
        $q = Zone::withCount('transactions');
        if ($this->zoneId) $q->where('id', $this->zoneId);
        $q->where('project_id', $this->projectId);
        $zones = $q->orderBy('name')->get();

        foreach ($zones as $zone) {
            $sheets[] = new ZoneTransactionsSheet($zone->id, $zone->name, $this->projectId);
        }

        return $sheets;
    }
}
