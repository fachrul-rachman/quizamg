# Panduan Manual Testing (Bahasa Non-Teknis)

Dokumen ini untuk kamu yang melakukan pengecekan sebagai **user yang pakai aplikasi**, bukan sebagai developer.

Tujuan utamanya:
- Memastikan halaman-halaman penting bisa dibuka tanpa error.
- Memastikan alur “Admin menyiapkan quiz” sampai “Peserta mengerjakan quiz” berjalan lancar.
- Memastikan daftar link dan statusnya tampil benar (khususnya halaman `/admin/links`).

---

## 0) Sebelum mulai (persiapan)

Kamu butuh:
- **Akun Admin** (untuk masuk ke halaman admin).
- (Opsional) **Akun Super Admin** (untuk cek menu “Admin Users”).
- Akses ke aplikasi (contoh: `https://domain-kamu.com` atau environment test).

Berhasil jika:
- Kamu bisa login dan melihat dashboard admin tanpa pesan error.

Catatan:
- Kalau ada halaman yang blank/putih atau muncul tulisan error teknis, itu **langsung dianggap gagal** dan dicatat.

---

## 1) Cek Login Admin

Yang dites:
- Halaman login admin dan proses login.

Langkah:
1. Buka `/admin/login`.
2. Pastikan halaman login muncul rapi (ada judul “Login Admin” atau sejenis).
3. Login pakai akun admin yang valid.

Berhasil jika:
- Login sukses dan kamu diarahkan ke halaman admin (dashboard).
- Tidak ada pesan error/halaman blank.

Yang perlu kamu catat jika gagal:
- Pesan yang muncul (kalau ada).
- Screenshot.

---

## 2) Cek Dashboard Admin

Yang dites:
- Dashboard bisa dibuka dan konten utamanya tampil.

Langkah:
1. Setelah login, buka `/admin/dashboard`.
2. Scroll halaman pelan-pelan dari atas sampai bawah.

Berhasil jika:
- Halaman terbuka normal, tidak ada error.
- Kalau ada tabel/angka ringkasan, tampil wajar (boleh kosong kalau belum ada data).

---

## 3) Cek Kategori Quiz (Admin)

Yang dites:
- Menambah dan menghapus kategori.

Langkah:
1. Buka `/admin/quiz-categories`.
2. Tambah kategori baru, contoh: `HRD`.
3. Pastikan kategori muncul di daftar.
4. Hapus kategori yang baru dibuat.

Berhasil jika:
- Setelah tambah, muncul notifikasi sukses dan kategori terlihat di list.
- Setelah hapus, kategori hilang dari list.

Catatan penting:
- Kalau aplikasi menolak penghapusan karena kategori sedang dipakai, itu normal **asal pesannya jelas**.

---

## 4) Buat Quiz Baru (Admin)

Yang dites:
- Admin bisa membuat quiz dasar.

Langkah:
1. Buka `/admin/quizzes/create`.
2. Isi minimal:
   - Nama quiz (contoh: `Quiz Test`)
   - Durasi (contoh: `5` menit untuk uji cepat)
   - Aktifkan quiz (kalau ada toggle aktif)
3. Simpan.

Berhasil jika:
- Quiz tersimpan dan bisa dibuka kembali dari daftar quiz.
- Tidak ada error saat menyimpan.

Yang perlu kamu cek:
- Apakah judul quiz tampil sesuai input.

---

## 5) Tambah Soal (2 tipe) di Quiz

Yang dites:
- Soal pilihan ganda (multiple choice).
- Soal jawaban singkat (short answer).

Langkah (pilihan ganda):
1. Di quiz yang kamu buat, tambahkan 1 soal pilihan ganda.
2. Isi pertanyaan (contoh: “2 + 2 = ?”).
3. Buat minimal 2 opsi (contoh: `3` dan `4`).
4. Tandai jawaban yang benar (`4`).
5. Simpan.

Berhasil jika:
- Soal dan opsi tersimpan.
- Saat kamu buka lagi, datanya masih ada.

Langkah (jawaban singkat):
1. Tambahkan 1 soal jawaban singkat.
2. Isi pertanyaan (contoh: “Sebutkan warna langit.”).
3. Masukkan jawaban yang dianggap benar (contoh: `biru`).
4. Simpan.

Berhasil jika:
- Soal tersimpan dan terlihat di quiz.

---

## 6) Cek Mode “Jawaban Instan” (Instant Feedback)

Yang dites:
- Saat mode ini aktif, setelah peserta klik “Jawab”, jawaban terkunci dan langsung lanjut soal berikutnya.

Langkah:
1. Di pengaturan quiz, aktifkan mode “Jawaban Instan”.
2. Pastikan quiz punya minimal 2 soal.

Berhasil jika:
- Nanti saat peserta mengerjakan:
  - Setelah klik tombol “Jawab”, pilihannya tidak bisa diubah lagi (terkunci).
  - Ada penanda yang jelas mana jawaban benar / jawaban yang dipilih (kalau memang fitur itu ditampilkan).
  - Peserta otomatis lanjut ke soal berikutnya (dengan jeda singkat).

---

## 7) Generate Link Quiz (Admin)

