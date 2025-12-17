<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Services\DailySummaryGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date');
        if (!$date) {
            $date = now()->toDateString();
        }

        $entries = Entry::whereDate('work_date', $date)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('entries.index', [
            'date' => $date,
            'entries' => $entries,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        // Tomar la fecha seleccionada en la UI (selector superior) sin pedirla en el formulario
        $selectedDate = $request->input('date', $request->query('date'));
        try {
            $selectedDate = $selectedDate ? Carbon::parse($selectedDate)->toDateString() : now()->toDateString();
        } catch (\Throwable $e) {
            $selectedDate = now()->toDateString();
        }

        Entry::create([
            'work_date' => $selectedDate,
            'content' => $data['content'],
        ]);

        return redirect()->route('entries.index', ['date' => $selectedDate])
            ->with('status', 'Entrada registrada');
    }

    public function summary(Request $request)
    {
        // Ignoramos la fecha recibida para la generación del daily: usamos HOY y AYER
        $today = $request->input('date', now()->toDateString());

        $todayEntries = Entry::whereDate('work_date', $today)
            ->orderBy('created_at', 'asc')
            ->pluck('content')
            ->all();

        $yesterday = Entry::whereDate('work_date', '<',$today)->orderBy('work_date','desc')->first()?->work_date;

        $yesterdayEntries = Entry::whereDate('work_date', $yesterday)
            ->orderBy('created_at', 'asc')
            ->pluck('content')
            ->all();

        $text = '';
        $source = 'fallback';

        if (count($todayEntries) === 0 && count($yesterdayEntries) === 0) {
            $text = 'No hay entradas para hoy ni ayer.';
        } else {
            /** @var DailySummaryGenerator $gen */
            $gen = app(DailySummaryGenerator::class);
            $ai = $gen->generateHoyAyer($todayEntries, $yesterdayEntries, $today, $yesterday);
            if (is_string($ai) && trim($ai) !== '') {
                $text = $ai;
                $source = 'ai';
            } else {
                // Fallback en dos secciones
                $fmt = function (array $items) {
                    if (empty($items)) {
                        return "(sin entradas)";
                    }
                    $lines = array_map(function ($i, $c) {
                        return ($i + 1) . '. ' . $c;
                    }, array_keys($items), $items);
                    return implode("\n", $lines);
                };
                $text = 'Ayer (' . $yesterday . "):\n" . $fmt($yesterdayEntries)
                    . "\n\nHoy (" . $today . "):\n" . $fmt($todayEntries);
            }
        }

        // Volver a la vista principal con el resumen generado
        $returnDate = $request->input('date', $today); // conservar contexto si venía del listado de otra fecha
        return redirect()->route('entries.index', ['date' => $returnDate])
            ->with('summary', $text)
            ->with('summary_source', $source)
            ->with('summary_today', $today);
    }

    public function update(Request $request, Entry $entry)
    {
        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'content' => ['required', 'string'],
        ]);

        $entry->update($data);

        return redirect()->route('entries.index', ['date' => $data['work_date']])
            ->with('status', 'Entrada actualizada');
    }

    public function destroy(Request $request, Entry $entry)
    {
        $date = $request->query('date');
        if (!$date) {
            $date = $entry->work_date instanceof \DateTimeInterface ? $entry->work_date->format('Y-m-d') : (string) $entry->work_date;
        }

        $entry->delete();

        return redirect()->route('entries.index', ['date' => $date])
            ->with('status', 'Entrada eliminada');
    }
}
