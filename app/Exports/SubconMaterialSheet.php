<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SubconMaterialSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK      = '1A1A2E';
    const C_WHITE     = 'FFFFFF';
    const C_HDR       = '2D3748';
    const C_MIF_HDR   = '2E7D32';
    const C_MIF_ROW   = 'E8F5E9';
    const C_MRF_HDR   = 'E65100';
    const C_MRF_ROW   = 'FFF3E0';
    const C_DMG_ROW   = 'FFEBEE';
    const C_DMG_TXT   = 'C62828';
    const C_SUBCON_BG = 'E3F2FD'; // light blue subcon separator

    public function __construct(
        private Collection $subcons,
        private ?Project   $project,
        private string     $type
    ) {}

    public function title(): string
    {
        return $this->type === 'MIF' ? 'All MIF Items' : 'All MRF Items';
    }

    private function accent(): string { return $this->type === 'MIF' ? self::C_MIF_HDR : self::C_MRF_HDR; }
    private function rowBg(): string  { return $this->type === 'MIF' ? self::C_MIF_ROW : self::C_MRF_ROW; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);

            // ── Main Title ──
            $ws->mergeCells('A1:I1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $icon  = $this->type === 'MIF' ? '📤' : '📥';
            $label = $this->type === 'MIF' ? 'ALL MATERIAL ISSUE FORMS' : 'ALL MATERIAL RETURN FORMS';
            $ws->setCellValue('A1', $icon . '  ' . $label . ' — ' . ($this->project?->name ?? ''));
            $ws->getStyle('A1:I1')->applyFromArray([
                'font'      => ['bold' => true, 'size' => 15, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_DARK]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // ── Info ──
            $row = 2;
            $ws->getRowDimension($row)->setRowHeight(20);
            $ws->setCellValue('A'.$row, 'Generated:');
            $ws->setCellValue('B'.$row, now()->format('d M Y, H:i'));
            $ws->getStyle('A'.$row)->applyFromArray(['font' => ['bold' => true, 'name' => 'Arial', 'size' => 10]]);
            $ws->getStyle('B'.$row)->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10]]);
            $row++;

            // Spacer
            $ws->getRowDimension($row)->setRowHeight(10);
            $row++;

            // ── Column Headers ──
            if ($this->type === 'MIF') {
                $cols = [
                    'A' => ['#',             5],
                    'B' => ['Subcon',        22],
                    'C' => ['MIF Number',    18],
                    'D' => ['Date',          14],
                    'E' => ['Material Name', 30],
                    'F' => ['Part No.',      16],
                    'G' => ['Qty',           9],
                    'H' => ['Unit',          10],
                    'I' => ['Remarks',       24],
                ];
            } else {
                $cols = [
                    'A' => ['#',             5],
                    'B' => ['Subcon',        22],
                    'C' => ['MRF Number',    18],
                    'D' => ['Date',          14],
                    'E' => ['Material Name', 30],
                    'F' => ['Part No.',      16],
                    'G' => ['Qty',           9],
                    'H' => ['Unit',          10],
                    'I' => ['Condition',     14],
                ];
            }

            $ws->getRowDimension($row)->setRowHeight(28);
            foreach ($cols as $col => [$colLabel, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.$row, $colLabel);
                $ws->getStyle($col.$row)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->accent()]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);
            }
            $headerRow = $row;
            $row++;

            // ── Data grouped by subcon ──
            $grandTotal = 0;
            $grandCount = 0;
            $seq = 1;

            foreach ($this->subcons as $subcon) {
                // Get records for this subcon
                if ($this->type === 'MIF') {
                    $records = $subcon->mifs->sortBy('date')->flatMap(fn($mif) =>
                        $mif->items->map(fn($item) => [
                            'subcon'      => $subcon->name,
                            'doc_number'  => $mif->mif_number,
                            'date'        => $mif->date?->format('d M Y') ?? '—',
                            'item_name'   => $item->item_name,
                            'part_number' => $item->part_number ?? '—',
                            'quantity'    => $item->quantity,
                            'unit'        => $item->unit ?? '—',
                            'last_col'    => $item->remarks ?? '—',
                            'condition'   => null,
                        ])
                    );
                } else {
                    $records = $subcon->mrfs->sortBy('date')->flatMap(fn($mrf) =>
                        $mrf->items->map(fn($item) => [
                            'subcon'      => $subcon->name,
                            'doc_number'  => $mrf->mrf_number,
                            'date'        => $mrf->date?->format('d M Y') ?? '—',
                            'item_name'   => $item->item_name,
                            'part_number' => $item->part_number ?? '—',
                            'quantity'    => $item->quantity,
                            'unit'        => $item->unit ?? '—',
                            'last_col'    => ucfirst($item->condition ?? '—'),
                            'condition'   => $item->condition,
                        ])
                    );
                }

                if ($records->isEmpty()) continue;

                // ── Subcon separator row ──
                $ws->getRowDimension($row)->setRowHeight(22);
                $ws->mergeCells('A'.$row.':I'.$row);
                $ws->setCellValue('A'.$row, '🏢  ' . strtoupper($subcon->name) . '   |   Zone: ' . ($subcon->zone?->name ?? '—') . '   |   Items: ' . $records->count() . '   |   Total Qty: ' . $records->sum('quantity'));
                $ws->getStyle('A'.$row.':I'.$row)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0D47A1'], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_SUBCON_BG]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BBDEFB']]],
                ]);
                $row++;

                // ── Item rows ──
                foreach ($records as $item) {
                    $isDamaged = in_array($item['condition'], ['damaged', 'defect']);
                    $bg = $isDamaged ? self::C_DMG_ROW : ($seq % 2 === 0 ? $this->rowBg() : self::C_WHITE);

                    $ws->getRowDimension($row)->setRowHeight(20);
                    $ws->setCellValue('A'.$row, $seq);
                    $ws->setCellValue('B'.$row, $item['subcon']);
                    $ws->setCellValue('C'.$row, $item['doc_number']);
                    $ws->setCellValue('D'.$row, $item['date']);
                    $ws->setCellValue('E'.$row, $item['item_name']);
                    $ws->setCellValue('F'.$row, $item['part_number']);
                    $ws->setCellValue('G'.$row, $item['quantity']);
                    $ws->setCellValue('H'.$row, $item['unit']);
                    $ws->setCellValue('I'.$row, $item['last_col']);

                    $ws->getStyle('A'.$row.':I'.$row)->applyFromArray([
                        'font'      => ['size' => 10, 'name' => 'Arial'],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => ltrim($bg, '#')]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
                    ]);
                    foreach (['A', 'G', 'H'] as $c) {
                        $ws->getStyle($c.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    if ($isDamaged) {
                        $ws->getStyle('I'.$row)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => self::C_DMG_TXT], 'name' => 'Arial'],
                        ]);
                    }

                    $grandTotal += $item['quantity'];
                    $grandCount++;
                    $seq++;
                    $row++;
                }

                // Subcon subtotal row
                $ws->getRowDimension($row)->setRowHeight(20);
                $ws->mergeCells('A'.$row.':F'.$row);
                $ws->setCellValue('A'.$row, 'Subtotal — ' . $subcon->name . ' (' . $records->count() . ' items)');
                $ws->setCellValue('G'.$row, $records->sum('quantity'));
                $ws->getStyle('A'.$row.':I'.$row)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0D47A1'], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BBDEFB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BBDEFB']]],
                ]);
                $row++;

                // Spacer between subcons
                $ws->getRowDimension($row)->setRowHeight(6);
                $row++;
            }

            // ── Grand Total ──
            $ws->getRowDimension($row)->setRowHeight(28);
            $ws->mergeCells('A'.$row.':F'.$row);
            $ws->setCellValue('A'.$row, 'GRAND TOTAL — ' . $grandCount . ' line items across ' . $this->subcons->count() . ' subcons');
            $ws->setCellValue('G'.$row, $grandTotal);
            $ws->getStyle('A'.$row.':I'.$row)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_HDR]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);

            $ws->freezePane('A'.($headerRow + 1));
        }];
    }
}
