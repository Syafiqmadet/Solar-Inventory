<?php
namespace App\Exports;

use App\Models\IsolatedItem;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InventoryIsolatedSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    public function __construct(private ?int $projectId = null) {}

    const C_DARK   = '1A1A2E';
    const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF';
    const C_HDR    = '2D3748';
    const C_GREEN  = '28A745';
    const C_RED    = 'DC3545';
    const C_YELLOW = '856404';
    const C_LIGHT  = 'F8F9FA';

    public function title(): string { return 'Isolated Items'; }

    public function registerEvents(): array
    {
        $q = IsolatedItem::with('item')->latest('isolated_date');
            $q->where('project_id', $this->projectId);
            $isolated = $q->get();

        return [AfterSheet::class => function (AfterSheet $e) use ($isolated) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A6');

            // ── Title ──────────────────────────────────────────────
            $ws->mergeCells('A1:J1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '🛡️  ISOLATED ITEMS — DEFECT & DAMAGED');
            $ws->getStyle('A1:J1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>16,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── KPI row ────────────────────────────────────────────
            $total    = $isolated->count();
            $defect   = $isolated->where('type','defect')->count();
            $damaged  = $isolated->where('type','damaged')->count();
            $isoStatus= $isolated->where('status','isolated')->count();
            $repaired = $isolated->where('status','repaired')->count();
            $scrapped = $isolated->where('status','scrapped')->count();
            $totalQty = $isolated->sum('quantity');

            $ws->getRowDimension(2)->setRowHeight(18);
            $ws->getRowDimension(3)->setRowHeight(34);
            $kpis = [
                ['A2:B3', "📋 Total\n{$total}",        'E8EAF6', self::C_DARK],
                ['C2:D3', "⚠️ Defect\n{$defect}",      'FFF3CD', self::C_YELLOW],
                ['E2:F3', "💥 Damaged\n{$damaged}",    'F8D7DA', self::C_RED],
                ['G2:H3', "🔒 Isolated\n{$isoStatus}", 'FFF5E6', self::C_ORANGE],
                ['I2:I3', "✅ Repaired\n{$repaired}",  'D4EDDA', self::C_GREEN],
                ['J2:J3', "🗑️ Scrapped\n{$scrapped}",  'F5F5F5', '6C757D'],
            ];
            foreach ($kpis as [$rng, $txt, $bg, $fc]) {
                $ws->mergeCells($rng);
                $ws->setCellValue(explode(':', $rng)[0], $txt);
                $ws->getStyle($rng)->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>13,'color'=>['rgb'=>$fc],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,
                                    'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            $ws->getRowDimension(4)->setRowHeight(6);

            // ── Table header ───────────────────────────────────────
            $cols = [
                'A' => ['#',           4],
                'B' => ['Date',       13],
                'C' => ['Name',       28],
                'D' => ['Part Number',18],
                'E' => ['Type',       12],
                'F' => ['Qty',         8],
                'G' => ['Status',     14],
                'H' => ['Reason',     35],
                'I' => ['Notes',      22],
                'J' => ['Linked Item',22],
            ];
            $ws->getRowDimension(5)->setRowHeight(26);
            foreach ($cols as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'5', $label);
                $ws->getStyle($col.'5')->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_ORANGE]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            // ── Data rows ──────────────────────────────────────────
            $row = 6;
            foreach ($isolated as $seq => $rec) {
                $ws->getRowDimension($row)->setRowHeight(22);

                $isDefect  = $rec->type === 'defect';
                $rowBg     = $isDefect ? 'FFFDF0' : 'FFF8F8';
                $typeColor = $isDefect ? self::C_YELLOW : self::C_RED;
                $typeLbl   = $isDefect ? '⚠️ Defect' : '💥 Damaged';

                $statusLbl   = match($rec->status) {
                    'isolated' => '🔒 Isolated',
                    'repaired' => '✅ Repaired',
                    'scrapped' => '🗑️ Scrapped',
                    default    => ucfirst($rec->status),
                };
                $statusColor = match($rec->status) {
                    'isolated' => self::C_ORANGE,
                    'repaired' => self::C_GREEN,
                    'scrapped' => '6C757D',
                    default    => '000000',
                };

                $ws->setCellValue('A'.$row, $seq + 1);
                $ws->setCellValue('B'.$row, $rec->isolated_date?->format('d M Y') ?? '—');
                $ws->setCellValue('C'.$row, $rec->name);
                $ws->setCellValue('D'.$row, $rec->part_number ?? '—');
                $ws->setCellValue('E'.$row, $typeLbl);
                $ws->setCellValue('F'.$row, $rec->quantity);
                $ws->setCellValue('G'.$row, $statusLbl);
                $ws->setCellValue('H'.$row, $rec->reason ?? '—');
                $ws->setCellValue('I'.$row, $rec->notes  ?? '—');
                $ws->setCellValue('J'.$row, $rec->item?->name ?? '—');

                $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                    'font'      => ['size'=>10,'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$rowBg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'DDDDDD']]],
                ]);

                foreach (['C','D','H','I','J'] as $c) {
                    $ws->getStyle($c.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
                $ws->getStyle('C'.$row)->getFont()->setBold(true);
                $ws->getStyle('E'.$row)->getFont()->setBold(true);
                $ws->getStyle('E'.$row)->getFont()->getColor()->setRGB($typeColor);
                $ws->getStyle('G'.$row)->getFont()->setBold(true);
                $ws->getStyle('G'.$row)->getFont()->getColor()->setRGB($statusColor);

                $row++;
            }

            if ($isolated->isEmpty()) {
                $ws->mergeCells('A6:J6');
                $ws->setCellValue('A6', 'No isolated items recorded.');
                $ws->getStyle('A6:J6')->applyFromArray([
                    'font'      => ['italic'=>true,'color'=>['rgb'=>'999999'],'name'=>'Arial'],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
                ]);
                $row = 7;
            }

            // ── Total row ──────────────────────────────────────────
            $ws->getRowDimension($row)->setRowHeight(26);
            $ws->mergeCells('A'.$row.':E'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL  —  '.$isolated->count().' records');
            $ws->setCellValue('F'.$row, $totalQty);
            foreach (['G','H','I','J'] as $c) $ws->setCellValue($c.$row, '');
            $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);
        }];
    }
}