Yang dites:
- Link quiz bisa dibuat dan bisa dibuka.

Langkah:
1. Buka `/admin/generate-link`.
2. Pilih quiz yang kamu buat.
3. Buat link (kalau ada pilihan tipe link, tes minimal satu).
4. Copy link yang dihasilkan.

Berhasil jika:
- Link berhasil dibuat dan muncul di daftar.
- Link bisa dibuka di tab/incognito.

---

## 8) Daftar Link (Admin) — Fokus Halaman `/admin/links`

Yang dites:
- Halaman daftar link terbuka normal.
- Status link tampil dengan label & tampilan yang sesuai.

Langkah:
1. Buka `/admin/links`.
2. Pastikan halaman terbuka tanpa error.
3. Cari link yang barusan kamu buat.
4. Perhatikan bagian status (contoh: “Belum Dibuka”, “Sudah Dibuka”, “Sedang Dikerjakan”, “Selesai”, “Kedaluwarsa”).

Berhasil jika:
- Tidak muncul tulisan error seperti “Undefined variable …”.
- Status tampil jelas dan konsisten.
- Kalau ada tombol/aksi (misal buka detail), berfungsi.

---

## 9) Detail Link (Admin)

Yang dites:
- Halaman detail link terbuka, dan informasi dasarnya terlihat.

Langkah:
1. Dari `/admin/links`, klik salah satu link untuk masuk ke detail.
2. Pastikan informasi link tampil (quiz apa, token/link, status).

Berhasil jika:
- Halaman detail tidak error.
- Informasi yang tampil masuk akal.

---

## 10) Peserta Memulai Quiz

Yang dites:
- Halaman awal peserta, isi nama, mulai mengerjakan.

Langkah:
1. Buka link peserta (hasil generate) di browser incognito.
2. Isi:
   - Nama peserta (contoh: `Budi`)
   - Melamar untuk/jabatan (contoh: `HRD`)
3. Mulai quiz.

Berhasil jika:
- Kamu masuk ke halaman pengerjaan.
- Ada info “Nama quiz”, nama peserta, jabatan.
- Timer/durasi terlihat (kalau memang ditampilkan).

---

## 11) Peserta Menjawab Soal (Pilihan Ganda)

Yang dites:
- Memilih opsi dan menyimpan jawaban.

Langkah:
1. Di soal pilihan ganda, pilih salah satu opsi.
2. Klik tombol “Jawab”.

Berhasil jika:
- Jawaban tersimpan (biasanya terlihat dari progress “Terjawab: x/y” naik).
- Aplikasi lanjut ke soal berikutnya (atau tetap di soal, tergantung mode).

Hal yang perlu kamu cari:
- Tidak ada error.
- Tidak ada tombol yang “nggak respon”.

---

## 12) Peserta Menjawab Soal (Jawaban Singkat)

Yang dites:
- Isi jawaban singkat dan simpan.

Langkah:
1. Ketik jawaban di kolom yang tersedia.
2. Klik “Jawab”.

Berhasil jika:
- Jawaban tersimpan dan progress bertambah.

---

## 13) Timer Habis (Skenario)

Yang dites:
- Saat waktu habis, quiz selesai otomatis (tidak membuat aplikasi error).

Cara uji yang mudah:
- Buat quiz durasi sangat pendek (contoh 1 menit), lalu tunggu sampai habis.

Berhasil jika:
- Peserta diarahkan ke halaman “selesai”/“waktu habis” (atau status final sejenis).
- Link tidak bisa dipakai lagi untuk mengerjakan.

---

## 14) Hasil Quiz (Admin)

Yang dites:
- Admin bisa melihat daftar hasil dan detail hasil.

Langkah:
1. Buka `/admin/results`.
2. Cari hasil untuk link/quiz yang tadi dikerjakan.
3. Buka detail hasil.

Berhasil jika:
- Daftar hasil tampil.
- Detail hasil menampilkan jawaban peserta.
- Tidak ada error saat membuka halaman.

---

## 15) File PDF Hasil (Jika ada tombol/fitur PDF)

Yang dites:
- Download/lihat PDF hasil.

Langkah:
1. Di detail hasil, cari tombol “PDF” atau “Download”.
2. Klik tombolnya.

Berhasil jika:
- PDF terbuka/terunduh.
- Isi PDF terbaca (tidak rusak).

---

## 16) Pengecekan “Halaman Tidak Valid” (Skenario)

Yang dites:
- Link quiz yang salah/diubah harus menampilkan pesan yang jelas.

Langkah:
1. Ambil link peserta, lalu ubah sedikit tokennya (misal 1 huruf).
2. Buka link itu.

Berhasil jika:
- Muncul pesan “Link tidak valid” atau pesan yang jelas untuk user.
- Tidak muncul error teknis.

---

## Format Catatan Bug (kalau ketemu masalah)

Tulis seperti ini:
- Halaman yang dibuka: (contoh: `/admin/links`)
- Langkah yang kamu lakukan: (ringkas 1–3 langkah)
- Yang kamu harapkan: (contoh: “list link tampil normal”)
- Yang terjadi: (contoh: “muncul tulisan error …”)
- Screenshot: (lampirkan)

