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
.sparkline-bar{display:inline-block;width:3px;border-radius:2px;margin:0 1px;transition:height .6s cubic-bezier(.4,0,.2,1);vertical-align:bottom}
.globe-container{position:relative;width:100%;height:100%;min-height:320px;border-radius:1.5rem;overflow:hidden}
.stat-ring{position:relative;width:140px;height:140px}
.stat-ring svg{transform:rotate(-90deg)}
.stat-ring .ring-bg{stroke:rgba(0,0,0,0.05);fill:none;stroke-width:8}
.dark .stat-ring .ring-bg{stroke:rgba(255,255,255,0.05)}
.stat-ring .ring-fill{fill:none;stroke-width:8;stroke-linecap:round;transition:stroke-dashoffset 1.5s cubic-bezier(.4,0,.2,1)}
.fade-up{opacity:0;transform:translateY(16px);animation:fadeUp .6s ease forwards}
@keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.4}}
.live-dot{animation:pulse-dot 2s infinite}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
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

// Three.js Globe — Ultra Premium
const gc=document.getElementById('globe-canvas');
if(gc){
    const scene=new THREE.Scene();
    const cam=new THREE.PerspectiveCamera(40, gc.clientWidth/gc.clientHeight, 0.1, 1000);
    const renderer=new THREE.WebGLRenderer({canvas:gc, alpha:true, antialias:true});
    renderer.setSize(gc.clientWidth, gc.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Globe group (everything rotates together)
    const globeGroup = new THREE.Group();
    scene.add(globeGroup);

    // --- 1. Inner Core (subtle gradient sphere) ---
    const coreGeo = new THREE.SphereGeometry(1.48, 64, 64);
    const coreMat = new THREE.ShaderMaterial({
        uniforms: {
            color1: { value: new THREE.Color(isDark ? 0x064E3B : 0xD1FAE5) },
            color2: { value: new THREE.Color(isDark ? 0x0D9488 : 0x6EE7B7) }
        },
        vertexShader: `varying vec2 vUv; void main(){ vUv=uv; gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0); }`,
        fragmentShader: `uniform vec3 color1; uniform vec3 color2; varying vec2 vUv; void main(){ gl_FragColor=vec4(mix(color1,color2,vUv.y),0.85); }`,
        transparent: true
    });
    const coreMesh = new THREE.Mesh(coreGeo, coreMat);
    globeGroup.add(coreMesh);

    // --- 2. Grid lines (latitude/longitude) ---
    const gridMat = new THREE.MeshBasicMaterial({ color: isDark ? 0x34D399 : 0x059669, wireframe: true, transparent: true, opacity: 0.08 });
    const gridMesh = new THREE.Mesh(new THREE.SphereGeometry(1.5, 36, 18), gridMat);
    globeGroup.add(gridMesh);

    // --- 3. Hex wireframe shell ---
    const hexGeo = new THREE.IcosahedronGeometry(1.52, 2);
    const hexMat = new THREE.MeshBasicMaterial({ color: isDark ? 0x10B981 : 0x047857, wireframe: true, transparent: true, opacity: 0.12 });
    const hexMesh = new THREE.Mesh(hexGeo, hexMat);
    globeGroup.add(hexMesh);

    // --- 4. Atmosphere glow (additive blending sprite) ---
    const glowGeo = new THREE.SphereGeometry(1.7, 32, 32);
    const glowMat = new THREE.ShaderMaterial({
        uniforms: { glowColor: { value: new THREE.Color(0x10B981) } },
        vertexShader: `varying vec3 vNormal; void main(){ vNormal=normalize(normalMatrix*normal); gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0); }`,
        fragmentShader: `uniform vec3 glowColor; varying vec3 vNormal; void main(){ float intensity=pow(0.65-dot(vNormal,vec3(0,0,1.0)),3.0); gl_FragColor=vec4(glowColor,intensity*0.6); }`,
        side: THREE.BackSide, transparent: true, blending: THREE.AdditiveBlending
    });
    scene.add(new THREE.Mesh(glowGeo, glowMat));

    // --- 5. Floating particles (star field) ---
    const starsGeo = new THREE.BufferGeometry(); const starPos = [];
    for(let i=0;i<400;i++){
        starPos.push((Math.random()-0.5)*12, (Math.random()-0.5)*12, (Math.random()-0.5)*12);
    }
    starsGeo.setAttribute('position', new THREE.Float32BufferAttribute(starPos, 3));
    const starsMat = new THREE.PointsMaterial({ color: isDark ? 0x6EE7B7 : 0x059669, size: 0.02, transparent: true, opacity: 0.5 });
    const starsMesh = new THREE.Points(starsGeo, starsMat);
    scene.add(starsMesh);

    // --- 6. Orbital ring ---
    const ringGeo = new THREE.TorusGeometry(2.1, 0.008, 8, 100);
    const ringMat = new THREE.MeshBasicMaterial({ color: 0x10B981, transparent: true, opacity: 0.3 });
    const ring = new THREE.Mesh(ringGeo, ringMat);
    ring.rotation.x = Math.PI / 2.5;
    ring.rotation.z = 0.3;
    globeGroup.add(ring);

    // Second orbital ring
    const ring2 = new THREE.Mesh(
        new THREE.TorusGeometry(2.3, 0.005, 8, 100),
        new THREE.MeshBasicMaterial({ color: 0x34D399, transparent: true, opacity: 0.15 })
    );
    ring2.rotation.x = Math.PI / 3;
    ring2.rotation.z = -0.5;
    globeGroup.add(ring2);

    // --- 7. Location pin markers (pulsing dots on surface) ---
    const pinColor = new THREE.Color(0x00E096);
    const locations = [
        {lat: 20.59, lon: -100.39}, // Querétaro
        {lat: 19.43, lon: -99.13},  // CDMX
        {lat: 20.67, lon: -103.35}, // Guadalajara
        {lat: 25.69, lon: -100.32}, // Monterrey
    ];
    locations.forEach(loc => {
        const phi = (90 - loc.lat) * Math.PI / 180;
        const theta = (loc.lon + 180) * Math.PI / 180;
        const r2 = 1.52;
        const x = -r2 * Math.sin(phi) * Math.cos(theta);
        const y = r2 * Math.cos(phi);
        const z = r2 * Math.sin(phi) * Math.sin(theta);

        // Pin dot
        const dotGeo = new THREE.SphereGeometry(0.03, 8, 8);
        const dotMat = new THREE.MeshBasicMaterial({ color: pinColor });
        const dot = new THREE.Mesh(dotGeo, dotMat);
        dot.position.set(x, y, z);
        globeGroup.add(dot);

        // Pulse ring
        const pulseGeo = new THREE.RingGeometry(0.04, 0.06, 16);
        const pulseMat = new THREE.MeshBasicMaterial({ color: pinColor, transparent: true, opacity: 0.6, side: THREE.DoubleSide });
        const pulse = new THREE.Mesh(pulseGeo, pulseMat);
        pulse.position.set(x, y, z);
        pulse.lookAt(0, 0, 0);
        pulse.userData = { basScale: 1, phase: Math.random() * Math.PI * 2 };
        globeGroup.add(pulse);
    });

    // --- Camera & Mouse interaction ---
    cam.position.z = 4.2;
    let mouseX = 0, mouseY = 0;
    gc.addEventListener('mousemove', (e) => {
        const rect = gc.getBoundingClientRect();
        mouseX = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
        mouseY = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
    });
    gc.addEventListener('mouseleave', () => { mouseX = 0; mouseY = 0; });

    const clock = new THREE.Clock();
    function animate(){
        requestAnimationFrame(animate);
        const t = clock.getElapsedTime();

        // Auto-rotation + mouse tilt
        globeGroup.rotation.y += 0.0015;
        globeGroup.rotation.y += mouseX * 0.003;
        globeGroup.rotation.x = mouseY * 0.15 + Math.sin(t * 0.3) * 0.02;

        // Stars drift
        starsMesh.rotation.y += 0.0002;
        starsMesh.rotation.x += 0.0001;

        // Pulse pins
        globeGroup.children.forEach(child => {
            if(child.userData && child.userData.basScale !== undefined){
                const s = 1 + 0.5 * Math.sin(t * 2 + child.userData.phase);
                child.scale.set(s, s, s);
                child.material.opacity = 0.3 + 0.4 * Math.sin(t * 2 + child.userData.phase);
            }
        });

        renderer.render(scene, cam);
    }
    animate();

    window.addEventListener('resize', () => {
        if(gc.parentElement){
            cam.aspect = gc.clientWidth / gc.clientHeight;
            cam.updateProjectionMatrix();
            renderer.setSize(gc.clientWidth, gc.clientHeight);
        }
    });
}

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
    <div class="dash-card p-5 relative overflow-hidden fade-up">
        <div class="metric-glow -top-2 -right-2" style="background:{{$c['color']}}"></div>
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
        {{-- SVG Sparkline --}}
        <div class="mt-3 h-8">
            <svg viewBox="0 0 120 28" class="w-full h-full" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="spark-{{$i}}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="{{$c['color']}}" stop-opacity="0.2"/>
                        <stop offset="100%" stop-color="{{$c['color']}}" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                @php
                    $points = $i === 0 ? '0,22 20,18 40,20 55,12 70,14 85,6 100,10 120,4'
                            : ($i === 1 ? '0,8 20,12 40,10 55,18 70,14 85,20 100,16 120,22'
                            : ($i === 2 ? '0,16 20,12 40,18 55,8 70,10 85,14 100,6 120,2'
                            : '0,20 20,16 40,18 55,10 70,14 85,8 100,12 120,6'));
                @endphp
                <polygon fill="url(#spark-{{$i}})" points="0,28 {{$points}} 120,28" />
                <polyline fill="none" stroke="{{$c['color']}}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="{{$points}}" />
            </svg>
        </div>
        @if($c['trend']!==null)
        <p class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
            <span class="material-symbols-outlined text-[12px] {{$c['trend']>=0?'text-emerald-500':'text-red-400'}}">{{$c['trend']>=0?'trending_up':'trending_down'}}</span>
            <span class="{{$c['trend']>=0?'text-emerald-500':'text-red-400'}} font-bold">{{$c['trend']>=0?'+':''}}{{$c['trend']}}%</span>
            <span>vs semana anterior</span>
        </p>
        @endif
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
    <div class="dash-card p-5 fade-up">
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
    @endforeach
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    {{-- Users Area Chart --}}
    <div class="dash-card p-6 lg:col-span-2 fade-up">
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

    {{-- 3D Globe Premium --}}
    <div class="dash-card p-0 overflow-hidden fade-up h-full flex flex-col relative group">
        <div class="globe-container" style="background: radial-gradient(ellipse at 40% 40%, {{ 'rgba(16,185,129,0.08)' }} 0%, transparent 60%), radial-gradient(ellipse at 60% 70%, {{ 'rgba(6,78,59,0.05)' }} 0%, transparent 50%); background-color: {{ 'rgba(240,253,244,0.5)' }};">
            <canvas id="globe-canvas" class="w-full h-full" style="cursor: grab;"></canvas>
            
            {{-- Vignette overlay --}}
            <div class="absolute inset-0 pointer-events-none" style="box-shadow: inset 0 0 80px rgba(0,0,0,0.06); border-radius: 1.5rem;"></div>
            
            {{-- Top-right live badge --}}
            <div class="absolute top-4 right-4 flex items-center gap-1.5 bg-white/60 dark:bg-black/30 backdrop-blur-md border border-emerald-200/30 dark:border-emerald-700/30 px-3 py-1.5 rounded-full shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[9px] font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-widest">En vivo</span>
            </div>

            {{-- Floating Glass Info Card --}}
            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 w-[88%]">
                <div class="bg-white/75 dark:bg-[#0B1F18]/70 backdrop-blur-xl border border-white/40 dark:border-emerald-800/30 p-4 rounded-2xl shadow-[0_8px_32px_rgba(0,0,0,0.08)] transition-all duration-500 group-hover:-translate-y-1 group-hover:shadow-[0_16px_48px_rgba(0,0,0,0.12)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <h4 class="text-[11px] font-black text-[#064E3B] dark:text-white uppercase tracking-[0.15em]">Ecosistema Global</h4>
                            </div>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">Puntos de reciclaje activos</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-none">{{$totalPuntos}}</p>
                            <p class="text-[9px] text-emerald-500/60 font-bold mt-0.5">QRO, MX</p>
                        </div>
                    </div>
                    {{-- Mini location dots --}}
                    <div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-200/40 dark:border-white/5">
                        <div class="flex -space-x-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-[#0B1F18]"></span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 ring-2 ring-white dark:ring-[#0B1F18]"></span>
                            <span class="w-2 h-2 rounded-full bg-teal-400 ring-2 ring-white dark:ring-[#0B1F18]"></span>
                            <span class="w-2 h-2 rounded-full bg-cyan-400 ring-2 ring-white dark:ring-[#0B1F18]"></span>
                        </div>
                        <span class="text-[9px] text-gray-400 dark:text-gray-500 font-medium">4 ciudades monitoreadas</span>
                    </div>
                </div>
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
