# PRD: Platform Website Warga RT/RW

**Versi:** 0.1 (draft awal) · **Tanggal:** 1 Oktober 2026 · **Status:** menunggu jawaban pertanyaan terbuka (Bagian 13)

Keterangan: **\[Jawaban\]** = keputusan dari kamu. **\[Usulan\]** = rekomendasi saya yang belum kamu konfirmasi.

---

## 1. Ringkasan

Platform web multi-wilayah untuk mengelola kehidupan warga di tingkat RT/RW: pengumuman, layanan pengajuan, kas, CCTV, kegiatan DKM dan Karang Taruna, serta pemilihan Ketua RT secara daring. Prioritas utama dipakai dari ponsel (lewat WhatsApp/browser). Dikelola satu admin/pengurus IT.

## 2. Tujuan dan indikator keberhasilan

**Tujuan:** menggantikan koordinasi lewat WhatsApp dan Excel dengan satu tempat yang rapi, transparan, dan aman.

**Masalah prioritas rilis pertama \[Jawaban\]:** pengumuman (ronda, kerja bakti, darurat), pengajuan surat dan keluhan, kas dan iuran, CCTV dan keamanan.

**Indikator keberhasilan (3-6 bulan):** *belum ditentukan* (lihat Bagian 13). **\[Usulan\]** contoh: % warga aktif terverifikasi, % iuran tercatat lewat sistem, waktu rata-rata proses pengajuan, jumlah pengumuman yang terbaca.

## 3. Pengguna dan peran

**Pengguna \[Jawaban\]:** warga, pengurus RT dan bendahara, DKM, Karang Taruna.

| Peran | Cakupan |
| --- | --- |
| Superadmin (pengurus IT) | Kelola wilayah, pengurus, konfigurasi, backup |
| Admin RT/RW | Data warga, pengumuman, pengajuan, persetujuan akun dan CCTV |
| Bendahara | Catat dan koreksi transaksi kas |
| Pengurus DKM | Jadwal, petugas, inventaris, donasi, kas DKM |
| Pengurus Karang Taruna | Acara, relawan, inventaris, anggaran, kas KT |
| Panitia pemilihan | Kelola calon, verifikasi pemilih, hasil |
| Warga | Lihat info, ajukan layanan, bayar iuran, lihat CCTV (dengan persetujuan), memilih |

Aturan tata kelola **\[Jawaban\]**: log aktivitas admin dan riwayat persetujuan, akun pengurus berakhir otomatis sesuai masa jabatan, dan alur serah terima akun, data, dan kas saat pengurus berganti.

## 4. Cakupan

**Multi-wilayah \[Jawaban\]:** satu platform melayani banyak RT/RW. Data tiap wilayah terpisah (tenant), pengurus hanya melihat wilayahnya.

**Informasi + pengajuan + transaksi/pembayaran \[Jawaban\].**

**Pembagian rilis \[Usulan\]**, berdasarkan prioritas masalah yang kamu pilih:

| Rilis 1 (MVP) | Rilis 2 |
| --- | --- |
| Akun, verifikasi, data warga | Pemilihan Ketua RT daring |
| Pengumuman dan notifikasi | Modul DKM lengkap (jadwal petugas, inventaris) |
| Pengajuan (surat, keluhan, izin, fasilitas) | Modul Karang Taruna lengkap |
| Kas dan iuran + pembayaran | Kalender dan pemesanan fasilitas lanjutan |
| CCTV (siaran langsung) | Laporan lanjutan, rekonsiliasi serah terima |
| Panel admin dan log aktivitas |  |

Alasan menunda pemilihan: risiko hukum dan sengketa paling tinggi, dan butuh aturan warga yang disepakati dulu.

## 5. Akun, data warga, dan privasi

**Masuk/verifikasi \[Jawaban\]:** undangan pengurus, daftar mandiri lalu disetujui pengurus, OTP nomor HP (WhatsApp/SMS), dan email.

**Data warga:** nama, alamat, nomor rumah, telepon, dan **NIK serta nomor KK \[Jawaban\]**. Data tambahan (anggota keluarga, status domisili, tanggal lahir, pekerjaan) **\[Usulan\]** diambil seperlunya saja.

**Pembaruan data \[Jawaban\]:** warga boleh mengubah data dasar (HP, pekerjaan); perubahan lain lewat pengurus.

**Akses data lengkap termasuk NIK/KK \[Jawaban\]:** pengurus RT, plus pengurus DKM/Karang Taruna hanya untuk bagiannya.

**Persetujuan (consent) \[Jawaban\]:** wajib bagi warga baru dan pemilih sebelum data disimpan/dipakai.

**Retensi \[Jawaban\]:** data warga yang pindah disimpan 3-5 tahun.

**Ekspor \[Jawaban\]:** CSV dan PDF.

**Persyaratan keamanan \[Usulan\]:**

