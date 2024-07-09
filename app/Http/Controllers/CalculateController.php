<?php

namespace App\Http\Controllers;

use App\Models\Penilaian;
use App\Models\Alternatif;
use App\Models\Kriteria;
use Illuminate\Http\Request;

class CalculateController extends Controller
{
    //
    public function saw()
{
    
    $alternatifs = Alternatif::all();
    $kriterias = Kriteria::all();
    $penilaians = Penilaian::all();

    // Step 1: Matriks Penilaian
    $matrixPenilaian = [];
    foreach ($alternatifs as $alternatif) {
        foreach ($kriterias as $kriteria) {
            $penilaian = $penilaians->where('alternatif_id', $alternatif->id)
                                    ->where('kriteria_id', $kriteria->id)
                                    ->first();
            $nilai = $penilaian ? floatval($penilaian->nilai) : 0;
            $matrixPenilaian[$alternatif->id][$kriteria->id] = $nilai;
        }
    }

    // Step 2: Matriks Normalisasi
    $matrixNormalisasi = [];
    foreach ($kriterias as $kriteria) {
        $nilaiArray = [];
        foreach ($alternatifs as $alternatif) {
            $nilaiArray[] = $matrixPenilaian[$alternatif->id][$kriteria->id];
        }

        $maxValue = max($nilaiArray);
        $minValue = min($nilaiArray);

        foreach ($alternatifs as $alternatif) {
            if ($kriteria->jenis_kriteria == 'Benefit') {
                $matrixNormalisasi[$alternatif->id][$kriteria->id] = $matrixPenilaian[$alternatif->id][$kriteria->id] / $maxValue;
            } else {
                $matrixNormalisasi[$alternatif->id][$kriteria->id] = $minValue / $matrixPenilaian[$alternatif->id][$kriteria->id];
            }
        }
    }

    // Step 3: Normalisasi Bobot
    $totalBobot = $kriterias->sum('weight');
    foreach ($kriterias as $kriteria) {
        $kriteria->bobot_normalized = $kriteria->weight / $totalBobot;
    }

    // Step 4: Menghitung Nilai Preferensi (Vektor V)
    $nilaiPreferensi = [];
    foreach ($alternatifs as $alternatif) {
        $nilaiTotal = 0.0;
        foreach ($kriterias as $kriteria) {
            $nilaiTotal += $matrixNormalisasi[$alternatif->id][$kriteria->id] * $kriteria->bobot_normalized;
        }
        $nilaiPreferensi[$alternatif->id] = $nilaiTotal;
    }

    // Step 5: Perankingan
    arsort($nilaiPreferensi);

    return view('penilaian.calculate', compact('alternatifs', 'kriterias', 'matrixPenilaian', 'matrixNormalisasi', 'nilaiPreferensi'));

}

}
