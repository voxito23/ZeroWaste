@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page_title', '')

@push('styles')
<style>
.dash-card{background:rgba(255,255,255,0.85);backdrop-filter:blur(20px);border:1px solid rgba(16,185,129,0.08);border-radius:1.5rem;transition:all .4s cubic-bezier(.4,0,.2,1)}
.dark .dash-card{background:rgba(15,42,32,0.7);border-color:rgba(255,255,255,0.05)}
.dash-card:hover{transform:translateY(-4px);box-shadow:0 20px 50px rgba(0,0,0,0.08)}
.dark .dash-card:hover{box-shadow:0 20px 50px rgba(0,0,0,0.3)}
.metric-glow{position:absolute;width:80px;height:80px;border-radius:50%;filter:blur(40px);opacity:0.15;pointer-events:none;transition:opacity .5s}
.dash-card:hover .metric-glow{opacity:0.3}
.stat-ring{position:relative;width:140px;height:140px}
.stat-ring svg{transform:rotate(-90deg)}
.stat-ring .ring-bg{stroke:rgba(0,0,0,0.05);fill:none;stroke-width:8}
.dark .stat-ring .ring-bg{stroke:rgba(255,255,255,0.05)}
.stat-ring .ring-fill{fill:none;stroke-width:8;stroke-linecap:round;transition:stroke-dashoffset 1.5s cubic-bezier(.4,0,.2,1)}
.fade-up{opacity:0;transform:translateY(16px);animation:fadeUp .6s ease forwards}
@keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.4}}
.live-dot{animation:pulse-dot 2s infinite}