- NIK dan No. KK dienkripsi di basis data, ditampilkan tersamar (mis. 3174\*\*\*\*0001) kecuali bagi yang berhak.
- Setiap akses ke NIK/KK dicatat di log.
- Ikuti prinsip UU Pelindungan Data Pribadi (UU 27/2022): tujuan jelas, minimisasi data, hak akses dan koreksi bagi warga.
- Dokumen kebijakan privasi dalam bahasa sederhana.

## 6. Pengumuman dan layanan

**Pengumuman:** kategori (ronda, kerja bakti, iuran, darurat, kegiatan), lampiran foto/PDF, arsip. Pengumuman umum, agenda, dan ringkasan kas bisa dibaca publik tanpa login **\[Jawaban\]**.

**Notifikasi \[Jawaban\]:** kombinasi website, email, dan WhatsApp. **\[Usulan\]** pengumuman darurat memakai semua kanal sekaligus; kategori biasa mengikuti preferensi warga.

**Pengajuan \[Jawaban\]:** surat pengantar, keluhan/laporan, izin kegiatan, peminjaman fasilitas. **\[Usulan\]** setiap pengajuan punya status (diajukan, diproses, disetujui/ditolak, selesai), komentar pengurus, dan penerima sesuai jenis; surat pengantar bisa diunduh sebagai PDF.

**Lainnya \[Usulan\]:** direktori kontak penting dan nomor darurat, kalender kegiatan, pemesanan fasilitas (rilis 2).

## 7. CCTV

**Ruang lingkup \[Jawaban\]:** website hanya menampilkan siaran langsung dari sistem yang sudah ada. Tidak mengatur kamera, tidak menyediakan rekaman.

**Akses \[Jawaban\]:** warga terverifikasi **mengajukan akses**, lalu **pengurus menyetujui** sebelum bisa menonton. *(Interpretasi dari dua jawabanmu; mohon konfirmasi.)*

**Persyaratan \[Usulan\]:**

- Pencatatan akses (siapa, kamera mana, kapan) dan opsi pencabutan akses.
- Indikator status kamera (online/offline) dan notifikasi ke pengurus bila mati.
- Streaming lewat gateway aman (tanpa membuka IP/port kamera ke publik), format HLS/WebRTC.
- Pembatasan per wilayah/blok bila diperlukan.
- Permintaan rekaman ditangani di luar website (lewat penanggung jawab CCTV).
- Persetujuan warga atas kebijakan CCTV dan penetapan area terlarang (privasi) sebelum diaktifkan.

**Belum diketahui:** jumlah kamera, merek/API, pemilik dan penanggung jawab, retensi rekaman. Ini menentukan kelayakan teknis (Bagian 13).

## 8. Kas, iuran, dan bantuan

**Pencatatan \[Jawaban\]:** pemasukan berupa iuran bulanan, donasi/bantuan, hasil kegiatan, dan sumber lain. Kas **terpisah** untuk RT, DKM, dan Karang Taruna.

**Persetujuan \[Jawaban\]:** pengeluaran di atas nominal tertentu butuh persetujuan 2 pengurus (nominal belum ditentukan).

**Transparansi warga \[Jawaban\]:** ringkasan saldo dan daftar transaksi. Bukti transaksi tidak ditampilkan ke warga.

**Donasi \[Jawaban\]:** pemberi boleh memilih anonim.

**Pembayaran \[Jawaban\]:** payment gateway (QRIS/e-wallet/VA).

**Persyaratan \[Usulan\]:**

- Pengeluaran wajib kategori dan unggah bukti (bukti hanya dilihat pengurus).
- Transaksi yang sudah disetujui tidak dihapus, hanya dikoreksi lewat transaksi pembalik, tercatat di log.
- Iuran: tagihan bulanan otomatis, pengingat, rekonsiliasi pembayaran otomatis dari gateway.
- Laporan periode (bulanan/tahunan) dalam PDF dan CSV.
- Rekonsiliasi saldo awal-akhir dan berita acara saat pergantian bendahara (rilis 2).
- Biaya gateway dan kebijakan pengelolaan dana perlu dijelaskan (siapa yang memegang rekening).

## 9. DKM dan Karang Taruna (rilis 2, kecuali kas)

**DKM \[Jawaban\]:** jadwal salat dan kajian, jadwal imam/khatib/petugas, donasi, inventaris, kas sendiri.

**Karang Taruna \[Jawaban\]:** acara dengan pendaftaran dan kuota, relawan, inventaris, anggaran kegiatan, kas sendiri.

**\[Usulan\]** halaman struktur organisasi per entitas (nama, jabatan, foto; kontak hanya bila pengurus setuju), arsip kepengurusan sebelumnya.

## 10. Pemilihan Ketua RT (rilis 2)

**Keputusan \[Jawaban\]:**

- Daring penuh di website.
- Pemilih: semua warga dewasa.
- Verifikasi pemilih oleh panitia, satu akun satu suara.
- Hasil tampil setelah pemilihan ditutup dan diverifikasi panitia.
- Fitur: profil calon + visi-misi, sesi tanya jawab.
- Consent wajib bagi pemilih.

