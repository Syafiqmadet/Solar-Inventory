<?php
namespace App\Exports;

use App\Models\DieselUsage;
use App\Models\FuelRecord;
use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SummarySheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E'; const C_ORANGE = 'FF6B35';
    const C_GREEN  = '28A745'; const C_BLUE   = '0D6EFD';
    const C_PL     = 'E8F8EE'; const C_DL     = 'E8F0FF';
    const C_HDR    = '2D3748'; const C_WHITE  = 'FFFFFF';
    const C_GRAY   = 'F8F9FA'; const C_GRAY2  = 'E9ECEF';

    private array $typeGroups;

    public function __construct(private ?int $projectId = null)
    {
        $q = Vehicle::with('dieselUsages')->orderBy('type')->orderBy('name');
        $q->where('project_id', $this->projectId);
        $vehicles = $q->get();
        $this->typeGroups = [];
        foreach ($vehicles as $v) {
            $t = $v->type ?? 'Other';
            if (!isset($this->typeGroups[$t])) $this->typeGroups[$t] = [];
            $this->typeGroups[$t][] = $v;
        }
    }

    public function title(): string { return 'Summary'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A6');
            // (KPI cards removed)

            // ── Title ─────────────────────────────────────────────
            $ws->mergeCells('A1:N1');
            $ws->getRowDimension(1)->setRowHeight(46);
            $ws->setCellValue('A1', '☀️  SOLAR PROJECT — FUEL USAGE REPORT');
            $this->styleCell($ws, 'A1:N1', [
                'font'  => ['bold'=>true,'size'=>18,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'  => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── Generated date ────────────────────────────────────
            $ws->mergeCells('A2:N2');
            $ws->getRowDimension(2)->setRowHeight(18);
            $ws->setCellValue('A2', 'Generated: ' . now()->format('d F Y  H:i'));
            $this->styleCell($ws, 'A2:N2', [
                'font'      => ['size'=>10,'color'=>['rgb'=>'888888'],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F0F2F5']],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── Section banner ────────────────────────────────────
            $ws->mergeCells('A4:N4');
            $ws->getRowDimension(4)->setRowHeight(26);
            $ws->setCellValue('A4', '🚛  FUEL USAGE — BY VEHICLE TYPE & INDIVIDUAL VEHICLE');
            $this->styleCell($ws, 'A4:N4', [
                'font'      => ['bold'=>true,'size'=>12,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── Table header ──────────────────────────────────────
            $ws->getRowDimension(5)->setRowHeight(28);
            $headers = [
                'A'=>['Vehicle Type / Name',24], 'B'=>['Plate No.',13], 'C'=>['Brand / Model',18],
                'D'=>['Total Records',13], 'E'=>['⛽ Petrol (L)',15], 'F'=>['🛢️ Diesel (L)',15],
                'G'=>['Total Fuel (L)',14], 'H'=>['Petrol %',10],  'I'=>['Diesel %',10],
                'J'=>['Avg L / Fill',12], 'K'=>['Last Used',14],  'L'=>['Driver',16],
                'M'=>['Purpose (latest)',22], 'N'=>['Status',10],
            ];
            foreach ($headers as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'5', $label);
                $this->styleCell($ws, $col.'5', [
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_ORANGE]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,
                                    'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            // ── Data rows ─────────────────────────────────────────
            $row = 6;
            $grandP = 0; $grandD = 0; $grandRec = 0;

            foreach ($this->typeGroups as $type => $vehicles) {
                $icon   = $this->typeIcon($type);
                $groupP = 0; $groupD = 0; $groupRec = 0;
                $vRows  = [];

                foreach ($vehicles as $v) {
                    $vp  = (float)$v->dieselUsages()->where('fuel_type','petrol')->sum('liters_used');
                    $vd  = (float)$v->dieselUsages()->where('fuel_type','diesel')->sum('liters_used');
                    $vr  = $v->dieselUsages()->count();
                    $vt  = $vp + $vd;
                    $last = $v->lastUsageDate();
                    $lastUsage = $v->dieselUsages()->latest('date')->first();
                    $vRows[] = compact('v','vp','vd','vr','vt','last','lastUsage');
                    $groupP += $vp; $groupD += $vd; $groupRec += $vr;
                }
                $grandP += $groupP; $grandD += $groupD; $grandRec += $groupRec;

                // Group header row
                $ws->getRowDimension($row)->setRowHeight(24);
                $ws->setCellValue('A'.$row, $icon.' '.$type);
                $ws->setCellValue('B'.$row, count($vehicles).' vehicles');
                $ws->setCellValue('D'.$row, $groupRec);
                $ws->setCellValue('E'.$row, $groupP);
                $ws->setCellValue('F'.$row, $groupD);
                $ws->setCellValue('G'.$row, $groupP + $groupD);
                $ws->setCellValue('N'.$row, '📂 Group');
                $groupStyle = [
                    'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_HDR],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_GRAY2]],
                    'alignment' => ['vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ];
                $this->styleCell($ws, 'A'.$row.':N'.$row, $groupStyle);
                $ws->getStyle('E'.$row)->getFont()->getColor()->setRGB(self::C_GREEN);
                $ws->getStyle('F'.$row)->getFont()->getColor()->setRGB(self::C_BLUE);
                $ws->getStyle('E'.$row.':G'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $ws->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $row++;

                // Per-vehicle rows
                foreach ($vRows as $vdata) {
                    ['v'=>$v,'vp'=>$vp,'vd'=>$vd,'vr'=>$vr,'vt'=>$vt,'last'=>$last,'lastUsage'=>$lastUsage] = $vdata;
                    $ws->getRowDimension($row)->setRowHeight(20);
                    $bg     = ($row % 2 === 0) ? 'F5F7FA' : 'FFFFFF';
                    $pp_pct = $vt > 0 ? round($vp/$vt*100,1) : 0;
                    $dp_pct = $vt > 0 ? round($vd/$vt*100,1) : 0;
                    $avg    = $vr > 0 ? round($vt/$vr,2) : 0;

                    $ws->setCellValue('A'.$row, '   └ '.$v->name);
                    $ws->setCellValue('B'.$row, $v->vehicle_no);
                    $ws->setCellValue('C'.$row, trim(($v->brand??'').' '.($v->model??'')));
                    $ws->setCellValue('D'.$row, $vr);
                    $ws->setCellValue('E'.$row, $vp);
                    $ws->setCellValue('F'.$row, $vd);
                    $ws->setCellValue('G'.$row, $vt);
                    $ws->setCellValue('H'.$row, $pp_pct.'%');
                    $ws->setCellValue('I'.$row, $dp_pct.'%');
                    $ws->setCellValue('J'.$row, $avg);
                    $ws->setCellValue('K'.$row, $last ? $last->format('d M Y') : '—');
                    $ws->setCellValue('L'.$row, $lastUsage?->driver_name ?? '—');
                    $ws->setCellValue('M'.$row, $lastUsage?->purpose ?? $lastUsage?->notes ?? '—');
                    $ws->setCellValue('N'.$row, $vt > 0 ? '✅ Active' : '💤 Idle');

                    $this->styleCell($ws, 'A'.$row.':N'.$row, [
                        'font'      => ['size'=>10,'name'=>'Arial'],
                        'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                        'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                    ]);
                    $ws->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $ws->getStyle('M'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $ws->getStyle('E'.$row)->getFont()->getColor()->setRGB(self::C_GREEN);
                    $ws->getStyle('F'.$row)->getFont()->getColor()->setRGB(self::C_BLUE);
                    $ws->getStyle('E'.$row.':G'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $ws->getStyle('J'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $ws->getStyle('N'.$row)->getFont()->setBold(true);
                    $fcN = $vt > 0 ? self::C_GREEN : '999999';
                    $ws->getStyle('N'.$row)->getFont()->getColor()->setRGB($fcN);
                    $row++;
                }
            }

            // Grand total
            $ws->getRowDimension($row)->setRowHeight(28);
            $ws->mergeCells('A'.$row.':C'.$row);
            $ws->setCellValue('A'.$row, 'GRAND TOTAL');
            $ws->setCellValue('D'.$row, $grandRec);
            $ws->setCellValue('E'.$row, $grandP);
            $ws->setCellValue('F'.$row, $grandD);
            $ws->setCellValue('G'.$row, $grandP + $grandD);
            foreach (range('H','N') as $col) $ws->setCellValue($col.$row, '');
            $this->styleCell($ws, 'A'.$row.':N'.$row, [
                'font'      => ['bold'=>true,'size'=>12,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);
            $ws->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $ws->getStyle('E'.$row)->getFont()->getColor()->setRGB('90EE90');
            $ws->getStyle('F'.$row)->getFont()->getColor()->setRGB('87CEEB');
            $ws->getStyle('E'.$row.':G'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        }];
    }

    private function typeIcon(string $type): string
    {
        return match(strtolower($type)) {
            'lorry','truck'   => '🚛', 'pickup','van' => '🚐',
            'excavator','jcb' => '🚜', 'crane'        => '🏗️',
            'forklift'        => '🏭', 'generator'    => '⚡',
            default           => '🚗',
        };
    }

    private function styleCell($ws, string $range, array $style): void
    {
        $ws->getStyle($range)->applyFromArray($style);
    }
}
