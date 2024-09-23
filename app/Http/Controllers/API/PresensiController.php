<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Presensi;
use Auth;
use Carbon\Carbon;
use DB;
use stdClass;

date_default_timezone_set("Asia/Jakarta");

class PresensiController extends Controller
{
    function getPresensis()
    {
        $presensis = Presensi::where('user_id', Auth::user()->id)->get();
        foreach ($presensis as $item) {
            if ($item->tanggal == date('Y-m-d')) {
                $item->is_hari_ini = true;
            } else {
                $item->is_hari_ini = false;
            }
            $datetime = Carbon::parse($item->tanggal)->locale('id');
            $masuk = Carbon::parse($item->masuk)->locale('id');
            $pulang = Carbon::parse($item->pulang)->locale('id');

            $datetime->settings(['formatFunction' => 'translatedFormat']);
            $masuk->settings(['formatFunction' => 'translatedFormat']);
            $pulang->settings(['formatFunction' => 'translatedFormat']);

            $item->tanggal = $datetime->format('l, j F Y');
            $item->masuk = $masuk->format('H:i');
            $item->pulang = $pulang->format('H:i');
        }

        return response()->json([
            'success' => true,
            'message' => 'Sukses menampilkan data',
            'data' => $presensis
        ]);
    }
    function savePresensi(Request $request)
    {
    $keterangan = "";
    $currentTime = date('H:i:s');
    $presensi = Presensi::whereDate('tanggal', '=', date('Y-m-d'))
        ->where('user_id', Auth::user()->id)
        ->first();

    // Batas waktu untuk presensi masuk (09:00:00) dan presensi keluar (21:00:00)
    $batasMasuk = "09:00:00";
    $batasPulang = "21:00:00";

    if ($presensi == null) {
        // Validasi untuk presensi masuk
        if ($currentTime > $batasMasuk) {
            return response()->json([
                'success' => false,
                'message' => 'Presensi masuk gagal karena melewati batas waktu (jam 9 pagi)',
                'data' => null
            ]);
        }

        $presensi = Presensi::create([
            'user_id' => Auth::user()->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'tanggal' => date('Y-m-d'),
            'masuk' => $currentTime,
            'pulang' => null
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Sukses absen untuk masuk',
            'data' => $presensi
        ]);
    } else {
        // Validasi untuk presensi keluar
        if ($presensi->pulang !== null) {
            $keterangan = "Anda sudah melakukan presensi";
            return response()->json([
                'success' => false,
                'message' => $keterangan,
                'data' => null
            ]);
        } else {
            if ($currentTime > $batasPulang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presensi keluar gagal karena melewati batas waktu (jam 9 malam)',
                    'data' => null
                ]);
            }

            $data = [
                'pulang' => $currentTime
            ];
            Presensi::whereDate('tanggal', '=', date('Y-m-d'))->update($data);
        }
        $presensi = Presensi::whereDate('tanggal', '=', date('Y-m-d'))
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Sukses Absen untuk Pulang',
            'data' => $presensi
            ]);
        }
    }

}