**Persyaratan \[Usulan\]:**

- Suara rahasia: pilihan tidak terhubung ke identitas pemilih, hanya status "sudah memilih" yang tercatat.
- Jejak audit pengelolaan pemilihan (tanpa membuka pilihan), dan prosedur sengketa.
- Panitia terpisah dari calon (cegah konflik kepentingan), kalender tahapan (pendaftaran, kampanye, pemungutan, penutupan).
- Aturan pemilihan daring disepakati warga dan dikonfirmasi ke kelurahan sebelum dipakai.
- Hasil resmi tetap dibukukan lewat berita acara.

## 11. UI/UX

**Platform \[Jawaban\]:** mobile-first. Bahasa Indonesia + Inggris.

**Nuansa \[Jawaban\]:** resmi, hangat, sederhana, dan modern sekaligus. **\[Usulan\]** arahan: struktur dan tipografi rapi (kesan resmi dan modern), bahasa dan warna ramah (hangat), minim ornamen (sederhana).

**Pengguna campuran gaptek dan terbiasa \[Jawaban\]:** **\[Usulan\]** tombol besar, alur pendek, satu tugas per layar, bahasa sehari-hari.

**Aksesibilitas \[Jawaban\]:** teks besar dan kontras tinggi, dukungan pembaca layar. **\[Usulan\]** target WCAG 2.2 AA, pengaturan ukuran teks.

**"Anti AI slop" \[Jawaban: semuanya\]:** tampilan tidak generik/bertemplat, bahasa lokal dan natural, tanpa ornamen berlebihan. **\[Usulan\]** tanpa ilustrasi stok generik, tanpa gradien dan ikon dekoratif, copywriting ditulis bersama pengurus setempat, identitas visual dari wilayah pilot.

**Navigasi \[Jawaban: rekomendasikan\]:** **\[Usulan\]** navigasi utama berbasis tugas (Info, Layanan, Keuangan, Keamanan), dengan filter organisasi (RT/DKM/Karang Taruna) di dalamnya. Beranda menampilkan pengumuman terbaru, tombol layanan cepat, dan ringkasan kas.

**Pengurus:** panel admin yang nyaman di komputer **\[Usulan\]**; warga di ponsel.

## 12. Teknis dan operasional

- **Pengelola \[Jawaban\]:** satu admin/pengurus IT.
- **Anggaran \[Jawaban\]:** tingkat menengah, ratusan ribu rupiah per bulan.
- **Integrasi \[Jawaban\]:** WhatsApp, email, Google Maps, payment gateway.
- **Migrasi \[Jawaban\]:** data warga dan pembukuan dari Excel/WhatsApp perlu diimpor. **\[Usulan\]** template impor CSV, validasi dan pratinjau sebelum simpan.
- **Backup \[Jawaban\]:** otomatis mingguan. **\[Usulan\]** tambahkan backup harian untuk data kas dan uji pemulihan berkala, mengingat sistem menyimpan keuangan.
- **Peluncuran \[Jawaban\]:** waktu fleksibel. **\[Usulan\]** mulai dari satu wilayah pilot.
- **Pengujian \[Jawaban\]:** panitia khusus + perwakilan warga. **\[Usulan\]** sertakan warga yang kurang terbiasa teknologi.
- **Multi-tenant \[Usulan\]:** isolasi data per wilayah di level basis data dan hak akses.

## 13. Pertanyaan terbuka

1. Nama RT, RW, kelurahan, kecamatan, kota wilayah pilot.
2. Indikator keberhasilan 3-6 bulan.
3. CCTV: jumlah kamera, merek/sistem/API, pemilik dan penanggung jawab, lama dan lokasi rekaman, area yang tidak boleh direkam.
4. Konfirmasi alur akses CCTV (ajukan lalu disetujui pengurus).
5. Nominal pemicu persetujuan 2 pengurus.
6. Warna, logo, identitas wilayah; contoh website yang disukai/tidak disukai.
7. Domain dan hosting yang sudah ada.
8. Penyetuju desain dan pemberi masukan.
9. Pertanyaan detail yang belum dibahas: kriteria penerima bantuan, struktur dan masa jabatan pengurus yang ditampilkan, siapa menerima tiap jenis pengajuan, penanganan permintaan koreksi data.

## 14. Risiko utama

| Risiko | Mitigasi |
| --- | --- |
| Kebocoran NIK/KK | Enkripsi, akses terbatas, log, minimisasi |
| Penyalahgunaan CCTV | Persetujuan, log akses, kebijakan tertulis, area terlarang |
| Sengketa pemilihan daring | Aturan disepakati, verifikasi panitia, audit, berita acara |
| Dana tidak transparan/salah catat | Dua persetujuan, log, transaksi tak bisa dihapus |
| Adopsi rendah (warga gaptek) | Pendaftaran lewat undangan pengurus, UI sederhana, WhatsApp sebagai pintu masuk |
| Beban satu admin | Dokumentasi, otomatisasi backup, peran pengurus yang jelas |