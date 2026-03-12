<?php
namespace App\Exports;

use App\Models\DieselUsage;
use App\Models\FuelRecord;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FuelUsageSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_GREEN = '28A745'; const C_BLUE  = '0D6EFD';
    const C_PL    = 'E8F8EE'; const C_DL    = 'E8F0FF';
    const C_WHITE = 'FFFFFF';

    private string $fuelType;
    private $usages;
    private float $purchased, $used, $balance;

    public function __construct(string $fuelType, private ?int $projectId = null)
    {
        $this->fuelType  = $fuelType;
        $pid = $this->projectId;
        $this->usages    = DieselUsage::with('vehicle')
            ->where('fuel_type', $fuelType)
            ->whereHas('vehicle', fn($vq) => $vq->where('project_id', $pid))
            ->orderBy('date')->orderBy('id')->get();
        $this->purchased = (float) FuelRecord::where('fuel_type',$fuelType)->where('project_id',$pid)->sum('liters');
        $this->used      = (float) DieselUsage::where('fuel_type',$fuelType)->whereHas('vehicle', fn($vq) => $vq->where('project_id',$pid))->sum('liters_used');
        $this->balance   = $this->purchased - $this->used;
    }

    public function title(): string
    {
        return $this->fuelType === 'petrol' ? 'Petrol Usage' : 'Diesel Usage';
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws       = $e->sheet->getDelegate();
            $isPetrol = $this->fuelType === 'petrol';
            $bgColor  = $isPetrol ? self::C_GREEN : self::C_BLUE;
            $ltColor  = $isPetrol ? self::C_PL    : self::C_DL;
            $titleTxt = $isPetrol ? '⛽  PETROL USAGE RECORDS' : '🛢️  DIESEL USAGE RECORDS';
            $ws->setShowGridlines(false);
            $ws->freezePane('A5');

            // Title
            $ws->mergeCells('A1:J1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', $titleTxt);
            $ws->getStyle('A1:J1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>16,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bgColor]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // Stats bar
            $ws->mergeCells('A2:J2');
            $ws->getRowDimension(2)->setRowHeight(22);
            $balIcon = $this->balance >= 50 ? '✅ OK' : ($this->balance > 0 ? '⚠️ LOW' : '⛔ EMPTY');
            $ws->setCellValue('A2',
                'Purchased: '.number_format($this->purchased,2).' L   |   '.
                'Used: '.number_format($this->used,2).' L   |   '.
                'Balance: '.number_format($this->balance,2).' L   '.$balIcon
            );
            $ws->getStyle('A2:J2')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>$bgColor],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$ltColor]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            $ws->getRowDimension(3)->setRowHeight(6);

            // Column widths & headers
            $cols = [
                'A'=>['#',5], 'B'=>['Date',13], 'C'=>['Vehicle Name',22],
                'D'=>['Vehicle Type',16], 'E'=>['Plate No.',13],
                'F'=>['Driver',18], 'G'=>['Liters Used (L)',16],
                'H'=>['Balance Before (L)',18], 'I'=>['Balance After (L)',17],
                'J'=>['Purpose / Notes',30],
            ];
            $ws->getRowDimension(4)->setRowHeight(26);
            foreach ($cols as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'4', $label);
                $ws->getStyle($col.'4')->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bgColor]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,
                                    'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            // Data rows
            $row = 5; $seq = 1;
            foreach ($this->usages as $u) {
                $ws->getRowDimension($row)->setRowHeight(20);
                $bg    = ($row % 2 === 0) ? $ltColor : 'FFFFFF';
                $after = (float)$u->balance_after;
                $afterColor = $after <= 0 ? 'DC3545' : ($after < 50 ? 'FD7E14' : '000000');

                $ws->setCellValue('A'.$row, $seq++);
                $ws->setCellValue('B'.$row, $u->date->format('d M Y'));
                $ws->setCellValue('C'.$row, $u->vehicle->name       ?? '—');
                $ws->setCellValue('D'.$row, $u->vehicle->type       ?? '—');
                $ws->setCellValue('E'.$row, $u->vehicle->vehicle_no ?? '—');
                $ws->setCellValue('F'.$row, $u->driver_name         ?? '—');
                $ws->setCellValue('G'.$row, (float)$u->liters_used);
                $ws->setCellValue('H'.$row, (float)$u->balance_before);
                $ws->setCellValue('I'.$row, $after);
                $ws->setCellValue('J'.$row, $u->purpose ?? $u->notes ?? '—');

                $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                    'font'      => ['size'=>10,'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
                $ws->getStyle('C'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle('J'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle('G'.$row)->getFont()->getColor()->setRGB($bgColor);
                $ws->getStyle('I'.$row)->getFont()->getColor()->setRGB($afterColor);
                if ($after <= 0 || ($after < 50 && $after > 0))
                    $ws->getStyle('I'.$row)->getFont()->setBold(true);
                $ws->getStyle('G'.$row.':I'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;
            }

            // Total row
            $ws->getRowDimension($row)->setRowHeight(24);
            $ws->mergeCells('A'.$row.':F'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL  —  '.$this->usages->count().' records');
            $ws->setCellValue('G'.$row, $this->usages->sum('liters_used'));
            foreach (['H','I','J'] as $c) $ws->setCellValue($c.$row, '');
            $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bgColor]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);
            $ws->getStyle('G'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        }];
    }
}
