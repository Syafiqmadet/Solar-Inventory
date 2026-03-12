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
use Carbon\Carbon;

class ContainerDetailSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK    = '1A1A2E';
    const C_ORANGE  = 'FF6B35';
    const C_ACTIVE  = '28A745';
    const C_PEND    = 'D97706';
    const C_CLOSED  = '6C757D';
    const C_AL      = 'E8F8EE';
    const C_PL      = 'FFF9E6';
    const C_CL      = 'F0F0F0';
    const C_HDR     = '2D3748';
    const C_WHITE   = 'FFFFFF';
    const C_ITEM_BG = 'F0F4FF';

    private $containers;

    public function __construct(private ?int $projectId = null)
    {
        $q = Container::with('items.item')->latest();
        $q->where('project_id', $this->projectId);
        $this->containers = $q->get();
    }

    public function title(): string { return 'Container Details'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A3');

            // Column widths
            $cols = [
                'A'=>5, 'B'=>20, 'C'=>28, 'D'=>12, 'E'=>13,
                'F'=>13,'G'=>15, 'H'=>22, 'I'=>18, 'J'=>12, 'K'=>15,
            ];
            foreach ($cols as $col => $w)
                $ws->getColumnDimension($col)->setWidth($w);

            // Title
            $ws->mergeCells('A1:K1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '📦  CONTAINER DETAILS — ITEMS PER CONTAINER');
            $ws->getStyle('A1:K1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>16,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // Stats bar
            $ws->mergeCells('A2:K2');
            $ws->getRowDimension(2)->setRowHeight(20);
            $total = $this->containers->count();
            $totalItems = $this->containers->sum(fn($c)=>$c->items->count());
            $totalQty   = $this->containers->sum(fn($c)=>$c->items->sum('quantity'));
            $ws->setCellValue('A2',
                'Total Containers: '.$total.
                '   |   Total Item Lines: '.$totalItems.
                '   |   Total Quantity: '.$totalQty
            );
            $ws->getStyle('A2:K2')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_ORANGE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'FFF5F0']],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            $row = 3;

            foreach ($this->containers as $c) {
                $dateIn  = $c->date_in  ? Carbon::parse($c->date_in)  : null;
                $dateOut = $c->date_out ? Carbon::parse($c->date_out) : null;
                $days    = $dateIn ? ($dateOut ? $dateIn->diffInDays($dateOut) : $dateIn->diffInDays(now())) : null;

                $statusLabel = match($c->status) {
                    'active'  => '✅ Active',
                    'pending' => '⏳ Pending',
                    default   => '🔒 Closed',
                };
                $statusColor = match($c->status) {
                    'active'  => self::C_ACTIVE,
                    'pending' => self::C_PEND,
                    default   => self::C_CLOSED,
                };
                $statusBg = match($c->status) {
                    'active'  => self::C_AL,
                    'pending' => self::C_PL,
                    default   => self::C_CL,
                };

                // ── Container header row ─────────────────────────
                $ws->getRowDimension($row)->setRowHeight(28);
                $ws->mergeCells('A'.$row.':B'.$row);
                $ws->setCellValue('A'.$row, '📦 '.$c->container_id);
                $ws->mergeCells('C'.$row.':E'.$row);
                $ws->setCellValue('C'.$row, $c->description ?? '');
                $ws->setCellValue('F'.$row, $statusLabel);
                $ws->setCellValue('G'.$row, $dateIn  ? $dateIn->format('d M Y')  : '—');
                $ws->setCellValue('H'.$row, $dateOut ? $dateOut->format('d M Y') : 'Still In');
                $ws->setCellValue('I'.$row, $days !== null ? $days.' days' : '—');
                $ws->setCellValue('J'.$row, $c->items->count().' items');
                $ws->setCellValue('K'.$row, 'Total qty: '.$c->items->sum('quantity'));

                $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_HDR],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'E8ECF4']],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'AAAAAA']]],
                ]);
                $ws->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle('C'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                // Color the status cell
                $ws->getStyle('F'.$row)->applyFromArray([
                    'font' => ['bold'=>true,'color'=>['rgb'=>$statusColor],'name'=>'Arial'],
                    'fill' => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$statusBg]],
                ]);
                $row++;

                if ($c->items->isEmpty()) {
                    // Empty container row
                    $ws->getRowDimension($row)->setRowHeight(18);
                    $ws->mergeCells('A'.$row.':K'.$row);
                    $ws->setCellValue('A'.$row, '(No items recorded for this container)');
                    $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                        'font'      => ['italic'=>true,'size'=>10,'color'=>['rgb'=>'999999'],'name'=>'Arial'],
                        'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'FAFAFA']],
                        'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'EEEEEE']]],
                    ]);
                    $row++;
                } else {
                    // ── Item sub-header ──────────────────────────
                    $ws->getRowDimension($row)->setRowHeight(20);
                    $itemHdrs = ['A'=>'#','B'=>'Part Number','C'=>'Item Name',
                                 'D'=>'Category','E'=>'Unit','F'=>'Qty',
                                 'G'=>'Description','H'=>'','I'=>'','J'=>'','K'=>''];
                    foreach ($itemHdrs as $col => $lbl) {
                        $ws->setCellValue($col.$row, $lbl);
                    }
                    $ws->mergeCells('G'.$row.':K'.$row);
                    $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                        'font'      => ['bold'=>true,'size'=>9,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                        'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'4A6FA5']],
                        'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                    ]);
                    $ws->getStyle('G'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $row++;

                    // ── Item rows ────────────────────────────────
                    $itemSeq = 1;
                    foreach ($c->items as $ci) {
                        $ws->getRowDimension($row)->setRowHeight(18);
                        $bg = ($itemSeq % 2 === 0) ? self::C_ITEM_BG : 'FFFFFF';

                        $ws->setCellValue('A'.$row, $itemSeq++);
                        $ws->setCellValue('B'.$row, $ci->part_number ?? ($ci->item?->part_number ?? '—'));
                        $ws->setCellValue('C'.$row, $ci->item?->name ?? '—');
                        $ws->setCellValue('D'.$row, $ci->item?->category ?? '—');
                        $ws->setCellValue('E'.$row, $ci->item?->unit ?? '—');
                        $ws->setCellValue('F'.$row, $ci->quantity);
                        $ws->mergeCells('G'.$row.':K'.$row);
                        $ws->setCellValue('G'.$row, $ci->description ?? $ci->item?->description ?? '—');

                        $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                            'font'      => ['size'=>9,'name'=>'Arial'],
                            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'EEEEEE']]],
                        ]);
                        $ws->getStyle('C'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $ws->getStyle('G'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $ws->getStyle('F'.$row)->getFont()->setBold(true);
                        $ws->getStyle('F'.$row)->getFont()->getColor()->setRGB('4A6FA5');
                        $row++;
                    }

                    // ── Container sub-total ──────────────────────
                    $ws->getRowDimension($row)->setRowHeight(18);
                    $ws->mergeCells('A'.$row.':E'.$row);
                    $ws->setCellValue('A'.$row, 'Sub-total: '.($itemSeq-1).' item lines');
                    $ws->setCellValue('F'.$row, $c->items->sum('quantity'));
                    $ws->mergeCells('G'.$row.':K'.$row);
                    $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                        'font'      => ['bold'=>true,'size'=>9,'color'=>['rgb'=>'FFFFFF'],'name'=>'Arial'],
                        'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'4A6FA5']],
                        'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                    ]);
                    $row++;
                }

                // Spacer between containers
                $ws->getRowDimension($row)->setRowHeight(8);
                $ws->mergeCells('A'.$row.':K'.$row);
                $ws->getStyle('A'.$row.':K'.$row)->getFill()
                   ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
                $row++;
            }
        }];
    }
}