/* Holographic Map */
.map-holographic{position:relative;width:100%;min-height:340px;border-radius:1.5rem;overflow:hidden;background:linear-gradient(135deg,#064E3B 0%,#0F2A20 60%,#0d3b2e 100%)}
.map-holographic::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 40%,rgba(16,185,129,0.12) 0%,transparent 70%);pointer-events:none;z-index:1}
.map-holographic::after{content:'';position:absolute;bottom:0;left:0;right:0;height:80px;background:linear-gradient(to top,rgba(6,78,59,0.9),transparent);pointer-events:none;z-index:2}
.map-svg-wrap{display:flex;align-items:center;justify-content:center;padding:30px 20px;position:relative;z-index:3}
.map-svg-wrap svg{max-width:480px;width:100%;height:auto;filter:drop-shadow(0 0 20px rgba(16,185,129,0.35));animation:mapFloat 6s ease-in-out infinite}
@keyframes mapFloat{0%,100%{transform:translateY(0);filter:drop-shadow(0 0 20px rgba(16,185,129,0.35))}50%{transform:translateY(-8px);filter:drop-shadow(0 0 30px rgba(16,185,129,0.55))}}
.map-svg-wrap svg path{fill:rgba(16,185,129,0.2);stroke:#10B981;stroke-width:0.5;transition:all .4s ease}
.map-svg-wrap svg path:hover{fill:rgba(16,185,129,0.45);stroke:#34D399;stroke-width:1;cursor:pointer}
.map-svg-wrap svg path.state-qro{fill:rgba(52,211,153,0.5);stroke:#34D399;stroke-width:1.2;animation:pulseQro 3s ease-in-out infinite}
@keyframes pulseQro{0%,100%{fill:rgba(52,211,153,0.5);stroke-width:1.2}50%{fill:rgba(52,211,153,0.7);stroke-width:1.8}}
.map-label{position:absolute;z-index:5;color:#fff;font-size:11px;font-weight:700;letter-spacing:0.5px;text-shadow:0 0 10px rgba(16,185,129,0.6)}
.map-pin{width:8px;height:8px;background:#34D399;border-radius:50%;box-shadow:0 0 12px rgba(52,211,153,0.8);animation:pinPulse 2s ease-in-out infinite;position:absolute;z-index:6}
@keyframes pinPulse{0%,100%{box-shadow:0 0 8px rgba(52,211,153,0.6);transform:scale(1)}50%{box-shadow:0 0 18px rgba(52,211,153,1);transform:scale(1.3)}}
.map-grid-line{position:absolute;background:rgba(16,185,129,0.06);z-index:0}
.map-grid-h{width:100%;height:1px}
.map-grid-v{height:100%;width:1px}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded",function(){
const isDark=document.documentElement.classList.contains('dark');
const cText=isDark?'#94a3b8':'#64748b';
const cGrid=isDark?'rgba(255,255,255,0.03)':'rgba(0,0,0,0.04)';
Chart.defaults.color=cText;Chart.defaults.font.family='Inter';

// CountUp
document.querySelectorAll('[data-count]').forEach(el=>{
const t=parseInt(el.dataset.count),dur=1200,fd=1000/60,tf=Math.round(dur/fd);let f=0;
const c=setInterval(()=>{f++;const p=1-Math.pow(1-f/tf,3);el.textContent=Math.round(t*p).toLocaleString('es-MX');if(f===tf){clearInterval(c);el.textContent=t.toLocaleString('es-MX')}},fd);
});

// Stagger cards
document.querySelectorAll('.fade-up').forEach((el,i)=>{el.style.animationDelay=i*80+'ms'});

// Users Area Chart
const cd=@json($usuariosPorMes);
const uc=document.getElementById('usersAreaChart');
if(uc){const x=uc.getContext('2d'),g=x.createLinearGradient(0,0,0,220);
g.addColorStop(0,'rgba(16,185,129,0.3)');g.addColorStop(1,'rgba(16,185,129,0)');
new Chart(x,{type:'line',data:{labels:cd.map(d=>d.mes),datasets:[{data:cd.map(d=>d.total),borderColor:'#10B981',backgroundColor:g,borderWidth:2.5,fill:true,tension:.45,pointBackgroundColor:isDark?'#0F2A20':'#fff',pointBorderColor:'#10B981',pointBorderWidth:2,pointRadius:4,pointHoverRadius:7}]},
options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:isDark?'#0F2A20':'#fff',titleColor:isDark?'#fff':'#064E3B',bodyColor:isDark?'#94a3b8':'#64748b',borderColor:isDark?'rgba(255,255,255,0.1)':'#e5e7eb',borderWidth:1,cornerRadius:12,padding:14,displayColors:false}},scales:{x:{grid:{display:false},border:{display:false},ticks:{font:{size:11,weight:'600'}}},y:{beginAtZero:true,border:{display:false},grid:{color:cGrid},ticks:{precision:0,font:{size:11}}}}}});
}

// Sentiment Doughnut
const sd=@json($sentimiento);
const sc=document.getElementById('sentChart');
if(sc){new Chart(sc,{type:'doughnut',data:{labels:['Positivo','Neutro','Negativo'],datasets:[{data:[sd.POS||0,sd.NEU||0,sd.NEG||0],backgroundColor:['#10B981','#6366F1','#F43F5E'],borderWidth:0,hoverOffset:6,borderRadius:6}]},
options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:{backgroundColor:isDark?'#0F2A20':'#fff',titleColor:isDark?'#fff':'#064E3B',bodyColor:cText,borderColor:isDark?'rgba(255,255,255,0.1)':'#e5e7eb',borderWidth:1,cornerRadius:12,padding:12,callbacks:{label:c=>' '+c.label+': '+c.raw+'%'}}},cutout:'78%',animation:{animateScale:true,duration:1400,easing:'easeOutQuart'}}});}

// Roles Bar Chart
const rc=document.getElementById('rolesChart');
if(rc){new Chart(rc,{type:'bar',data:{labels:['Admins','Usuarios','Bloqueados'],datasets:[{data:[{{$totalAdmins}},{{$totalNormales}},{{$totalBloqueados}}],backgroundColor:['#8B5CF6','#10B981','#F43F5E'],borderRadius:8,borderSkipped:false,barThickness:32}]},
options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:isDark?'#0F2A20':'#fff',titleColor:isDark?'#fff':'#064E3B',bodyColor:cText,borderColor:isDark?'rgba(255,255,255,0.1)':'#e5e7eb',borderWidth:1,cornerRadius:12,padding:12}},scales:{x:{grid:{display:false},border:{display:false},ticks:{font:{size:11,weight:'600'}}},y:{beginAtZero:true,border:{display:false},grid:{color:cGrid},ticks:{precision:0,font:{size:11}}}}}});}



