<h2 class="text-center mb-4">Reporte General del Inquilino:{{ optional(Auth::user()->inquilino)->nombre }}</h2>

<hr>

<h4>1. Información General de Animales</h4>
<ul>
    <li><strong>Total de animales:</strong> {{ $total_animales }}</li>
    <li><strong>Machos:</strong> {{ $machos }}</li>
    <li><strong>Hembras:</strong> {{ $hembras }}</li>
    <li><strong>Madres:</strong> {{ $madres }}</li>
    <li><strong>Padres:</strong> {{ $padres }}</li>
    <li><strong>Mautes:</strong> {{ $mautes }}</li>
</ul>

<hr>

<h4>2. Producción</h4>
<ul>
    <li><strong>Producción total de leche:</strong> {{ number_format($produccion_leche_total, 2) }} litros</li>
    <li><strong>Producción total de carne:</strong> {{ number_format($produccion_carne_total, 2) }} kg</li>
</ul>

<hr>

<h4>3. Movimientos Económicos</h4>
<ul>
    <li><strong>Compras registradas:</strong> {{ $compras }}</li>
    <li><strong>Ventas registradas:</strong> {{ $ventas }}</li>
    <li><strong>Otros Gastos Operativos:</strong> ${{ number_format($total_gastos_operativos, 2) }}</li>
</ul>

<hr>

<h4>4. Movilizaciones</h4>
<p>Total de movilizaciones: <strong>{{ $movilizaciones }}</strong></p>

<hr>

<h4>5. Reproducción</h4>
<p>Total de eventos reproductivos: <strong>{{ $reproducciones }}</strong></p>

<hr>

<h4>6. Inventario de Insumos</h4>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Insumo</th>
            <th>Cantidad</th>
            <th>Unidad</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inventario_insumos as $i)
            <tr>
                <td>{{ $i->nombre }}</td>
                <td>{{ $i->cantidad }}</td>
                <td>{{ $i->unidad }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center">No hay insumos registrados.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<hr>

<h4>7. Animales en Tratamiento (Activos)</h4>
<ul>
    @forelse($tratamientos_activos as $t)
        <li>
            <strong>{{ $t->animal->codigo_interno }}</strong> — {{ $t->motivo }}  
            ({{ $t->medicamento }}, desde {{ \Carbon\Carbon::parse($t->fecha)->format('d/m/Y') }})
        </li>
    @empty
        <p>No hay tratamientos activos.</p>
    @endforelse
</ul>

<hr>

<h4>8. Histórico de Tratamientos</h4>
<ul>
    @forelse($tratamientos_historico as $t)
        <li>
            <strong>{{ $t->animal->codigo_interno }}</strong> — {{ $t->motivo }}  
            ({{ \Carbon\Carbon::parse($t->fecha_inicio)->format('d/m/Y') }} a {{ $t->fecha_fin ? \Carbon\Carbon::parse($t->fecha_fin)->format('d/m/Y') : 'En curso' }})
        </li>
    @empty
        <p>No hay historial de tratamientos.</p>
    @endforelse
</ul>

<hr>

<h4>9. Registro de Vacunas</h4>
<ul>
    @forelse($vacunas as $v)
        <li>
            <strong>{{ $v->animal->codigo_interno }}</strong> — {{ $v->nombre }}  
            ({{ \Carbon\Carbon::parse($v->fecha)->format('d/m/Y') }})
        </li>
    @empty
        <p>No hay vacunas registradas.</p>
    @endforelse
</ul>

<hr>

<h4>10. Genealogía</h4>
<ul>
    @forelse($genealogia as $g)
        <li>
            <strong>{{ $g->animal->codigo_interno }}</strong>  
            Padre: {{ $g->padre ?? 'N/A' }} — Madre: {{ $g->madre ?? 'N/A' }}
        </li>
    @empty
        <p>No hay datos de genealogía.</p>
    @endforelse
</ul>
<hr>