<?php
namespace App\Exports;

use App\Models\Subcon;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SubconProjectExport implements WithMultipleSheets
{
    private $subcons;
    private $project;

    public function __construct(int $projectId)
    {
        $this->project = Project::find($projectId);
        $this->subcons = Subcon::with(['zone', 'mifs.items', 'mifs.issuedBy', 'mrfs.items'])
            ->where('project_id', $projectId)
            ->orderBy('name')
            ->get();
    }

    public function sheets(): array
    {
        $sheets = [new SubconProjectSummarySheet($this->subcons, $this->project)];

        // One MIF sheet per subcon that has MIFs
        foreach ($this->subcons as $subcon) {
            if ($subcon->mifs->isNotEmpty()) {
                $sheets[] = new SubconMifSummarySheet($subcon, $subcon->mifs);
            }
        }

        // One MRF sheet per subcon that has MRFs
        foreach ($this->subcons as $subcon) {
            if ($subcon->mrfs->isNotEmpty()) {
                $sheets[] = new SubconMrfSummarySheet($subcon, $subcon->mrfs);
            }
        }

        return $sheets;
    }
}
