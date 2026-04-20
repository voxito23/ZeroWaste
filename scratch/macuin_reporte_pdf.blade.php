<!DOCTYPE html>
<html class="dark" lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Vista previa reporte</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;700&family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>

<script>

tailwind.config = {

darkMode:"class",

theme:{
extend:{
colors:{
primary:"#BE0000",
dashboard:"#1A1A1A"
},
fontFamily:{
display:["Space Grotesk","sans-serif"],
body:["Noto Sans","sans-serif"]
}
}
}

}

</script>

<style>

.a4-paper{
max-width:210mm;
min-height:297mm;
background:white;
margin:auto;
box-shadow:0 0 40px rgba(0,0,0,0.5);
}

</style>

</head>


<body class="bg-dashboard text-white font-body">


<div class="flex h-screen">


<!-- SIDEBAR -->

<aside class="w-64 bg-black border-r border-white/10 flex flex-col">

<div class="p-6 border-b border-white/10">

<h1 class="font-display font-bold text-lg">MACUIN</h1>
<p class="text-xs text-gray-400 uppercase">Autopartes</p>

</div>


<nav class="flex-1 p-4 space-y-2">

<a href="/admin" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 rounded">
<span class="material-symbols-outlined">dashboard</span>
Dashboard
</a>

<a href="/inventario" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 rounded">
<span class="material-symbols-outlined">inventory_2</span>
Inventario
</a>

<a href="/pedidos" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 rounded">
<span class="material-symbols-outlined">shopping_cart</span>
Pedidos
</a>

<a href="/clientes" class="flex items-center gap-3 p-3 text-gray-400 hover:bg-white/5 rounded">
<span class="material-symbols-outlined">group</span>
Clientes
</a>

<a href="/reportes" class="flex items-center gap-3 p-3 bg-primary/10 border border-primary/40 rounded">
<span class="material-symbols-outlined">assessment</span>
Reportes
</a>

</nav>

</aside>



<!-- CONTENIDO -->

<main class="flex-1 p-10 overflow-y-auto bg-black">


<div class="flex justify-between items-center mb-6">

<h2 class="text-2xl font-display font-bold">

Vista previa del reporte

</h2>

<div class="flex gap-3">

<button onclick="window.print()" class="bg-primary px-4 py-2 rounded">

Imprimir

</button>

</div>

</div>



<div class="a4-paper p-10 text-black">



<h1 class="text-3xl font-bold mb-2">

MACUIN AUTOPARTES

</h1>

<p class="text-sm text-gray-500 mb-8">

Reporte técnico de pedidos

</p>



<table class="w-full text-sm border-collapse">

<thead>

<tr class="border-b-2 border-gray-300">

<th class="text-left py-2">Pedido</th>
<th class="text-left">Fecha</th>
<th class="text-left">Cliente</th>
<th class="text-left">Producto</th>
<th class="text-center">Cantidad</th>
<th class="text-right">Precio</th>
<th class="text-right">Total</th>

</tr>

</thead>

<tbody>

<tr class="border-b">

<td>P-001</td>
<td>10/01/2026</td>
<td>Taller Vega</td>
<td>Kit embrague</td>
<td class="text-center">2</td>
<td class="text-right">$2450</td>
<td class="text-right">$4900</td>

</tr>

<tr class="border-b">

<td>P-002</td>
<td>12/01/2026</td>
<td>Juan Pérez</td>
<td>Amortiguadores</td>
<td class="text-center">4</td>
<td class="text-right">$850</td>
<td class="text-right">$3400</td>

</tr>

<tr class="border-b">

<td>P-003</td>
<td>14/01/2026</td>
<td>Refaccionaria UPQ</td>
<td>Bujías NGK</td>
<td class="text-center">24</td>
<td class="text-right">$120</td>
<td class="text-right">$2880</td>

</tr>

</tbody>

</table>



<div class="mt-10 text-right">

<p>Subtotal: $11,180</p>
<p>IVA: $1,788.80</p>

<p class="text-xl font-bold">

Total: $12,968.80

</p>

</div>


</div>

</main>

</div>


</body>
</html>