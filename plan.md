# Plan Pengerjaan (Iterasi per 1x Run)

Aturan update:
- File ini di-update setelah 1 iterasi selesai (centang item yang selesai, tambah catatan bila ada blocker).
- 1 iterasi = paket fitur yang bisa dikerjakan end-to-end sampai bisa diuji manual.

---

## Iterasi 1 — Fondasi Proyek & Struktur Admin
- Status: DONE (2026-04-17)
- Setup Laravel + Livewire + Blade + Tailwind (atau styling sederhana konsisten)
- Struktur route: `/admin/...` dan `/quiz/{token}`
- Layout Admin: sidebar + topbar + area konten
- Menu sidebar sesuai urutan + visibilitas role (Admin tanpa `Admin Users`)
- Halaman Login Admin (email+password, validasi, pesan akun nonaktif)

## Iterasi 2 — Auth & Authorization Admin
- Status: DONE (2026-04-17)
- Login/session admin + middleware auth admin
- Middleware role khusus super admin untuk route `Admin Users`
- Proteksi backend (bukan hanya hide menu)
- Aturan: user soft-deleted / nonaktif tidak bisa login

## Iterasi 3 — Admin Dashboard
- Status: DONE (2026-04-17)
- 4 kartu statistik (opsional 3 kartu bila `Total Admin User` disembunyikan untuk Admin)
- Tabel `Hasil Terbaru` + aksi `Detail`
- Empty state: `Belum ada hasil quiz.`

## Iterasi 4 — CRUD Quiz + Soal (1 Pengerjaan)
- Status: DONE (2026-04-17)
- Quiz List: search nama, filter aktif/nonaktif, tabel sesuai spesifikasi, aksi Detail/Edit/Hapus (soft delete)
- Quiz Create/Edit: field quiz + builder Soal dalam halaman yang sama
- Input soal:
  - `multiple_choice`: isi opsi + pilih tepat 1 jawaban benar
  - `short_answer`: isi jawaban benar pakai pemisah `|`
- Grade bukan input admin (system)
- Skor & grade berdasarkan persentase benar dari total soal
- Rule: quiz nonaktif tidak bisa dipakai generate link baru / start attempt baru

## Iterasi 5 — CRUD Soal (Multiple Choice & Short Answer)
- Soal List per quiz + filter ringan bila ada di desain + empty state
- Soal Create/Edit:
  - `multiple_choice`: minimal 2 opsi valid, tepat 1 opsi benar, shuffle opsi mengikuti quiz setting
  - `short_answer`: input key jawaban (normalisasi + unique per question)
- Upload gambar:
  - 1 gambar utama per soal (`question_image_path`)
  - gambar opsi untuk multiple choice (`option_image_path`)
- Validasi kombinasi konten opsi (text/image) sesuai aturan data

## Iterasi 6 — Import Soal via `.xlsx`
- Status: DONE (2026-04-17)
- UI Import Soal di halaman Tambah/Edit Quiz
- Parser `.xlsx` dengan template kolom tetap
- Import hanya menambah soal baru (tanpa edit/hapus soal lama)
- Validasi baris ketat: baris invalid ditolak dengan error jelas (tidak masuk diam-diam)
- Tidak memproses gambar dari Excel

## Iterasi 7 — Generate Link Quiz
- Status: DONE (2026-04-17)
- Halaman Generate Link: pilih quiz aktif + input jumlah link
- Generate token random unik (`quiz_links.token`)
- Link List + status badge + Link Detail
- Status flow link: `unused -> opened -> in_progress -> (submitted|expired)`

## Iterasi 8 — Halaman Peserta: Start (Identitas) + Mulai Test
- Status: DONE (2026-04-17)
- Route peserta `/quiz/{token}`:
  - token tidak valid -> `Link Quiz Tidak Valid`
  - token final submitted -> `Quiz ini sudah selesai dikerjakan.`
  - token final expired -> `Waktu pengerjaan quiz ini sudah habis.`
  - quiz nonaktif / data rusak -> `Quiz tidak tersedia.`
- Form identitas: nama + melamar untuk (wajib)
- Tombol `Mulai Test`:
  - timer dimulai saat klik
  - link tidak hangus hanya karena isi nama/buka halaman
- Resume attempt yang sama jika belum submit dan waktu belum habis

