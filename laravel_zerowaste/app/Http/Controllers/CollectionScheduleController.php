<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CollectionScheduleController extends Controller
{
    private const DAYS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    public function index(): View
    {
        $schedules = DB::table('collection_schedules')->orderBy('weekday')->get()->keyBy('weekday');
        $exceptions = DB::table('schedule_exceptions')->where('active', true)->orderBy('exception_date')->paginate(12);
        return view('admin.recolecciones.horarios', ['schedules' => $schedules, 'exceptions' => $exceptions, 'days' => self::DAYS]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'array'],
            'days.*.active' => ['nullable', 'boolean'],
            'days.*.starts_at' => ['required', 'date_format:H:i'],
            'days.*.ends_at' => ['required', 'date_format:H:i', 'after:days.*.starts_at'],
            'days.*.interval_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'days.*.capacity_per_interval' => ['required', 'integer', 'min:1', 'max:500'],
        ], ['days.*.ends_at.after' => 'La hora final debe ser posterior a la inicial.']);

        DB::transaction(function () use ($validated) {
            foreach ($validated['days'] as $weekday => $day) {
                DB::table('collection_schedules')->where('weekday', (int) $weekday)->update([
                    'active' => (bool) ($day['active'] ?? false),
                    'starts_at' => $day['starts_at'],
                    'ends_at' => $day['ends_at'],
                    'interval_minutes' => $day['interval_minutes'],
                    'capacity_per_interval' => $day['capacity_per_interval'],
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            }
        });
        return back()->with('success', 'Los horarios de recolección fueron guardados.');
    }

    public function storeException(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'exception_date' => ['required', 'date'],
            'kind' => ['required', 'in:closed,holiday,blocked,override'],
            'starts_at' => ['nullable', 'required_if:kind,override', 'date_format:H:i'],
            'ends_at' => ['nullable', 'required_if:kind,override', 'date_format:H:i', 'after:starts_at'],
            'capacity_per_interval' => ['nullable', 'integer', 'min:1', 'max:500'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $data['active'] = true;
        $data['created_by'] = auth()->id();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('schedule_exceptions')->updateOrInsert(
            ['exception_date' => $data['exception_date'], 'kind' => $data['kind']],
            $data,
        );
        return back()->with('success', 'La excepción fue guardada.');
    }

    public function destroyException(int $id): RedirectResponse
    {
        DB::table('schedule_exceptions')->where('id', $id)->update(['active' => false, 'updated_at' => now()]);
        return back()->with('success', 'La excepción fue retirada.');
    }

    public function restore(): RedirectResponse
    {
        DB::transaction(function () {
            foreach (range(1, 7) as $weekday) {
                DB::table('collection_schedules')->where('weekday', $weekday)->update([
                    'active' => in_array($weekday, [1, 3, 5], true),
                    'starts_at' => '10:00', 'ends_at' => '14:00',
                    'interval_minutes' => 60, 'capacity_per_interval' => 10,
                    'updated_by' => auth()->id(), 'updated_at' => now(),
                ]);
            }
        });
        return back()->with('success', 'Se restauró el horario inicial de lunes, miércoles y viernes.');
    }
}
