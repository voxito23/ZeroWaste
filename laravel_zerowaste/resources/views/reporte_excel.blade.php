<table>
    <thead>
        <tr>
            <td colspan="7" style="background-color:#ECFDF5; color:#064E3B; font-weight:bold; font-size:24px; text-align:center; border: 1px solid #10B981; border-bottom: none;">
                ♻ ZEROWASTE 
            </td>
        </tr>
        <tr>
            <td colspan="7" style="background-color:#ECFDF5; color:#059669; font-weight:bold; font-size:16px; text-align:center; border: 1px solid #10B981; border-top: none; border-bottom: 3px solid #059669;">
                Reporte Oficial de {{ ucfirst($tipo) }}
            </td>
        </tr>
        <tr>
            <td colspan="7" style="background-color:#F8FAFC; color:#475569; font-size:12px; text-align:center; border: 1px solid #E2E8F0;">
                <strong>Generado:</strong> {{ $fecha_generada }} &nbsp;|&nbsp; <strong>Periodo:</strong> {{ $rango_inicio }} a {{ $rango_fin }} &nbsp;|&nbsp; <strong>Total:</strong> {{ $total }} registros
            </td>
        </tr>
        <tr><td colspan="7"></td></tr>
        <tr>
            @if($tipo === 'usuarios')
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">#</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Usuario</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Email</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Ubicación</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Rol</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Estado</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">Registro</th>
            @elseif($tipo === 'campanas')
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">#</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Campaña</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Clasificación</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Descripción</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Ubicación</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Estado</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">Creada</th>
            @elseif($tipo === 'mapa')
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">#</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Punto de Reciclaje</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Tipo</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Dirección</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Materiales</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Latitud</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Longitud</th>
            @elseif($tipo === 'eventos')
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">#</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Evento</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Categoría</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Ubicación</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Detalles</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">Inicio</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">Fin</th>
            @elseif($tipo === 'foro')
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">#</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Publicación</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Categoría</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669;">Autor</th>
                <th style="background-color:#10B981; color:#fff; font-weight:bold; border: 1px solid #059669; text-align:center;">Fecha de Publicación</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($registros as $idx => $item)
            @php $bg = $idx % 2 === 0 ? '#FFFFFF' : '#F8FAFC'; @endphp
            <tr>
                @if($tipo === 'usuarios')
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->nombre }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#334155;">{{ $item->email }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $item->ubicacion ?? '—' }}</td>
                    <td style="font-weight:bold; color:{{ $item->is_admin ? '#7C3AED' : '#059669' }}; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->is_admin ? 'Admin' : 'Usuario' }}</td>
                    <td style="font-weight:bold; color:{{ ($item->bloqueado ?? false) ? '#DC2626' : '#059669' }}; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ ($item->bloqueado ?? false) ? 'Bloqueado' : 'Activo' }}</td>
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                @elseif($tipo === 'campanas')
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->nombre }}</td>
                    <td style="font-weight:bold; color:#2563EB; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->tipo_etiqueta ?? 'General' }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#334155;">{{ mb_strimwidth($item->descripcion ?? '', 0, 80, '...') }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $item->lugar ?? '—' }}</td>
                    <td style="font-weight:bold; color:{{ ($item->activa ?? false) ? '#059669' : '#DC2626' }}; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ ($item->activa ?? false) ? 'Activa' : 'Inactiva' }}</td>
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                @elseif($tipo === 'mapa')
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->nombre }}</td>
                    <td style="font-weight:bold; color:#D97706; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->tipo }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#334155;">{{ $item->direccion ?? '—' }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $item->materiales ?? '—' }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ number_format($item->latitud, 6) }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ number_format($item->longitud, 6) }}</td>
                @elseif($tipo === 'eventos')
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</td>
                    <td style="font-weight:bold; color:#7C3AED; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->tipo ?? 'General' }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#334155;">{{ $item->ubicacion ?? $item->lugar ?? '—' }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ mb_strimwidth($item->descripcion ?? '', 0, 70, '...') }}</td>
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d/m/Y H:i') : '—' }}</td>
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $item->fecha_fin ? \Illuminate\Support\Carbon::parse($item->fecha_fin)->format('d/m/Y H:i') : '—' }}</td>
                @elseif($tipo === 'foro')
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $item->titulo ?? 'Post' }}</td>
                    @php
                        $catNombreE = $item->categoria->nombre ?? 'Sin Categoría';
                        $catLowerE = strtolower($catNombreE);
                        $catColorE = '#059669'; // Default verde (Compostaje)
                        if (str_contains($catLowerE, 'reciclaje')) $catColorE = '#D97706'; // Ambar
                        elseif (str_contains($catLowerE, 'reducción') || str_contains($catLowerE, 'residuos')) { $catColorE = '#0891B2'; } // Cyan
                        elseif (str_contains($catLowerE, 'eventos')) $catColorE = '#7C3AED'; // Violeta
                        elseif (str_contains($catLowerE, 'dudas') || str_contains($catLowerE, 'preguntas')) $catColorE = '#E11D48'; // Rosa/Rojo
                    @endphp
                    <td style="font-weight:bold; color:{{ $catColorE }}; background-color:{{ $bg }}; border: 1px solid #E2E8F0;">{{ $catNombreE }}</td>
                    <td style="background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#334155;">{{ $item->autor->nombre ?? 'Usuario Eliminado' }}</td>
                    <td style="text-align:center; background-color:{{ $bg }}; border: 1px solid #E2E8F0; color:#64748B;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y H:i') : '—' }}</td>
                @endif
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center; padding:20px; color:#9CA3AF; border: 1px solid #E2E8F0;">Sin datos documentados en este periodo.</td></tr>
        @endforelse
    </tbody>
</table>
