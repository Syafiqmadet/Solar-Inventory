<?php
namespace App\Exports;

use App\Models\Zone;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ZoneSummarySheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E';
    const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF';
    const C_HDR    = '2D3748';
    const C_GREEN  = '28A745';
    const C_RED    = 'DC3545';
    const C_BLUE   = '0D6EFD';
    const C_LIGHT  = 'F8F9FA';
    const C_GRAY   = 'E9ECEF';

    private $zones;

    public function __construct(private ?int $zoneId = null, private ?int $projectId = null)
    {
        $q = Zone::with('transactions.item');
        if ($zoneId) $q->where('id', $zoneId);
        $q->where('project_id', $projectId);
        $this->zones = $q->get();
    }

    public function title(): string { return 'Zone Summary'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A7');

            // ── Title ──────────────────────────────────────────────
            $ws->mergeCells('A1:J1');
            $ws->getRowDimension(1)->setRowHeight(48);
            $ws->setCellValue('A1', '📍  ZONE STOCK REPORT');
            $ws->getStyle('A1:J1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>22,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── Subtitle ───────────────────────────────────────────
            $ws->mergeCells('A2:J2');
            $ws->getRowDimension(2)->setRowHeight(18);
            $subtitle = $this->zoneId
                ? 'Zone: '.$this->zones->first()?->name.'   |   Generated: '.now()->format('d F Y  H:i')
                : 'All Zones   |   Generated: '.now()->format('d F Y  H:i');
            $ws->setCellValue('A2', $subtitle);
            $ws->getStyle('A2:J2')->applyFromArray([
                'font'      => ['size'=>10,'color'=>['rgb'=>'888888'],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F0F2F5']],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            $ws->getRowDimension(3)->setRowHeight(6);

            // ── KPI Cards ──────────────────────────────────────────
            $totalZones = $this->zones->count();
            $totalTxns  = $this->zones->sum(fn($z) => $z->transactions->count());
            $totalIn    = $this->zones->sum(fn($z) => $z->transactions->where('type','in')->sum('quantity'));
            $totalOut   = $this->zones->sum(fn($z) => $z->transactions->where('type','out')->sum('quantity'));
            $uniqueItems= $this->zones->flatMap(fn($z) => $z->transactions->pluck('item_id'))->unique()->count();
            $netDeployed= $totalIn - $totalOut;

            $ws->getRowDimension(4)->setRowHeight(18);
            $ws->getRowDimension(5)->setRowHeight(36);
            $kpis = [
                ['A4:B5', "📍 Zones\n{$totalZones}",          'E8EAF6', self::C_DARK],
                ['C4:D5', "📋 Transactions\n{$totalTxns}",    'CCE5FF', self::C_BLUE],
                ['E4:F5', "↓ Total IN\n{$totalIn}",           'D4EDDA', self::C_GREEN],
                ['G4:H5', "↑ Total OUT\n{$totalOut}",         'F8D7DA', self::C_RED],
                ['I4:I5', "🔩 Items\n{$uniqueItems}",         'FFF3CD', '856404'],
                ['J4:J5', "⚖️ Net\n{$netDeployed}",           'FFF0E6', self::C_ORANGE],
            ];
            foreach ($kpis as [$rng, $txt, $bg, $fc]) {
                $ws->mergeCells($rng);
                $ws->setCellValue(explode(':', $rng)[0], $txt);
                $ws->getStyle($rng)->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>14,'color'=>['rgb'=>$fc],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,
                                    'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            $ws->getRowDimension(6)->setRowHeight(6);

            // ── Table header ───────────────────────────────────────
            $cols = [
                'A' => ['#',            4],
                'B' => ['Color',        6],
                'C' => ['Zone Name',   22],
                'D' => ['Code',         8],
                'E' => ['Description', 30],
                'F' => ['Stock IN',    12],
                'G' => ['Stock OUT',   12],
                'H' => ['Net Deployed',13],
                'I' => ['Transactions',13],
                'J' => ['Unique Items',13],
            ];
            $ws->getRowDimension(7)->setRowHeight(26);
            foreach ($cols as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'7', $label);
                $ws->getStyle($col.'7')->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_ORANGE]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,
                                    'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            // ── Data rows ──────────────────────────────────────────
            $row = 8;
            foreach ($this->zones as $seq => $zone) {
                $ws->getRowDimension($row)->setRowHeight(22);

                $zIn    = $zone->transactions->where('type', 'in')->sum('quantity');
                $zOut   = $zone->transactions->where('type', 'out')->sum('quantity');
                $zNet   = $zIn - $zOut;
                $zTxns  = $zone->transactions->count();
                $zItems = $zone->transactions->pluck('item_id')->unique()->count();

                $bg = ($row % 2 === 0) ? self::C_LIGHT : self::C_WHITE;

                // Paint color cell
                $colorHex = $zone->color ? ltrim($zone->color, '#') : null;
                if ($colorHex && strlen($colorHex) === 3) {
                    $colorHex = str_repeat($colorHex[0], 2).str_repeat($colorHex[1], 2).str_repeat($colorHex[2], 2);
                }

                $ws->setCellValue('A'.$row, $seq + 1);
                $ws->setCellValue('B'.$row, '');
                $ws->setCellValue('C'.$row, $zone->name);
                $ws->setCellValue('D'.$row, $zone->code ?? '—');
                $ws->setCellValue('E'.$row, $zone->description ?? '—');
                $ws->setCellValue('F'.$row, $zIn);
                $ws->setCellValue('G'.$row, $zOut);
                $ws->setCellValue('H'.$row, $zNet);
                $ws->setCellValue('I'.$row, $zTxns);
                $ws->setCellValue('J'.$row, $zItems);

                $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                    'font'      => ['size'=>10,'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'DDDDDD']]],
                ]);
                $ws->getStyle('C'.$row)->getFont()->setBold(true);
                $ws->getStyle('C'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Color stock IN/OUT
                $ws->getStyle('F'.$row)->getFont()->getColor()->setRGB(self::C_GREEN);
                $ws->getStyle('F'.$row)->getFont()->setBold(true);
                $ws->getStyle('G'.$row)->getFont()->getColor()->setRGB(self::C_RED);
                $ws->getStyle('G'.$row)->getFont()->setBold(true);
                $ws->getStyle('H'.$row)->getFont()->setBold(true);
                $ws->getStyle('H'.$row)->getFont()->getColor()->setRGB($zNet >= 0 ? self::C_BLUE : self::C_RED);

                // Paint zone color cell
                if ($colorHex && preg_match('/^[0-9A-Fa-f]{6}$/', $colorHex)) {
                    $ws->getStyle('B'.$row)->getFill()
                       ->setFillType(Fill::FILL_SOLID)
                       ->getStartColor()->setRGB($colorHex);
                }

                $row++;
            }

            // ── Total row ──────────────────────────────────────────
            $ws->getRowDimension($row)->setRowHeight(26);
            $ws->mergeCells('A'.$row.':E'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL  —  '.$this->zones->count().' zones');
            $ws->setCellValue('F'.$row, $totalIn);
            $ws->setCellValue('G'.$row, $totalOut);
            $ws->setCellValue('H'.$row, $netDeployed);
            $ws->setCellValue('I'.$row, $totalTxns);
            $ws->setCellValue('J'.$row, $uniqueItems);
            $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);
        }];
    }
}
