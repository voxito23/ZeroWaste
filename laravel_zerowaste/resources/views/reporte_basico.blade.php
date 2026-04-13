<table border="1">
    <thead>
        <tr>
            <th colspan="4" style="text-align: center; font-size: 16px; font-weight: bold;">
                ZEROWASTE PLATAFORMA - {{ $titulo }}
            </th>
        </tr>
        <tr>
            <th colspan="4">
                Generado: {{ $fecha_generada }} | Rango: {{ $rango_inicio }} hasta {{ $rango_fin }} | Total: {{ $total }}
            </th>
        </tr>
        <tr>
            @if($tipo === 'usuarios')
                <th>USUARIO</th>
                <th>EMAIL</th>
                <th>UBICACION</th>
                <th>CREADO EN</th>
            @elseif($tipo === 'campanas')
                <th>CAMPANA</th>
                <th>TIPO</th>
                <th>DESCRIPCION</th>
                <th>CREADO EN</th>
            @elseif($tipo === 'mapa')
                <th>NOMBRE</th>
                <th>CLASIFICACION</th>
                <th>DIRECCION</th>
                <th>CREADO EN</th>
            @elseif($tipo === 'eventos')
                <th>EVENTO</th>
                <th>LUGAR</th>
                <th>TIPO</th>
                <th>FECHA INICIO</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($registros as $item)
            <tr>
                @if($tipo === 'usuarios')
                    <td>{{ $item->nombre }}</td>
                    <td>{{ $item->email }}</td>
                    <td>{{ $item->ubicacion ?? 'N/A' }}</td>
                    <td>{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : 'N/A' }}</td>
                @elseif($tipo === 'campanas')
                    <td>{{ $item->nombre }}</td>
                    <td>{{ $item->tipo_etiqueta ?? 'N/A' }}</td>
                    <td>{{ $item->descripcion ?? 'N/A' }}</td>
                    <td>{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : 'N/A' }}</td>
                @elseif($tipo === 'mapa')
                    <td>{{ $item->nombre }}</td>
                    <td>{{ $item->tipo }}</td>
                    <td>{{ $item->direccion ?? 'N/A' }}</td>
                    <td>{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : 'N/A' }}</td>
                @elseif($tipo === 'eventos')
                    <td>{{ $item->titulo }}</td>
                    <td>{{ $item->lugar ?? 'N/A' }}</td>
                    <td>{{ $item->tipo_etiqueta ?? 'N/A' }}</td>
                    <td>{{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d/m/Y') : 'N/A' }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
