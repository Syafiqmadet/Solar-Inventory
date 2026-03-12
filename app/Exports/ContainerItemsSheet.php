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

class ContainerItemsSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E'; const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF'; const C_HDR    = '2D3748';
    const C_GREEN  = '28A745'; const C_BLUE   = '0D6EFD';
    const C_INFO   = '0DCAF0'; const C_GRAY   = '6C757D';

    private $containers;

    public function __construct(
        private ?string $search    = null,
        private ?string $status    = null,
        private ?string $batch     = null,
        private ?string $itemName  = null,
        private ?string $dateFrom  = null,
        private ?string $dateTo    = null,
        private ?int    $projectId = null,
    ) {
        $q = Container::with('items.item');
        $q->where('project_id', $this->projectId);
        if ($search) {
            $q->where(fn($sq) =>
                $sq->where('container_id','like',"%{$search}%")
                   ->orWhere('description','like',"%{$search}%")
                   ->orWhere('batch','like',"%{$search}%")
                   ->orWhereHas('items.item', fn($qi) =>
                       $qi->where('name','like',"%{$search}%"))
            );
        }
        if ($status)   $q->where('status', $status);
        if ($batch)    $q->where('batch','like',"%{$batch}%");
        if ($itemName) $q->whereHas('items.item', fn($qi) =>
            $qi->where('name','like',"%{$itemName}%"));
        if ($dateFrom) $q->whereDate('date_in','>=',$dateFrom);
        if ($dateTo)   $q->whereDate('date_in','<=',$dateTo);
        $this->containers = $q->latest()->get();
    }

    public function title(): string { return 'Container Items'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A5');

            // ── Title ──────────────────────────────────────────────
            $ws->mergeCells('A1:J1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '🔩  CONTAINER ITEMS DETAIL');
            $ws->getStyle('A1:J1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>16,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── Subtitle ───────────────────────────────────────────
            $totalItems = $this->containers->sum(fn($c) => $c->items->count());
            $totalQty   = $this->containers->sum(fn($c) => $c->items->sum('quantity'));
            $ws->mergeCells('A2:J2');
            $ws->getRowDimension(2)->setRowHeight(20);
            $ws->setCellValue('A2',
                $this->containers->count().' containers   |   '.$totalItems.' item lines   |   '.$totalQty.' total qty');
            $ws->getStyle('A2:J2')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_ORANGE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'FFF5F0']],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            $ws->getRowDimension(3)->setRowHeight(6);

            // ── Column headers ─────────────────────────────────────
            $cols = [
                'A'=>['#',5],             'B'=>['Container ID',18],
                'C'=>['Batch',14],        'D'=>['Status',12],
                'E'=>['Date In',13],      'F'=>['Item Name',28],
                'G'=>['Part Number',18],  'H'=>['Description',30],
                'I'=>['Quantity',11],     'J'=>['Notes',20],
            ];
            $ws->getRowDimension(4)->setRowHeight(26);
            foreach ($cols as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'4', $label);
                $ws->getStyle($col.'4')->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_ORANGE]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,
                                    'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            // ── Data rows — grouped by container ──────────────────
            $row = 5; $seq = 1;
            foreach ($this->containers as $c) {
                $status      = $c->status ?? 'active';
                $statusLbl   = match($status) {
                    'active'  => '✅ Active',
                    'closed'  => '🔒 Closed',
                    'pending' => '⏳ Pending',
                    default   => ucfirst($status),
                };
                $statusColor = match($status) {
                    'active'  => self::C_GREEN,
                    'closed'  => self::C_GRAY,
                    'pending' => '856404',
                    default   => '000000',
                };
                $dateIn  = $c->date_in ? \Carbon\Carbon::parse($c->date_in)->format('d M Y') : '—';
                $groupBg = match($status) {
                    'active'  => 'F0FFF4', 'closed' => 'F5F5F5', 'pending' => 'FFFDF0', default => 'F8F9FA',
                };

                if ($c->items->isEmpty()) {
                    $ws->getRowDimension($row)->setRowHeight(22);
                    $ws->setCellValue('A'.$row, $seq++);
                    $ws->setCellValue('B'.$row, $c->container_id);
                    $ws->setCellValue('C'.$row, $c->batch ?? '—');
                    $ws->setCellValue('D'.$row, $statusLbl);
                    $ws->setCellValue('E'.$row, $dateIn);
                    $ws->setCellValue('F'.$row, '(no items)');
                    foreach (['G','H','I','J'] as $col) $ws->setCellValue($col.$row, '—');
                    $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                        'font'      => ['size'=>10,'italic'=>true,'color'=>['rgb'=>'999999'],'name'=>'Arial'],
                        'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$groupBg]],
                        'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                    ]);
                    $ws->getStyle('B'.$row)->getFont()->setBold(true)->setItalic(false);
                    $ws->getStyle('C'.$row)->getFont()->getColor()->setRGB(self::C_INFO);
                    $ws->getStyle('C'.$row)->getFont()->setItalic(false)->setBold(true);
                    $ws->getStyle('D'.$row)->getFont()->setBold(true)->setItalic(false);
                    $ws->getStyle('D'.$row)->getFont()->getColor()->setRGB($statusColor);
                    $row++;
                } else {
                    foreach ($c->items as $idx => $ci) {
                        $ws->getRowDimension($row)->setRowHeight(20);
                        $bg = ($idx % 2 === 0) ? $groupBg : 'FFFFFF';

                        $ws->setCellValue('A'.$row, $idx === 0 ? $seq++ : '');
                        $ws->setCellValue('B'.$row, $idx === 0 ? $c->container_id : '');
                        $ws->setCellValue('C'.$row, $idx === 0 ? ($c->batch ?? '—') : '');
                        $ws->setCellValue('D'.$row, $idx === 0 ? $statusLbl : '');
                        $ws->setCellValue('E'.$row, $idx === 0 ? $dateIn : '');
                        $ws->setCellValue('F'.$row, $ci->item->name ?? '—');
                        $ws->setCellValue('G'.$row, $ci->part_number ?? $ci->item->part_number ?? '—');
                        $ws->setCellValue('H'.$row, $ci->description ?? $ci->item->description ?? '—');
                        $ws->setCellValue('I'.$row, (int)($ci->quantity ?? 1));
                        $ws->setCellValue('J'.$row, '');

                        $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                            'font'      => ['size'=>10,'name'=>'Arial'],
                            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                        ]);
                        $ws->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $ws->getStyle('H'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                        if ($idx === 0) {
                            $ws->getStyle('B'.$row)->getFont()->setBold(true);
                            $ws->getStyle('C'.$row)->getFont()->setBold(true);
                            $ws->getStyle('C'.$row)->getFont()->getColor()->setRGB(self::C_INFO);
                            $ws->getStyle('D'.$row)->getFont()->setBold(true);
                            $ws->getStyle('D'.$row)->getFont()->getColor()->setRGB($statusColor);
                        }
                        $row++;
                    }
                }

                // Thin separator between containers
                $ws->getRowDimension($row)->setRowHeight(4);
                $ws->getStyle('A'.$row.':J'.$row)->getFill()
                   ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9ECEF');
                $row++;
            }

            // ── Total row ──────────────────────────────────────────
            $ws->getRowDimension($row)->setRowHeight(24);
            $ws->mergeCells('A'.$row.':H'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL  —  '.$this->containers->count().' containers  /  '.$totalItems.' item lines');
            $ws->setCellValue('I'.$row, $totalQty);
            $ws->setCellValue('J'.$row, '');
            $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);
        }];
    }
}
