<?php
namespace App\Exports;

use App\Models\Subcon;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SubconMaterialReportExport implements WithMultipleSheets
{
    private $subcons;
    private $project;

    public function __construct(int $projectId)
    {
        $this->project = Project::find($projectId);
        $this->subcons = Subcon::with(['zone', 'mifs.items', 'mrfs.items'])
            ->where('project_id', $projectId)
            ->orderBy('name')
            ->get();
    }

    public function sheets(): array
    {
        return [
            new SubconMaterialSheet($this->subcons, $this->project, 'MIF'),
            new SubconMaterialSheet($this->subcons, $this->project, 'MRF'),
        ];
    }
}
