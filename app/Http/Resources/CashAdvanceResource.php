<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashAdvanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        $data = [
            'data_category' => [
                'id' => $this->tm_category_ca->id,
                'name' => $this->tm_category_ca->name_category
            ],
            'id' => $this->id,
            'kode_ca' => $this->kode_ca,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'judul_kegiatan' => $this->judul_kegiatan,
            'tahun_anggaran' => $this->tahun_anggaran,
            'total_penerimaan' => $this->total_penerimaan,
            'total_pengeluaran' => $this->total_pengeluaran,
            'status' => $this->status,
            // 'tanggal_mulai' => $this->tanggal_mulai,
            // 'tanggal_selesai' => $this->tanggal_selesai,
            // 'total_penerimaan' => $this->total_penerimaan,
            // 'total_pengeluaran' => $this->total_pengeluaran,
            // 'saldo_akhir' => $this->saldo_akhir,
            // 'status' => $this->status,
        ];
        return $data;
    }
}
