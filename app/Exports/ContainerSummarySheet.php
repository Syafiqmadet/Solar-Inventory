<?php
namespace App\Exports;

use App\Models\Container;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ContainerSummarySheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E'; const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF'; const C_HDR    = '2D3748';
    const C_GREEN  = '28A745'; const C_YELLOW = 'FFC107';
    const C_GRAY   = '6C757D'; const C_BLUE   = '0D6EFD';
    const C_INFO   = '0DCAF0'; const C_LIGHT  = 'F8F9FA';
    const C_GRAY2  = 'E9ECEF';

    private $containers;
    private array $filters;

    public function __construct(
        private ?string $search    = null,
        private ?string $status    = null,
        private ?string $batch     = null,
        private ?string $itemName  = null,
        private ?string $dateFrom  = null,
        private ?string $dateTo    = null,
        private ?int    $projectId = null,
    ) {
        $this->containers = $this->buildQuery()->latest()->get();
        $this->filters = array_filter([
            'Search'    => $search,
            'Batch'     => $batch,
            'Item Name' => $itemName,
            'Status'    => $status ? ucfirst($status) : null,
            'Date From' => $dateFrom,
            'Date To'   => $dateTo,
        ]);
    }

    private function buildQuery()
    {
        $q = Container::with('items.item');
        $q->where('project_id', $this->projectId);
        if ($this->search) {
            $q->where(fn($sq) =>
                $sq->where('container_id', 'like', "%{$this->search}%")
                   ->orWhere('description', 'like', "%{$this->search}%")
                   ->orWhere('batch', 'like', "%{$this->search}%")
                   ->orWhereHas('items.item', fn($qi) =>
                       $qi->where('name', 'like', "%{$this->search}%"))
            );
        }
        if ($this->status)   $q->where('status', $this->status);
        if ($this->batch)    $q->where('batch', 'like', "%{$this->batch}%");
        if ($this->itemName) $q->whereHas('items.item', fn($qi) =>
            $qi->where('name', 'like', "%{$this->itemName}%"));
        if ($this->dateFrom) $q->whereDate('date_in', '>=', $this->dateFrom);
        if ($this->dateTo)   $q->whereDate('date_in', '<=', $this->dateTo);
        return $q;
    }

    public function title(): string { return 'Containers'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A6');

            // ── Title ──────────────────────────────────────────────
            $ws->mergeCells('A1:L1');
            $ws->getRowDimension(1)->setRowHeight(46);
            $ws->setCellValue('A1', '📦  CONTAINER REPORT');
            $ws->getStyle('A1:L1')->applyFromArray([
                'font'      => ['bold' => true, 'size' => 20, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_DARK]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // ── Generated / filters row ────────────────────────────
            $ws->mergeCells('A2:L2');
            $ws->getRowDimension(2)->setRowHeight(18);
            $filterStr = empty($this->filters)
                ? 'All containers — no filters applied'
                : 'Filters: ' . implode('   |   ', array_map(fn($k, $v) => "$k: $v", array_keys($this->filters), $this->filters));
            $ws->setCellValue('A2', 'Generated: ' . now()->format('d F Y  H:i') . '     ' . $filterStr);
            $ws->getStyle('A2:L2')->applyFromArray([
                'font'      => ['size' => 10, 'color' => ['rgb' => '888888'], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F2F5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $ws->getRowDimension(3)->setRowHeight(6);

            // ── KPI Cards ──────────────────────────────────────────
            $total    = $this->containers->count();
            $active   = $this->containers->where('status', 'active')->count();
            $closed   = $this->containers->where('status', 'closed')->count();
            $pending  = $this->containers->where('status', 'pending')->count();
            $itemsCnt = $this->containers->sum(fn($c) => $c->items->count());
            $batches  = $this->containers->whereNotNull('batch')->pluck('batch')->unique()->count();

            $ws->getRowDimension(4)->setRowHeight(18);
            $ws->getRowDimension(5)->setRowHeight(34);
            $kpis = [
                ['A4:B5', "📦 Total\n{$total}",       self::C_DARK,  self::C_DARK],
                ['C4:D5', "✅ Active\n{$active}",      'D4EDDA',      self::C_GREEN],
                ['E4:F5', "🔒 Closed\n{$closed}",      self::C_GRAY2, self::C_GRAY],
                ['G4:H5', "⏳ Pending\n{$pending}",    'FFF3CD',      '856404'],
                ['I4:J5', "🔩 Items\n{$itemsCnt}",     'CCE5FF',      self::C_BLUE],
                ['K4:L5', "📋 Batches\n{$batches}",    'FFF0F5',      self::C_ORANGE],
            ];
            foreach ($kpis as [$rng, $txt, $bg, $fc]) {
                $ws->mergeCells($rng);
                $first = explode(':', $rng)[0];
                $ws->setCellValue($first, $txt);
                $ws->getStyle($rng)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $fc], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical'   => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);
            }

            $ws->getRowDimension(6)->setRowHeight(6);

            // ── Table headers ──────────────────────────────────────
            // G = Total Qty (was Duration)
            // I = Part Number (was Status)
            // J = Color — cell is FILLED with actual colour, no text
            $cols = [
                'A' => ['#',              5],
                'B' => ['Container ID',  18],
                'C' => ['Batch',         14],
                'D' => ['Description',   28],
                'E' => ['Date In',       13],
                'F' => ['Date Out',      13],
                'G' => ['Total Qty',     12],   // ← was Duration
                'H' => ['Items',         10],
                'I' => ['Part Number',   18],   // ← was Status
                'J' => ['Color',          8],   // ← filled with actual colour
                'K' => ['Items Summary', 30],
                'L' => ['Last Updated',  15],
            ];
            $ws->getRowDimension(7)->setRowHeight(26);
            foreach ($cols as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col . '7', $label);
                $ws->getStyle($col . '7')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_ORANGE]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical'   => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);
            }

            // ── Data rows ──────────────────────────────────────────
            $row = 8;
            foreach ($this->containers as $seq => $c) {
                $ws->getRowDimension($row)->setRowHeight(22);
                $bg = ($row % 2 === 0) ? self::C_LIGHT : self::C_WHITE;

                $dateIn  = $c->date_in  ? \Carbon\Carbon::parse($c->date_in)->format('d M Y')  : '—';
                $dateOut = $c->date_out ? \Carbon\Carbon::parse($c->date_out)->format('d M Y') : '—';

                // G — total quantity across all items in this container
                $totalQty = $c->items->sum('quantity');

                // I — collect part numbers from items (up to 3)
                $partNumbers = $c->items
                    ->map(fn($i) => $i->part_number ?? $i->item?->part_number ?? null)
                    ->filter()
                    ->unique()
                    ->take(3)
                    ->join(', ');
                if (!$partNumbers) $partNumbers = '—';

                // K — item name summary
                $itemSummary = $c->items->take(3)->map(fn($i) => $i->item->name ?? $i->description ?? '—')->join(', ');
                if ($c->items->count() > 3) $itemSummary .= ' +' . ($c->items->count() - 3) . ' more';

                // J — parse colour hex for cell fill (strip #, expand 3-char shorthand)
                $colorHex = $c->color_code ? ltrim($c->color_code, '#') : null;
                if ($colorHex && strlen($colorHex) === 3) {
                    $colorHex = $colorHex[0] . $colorHex[0]
                              . $colorHex[1] . $colorHex[1]
                              . $colorHex[2] . $colorHex[2];
                }
                $validColor = $colorHex && preg_match('/^[0-9A-Fa-f]{6}$/', $colorHex);

                $ws->setCellValue('A' . $row, $seq + 1);
                $ws->setCellValue('B' . $row, $c->container_id);
                $ws->setCellValue('C' . $row, $c->batch ?? '—');
                $ws->setCellValue('D' . $row, $c->description ?? '—');
                $ws->setCellValue('E' . $row, $dateIn);
                $ws->setCellValue('F' . $row, $dateOut);
                $ws->setCellValue('G' . $row, $totalQty);      // Total Qty
                $ws->setCellValue('H' . $row, $c->items->count());
                $ws->setCellValue('I' . $row, $partNumbers);   // Part Number(s)
                $ws->setCellValue('J' . $row, '');              // no text — painted below
                $ws->setCellValue('K' . $row, $itemSummary ?: '—');
                $ws->setCellValue('L' . $row, $c->updated_at?->format('d M Y') ?? '—');

                // Base row style
                $ws->getStyle('A' . $row . ':L' . $row)->applyFromArray([
                    'font'      => ['size' => 10, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);

                // Per-column overrides
                $ws->getStyle('B' . $row)->getFont()->setBold(true);
                $ws->getStyle('C' . $row)->getFont()->getColor()->setRGB(self::C_INFO);
                $ws->getStyle('C' . $row)->getFont()->setBold(true);
                $ws->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle('K' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // J — fill cell with the actual container colour
                if ($validColor) {
                    $ws->getStyle('J' . $row)->getFill()
                       ->setFillType(Fill::FILL_SOLID)
                       ->getStartColor()->setRGB($colorHex);
                } else {
                    $ws->setCellValue('J' . $row, '—');
                }

                $row++;
            }

            // ── Total row ──────────────────────────────────────────
            $ws->getRowDimension($row)->setRowHeight(26);
            $ws->mergeCells('A' . $row . ':F' . $row);
            $ws->setCellValue('A' . $row, 'TOTAL  —  ' . $this->containers->count() . ' containers');
            $ws->setCellValue('G' . $row, $this->containers->sum(fn($c) => $c->items->sum('quantity')));
            $ws->setCellValue('H' . $row, $itemsCnt);
            foreach (['I', 'J', 'K', 'L'] as $c) $ws->setCellValue($c . $row, '');
            $ws->getStyle('A' . $row . ':L' . $row)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_HDR]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }];
    }
}
