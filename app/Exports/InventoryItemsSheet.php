<?php
namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InventoryItemsSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E';
    const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF';
    const C_HDR    = '2D3748';
    const C_GREEN  = '28A745';
    const C_RED    = 'DC3545';
    const C_YELLOW = 'FFC107';
    const C_BLUE   = '0D6EFD';
    const C_LIGHT  = 'F8F9FA';

    private $items;
    private array $filters;

    public function __construct(
        private ?string $search,
        private ?string $category,
        private ?string $stock,
        private ?int    $projectId = null,
    ) {
        $q = Item::query();
        $q->where('project_id', $this->projectId);
        if ($search) {
            $q->where(fn($sq) =>
                $sq->where('name',        'like', "%{$search}%")
                   ->orWhere('part_number','like', "%{$search}%")
                   ->orWhere('description','like', "%{$search}%")
                   ->orWhere('category',  'like', "%{$search}%")
            );
        }
        if ($category) $q->where('category', $category);
        if ($stock === 'low')      $q->whereRaw('current_stock <= min_stock AND current_stock > 0');
        if ($stock === 'out')      $q->where('current_stock', '<=', 0);
        if ($stock === 'ok')       $q->whereRaw('current_stock > min_stock');
        $this->items = $q->orderBy('name')->get();

        $this->filters = array_filter([
            'Search'   => $search,
            'Category' => $category,
            'Stock'    => $stock ? ucfirst($stock).' stock' : null,
        ]);
    }

    public function title(): string { return 'Inventory'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A7');

            // ── Title ──────────────────────────────────────────────
            $ws->mergeCells('A1:K1');
            $ws->getRowDimension(1)->setRowHeight(48);
            $ws->setCellValue('A1', '☀️  SOLAR INVENTORY REPORT');
            $ws->getStyle('A1:K1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>22,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── Subtitle / filters row ─────────────────────────────
            $ws->mergeCells('A2:K2');
            $ws->getRowDimension(2)->setRowHeight(18);
            $filterStr = empty($this->filters)
                ? 'All items — no filters applied'
                : 'Filters: '.implode('   |   ', array_map(fn($k,$v) => "$k: $v", array_keys($this->filters), $this->filters));
            $ws->setCellValue('A2', 'Generated: '.now()->format('d F Y  H:i').'     '.$filterStr);
            $ws->getStyle('A2:K2')->applyFromArray([
                'font'      => ['size'=>10,'color'=>['rgb'=>'888888'],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F0F2F5']],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            $ws->getRowDimension(3)->setRowHeight(6);

            // ── KPI Cards ──────────────────────────────────────────
            $total     = $this->items->count();
            $okCount   = $this->items->filter(fn($i) => $i->current_stock > $i->min_stock)->count();
            $lowCount  = $this->items->filter(fn($i) => $i->current_stock > 0 && $i->current_stock <= $i->min_stock)->count();
            $outCount  = $this->items->filter(fn($i) => $i->current_stock <= 0)->count();
            $totalQty  = $this->items->sum('current_stock');
            $cats      = $this->items->pluck('category')->filter()->unique()->count();

            $ws->getRowDimension(4)->setRowHeight(18);
            $ws->getRowDimension(5)->setRowHeight(36);
            $kpis = [
                ['A4:B5', "📦 Total Items\n{$total}",        'E8EAF6',      self::C_DARK],
                ['C4:D5', "✅ OK Stock\n{$okCount}",          'D4EDDA',      self::C_GREEN],
                ['E4:F5', "⚠️ Low Stock\n{$lowCount}",        'FFF3CD',      '856404'],
                ['G4:H5', "❌ Out of Stock\n{$outCount}",     'F8D7DA',      self::C_RED],
                ['I4:J5', "🔢 Total Units\n{$totalQty}",      'CCE5FF',      self::C_BLUE],
                ['K4:K5', "📂 Categories\n{$cats}",           'FFF0E6',      self::C_ORANGE],
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

            $ws->getRowDimension(6)->setRowHeight(6);

            // ── Table header ───────────────────────────────────────
            $cols = [
                'A' => ['#',          4],
                'B' => ['Color',      6],
                'C' => ['Part Number',18],
                'D' => ['Name',       28],
                'E' => ['Description',32],
                'F' => ['Category',   14],
                'G' => ['Unit',        8],
                'H' => ['Stock',      10],
                'I' => ['Min Stock',  10],
                'J' => ['Status',     14],
                'K' => ['Isolated',   10],
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
            foreach ($this->items as $seq => $item) {
                $ws->getRowDimension($row)->setRowHeight(22);

                $stock   = (int)$item->current_stock;
                $min     = (int)$item->min_stock;
                $isOut   = $stock <= 0;
                $isLow   = !$isOut && $stock <= $min;
                $isOk    = !$isOut && !$isLow;

                $statusLbl   = $isOut ? '❌ Out of Stock' : ($isLow ? '⚠️ Low Stock' : '✅ OK');
                $statusColor = $isOut ? self::C_RED : ($isLow ? '856404' : self::C_GREEN);
                $rowBg       = $isOut ? 'FFF5F5' : ($isLow ? 'FFFDF0' : ($row % 2 === 0 ? self::C_LIGHT : self::C_WHITE));

                // Isolated qty for this item
                $isolatedQty = $item->isolatedItems()->sum('quantity') ?? 0;

                // Color hex
                $colorHex = $item->color_code ? ltrim($item->color_code, '#') : null;
                if ($colorHex && strlen($colorHex) === 3) {
                    $colorHex = $colorHex[0].$colorHex[0].$colorHex[1].$colorHex[1].$colorHex[2].$colorHex[2];
                }

                $ws->setCellValue('A'.$row, $seq + 1);
                $ws->setCellValue('B'.$row, '');  // painted cell
                $ws->setCellValue('C'.$row, $item->part_number);
                $ws->setCellValue('D'.$row, $item->name);
                $ws->setCellValue('E'.$row, $item->description ?? '—');
                $ws->setCellValue('F'.$row, $item->category ?? '—');
                $ws->setCellValue('G'.$row, $item->unit ?? 'pcs');
                $ws->setCellValue('H'.$row, $stock);
                $ws->setCellValue('I'.$row, $min);
                $ws->setCellValue('J'.$row, $statusLbl);
                $ws->setCellValue('K'.$row, $isolatedQty > 0 ? $isolatedQty : '—');

                $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                    'font'      => ['size'=>10,'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$rowBg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'DDDDDD']]],
                ]);

                // Left-align text columns
                foreach (['C','D','E','F'] as $c) {
                    $ws->getStyle($c.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // Bold part number and name
                $ws->getStyle('C'.$row)->getFont()->setBold(true);
                $ws->getStyle('D'.$row)->getFont()->setBold(true);

                // Status color
                $ws->getStyle('J'.$row)->getFont()->setBold(true);
                $ws->getStyle('J'.$row)->getFont()->getColor()->setRGB($statusColor);

                // Isolated qty red if > 0
                if ($isolatedQty > 0) {
                    $ws->getStyle('K'.$row)->getFont()->setBold(true);
                    $ws->getStyle('K'.$row)->getFont()->getColor()->setRGB(self::C_RED);
                }

                // Paint color cell
                if ($colorHex && preg_match('/^[0-9A-Fa-f]{6}$/', $colorHex)) {
                    $ws->getStyle('B'.$row)->getFill()
                       ->setFillType(Fill::FILL_SOLID)
                       ->getStartColor()->setRGB($colorHex);
                }

                $row++;
            }

            // ── Total row ──────────────────────────────────────────
            $ws->getRowDimension($row)->setRowHeight(26);
            $ws->mergeCells('A'.$row.':G'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL  —  '.$this->items->count().' items');
            $ws->setCellValue('H'.$row, $this->items->sum('current_stock'));
            foreach (['I','J','K'] as $c) $ws->setCellValue($c.$row, '');
            $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);
        }];
    }
}
