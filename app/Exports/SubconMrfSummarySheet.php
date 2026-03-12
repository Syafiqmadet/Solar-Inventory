<?php
namespace App\Exports;

use App\Models\Subcon;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SubconMrfSummarySheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E';
    const C_BLUE   = '0D6EFD';
    const C_INFO   = '17A2B8';
    const C_WHITE  = 'FFFFFF';
    const C_HDR    = '2D3748';
    const C_GREEN  = '28A745';
    const C_RED    = 'DC3545';
    const C_LIGHT  = 'F8F9FA';

    public function __construct(private Subcon $subcon, private $mrfs) {}

    public function title(): string { return 'Summary'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);

            $totalForms   = $this->mrfs->count();
            $totalGood    = $this->mrfs->sum(fn($m) => $m->items->where('condition','good')->sum('quantity'));
            $totalDamaged = $this->mrfs->sum(fn($m) => $m->items->whereIn('condition',['damaged','defect'])->sum('quantity'));
            $totalLines   = $this->mrfs->sum(fn($m) => $m->items->count());

            // ── Title ──
            $ws->mergeCells('A1:I1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '📋  MATERIAL RETURN FORM (MRF) — ' . strtoupper($this->subcon->name));
            $ws->getStyle('A1:I1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>16,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── Subcon info ──
            $ws->getRowDimension(2)->setRowHeight(22);
            foreach ([
                'A2' => 'Zone: ' . ($this->subcon->zone?->name ?? '—'),
                'C2' => 'Status: ' . ucfirst($this->subcon->status),
                'G2' => 'Exported: ' . now()->format('d M Y H:i'),
            ] as $cell => $val) {
                $ws->setCellValue($cell, $val);
                $ws->getStyle($cell)->applyFromArray([
                    'font' => ['size'=>10,'name'=>'Arial','color'=>['rgb'=>'555555']],
                    'fill' => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F0F0F0']],
                ]);
            }

            // ── KPI Row ──
            $ws->getRowDimension(3)->setRowHeight(36);
            $kpis = [
                ['A3:B3', "📋 Total Forms\n{$totalForms}",           'E8EAF6', self::C_DARK],
                ['C3:D3', "✅ Returned Good\n{$totalGood}",          'D4EDDA', self::C_GREEN],
                ['E3:F3', "⚠️ Damaged/Defect → Isolated\n{$totalDamaged}", 'F8D7DA', self::C_RED],
                ['G3:I3', "📝 Total Lines\n{$totalLines}",           'FFF3CD', '856404'],
            ];
            foreach ($kpis as [$rng, $txt, $bg, $fc]) {
                $ws->mergeCells($rng);
                $ws->setCellValue(explode(':', $rng)[0], $txt);
                $ws->getStyle($rng)->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>13,'color'=>['rgb'=>$fc],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            $ws->getRowDimension(4)->setRowHeight(6);

            // ── Table Header ──
            $cols = [
                'A'=>['MRF Number',16],'B'=>['Date',13],'C'=>['Title',24],'D'=>['Item Name',26],
                'E'=>['Part No.',15],'F'=>['Qty',8],'G'=>['Unit',10],'H'=>['Condition',13],'I'=>['Remarks',28],
            ];
            $ws->getRowDimension(5)->setRowHeight(26);
            foreach ($cols as $col => [$label,$width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'5', $label);
                $ws->getStyle($col.'5')->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_INFO]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            // ── Data Rows ──
            $row = 6;
            foreach ($this->mrfs as $mrf) {
                $itemCount = $mrf->items->count();
                foreach ($mrf->items as $seq => $item) {
                    $isDamaged = in_array($item->condition, ['damaged', 'defect']);
                    $bg = $isDamaged ? 'FFF5F5' : ($row % 2 === 0 ? self::C_LIGHT : self::C_WHITE);

                    $ws->getRowDimension($row)->setRowHeight(20);
                    if ($seq === 0) {
                        $ws->setCellValue('A'.$row, $mrf->mrf_number);
                        $ws->setCellValue('B'.$row, $mrf->date->format('d M Y'));
                        $ws->getStyle('A'.$row)->getFont()->setBold(true)->setColor(
                            (new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB(self::C_INFO)
                        );
                    }
                    $ws->setCellValue('C'.$row, $item->title ?? '—');
                    $ws->setCellValue('D'.$row, $item->item_name);
                    $ws->setCellValue('E'.$row, $item->part_number ?? '—');
                    $ws->setCellValue('F'.$row, $item->quantity);
                    $ws->setCellValue('G'.$row, $item->unit ?? '—');
                    $ws->setCellValue('H'.$row, $isDamaged ? ($item->condition === 'defect' ? '🔧 Defect' : '⚠️ Damaged') : '✅ Good');
                    $ws->setCellValue('I'.$row, $item->remarks ?? '—');

                    $ws->getStyle('A'.$row.':I'.$row)->applyFromArray([
                        'font'      => ['size'=>10,'name'=>'Arial'],
                        'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                        'alignment' => ['horizontal'=>Alignment::HORIZONTAL_LEFT,'vertical'=>Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'E0E0E0']]],
                    ]);
                    $ws->getStyle('H'.$row)->getFont()->setBold(true)->setColor(
                        (new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB($isDamaged ? self::C_RED : self::C_GREEN)
                    );
                    $ws->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $row++;
                }

                // Subtotal per MRF
                $ws->getRowDimension($row)->setRowHeight(20);
                $ws->mergeCells('A'.$row.':E'.$row);
                $ws->setCellValue('A'.$row, 'Subtotal — '.$mrf->mrf_number.' ('.$itemCount.' lines)');
                $ws->setCellValue('F'.$row, $mrf->items->sum('quantity'));
                $ws->getStyle('A'.$row.':I'.$row)->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'name'=>'Arial','color'=>['rgb'=>self::C_WHITE]],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
                $row++;
            }

            if ($this->mrfs->isEmpty()) {
                $ws->mergeCells('A6:I6');
                $ws->setCellValue('A6', 'No MRF records found.');
                $ws->getStyle('A6:I6')->applyFromArray([
                    'font'      => ['italic'=>true,'color'=>['rgb'=>'999999'],'name'=>'Arial'],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
                ]);
            }

            $ws->freezePane('A6');
        }];
    }
}