// Ring animations
document.querySelectorAll('.ring-fill').forEach(r=>{const v=r.dataset.value||0;const c=2*Math.PI*60;r.style.strokeDasharray=c;r.style.strokeDashoffset=c;setTimeout(()=>{r.style.strokeDashoffset=c-(c*v/100);},300);});

// Stagger table rows
document.querySelectorAll('.stagger-row').forEach((row,i)=>{row.style.opacity='0';row.style.transform='translateY(8px)';setTimeout(()=>{row.style.transition='all .35s ease';row.style.opacity='1';row.style.transform='translateY(0)'},100*i)});
});
</script>
@endpush

@section('content')
{{-- Welcome Banner --}}
<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-8 fade-up">
    <div>
        <h2 class="text-3xl lg:text-4xl font-black tracking-tight text-[#064E3B] dark:text-white leading-none">Panel de Control</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2 font-medium">Bienvenido de vuelta. Aquí tienes un resumen de tu ecosistema ZeroWaste.</p>
    </div>
    <div class="flex items-center gap-2 bg-emerald-500/10 dark:bg-emerald-500/5 border border-emerald-500/20 rounded-full px-4 py-2">
        <span class="w-2 h-2 rounded-full bg-emerald-500 live-dot"></span>
        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Sistema en línea</span>
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php $cards=[
        ['label'=>'Usuarios','icon'=>'group','val'=>$totalUsuarios,'trend'=>$trendUsuarios,'color'=>'#10B981','bg'=>'from-emerald-500 to-teal-600'],
        ['label'=>'Publicaciones','icon'=>'forum','val'=>$totalPosts,'trend'=>$trendPosts,'color'=>'#6366F1','bg'=>'from-indigo-500 to-violet-600'],
        ['label'=>'Campañas','icon'=>'campaign','val'=>$campaignCount,'trend'=>$trendCampanas,'color'=>'#F59E0B','bg'=>'from-amber-500 to-orange-600'],
        ['label'=>'Puntos de Acopio','icon'=>'location_on','val'=>$totalPuntos,'trend'=>null,'color'=>'#06B6D4','bg'=>'from-cyan-500 to-blue-600'],
    ]; @endphp
    @foreach($cards as $i=>$c)
    <div class="dash-card p-5 relative overflow-hidden fade-up group">
        <div class="metric-glow -top-2 -right-2" style="background:{{$c['color']}}"></div>
        {{-- Faded icon watermark --}}
        <div class="absolute -bottom-3 -right-3 pointer-events-none opacity-[0.35] group-hover:opacity-[0.55] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 80px; color: {{$c['color']}};">{{$c['icon']}}</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{$c['bg']}} flex items-center justify-center shadow-lg" style="box-shadow:0 8px 20px {{$c['color']}}33">
                    <span class="material-symbols-outlined text-white text-lg">{{$c['icon']}}</span>
                </div>
                @if($c['trend']!==null)
                <div class="flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-bold {{$c['trend']>=0?'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400':'bg-red-500/10 text-red-500'}}">
                    <span class="material-symbols-outlined text-xs">{{$c['trend']>=0?'trending_up':'trending_down'}}</span>
                    {{$c['trend']>=0?'+':''}}{{$c['trend']}}%
                </div>
                @endif
            </div>
            <p class="text-2xl font-black text-[#064E3B] dark:text-white tracking-tight" data-count="{{$c['val']}}">0</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold mt-1">{{$c['label']}}</p>
            @if($c['trend']!==null)
            <p class="text-[10px] text-gray-400 mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-[12px] {{$c['trend']>=0?'text-emerald-500':'text-red-400'}}">{{$c['trend']>=0?'trending_up':'trending_down'}}</span>
                <span class="{{$c['trend']>=0?'text-emerald-500':'text-red-400'}} font-bold">{{$c['trend']>=0?'+':''}}{{$c['trend']}}%</span>
                <span>vs semana anterior</span>
            </p>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Secondary KPIs --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @php $cards2=[
        ['label'=>'Mensajes','icon'=>'mail','val'=>$totalMensajes,'link'=>route('mensajes.index'),'color'=>'#8B5CF6'],
        ['label'=>'Recuperación','icon'=>'lock_reset','val'=>$totalSolicitudes,'link'=>route('recuperacion.index'),'color'=>'#F43F5E'],
        ['label'=>'Actividades','icon'=>'bolt','val'=>$totalActividades,'link'=>null,'color'=>'#F97316'],
        ['label'=>'Último Registro','icon'=>'person_add','val'=>null,'link'=>null,'color'=>'#0EA5E9'],
    ]; @endphp
    @foreach($cards2 as $c2)
    <div class="dash-card p-5 fade-up relative overflow-hidden group">
        {{-- Faded icon watermark --}}
        <div class="absolute -bottom-3 -right-3 pointer-events-none opacity-[0.35] group-hover:opacity-[0.55] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 80px; color: {{$c2['color']}};">{{$c2['icon']}}</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:{{$c2['color']}}15">
                    <span class="material-symbols-outlined text-lg" style="color:{{$c2['color']}}">{{$c2['icon']}}</span>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{$c2['label']}}</span>
            </div>
            @if($c2['val']!==null)
                <p class="text-xl font-black text-[#064E3B] dark:text-white" data-count="{{$c2['val']}}">0</p>
            @else
                @if($ultimoRegistro)
                    <p class="text-sm font-bold text-[#064E3B] dark:text-white truncate">{{$ultimoRegistro->nombre}}</p>
                    <span class="text-[11px] text-gray-400">{{$ultimoRegistro->created_at?$ultimoRegistro->created_at->diffForHumans():'Reciente'}}</span>
                @else
                    <p class="text-sm text-gray-400">Sin registros</p>
                @endif
            @endif
            @if($c2['link'])<a href="{{$c2['link']}}" class="text-[11px] font-bold mt-1 inline-block" style="color:{{$c2['color']}}">Ver detalles →</a>@endif
        </div>
    </div>
    @endforeach
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    {{-- Users Area Chart --}}
    <div class="dash-card p-6 lg:col-span-3 fade-up">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-lg text-[#064E3B] dark:text-white tracking-tight">Registro de Usuarios</h3>
                <p class="text-xs text-gray-400 mt-0.5">Últimos 7 días de actividad</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">En vivo</span>
            </div>
        </div>
        <div class="h-[220px]"><canvas id="usersAreaChart"></canvas></div>
    </div>
</div>

{{-- Holographic Map of Mexico --}}
<div class="dash-card overflow-hidden mb-8 fade-up">
    <div class="p-5 flex items-center justify-between border-b border-gray-100 dark:border-emerald-800/30">
        <div>
            <h3 class="font-bold text-base text-[#064E3B] dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-500">public</span>
                Ecosistema Regional
            </h3>
            <p class="text-[11px] text-gray-400 mt-0.5">República Mexicana — Cobertura ZeroWaste</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 live-dot"></span>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Querétaro</span>
        </div>
    </div>
    <div class="map-holographic">
        {{-- Grid lines --}}
        @for($i=1;$i<=6;$i++)
        <div class="map-grid-line map-grid-h" style="top:{{$i*14}}%"></div>
        @endfor
        @for($i=1;$i<=8;$i++)
        <div class="map-grid-line map-grid-v" style="left:{{$i*11}}%"></div>
        @endfor

        <div class="map-svg-wrap">
            {{-- Simplified SVG map of Mexico with state outlines --}}
            <svg viewBox="0 0 800 520" xmlns="http://www.w3.org/2000/svg">
                {{-- Baja California --}}
                <path d="M30,30 L55,25 L65,60 L70,100 L68,140 L60,180 L50,200 L40,190 L35,150 L30,110 L25,70 Z" />
                {{-- Baja California Sur --}}
                <path d="M50,200 L60,180 L68,210 L75,250 L70,280 L60,300 L45,290 L40,260 L42,230 Z" />
                {{-- Sonora --}}
                <path d="M65,60 L70,30 L130,20 L180,25 L190,60 L185,100 L170,130 L140,140 L100,145 L70,100 Z" />
                {{-- Chihuahua --}}
                <path d="M180,25 L240,20 L260,30 L270,70 L265,120 L250,160 L220,170 L190,160 L170,130 L185,100 L190,60 Z" />
                {{-- Coahuila --}}
                <path d="M260,30 L320,25 L350,40 L360,80 L350,120 L330,150 L300,160 L270,155 L250,160 L265,120 L270,70 Z" />
                {{-- Nuevo León --}}
                <path d="M350,40 L390,35 L410,60 L415,100 L400,130 L380,150 L350,155 L330,150 L350,120 L360,80 Z" />
                {{-- Tamaulipas --}}
                <path d="M390,35 L430,30 L450,55 L460,100 L455,150 L440,190 L420,200 L395,190 L380,160 L380,150 L400,130 L415,100 L410,60 Z" />
                {{-- Sinaloa --}}
                <path d="M100,145 L140,140 L170,130 L190,160 L180,200 L160,230 L140,240 L115,235 L100,210 L90,180 Z" />
                {{-- Durango --}}
                <path d="M190,160 L220,170 L250,160 L270,155 L280,185 L275,220 L260,250 L230,260 L200,250 L180,230 L180,200 Z" />
                {{-- Zacatecas --}}
                <path d="M270,155 L300,160 L330,150 L340,175 L335,210 L320,240 L295,250 L275,245 L260,250 L275,220 L280,185 Z" />
                {{-- San Luis Potosí --}}
                <path d="M330,150 L350,155 L380,150 L395,190 L390,230 L375,260 L350,270 L330,260 L320,240 L335,210 L340,175 Z" />
                {{-- Nayarit --}}
                <path d="M140,240 L160,230 L180,230 L200,250 L195,275 L180,290 L160,285 L145,270 Z" />
                {{-- Aguascalientes --}}
                <path d="M275,245 L295,250 L300,270 L290,285 L275,280 L270,265 Z" />
                {{-- Jalisco --}}
                <path d="M145,270 L160,285 L180,290 L195,275 L200,250 L230,260 L260,250 L275,245 L270,265 L275,280 L285,300 L280,330 L260,350 L230,355 L200,340 L180,320 L165,305 L150,295 Z" />
                {{-- Guanajuato --}}
                <path d="M290,285 L300,270 L320,265 L340,275 L345,295 L335,315 L315,320 L300,310 L285,300 Z" />
                {{-- Querétaro (highlighted) --}}
                <path class="state-qro" d="M340,275 L355,270 L370,280 L375,300 L365,315 L350,318 L338,310 L335,295 L345,295 Z" />
                {{-- Hidalgo --}}
                <path d="M370,280 L390,275 L405,290 L410,310 L400,325 L385,328 L375,320 L365,315 L375,300 Z" />
                {{-- Colima --}}
                <path d="M180,320 L200,340 L195,360 L175,365 L165,350 L165,330 Z" />
                {{-- Michoacán --}}
                <path d="M200,340 L230,355 L260,350 L280,330 L285,300 L300,310 L315,320 L335,315 L338,310 L350,318 L345,340 L330,360 L305,375 L275,385 L245,380 L220,370 L195,360 Z" />
                {{-- Estado de México --}}
                <path d="M350,318 L365,315 L375,320 L385,328 L395,340 L390,360 L375,368 L360,365 L345,355 L345,340 Z" />
                {{-- CDMX --}}
                <path d="M375,340 L385,338 L390,348 L385,358 L375,358 L372,348 Z" style="fill:rgba(139,92,246,0.4);stroke:#A78BFA;stroke-width:1" />
                {{-- Tlaxcala --}}
                <path d="M400,325 L415,320 L420,335 L412,345 L400,340 L395,340 Z" />
                {{-- Puebla --}}
                <path d="M395,340 L400,340 L412,345 L420,335 L435,340 L445,365 L440,390 L420,400 L400,395 L390,375 L390,360 Z" />
                {{-- Veracruz --}}
                <path d="M410,310 L430,300 L450,290 L465,310 L475,340 L480,370 L475,400 L460,420 L445,430 L435,410 L440,390 L445,365 L435,340 L420,335 L415,320 L405,290 Z" />
                {{-- Morelos --}}
                <path d="M360,365 L375,368 L380,380 L370,390 L358,385 L355,375 Z" />
                {{-- Guerrero --}}
                <path d="M245,380 L275,385 L305,375 L330,360 L345,355 L360,365 L355,375 L358,385 L370,390 L380,380 L390,375 L400,395 L390,420 L370,440 L340,450 L310,445 L280,435 L260,420 L245,400 Z" />
                {{-- Oaxaca --}}
                <path d="M340,450 L370,440 L390,420 L400,395 L420,400 L435,410 L445,430 L450,450 L440,470 L420,480 L395,485 L370,480 L345,470 L335,460 Z" />
                {{-- Tabasco --}}
                <path d="M475,400 L500,390 L530,385 L550,395 L555,415 L540,425 L520,430 L500,425 L485,420 L475,400 Z" />
                {{-- Chiapas --}}
                <path d="M500,425 L520,430 L540,425 L555,415 L570,420 L580,440 L575,470 L560,490 L535,500 L510,495 L495,480 L490,460 L495,440 Z" />
                {{-- Campeche --}}
                <path d="M555,395 L580,370 L600,360 L615,365 L620,390 L615,415 L600,430 L580,435 L570,420 L555,415 Z" />
                {{-- Yucatán --}}
                <path d="M600,360 L620,340 L650,320 L680,315 L700,325 L710,345 L700,365 L680,375 L655,380 L635,380 L615,365 Z" />
                {{-- Quintana Roo --}}
                <path d="M700,325 L720,320 L740,330 L750,360 L745,400 L735,430 L720,450 L700,440 L690,420 L685,395 L680,375 L700,365 L710,345 Z" />
            </svg>
        </div>

        {{-- Pin on Querétaro --}}
        <div class="map-pin" style="top:58%;left:45%"></div>
        <div class="map-label" style="top:61%;left:43%">Querétaro, Qro.</div>

        {{-- Bottom info bar --}}
        <div class="absolute bottom-0 left-0 right-0 z-10 flex items-center justify-between px-6 py-3" style="background:rgba(6,78,59,0.85);backdrop-filter:blur(8px)">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
                    <span class="text-[10px] font-bold text-emerald-300 uppercase tracking-wider">Ecosistema Activo</span>
                </div>
                <span class="text-[10px] text-emerald-500/60">|</span>
                <span class="text-[10px] text-emerald-200/70 font-semibold">{{ $totalPuntos }} puntos de acopio</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-emerald-400 text-sm">location_on</span>
                <span class="text-[10px] text-emerald-200/70 font-semibold">20.5888° N, 100.3899° W</span>
            </div>
        </div>
    </div>
</div>

{{-- Analytics Row --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    {{-- Sentiment NLP --}}
    <div class="dash-card p-6 flex flex-col items-center fade-up">
        <div class="flex items-center justify-between w-full mb-4">
            <h3 class="font-bold text-sm text-[#064E3B] dark:text-white">Sentimiento NLP</h3>
            <span class="text-[10px] px-2 py-0.5 bg-violet-500/10 text-violet-600 dark:text-violet-400 rounded-full font-bold uppercase tracking-wider">IA</span>
        </div>
        <div class="w-[160px] h-[160px] relative mb-4">
            <canvas id="sentChart"></canvas>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-2xl font-black text-emerald-500">{{$sentimiento['POS']??0}}%</span>
            </div>
        </div>
        <div class="flex gap-4 text-[11px] font-semibold">
            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400"><span class="w-2 h-2 rounded-sm bg-emerald-500"></span>Pos {{$sentimiento['POS']??0}}%</span>
            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400"><span class="w-2 h-2 rounded-sm bg-indigo-500"></span>Neu {{$sentimiento['NEU']??0}}%</span>
            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400"><span class="w-2 h-2 rounded-sm bg-rose-500"></span>Neg {{$sentimiento['NEG']??0}}%</span>
        </div>
    </div>

    {{-- Roles Distribution --}}
    <div class="dash-card p-6 fade-up">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-sm text-[#064E3B] dark:text-white">Distribución de Roles</h3>
            <span class="material-symbols-outlined text-gray-300 dark:text-gray-600 text-lg">pie_chart</span>
        </div>
        <div class="h-[180px]"><canvas id="rolesChart"></canvas></div>
    </div>

    {{-- Stat Rings --}}
    <div class="dash-card p-6 fade-up">
        <h3 class="font-bold text-sm text-[#064E3B] dark:text-white mb-4">Métricas Clave</h3>
        <div class="flex items-center justify-around">
            @php
                $adminPct=$totalUsuarios>0?round($totalAdmins/$totalUsuarios*100):0;
                $blockPct=$totalUsuarios>0?round($totalBloqueados/$totalUsuarios*100):0;
            @endphp
            <div class="text-center">
                <div class="stat-ring mx-auto">
                    <svg width="140" height="140" viewBox="0 0 140 140">
                        <circle cx="70" cy="70" r="60" class="ring-bg"/>
                        <circle cx="70" cy="70" r="60" class="ring-fill" stroke="#8B5CF6" data-value="{{$adminPct}}" style="stroke-dasharray:377;stroke-dashoffset:377"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-black text-[#064E3B] dark:text-white">{{$adminPct}}%</span>
                        <span class="text-[10px] text-gray-400 font-bold">Admins</span>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <div class="stat-ring mx-auto">
                    <svg width="140" height="140" viewBox="0 0 140 140">
                        <circle cx="70" cy="70" r="60" class="ring-bg"/>
                        <circle cx="70" cy="70" r="60" class="ring-fill" stroke="#F43F5E" data-value="{{$blockPct}}" style="stroke-dasharray:377;stroke-dashoffset:377"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-black text-[#064E3B] dark:text-white">{{$blockPct}}%</span>
                        <span class="text-[10px] text-gray-400 font-bold">Bloqueados</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Users Table --}}
<div class="dash-card overflow-hidden mb-8 fade-up">
    <div class="p-5 flex items-center justify-between border-b border-gray-100 dark:border-emerald-800/30">
        <div>
            <h3 class="font-bold text-base text-[#064E3B] dark:text-white">Usuarios Recientes</h3>
            <p class="text-[11px] text-gray-400 mt-0.5">Últimos 5 registros del ecosistema</p>
        </div>
        <a href="{{route('usuarios.index')}}" class="text-xs bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-1.5 border border-emerald-500/20">
            <span class="material-symbols-outlined text-sm">group</span> Ver todos
        </a>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50/80 dark:bg-white/[0.02] text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider font-bold">
            <tr><th class="p-3 pl-5">Usuario</th><th class="p-3">Rol</th><th class="p-3">Estado</th><th class="p-3">Ubicación</th><th class="p-3">Registro</th></tr>
        </thead>
        <tbody>
            @forelse($usuariosRecientes as $u)
            <tr class="border-b border-gray-50 dark:border-emerald-800/20 hover:bg-emerald-50/40 dark:hover:bg-white/[0.02] transition-colors stagger-row">
                <td class="p-3 pl-5">
                    <div class="flex items-center gap-3">
                        @php $uf=$u->foto_perfil??'default.png'; @endphp
                        <img src="{{url('/static/img/perfiles/'.$uf)}}" alt="{{$u->nombre}}" class="w-9 h-9 rounded-full border-2 {{$u->is_admin?'border-violet-400':'border-emerald-400'}} object-cover" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%2334D399%22 width=%2240%22 height=%2240%22 rx=%2220%22/><text x=%2250%%22 y=%2254%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2218%22 font-weight=%22bold%22>{{strtoupper(substr($u->nombre,0,1))}}</text></svg>'">
                        <div>
                            <p class="font-bold text-[#064E3B] dark:text-white text-sm">{{$u->nombre}}</p>
                            <p class="text-[11px] text-gray-400">{{$u->email}}</p>
                        </div>
                    </div>
                </td>
                <td class="p-3">
                    @if($u->is_admin)
                    <span class="px-2 py-1 rounded-lg text-[10px] font-bold bg-violet-500/10 text-violet-600 dark:text-violet-400">Admin</span>
                    @else
                    <span class="px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Usuario</span>
                    @endif
                </td>
                <td class="p-3">
                    @if($u->bloqueado??false)
                    <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span><span class="text-xs font-bold text-red-500">Bloqueado</span></span>
                    @else
                    <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 live-dot"></span><span class="text-xs font-bold text-emerald-500">Activo</span></span>
                    @endif
                </td>
                <td class="p-3 text-gray-400 text-xs">{{$u->ubicacion??'—'}}</td>
                <td class="p-3 text-gray-400 text-xs">{{$u->created_at?$u->created_at->format('d M Y'):'—'}}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="p-8 text-center text-gray-400 italic">Sin usuarios registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
