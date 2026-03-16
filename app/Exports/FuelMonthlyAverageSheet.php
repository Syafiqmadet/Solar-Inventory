<?php
namespace App\Exports;

use App\Models\DieselUsage;
use App\Models\FuelRecord;
use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FuelMonthlyAverageSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E';
    const C_ORANGE = 'FF6B35';
    const C_GREEN  = '28A745';
    const C_BLUE   = '0D6EFD';
    const C_HDR    = '2D3748';
    const C_WHITE  = 'FFFFFF';
    const C_LIGHT  = 'F8F9FA';
    const C_PL     = 'E8F8EE';
    const C_DL     = 'E8F0FF';

    private $monthlyData;
    private $vehicles;

    public function __construct(private ?int $projectId = null)
    {
        // Build monthly aggregates
        $usages = DieselUsage::with('vehicle')
            ->whereHas('vehicle', fn($q) => $q->where('project_id', $projectId))
            ->orderBy('date')
            ->get();

        $purchases = FuelRecord::where('project_id', $projectId)
            ->orderBy('date')
            ->get();

        // Group usage by year-month
        $monthly = [];
        foreach ($usages as $u) {
            $key = $u->date->format('Y-m');
            if (!isset($monthly[$key])) {
                $monthly[$key] = ['petrol_used' => 0, 'diesel_used' => 0, 'records' => 0];
            }
            if ($u->fuel_type === 'petrol') {
                $monthly[$key]['petrol_used'] += $u->liters_used;
            } else {
                $monthly[$key]['diesel_used'] += $u->liters_used;
            }
            $monthly[$key]['records']++;
        }

        // Add purchases to monthly
        foreach ($purchases as $p) {
            $key = $p->date->format('Y-m');
            if (!isset($monthly[$key])) {
                $monthly[$key] = ['petrol_used' => 0, 'diesel_used' => 0, 'records' => 0];
            }
            if ($p->fuel_type === 'petrol') {
                $monthly[$key]['petrol_purchased'] = ($monthly[$key]['petrol_purchased'] ?? 0) + $p->liters;
            } else {
                $monthly[$key]['diesel_purchased'] = ($monthly[$key]['diesel_purchased'] ?? 0) + $p->liters;
            }
        }

        ksort($monthly);
        $this->monthlyData = $monthly;
        $this->vehicles    = Vehicle::where('project_id', $projectId)->get();
    }

    public function title(): string { return 'Monthly Average'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);
            $ws->freezePane('A5');

            // ── Title ──
            $ws->mergeCells('A1:J1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '📊  MONTHLY FUEL USAGE & AVERAGE REPORT');
            $ws->getStyle('A1:J1')->applyFromArray([
                'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_DARK]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // ── Info row ──
            $ws->mergeCells('A2:J2');
            $ws->getRowDimension(2)->setRowHeight(18);
            $ws->setCellValue('A2', 'Generated: ' . now()->format('d F Y  H:i') . '   |   Vehicles: ' . $this->vehicles->count());
            $ws->getStyle('A2:J2')->applyFromArray([
                'font'      => ['size' => 10, 'color' => ['rgb' => '888888'], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F2F5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $ws->getRowDimension(3)->setRowHeight(8);

            // ── Column Headers ──
            $cols = [
                'A' => ['Month',           14],
                'B' => ['⛽ Petrol Used (L)',  18],
                'C' => ['⛽ Petrol Bought (L)', 18],
                'D' => ['🛢️ Diesel Used (L)',   18],
                'E' => ['🛢️ Diesel Bought (L)', 18],
                'F' => ['Total Used (L)',   16],
                'G' => ['Total Bought (L)', 16],
                'H' => ['Usage Records',   14],
                'I' => ['Avg Daily Use (L)',16],
                'J' => ['vs Prev Month',   14],
            ];

            $ws->getRowDimension(4)->setRowHeight(28);
            foreach ($cols as $col => [$label, $width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'4', $label);
                $ws->getStyle($col.'4')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_ORANGE]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);
            }

            // ── Data rows ──
            $row = 5;
            $prevTotal = null;
            $allPetrolUsed = [];
            $allDieselUsed = [];
            $allTotalUsed  = [];

            foreach ($this->monthlyData as $yearMonth => $data) {
                $petrolUsed     = round($data['petrol_used'] ?? 0, 2);
                $dieselUsed     = round($data['diesel_used'] ?? 0, 2);
                $petrolBought   = round($data['petrol_purchased'] ?? 0, 2);
                $dieselBought   = round($data['diesel_purchased'] ?? 0, 2);
                $totalUsed      = $petrolUsed + $dieselUsed;
                $totalBought    = $petrolBought + $dieselBought;
                $records        = $data['records'];

                // Days in month
                $dt = \Carbon\Carbon::createFromFormat('Y-m', $yearMonth);
                $daysInMonth = $dt->daysInMonth;
                $avgDaily    = $daysInMonth > 0 ? round($totalUsed / $daysInMonth, 2) : 0;

                // vs previous month
                $vsPrev = '';
                $vsPrevColor = self::C_HDR;
                if ($prevTotal !== null && $prevTotal > 0) {
                    $diff = $totalUsed - $prevTotal;
                    $pct  = round(($diff / $prevTotal) * 100, 1);
                    if ($diff > 0) {
                        $vsPrev = '▲ +' . $pct . '%';
                        $vsPrevColor = 'DC3545'; // red - increased
                    } elseif ($diff < 0) {
                        $vsPrev = '▼ ' . $pct . '%';
                        $vsPrevColor = self::C_GREEN; // green - decreased
                    } else {
                        $vsPrev = '— 0%';
                    }
                } elseif ($prevTotal === null) {
                    $vsPrev = '—';
                }

                $bg = $row % 2 === 0 ? 'F5F7FA' : self::C_WHITE;

                $ws->getRowDimension($row)->setRowHeight(22);
                $ws->setCellValue('A'.$row, $dt->format('M Y'));
                $ws->setCellValue('B'.$row, $petrolUsed);
                $ws->setCellValue('C'.$row, $petrolBought);
                $ws->setCellValue('D'.$row, $dieselUsed);
                $ws->setCellValue('E'.$row, $dieselBought);
                $ws->setCellValue('F'.$row, $totalUsed);
                $ws->setCellValue('G'.$row, $totalBought);
                $ws->setCellValue('H'.$row, $records);
                $ws->setCellValue('I'.$row, $avgDaily);
                $ws->setCellValue('J'.$row, $vsPrev);

                $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                    'font'      => ['size' => 10, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
                ]);

                // Color petrol green, diesel blue
                $ws->getStyle('B'.$row.':C'.$row)->getFont()->getColor()->setRGB(self::C_GREEN);
                $ws->getStyle('D'.$row.':E'.$row)->getFont()->getColor()->setRGB(self::C_BLUE);
                $ws->getStyle('F'.$row)->getFont()->setBold(true);
                $ws->getStyle('J'.$row)->getFont()->setBold(true)->getColor()->setRGB($vsPrevColor);

                // Number format
                foreach (['B','C','D','E','F','G','I'] as $c) {
                    $ws->getStyle($c.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $allPetrolUsed[] = $petrolUsed;
                $allDieselUsed[] = $dieselUsed;
                $allTotalUsed[]  = $totalUsed;
                $prevTotal = $totalUsed;
                $row++;
            }

            // Spacer
            $ws->getRowDimension($row)->setRowHeight(8);
            $row++;

            // ── Average Row ──
            $months = count($this->monthlyData);
            if ($months > 0) {
                $avgPetrol  = round(array_sum($allPetrolUsed) / $months, 2);
                $avgDiesel  = round(array_sum($allDieselUsed) / $months, 2);
                $avgTotal   = round(array_sum($allTotalUsed)  / $months, 2);

                $ws->getRowDimension($row)->setRowHeight(26);
                $ws->setCellValue('A'.$row, '📊 MONTHLY AVERAGE (' . $months . ' months)');
                $ws->setCellValue('B'.$row, $avgPetrol);
                $ws->setCellValue('D'.$row, $avgDiesel);
                $ws->setCellValue('F'.$row, $avgTotal);
                $ws->setCellValue('G'.$row, '');
                $ws->setCellValue('H'.$row, round(array_sum(array_column(array_values($this->monthlyData), 'records')) / $months, 1));
                $ws->setCellValue('I'.$row, round($avgTotal / 30, 2));
                $ws->setCellValue('J'.$row, '');

                $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_ORANGE]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);
                foreach (['B','D','F','I'] as $c) {
                    $ws->getStyle($c.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $row++;

                // ── Grand Total Row ──
                $ws->getRowDimension($row)->setRowHeight(26);
                $ws->setCellValue('A'.$row, '🔢 GRAND TOTAL (' . $months . ' months)');
                $ws->setCellValue('B'.$row, round(array_sum($allPetrolUsed), 2));
                $ws->setCellValue('D'.$row, round(array_sum($allDieselUsed), 2));
                $ws->setCellValue('F'.$row, round(array_sum($allTotalUsed), 2));
                $ws->setCellValue('H'.$row, array_sum(array_column(array_values($this->monthlyData), 'records')));

                $ws->getStyle('A'.$row.':J'.$row)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::C_WHITE], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_HDR]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);
                foreach (['B','D','F'] as $c) {
                    $ws->getStyle($c.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
        }];
    }
}
