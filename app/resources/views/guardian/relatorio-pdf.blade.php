<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .subtitulo { font-size: 11px; color: #6b7280; margin: 0 0 14px; }
        .aviso { background: #fef3c7; color: #92400e; padding: 6px 10px; margin-bottom: 10px; border: 1px solid #fde68a; }
        .erro { background: #fee2e2; color: #b91c1c; padding: 6px 10px; margin-bottom: 10px; border: 1px solid #fecaca; }

        table.metricas { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.metricas td { border: 1px solid #e5e7eb; padding: 8px 10px; width: 25%; }
        table.metricas .label { display: block; font-size: 8px; color: #9ca3af; text-transform: uppercase; margin-bottom: 3px; }
        table.metricas .valor { font-size: 14px; font-weight: bold; }

        table.tickets { width: 100%; border-collapse: collapse; }
        table.tickets th { background: #f3f4f6; text-align: left; padding: 5px 6px; font-size: 8px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        table.tickets td { padding: 4px 6px; border-bottom: 1px solid #f3f4f6; }
        table.tickets td.num { text-align: right; }
        tfoot td { font-weight: bold; border-top: 2px solid #d1d5db; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>Relatório Guardian por período</h1>
    <p class="subtitulo">{{ \Carbon\Carbon::parse($filtros['data_de'])->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($filtros['data_ate'])->format('d/m/Y') }} — gerado em {{ now()->format('d/m/Y H:i') }}</p>

    @if($mock_ativo)
        <div class="aviso">⚠ MOCK ativo — dados sintéticos, não são tickets reais do Guardian.</div>
    @endif

    @if($erro)
        <div class="erro">✗ {{ $erro }}</div>
    @endif

    @if($metricas)
        <table class="metricas">
            <tr>
                <td>
                    <span class="label">Total de tickets</span>
                    <span class="valor">{{ $metricas['total_tickets'] }}</span>
                </td>
                <td>
                    <span class="label">Com pesagem final</span>
                    <span class="valor">{{ $metricas['total_com_pesagem'] }}</span>
                </td>
                <td>
                    <span class="label">Peso líquido total</span>
                    <span class="valor">{{ number_format($metricas['peso_liquido_total_kg'], 0, ',', '.') }} kg</span>
                </td>
                <td>
                    <span class="label">Tempo médio de pátio</span>
                    <span class="valor">
                        @if($metricas['tempo_medio_patio_min'] !== null)
                            {{ intdiv($metricas['tempo_medio_patio_min'], 60) }}h{{ str_pad($metricas['tempo_medio_patio_min'] % 60, 2, '0', STR_PAD_LEFT) }}
                        @else
                            —
                        @endif
                    </span>
                </td>
            </tr>
        </table>
    @endif

    <table class="tickets">
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Status</th>
                <th>Placa</th>
                <th>Motorista</th>
                <th>Unidade</th>
                <th>Atendente</th>
                <th>Pedido</th>
                <th>Peso doc. (kg)</th>
                <th>Tara (kg)</th>
                <th>Peso bruto (kg)</th>
                <th>Peso líq. (kg)</th>
                <th>Entrada</th>
                <th>Pátio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $t)
                <tr>
                    <td>{{ $t['ticket'] }}</td>
                    <td>{{ $t['status'] }}</td>
                    <td>{{ $t['placa'] ?? '—' }}</td>
                    <td>{{ $t['motorista'] ?? '—' }}</td>
                    <td>{{ $t['unidade'] ?? '—' }}</td>
                    <td>{{ $t['atendente'] ?? '—' }}</td>
                    <td>{{ $t['pedido'] ?? '—' }}</td>
                    <td class="num">{{ $t['peso_doc_kg'] !== null ? number_format($t['peso_doc_kg'], 0, ',', '.') : '—' }}</td>
                    <td class="num">{{ $t['tara_kg'] !== null ? number_format($t['tara_kg'], 0, ',', '.') : '—' }}</td>
                    <td class="num">{{ $t['peso_bruto_kg'] !== null ? number_format($t['peso_bruto_kg'], 0, ',', '.') : '—' }}</td>
                    <td class="num">{{ $t['peso_liquido_kg'] !== null ? number_format($t['peso_liquido_kg'], 0, ',', '.') : '—' }}</td>
                    <td>{{ $t['data_entrada'] ? \Carbon\Carbon::parse($t['data_entrada'])->format('d/m/Y H:i') : '—' }}</td>
                    <td>
                        @if($t['tempo_patio_min'] !== null)
                            {{ intdiv($t['tempo_patio_min'], 60) }}h{{ str_pad($t['tempo_patio_min'] % 60, 2, '0', STR_PAD_LEFT) }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="13">Nenhum ticket no período selecionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
