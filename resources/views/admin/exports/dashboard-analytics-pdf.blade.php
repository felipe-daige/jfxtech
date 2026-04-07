<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Analytics Dashboard</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        h1, h2 {
            margin: 0 0 8px;
        }

        .meta {
            margin-bottom: 18px;
            color: #4b5563;
            font-size: 10px;
        }

        .section {
            margin-bottom: 18px;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
        }

        .grid th,
        .grid td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
        }

        .grid th {
            background: #111827;
            color: #ffffff;
            text-align: left;
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid #d1d5db;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h1>Analytics Dashboard</h1>
    <div class="meta">Exportado em {{ $analytics['exportedAt']->format('d/m/Y H:i') }}</div>

    <div class="section">
        <h2>Resumo</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Métrica</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summaryRows as [$label, $value])
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Status dos Pedidos</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderStatusRows as [$label, $value])
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Alertas</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Alerta</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alertRows as [$label, $value])
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Lista de Produtos</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Marca</th>
                    <th class="text-right">Preço Venda</th>
                    <th class="text-right">Custo</th>
                    <th class="text-right">Lucro Unit.</th>
                    <th class="text-right">Margem %</th>
                    <th class="text-right">Estoque</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['produtos_analytics'] as $produto)
                    <tr>
                        <td>{{ $produto->nome }}</td>
                        <td>{{ $produto->marca ?? '—' }}</td>
                        <td class="text-right">R$ {{ number_format($produto->preco_com_desconto, 2, ',', '.') }}</td>
                        <td class="text-right">{{ $produto->custo_compra !== null ? 'R$ ' . number_format($produto->custo_compra, 2, ',', '.') : '—' }}</td>
                        <td class="text-right">{{ $produto->lucro_bruto_unitario !== null ? 'R$ ' . number_format($produto->lucro_bruto_unitario, 2, ',', '.') : '—' }}</td>
                        <td class="text-right">
                            @if($produto->margem_bruta_percentual !== null)
                                {{ number_format($produto->margem_bruta_percentual, 1, ',', '.') }}%
                            @else
                                <span class="muted">Sem custo</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $produto->estoque }}</td>
                        <td>
                            <span class="badge">{{ $produto->ativo ? 'Ativo' : 'Inativo' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
