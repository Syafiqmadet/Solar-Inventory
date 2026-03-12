<?php
namespace App\Exports;

use App\Models\Zone;
use App\Models\StockTransaction;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ZoneTransactionsSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E';
    const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF';
    const C_HDR    = '2D3748';
    const C_GREEN  = '28A745';
    const C_RED    = 'DC3545';
    const C_BLUE   = '0D6EFD';
    const C_LIGHT  = 'F8F9FA';

    private $transactions;

    public function __construct(
        private ?int    $zoneId    = null,
        private ?string $zoneName  = null,
        private ?int    $projectId = null,
    ) {
        $q = StockTransaction::with(['item', 'zone'])
            ->orderBy('created_at', 'desc');
        if ($zoneId) $q->where('zone_id', $zoneId);
        $q->whereHas('zone', fn($zq) => $zq->where('project_id', $projectId));
        $this->transactions = $q->get();

        if (!$this->zoneName && $zoneId) {
            $this->zoneName = Zone::find($zoneId)?->name ?? 'Zone';
        }
        $this->zoneName = $this->zoneName ?? 'All Zones';
    }

    public function title(): string
    {
        // Excel sheet names: max 31 chars, no special chars
        $name = preg_replace('/[\/\\\?\*\[\]:]/', '', $this->zoneName);
        return mb_substr($name, 0, 31);
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A6');

            // ── Title ──────────────────────────────────────────────
            $ws->mergeCells('A1:H1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '📋  '.$this->zoneName.' — Transactions');
            $ws->getStyle('A1:H1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>16,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── KPI row ────────────────────────────────────────────
            $totalTxns = $this->transactions->count();
            $totalIn   = $this->transactions->where('type','in')->sum('quantity');
            $totalOut  = $this->transactions->where('type','out')->sum('quantity');
            $inCount   = $this->transactions->where('type','in')->count();
            $outCount  = $this->transactions->where('type','out')->count();
            $netDeploy = $totalIn - $totalOut;

            $ws->getRowDimension(2)->setRowHeight(18);
            $ws->getRowDimension(3)->setRowHeight(34);
            $kpis = [
                ['A2:B3', "📋 Transactions\n{$totalTxns}",        'E8EAF6', self::C_DARK],
                ['C2:D3', "↓ IN\n{$inCount} txn / {$totalIn} u", 'D4EDDA', self::C_GREEN],
                ['E2:F3', "↑ OUT\n{$outCount} txn / {$totalOut} u",'F8D7DA', self::C_RED],
                ['G2:H3', "⚖️ Net Deployed\n{$netDeploy}",        'CCE5FF', self::C_BLUE],
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

            // ── Column headers ─────────────────────────────────────
            $cols = [
                'A' => ['#',           4],
                'B' => ['Date & Time', 18],
                'C' => ['Item Name',   28],
                'D' => ['Part Number', 18],
                'E' => ['Type',        10],
                'F' => ['Quantity',    11],
                'G' => ['Notes',       30],
                'H' => ['Recorded At', 18],
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
            foreach ($this->transactions as $seq => $tx) {
                $ws->getRowDimension($row)->setRowHeight(20);
                $isIn = $tx->type === 'in';
                $bg   = $isIn
                    ? ($row % 2 === 0 ? 'F0FFF4' : 'F8FFFA')
                    : ($row % 2 === 0 ? 'FFF5F5' : 'FFFAFA');

                $ws->setCellValue('A'.$row, $seq + 1);
                $ws->setCellValue('B'.$row, $tx->created_at->format('d M Y  H:i'));
                $ws->setCellValue('C'.$row, $tx->item->name ?? '—');
                $ws->setCellValue('D'.$row, $tx->item->part_number ?? '—');
                $ws->setCellValue('E'.$row, $isIn ? '↓ IN' : '↑ OUT');
                $ws->setCellValue('F'.$row, $tx->quantity);
                $ws->setCellValue('G'.$row, $tx->notes ?? '—');
                $ws->setCellValue('H'.$row, $tx->created_at->format('d M Y H:i'));

                $ws->getStyle('A'.$row.':H'.$row)->applyFromArray([
                    'font'      => ['size'=>10,'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'DDDDDD']]],
                ]);
                foreach (['B','C','D','G','H'] as $c) {
                    $ws->getStyle($c.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
                $ws->getStyle('C'.$row)->getFont()->setBold(true);
                $ws->getStyle('E'.$row)->getFont()->setBold(true);
                $ws->getStyle('E'.$row)->getFont()->getColor()->setRGB($isIn ? self::C_GREEN : self::C_RED);
                $ws->getStyle('F'.$row)->getFont()->setBold(true);
                $ws->getStyle('F'.$row)->getFont()->getColor()->setRGB($isIn ? self::C_GREEN : self::C_RED);
                $row++;
            }

            if ($this->transactions->isEmpty()) {
                $ws->mergeCells('A6:H6');
                $ws->setCellValue('A6', 'No transactions recorded for this zone.');
                $ws->getStyle('A6:H6')->applyFromArray([
                    'font'      => ['italic'=>true,'color'=>['rgb'=>'999999'],'name'=>'Arial'],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
                ]);
                $row = 7;
            }

            // ── Total row ──────────────────────────────────────────
            $ws->getRowDimension($row)->setRowHeight(26);
            $ws->mergeCells('A'.$row.':D'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL  —  '.$totalTxns.' transactions');
            $ws->setCellValue('E'.$row, $inCount.' IN / '.$outCount.' OUT');
            $ws->setCellValue('F'.$row, $totalIn.' / '.$totalOut);
            $ws->setCellValue('G'.$row, '');
            $ws->setCellValue('H'.$row, '');
            $ws->getStyle('A'.$row.':H'.$row)->applyFromArray([
                'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);
        }];
    }
}