## Iterasi 9 — Halaman Peserta: Pengerjaan Quiz + Simpan Jawaban
- Status: DONE (2026-04-17)
- Render soal:
- Tampilan soal 1-per-1 (Back/Forward)
- Pengacakan soal/opsi deterministik saat awal attempt (tidak berubah saat Back/Forward)
- multiple choice: tampil opsi (pilih 1)
- short answer: input teks
- Simpan jawaban per soal (idempotent, update jawaban dalam attempt yang sama)
- Shuffle soal & opsi mengikuti setting quiz
- Enforcement timer berbasis server (deadline server, bukan hanya UI)

## Iterasi 10 — Submit Manual + Autosubmit
- Status: DONE (2026-04-17)
- Submit manual dengan modal konfirmasi
- Autosubmit saat timer habis
- Finalisasi dalam transaction:
  - update `quiz_attempts.status` + `submitted_at`
  - update `quiz_links.status` + `submitted_at` + `expired_at`
  - buat `quiz_results`
- Aturan: jawaban kosong dianggap salah; hitung `unanswered_answers`
- Halaman selesai: `Jawaban Berhasil Dikirim` + pesan terima kasih (tanpa score/grade)

## Iterasi 11 — Scoring + Grade Lookup
- Status: DONE (2026-04-17)
- Hitung benar/salah per soal (bobot sama)
- Skor persen disimpan (`score_percentage`)
- Grade letter + label dari sistem:
  - A (Sangat Baik): 90.00–100.00
  - B (Baik): 80.00–89.99
  - C (Cukup): 70.00–79.99
  - D (Kurang): 60.00–69.99
  - E (Sangat Kurang): 0.00–59.99
- Pastikan 1 attempt hanya punya 1 result (unique constraint logis)

## Iterasi 12 — PDF Result Generation
- Status: DONE (2026-04-17)
- Generate PDF hasil lengkap sesuai kebutuhan hasil (format konsisten)
- Simpan metadata di `result_pdfs` (tanpa fitur regenerate manual)
- Error handling admin: `Upload PDF ke Google Drive gagal.` / `Data gagal disimpan.` sesuai konteks

## Iterasi 13 — Upload Google Drive + Discord Webhook
- Status: DONE (2026-04-18)
- Upload PDF ke Google Drive API (simpan `google_drive_file_id`, `google_drive_url`)
- Kirim Discord webhook (payload sesuai spesifikasi) + simpan `discord_webhook_logs`
- Rule: kegagalan upload/webhook tidak boleh membatalkan result final (no rollback result)

## Iterasi 14 — Admin: Hasil (List + Detail) Read-Only
- Status: DONE (2026-04-18)
- Hasil List: filter/search ringan sesuai desain + badge status + empty state
- Hasil Detail:
  - identitas peserta (nama, melamar untuk)
  - ringkasan score/grade
  - detail jawaban per soal (benar/salah/tidak dijawab)
  - link ke Google Drive file bila ada
- Rule: tidak ada edit hasil, tidak ada resend webhook, tidak ada regenerate PDF

## Iterasi 15 — Admin Users (Super Admin Only)
- Status: DONE (2026-04-18)
- Admin User List/Create/Edit (role enum + is_active)
- Validasi: email unique, role hanya `super_admin`/`admin`
- Proteksi route khusus super admin

## Iterasi 16 — Hardening & QA
- Status: DONE (2026-04-18)
- Hardening auth: user nonaktif / soft-deleted ditolak login
- Hardening super admin: tidak bisa hapus akun sendiri, nonaktifkan akun sendiri, atau menurunkan role sendiri
- Hardening peserta: start/save identitas hanya boleh dari status link yang valid
- QA otomatis: suite test dirapikan ke flow aplikasi dan memakai RefreshDatabase
- Catatan: verifikasi manual integrasi eksternal tetap bergantung pada kredensial env aktif
- Konsistensi status flow (no illegal jump)
- Proteksi akses peserta (tidak bisa enumerate token lain)
- Sanitasi nama file upload + path storage aman
- Indeks & constraint sesuai `data.md`
- Uji manual end-to-end: create quiz -> import/soal -> generate link -> attempt -> submit/autosubmit -> result -> PDF -> Drive -> webhook -> admin lihat hasil
