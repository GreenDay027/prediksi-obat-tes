<?php

namespace App\Http\Controllers;

use App\Models\DataObat;
use App\Models\ObatKeluar;
use App\Models\ObatMasuk;
use Illuminate\Http\Request;

class ObatController extends Controller
{
    public function index()
    {
        $dataObat = DataObat::all();
        $dataObatMasuk = ObatMasuk::with('dataObat')->get();
        $dataObatKeluar = ObatKeluar::with('dataObat')->get();
        return view('obat.index', compact('dataObat', 'dataObatMasuk', 'dataObatKeluar'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if ($request->has('jenis_form')) {
            if ($request->jenis_form == 'data_obat') {
                $request->validate([
                    'nama_obat' => 'required|string|max:255',
                    'jenis' => 'required|string|max:255',
                    'satuan' => 'required|in:botol,kapsul,tablet,sachet',
                    'periode' => 'required|string|max:4',
                ]);

                DataObat::create($request->all());
            } elseif ($request->jenis_form == 'obat_masuk') {
                $request->validate([
                    'data_obat_id' => 'required|exists:data_obat,id',
                    'jumlah' => 'required|integer|min:1',
                    'tanggal' => 'required|date',
                    'kadaluarsa' => 'required|date',
                ]);

                $obatMasuk = ObatMasuk::create($request->all());
                $dataObat = DataObat::find($request->data_obat_id);
                $dataObat->increment('stok_masuk', $request->jumlah);
                $dataObat->increment('sisa', $request->jumlah);
            } elseif ($request->jenis_form == 'obat_keluar') {
                $request->validate([
                    'data_obat_id' => 'required|exists:data_obat,id',
                    'jumlah' => 'required|integer|min:1',
                    'tanggal' => 'required|date',
                ]);

                $obatKeluar = ObatKeluar::create($request->all());
                $dataObat = DataObat::find($request->data_obat_id);
                $dataObat->increment('stok_keluar', $request->jumlah);
                $dataObat->decrement('sisa', $request->jumlah);
            }
        }

        return redirect()->route('obat.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, $id)
    {

        if ($request->has('jenis_form')) {
            if ($request->jenis_form == 'data_obat') {
                $request->validate([
                    'nama_obat' => 'required|string|max:255',
                    'jenis' => 'required|string|max:255',
                    'satuan' => 'required|in:botol,kapsul,tablet,sachet',
                    'periode' => 'required|string|max:4',
                ]);

                $dataObat = DataObat::find($id);
                $oldJenis = $dataObat->jenis;

                // Update DataObat
                $dataObat->update($request->all());

                // Handle changes for `stok_keluar` from the old type to the new type
                if ($dataObat->jenis !== $oldJenis) {
                    $obatKeluars = ObatKeluar::where('data_obat_id', $id)->get();
                    foreach ($obatKeluars as $obatKeluar) {
                        $dataObatLama = DataObat::where('jenis', $oldJenis)->first();
                        if ($dataObatLama) {
                            $dataObatLama->decrement('stok_keluar', $obatKeluar->jumlah);
                            $dataObatLama->increment('sisa', $obatKeluar->jumlah);
                        }

                        $dataObatBaru = DataObat::where('jenis', $dataObat->jenis)->first();
                        if ($dataObatBaru) {
                            $dataObatBaru->increment('stok_keluar', $obatKeluar->jumlah);
                            $dataObatBaru->decrement('sisa', $obatKeluar->jumlah);
                        }
                    }
                }
            } elseif ($request->jenis_form == 'obat_masuk') {
                $request->validate([
                    'data_obat_id' => 'required|exists:data_obat,id',
                    'jumlah' => 'required|integer|min:1',
                    'tanggal' => 'required|date',
                ]);

                $obatMasuk = ObatMasuk::find($id);
                $dataObat = DataObat::find($obatMasuk->data_obat_id);
                $dataObat->decrement('stok_masuk', $obatMasuk->jumlah);
                $dataObat->decrement('sisa', $obatMasuk->jumlah);
                $obatMasuk->update($request->all());
                $dataObat->increment('stok_masuk', $request->jumlah);
                $dataObat->increment('sisa', $request->jumlah);
            } elseif ($request->jenis_form == 'obat_keluar') {
                $request->validate([
                    'data_obat_id' => 'required|exists:data_obat,id',
                    'jumlah' => 'required|integer|min:1',
                    'tanggal' => 'required|date',
                ]);

                $obatKeluar = ObatKeluar::find($id);
                $dataObat = DataObat::find($obatKeluar->data_obat_id);
                $dataObat->decrement('stok_keluar', $obatKeluar->jumlah);
                $dataObat->increment('sisa', $obatKeluar->jumlah);
                $obatKeluar->update($request->all());
                $dataObat->increment('stok_keluar', $request->jumlah);
                $dataObat->decrement('sisa', $request->jumlah);
            }
        }

        return redirect()->route('obat.index');
    }

    public function destroy($id)
    {
        if (request()->has('jenis_form')) {
            if (request()->jenis_form == 'data_obat') {
                DataObat::destroy($id);
            } elseif (request()->jenis_form == 'obat_masuk') {
                $obatMasuk = ObatMasuk::find($id);
                $dataObat = DataObat::find($obatMasuk->data_obat_id);
                $dataObat->decrement('stok_masuk', $obatMasuk->jumlah);
                $dataObat->decrement('sisa', $obatMasuk->jumlah);
                $obatMasuk->delete();
            } elseif (request()->jenis_form == 'obat_keluar') {
                $obatKeluar = ObatKeluar::find($id);
                $dataObat = DataObat::find($obatKeluar->data_obat_id);
                $dataObat->decrement('stok_keluar', $obatKeluar->jumlah);
                $dataObat->increment('sisa', $obatKeluar->jumlah);
                $obatKeluar->delete();
            }
        }

        return redirect()->route('obat.index');
    }
}
