<?php
namespace App\Exports;

use App\Models\Subcon;
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
    const C_DARK   = '1A1A2E';
    const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF';
    const C_HDR    = '2D3748';
    const C_LIGHT  = 'F8F9FA';
    const C_MIF    = 'E8F5E9'; // light green for MIF rows
    const C_MRF    = 'FFF3E0'; // light orange for MRF rows
    const C_DMG    = 'FFEBEE'; // light red for damaged/defect rows
    const C_MIF_H  = '2E7D32'; // dark green MIF header
    const C_MRF_H  = 'E65100'; // dark orange MRF header

    public function __construct(private Subcon $subcon, private ?Project $project) {}

    public function title(): string
    {
        return substr(preg_replace('/[\/\\\?\*\[\]:]/', '-', $this->subcon->name), 0, 31);
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);

            $row = 1;

            // ── Main Title ──
            $ws->mergeCells('A1:H1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '📦  MATERIAL USAGE REPORT — ' . strtoupper($this->subcon->name));
            $ws->getStyle('A1:H1')->applyFromArray([
                'font'      => ['bold' => true, 'size' => 15, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_DARK]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $row = 2;

            // ── Info Block ──
            $info = [
                ['Subcontractor', $this->subcon->name,                    'Project',  $this->project?->name ?? '—'],
                ['Zone',          $this->subcon->zone?->name ?? '—',      'Status',   ucfirst($this->subcon->status ?? '—')],
                ['Supervisor',    $this->subcon->supervisor_name ?? '—',  'Contact',  $this->subcon->supervisor_contact ?? '—'],
                ['Generated',     now()->format('d M Y, H:i'),            '',         ''],
            ];

            foreach ($info as $line) {
                $ws->getRowDimension($row)->setRowHeight(20);
                $ws->setCellValue('A'.$row, $line[0]);
                $ws->mergeCells('B'.$row.':C'.$row);
                $ws->setCellValue('B'.$row, $line[1]);
                $ws->setCellValue('E'.$row, $line[2]);
                $ws->mergeCells('F'.$row.':H'.$row);
                $ws->setCellValue('F'.$row, $line[3]);
                $ws->getStyle('A'.$row)->applyFromArray(['font' => ['bold' => true, 'name' => 'Arial', 'size' => 10]]);
                $ws->getStyle('E'.$row)->applyFromArray(['font' => ['bold' => true, 'name' => 'Arial', 'size' => 10]]);
                foreach (['B'.$row, 'F'.$row] as $c) {
                    $ws->getStyle($c)->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 10],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
                    ]);
                }
                $row++;
            }

            // Spacer
            $ws->getRowDimension($row)->setRowHeight(10);
            $row++;

            // ── Column Headers ──
            $cols = [
                'A' => ['#',         5],
                'B' => ['Doc No.',   18],
                'C' => ['Type',      8],
                'D' => ['Date',      14],
                'E' => ['Material Name', 28],
                'F' => ['Part No.',  16],
                'G' => ['Qty',       9],
                'H' => ['Unit',      10],
            ];

            $ws->getRowDimension($row)->setRowHeight(28);
            foreach ($cols as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.$row, $label);
                $ws->getStyle($col.$row)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_ORANGE]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);
            }

            $headerRow = $row;
            $row++;

            // ── MIF Rows ──
            $mifItems = $this->subcon->mifs->flatMap(function ($mif) {
                return $mif->items->map(function ($item) use ($mif) {
                    return [
                        'doc_number'  => $mif->mif_number,
                        'type'        => 'MIF',
                        'date'        => $mif->date?->format('d M Y') ?? '—',
                        'item_name'   => $item->item_name,
                        'part_number' => $item->part_number ?? '—',
                        'quantity'    => $item->quantity,
                        'unit'        => $item->unit ?? '—',
                        'condition'   => null,
                    ];
                });
            });

            // ── MRF Rows ──
            $mrfItems = $this->subcon->mrfs->flatMap(function ($mrf) {
                return $mrf->items->map(function ($item) use ($mrf) {
                    return [
                        'doc_number'  => $mrf->mrf_number,
                        'type'        => 'MRF',
                        'date'        => $mrf->date?->format('d M Y') ?? '—',
                        'item_name'   => $item->item_name,
                        'part_number' => $item->part_number ?? '—',
                        'quantity'    => $item->quantity,
                        'unit'        => $item->unit ?? '—',
                        'condition'   => $item->condition,
                    ];
                });
            });

            $allItems = $mifItems->concat($mrfItems)->sortBy('date');
            $seq = 1;

            foreach ($allItems as $item) {
                $isMif     = $item['type'] === 'MIF';
                $isDamaged = in_array($item['condition'], ['damaged', 'defect']);

                if ($isDamaged) {
                    $bg = self::C_DMG;
                } elseif ($isMif) {
                    $bg = $seq % 2 === 0 ? self::C_MIF : self::C_WHITE;
                } else {
                    $bg = $seq % 2 === 0 ? self::C_MRF : self::C_WHITE;
                }

                $ws->getRowDimension($row)->setRowHeight(20);
                $ws->setCellValue('A'.$row, $seq);
                $ws->setCellValue('B'.$row, $item['doc_number']);
                $ws->setCellValue('C'.$row, $item['type']);
                $ws->setCellValue('D'.$row, $item['date']);
                $ws->setCellValue('E'.$row, $item['item_name']);
                $ws->setCellValue('F'.$row, $item['part_number']);
                $ws->setCellValue('G'.$row, $item['quantity']);
                $ws->setCellValue('H'.$row, $item['unit'] . ($item['condition'] ? ' (' . ucfirst($item['condition']) . ')' : ''));

                $ws->getStyle('A'.$row.':H'.$row)->applyFromArray([
                    'font'      => ['size' => 10, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => ltrim($bg, '#')]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
                ]);

                // Center number columns
                foreach (['A', 'C', 'G', 'H'] as $c) {
                    $ws->getStyle($c.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Color MIF/MRF type badge
                $typeColor = $isMif ? self::C_MIF_H : self::C_MRF_H;
                $ws->getStyle('C'.$row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $typeColor], 'name' => 'Arial', 'size' => 10],
                ]);

                $seq++;
                $row++;
            }

            // ── Totals Row ──
            $ws->getRowDimension($row)->setRowHeight(26);
            $ws->mergeCells('A'.$row.':F'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL — ' . $allItems->count() . ' line items  |  MIF: ' . $mifItems->count() . '  |  MRF: ' . $mrfItems->count());
            $ws->setCellValue('G'.$row, $allItems->sum('quantity'));
            $ws->getStyle('A'.$row.':H'.$row)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_HDR]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);

            // ── Legend ──
            $row += 2;
            $ws->getRowDimension($row)->setRowHeight(18);
            $ws->setCellValue('A'.$row, 'Legend:');
            $ws->getStyle('A'.$row)->applyFromArray(['font' => ['bold' => true, 'name' => 'Arial', 'size' => 10]]);

            $legends = [
                ['B', self::C_MIF_H, 'MIF = Material Issued to Subcon'],
                ['D', self::C_MRF_H, 'MRF = Material Returned from Subcon'],
                ['F', 'C62828',      'Pink rows = Damaged / Defect items'],
            ];
            foreach ($legends as [$col, $color, $label]) {
                $ws->setCellValue($col.$row, $label);
                $ws->getStyle($col.$row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $color], 'name' => 'Arial', 'size' => 10],
                ]);
            }

            $ws->freezePane('A'.($headerRow + 1));
        }];
    }
}
