<?php

namespace App\Exports;

use App\Models\Produto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProdutosExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    protected array $ids;

    public function __construct(array $ids = [])
    {
        $this->ids = $ids;
    }

    public function collection()
    {
        $query = Produto::with('categoria');

        if (!empty($this->ids)) {
            $query->whereIn('id', $this->ids);
        }

        return $query->get()->map(function ($produto) {
            return [
                $produto->id,
                $produto->nome,
                $produto->marca ?? '',
                $produto->slug,
                $produto->descricao_curta ?? '',
                $produto->descricao ?? '',
                $produto->preco,
                $produto->preco_original ?? '',
                $produto->desconto_percentual ?? '',
                $produto->em_promocao ? 'Sim' : 'Não',
                $produto->preco_com_desconto,
                $produto->estoque,
                $produto->ativo ? 'Sim' : 'Não',
                $produto->destaque ? 'Sim' : 'Não',
                $produto->categoria ? $produto->categoria->nome : '',
                is_array($produto->tags) ? implode(', ', $produto->tags) : ($produto->tags ?? ''),
                $produto->specs ? json_encode($produto->specs, JSON_UNESCAPED_UNICODE) : '',
                $produto->peso ?? '',
                $produto->created_at ? $produto->created_at->format('d/m/Y H:i') : '',
                $produto->updated_at ? $produto->updated_at->format('d/m/Y H:i') : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Código',
            'Nome',
            'Marca',
            'Slug',
            'Descrição Curta',
            'Descrição',
            'Preço',
            'Preço Original',
            'Desconto (%)',
            'Em Promoção',
            'Preço c/ Desconto',
            'Estoque',
            'Ativo',
            'Destaque',
            'Categoria',
            'Tags',
            'Specs',
            'Peso (kg)',
            'Criado em',
            'Atualizado em',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF374151'],
                ],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => '#,##0.00',
            'H' => '#,##0.00',
            'K' => '#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }
}
