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
            'tanggal_mulai' => $this->tanggal_mulai,
            'tahun_anggaran' => $this->tahun_anggaran,
            'total_penerimaan' => $this->total_penerimaan,
            'total_pengeluaran' => $this->total_pengeluaran,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'is_active_label' => $this->is_active == 1
                ? 'Aktif'
                : 'Tidak Aktif',
            // 'tanggal_mulai' => $this->tanggal_mulai,
            // 'tanggal_selesai' => $this->tanggal_selesai,
            // 'total_penerimaan' => $this->total_penerimaan,
            // 'total_pengeluaran' => $this->total_pengeluaran,
            'saldo_akhir' => $this->saldo_akhir,
            'created_at' => $this->created_at->format('d-m-Y'),
            // 'status' => $this->status,
        ];
        return $data;
    }
}
