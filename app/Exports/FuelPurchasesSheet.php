<?php
namespace App\Exports;

use App\Models\FuelRecord;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FuelPurchasesSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E'; const C_ORANGE = 'FF6B35';
    const C_GREEN  = '28A745'; const C_BLUE   = '0D6EFD';
    const C_PL     = 'E8F8EE'; const C_DL     = 'E8F0FF';
    const C_WHITE  = 'FFFFFF';

    public function __construct(private ?int $projectId = null) {}

    public function title(): string { return 'Fuel Purchases'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws       = $e->sheet->getDelegate();
            $pid      = $this->projectId;
            $q        = FuelRecord::query()->where('project_id', $pid);
            $records  = (clone $q)->orderByDesc('date')->get();
            $petrolL  = (float)(clone $q)->where('fuel_type','petrol')->sum('liters');
            $dieselL  = (float)(clone $q)->where('fuel_type','diesel')->sum('liters');
            $petrolRM = (float)(clone $q)->where('fuel_type','petrol')->sum('amount_rm');
            $dieselRM = (float)(clone $q)->where('fuel_type','diesel')->sum('amount_rm');

            $ws->setShowGridlines(false);
            $ws->freezePane('A5');

            // Title
            $ws->mergeCells('A1:K1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '🧾  FUEL PURCHASE RECORDS (Deliveries / DO)');
            $ws->getStyle('A1:K1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>15,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // Summary bar
            $ws->mergeCells('A2:K2');
            $ws->getRowDimension(2)->setRowHeight(22);
            $ws->setCellValue('A2',
                '⛽ Petrol: '.number_format($petrolL,2).' L  (RM '.number_format($petrolRM,2).')'.
                '    |    '.
                '🛢️ Diesel: '.number_format($dieselL,2).' L  (RM '.number_format($dieselRM,2).')'.
                '    |    Total: RM '.number_format($petrolRM+$dieselRM,2)
            );
            $ws->getStyle('A2:K2')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_ORANGE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'FFF5F0']],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            $ws->getRowDimension(3)->setRowHeight(6);

            // Headers
            $cols = [
                'A'=>['#',5], 'B'=>['Date',13], 'C'=>['Fuel Type',12],
                'D'=>['Liters (L)',14], 'E'=>['Amount (RM)',16], 'F'=>['RM / Liter',14],
                'G'=>['DO Number',20], 'H'=>['Supplier',22],
                'I'=>['Vehicle No.',14], 'J'=>['Notes',25], 'K'=>['DO Image',10],
            ];
            $ws->getRowDimension(4)->setRowHeight(26);
            foreach ($cols as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'4', $label);
                $ws->getStyle($col.'4')->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,
                                    'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            // Data rows
            $row = 5; $seq = 1;
            foreach ($records as $p) {
                $ws->getRowDimension($row)->setRowHeight(20);
                $isPetrol = $p->fuel_type === 'petrol';
                $bg       = $isPetrol ? self::C_PL : self::C_DL;
                $fc       = $isPetrol ? self::C_GREEN : self::C_BLUE;
                $rml      = $p->liters > 0 ? round($p->amount_rm / $p->liters, 4) : 0;

                $ws->setCellValue('A'.$row, $seq++);
                $ws->setCellValue('B'.$row, $p->date->format('d M Y'));
                $ws->setCellValue('C'.$row, ($isPetrol ? '⛽ ' : '🛢️ ').ucfirst($p->fuel_type));
                $ws->setCellValue('D'.$row, (float)$p->liters);
                $ws->setCellValue('E'.$row, (float)$p->amount_rm);
                $ws->setCellValue('F'.$row, $rml);
                $ws->setCellValue('G'.$row, $p->do_number  ?? '—');
                $ws->setCellValue('H'.$row, $p->supplier   ?? '—');
                $ws->setCellValue('I'.$row, $p->vehicle_no ?? '—');
                $ws->setCellValue('J'.$row, $p->notes      ?? '—');
                $ws->setCellValue('K'.$row, $p->do_image   ? '✅ Yes' : '—');

                $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                    'font'      => ['size'=>10,'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
                $ws->getStyle('H'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle('J'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle('C'.$row)->getFont()->getColor()->setRGB($fc);
                $ws->getStyle('C'.$row)->getFont()->setBold(true);
                $ws->getStyle('D'.$row.':F'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $ws->getStyle('E'.$row)->getNumberFormat()->setFormatCode('"RM "#,##0.00');
                if ($p->do_image) $ws->getStyle('K'.$row)->getFont()->getColor()->setRGB(self::C_GREEN);
                $row++;
            }

            // Total row
            $ws->getRowDimension($row)->setRowHeight(24);
            $ws->mergeCells('A'.$row.':C'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL  —  '.$records->count().' purchases');
            $ws->setCellValue('D'.$row, $records->sum('liters'));
            $ws->setCellValue('E'.$row, $records->sum('amount_rm'));
            foreach (['F','G','H','I','J','K'] as $c) $ws->setCellValue($c.$row, '');
            $ws->getStyle('A'.$row.':K'.$row)->applyFromArray([
                'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);
            $ws->getStyle('D'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $ws->getStyle('E'.$row)->getNumberFormat()->setFormatCode('"RM "#,##0.00');
        }];
    }
}
