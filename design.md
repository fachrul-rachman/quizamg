# Design Specification

## Tujuan Dokumen
Dokumen ini mendefinisikan tampilan, struktur halaman, alur interaksi, dan elemen UI aplikasi quiz internal perusahaan berbasis Laravel + Livewire.

Dokumen ini wajib dijadikan acuan tunggal untuk:
- struktur halaman admin
- struktur halaman peserta
- komponen yang muncul
- urutan elemen
- isi tabel
- isi form
- behavior tampilan
- pesan status utama

Dokumen ini tidak membahas detail database atau logic backend secara mendalam. Fokus dokumen ini adalah tampilan dan interaksi UI/UX yang harus dibuat secara konsisten.

Jangan menambahkan halaman, widget, menu, modal, tombol, atau alur visual di luar yang tertulis pada dokumen ini kecuali dibutuhkan untuk kebutuhan teknis yang sangat kecil dan tidak mengubah scope produk.

---

# 1. Prinsip Umum Tampilan

## 1.1 Karakter UI
Tampilan harus:
- bersih
- sederhana
- fokus ke fungsi
- mudah dipakai admin internal perusahaan
- mudah dipakai peserta anonim
- tidak memakai gaya marketing website
- tidak memakai landing page publik

## 1.2 Framework Tampilan
Gunakan:
- Blade
- Livewire
- Tailwind CSS atau styling sederhana yang konsisten dengan Laravel stack

Jangan membuat SPA terpisah.
Jangan membuat frontend React/Vue terpisah.

## 1.3 Struktur Besar Aplikasi
Aplikasi memiliki 2 area utama:
1. **Admin Panel**
2. **Halaman Peserta**

---

# 2. Admin Panel

## 2.1 Struktur Umum Admin Panel
Setelah login, admin masuk ke layout admin yang terdiri dari:
- topbar/header
- sidebar kiri
- area konten utama

## 2.2 Sidebar Admin
Sidebar wajib menampilkan menu berikut, dalam urutan ini:
1. Dashboard
2. Quiz
3. Generate Link
4. Hasil
5. Admin Users
6. Logout

### Aturan visibilitas menu
- **Super Admin** melihat semua menu
- **Admin** tidak melihat menu `Admin Users`

### Catatan
Nama menu harus jelas dan literal.
Jangan ganti dengan istilah lain seperti:
- Tests
- Exam Manager
- Invitations
- Reports
- Team Members

Gunakan nama menu yang mudah dipahami admin internal.

---

## 2.3 Topbar Admin
Topbar minimal berisi:
- judul halaman aktif
- nama user login
- role user login
- tombol logout atau dropdown user

Jangan tambahkan notifikasi kompleks, chat, atau widget lain.

---

# 3. Halaman Login Admin

## 3.1 Tujuan
Halaman untuk login Super Admin dan Admin.

## 3.2 Isi Halaman
Tampilkan:
- logo atau nama aplikasi
- judul halaman: `Login Admin`
- field email
- field password
- tombol login

## 3.3 Validasi UI
- email wajib
- password wajib
- tampilkan error validation di bawah field
- tampilkan pesan login gagal jika kredensial salah
- tampilkan pesan jika akun nonaktif

## 3.4 Elemen yang Tidak Boleh Ada
Jangan tampilkan:
- registrasi
- forgot password
- social login
- remember me
- login peserta

---

# 4. Dashboard Admin

## 4.1 Tujuan
Halaman ringkasan sederhana setelah login.

## 4.2 Kartu Ringkasan
Tampilkan 4 kartu statistik:
1. Total Quiz
2. Total Link Generated
3. Total Hasil Masuk
4. Total Admin User

### Aturan visibilitas
- untuk Admin biasa, kartu `Total Admin User` boleh tetap tampil sebagai informasi, tetapi admin tidak memiliki akses ke manajemen user
- atau kartu itu boleh disembunyikan untuk Admin jika implementasi ingin lebih ketat
- jika disembunyikan, jumlah kartu menjadi 3

## 4.3 Tabel Ringkas
Di bawah kartu statistik, tampilkan tabel `Hasil Terbaru` dengan kolom:
- Nama Test
- Nama Peserta
- KTP
- Score
- Grade
- Status
- Waktu Selesai
- Aksi

Aksi hanya:
- `Detail`

## 4.4 Empty State
Jika belum ada hasil:
- tampilkan pesan: `Belum ada hasil quiz.`

---

# 5. Halaman Quiz - List

## 5.1 Tujuan
Menampilkan daftar quiz yang sudah dibuat.

## 5.2 Header Halaman
Tampilkan:
- judul: `Quiz`
- tombol `Tambah Quiz`

## 5.3 Filter Ringan
Tampilkan di area atas tabel:
- search berdasarkan nama quiz
- filter status aktif / nonaktif

Jangan tambah filter kompleks lain.

## 5.4 Tabel List Quiz
Kolom tabel:
- Nama Quiz
- Durasi
- Jumlah Soal
- Shuffle Soal
- Shuffle Opsi
- Status
- Dibuat Oleh
- Aksi

### Isi kolom
- `Durasi` ditampilkan dalam menit
- `Jumlah Soal` menampilkan total soal aktif
- `Shuffle Soal` menampilkan Ya / Tidak
- `Shuffle Opsi` menampilkan Ya / Tidak
- `Status` menampilkan Aktif / Nonaktif

### Tombol Aksi
Aksi per row:
- `Detail`
- `Edit`
- `Hapus`

Jangan tambahkan duplicate, publish, archive, preview publik, atau export.

## 5.5 Konfirmasi Hapus
Saat klik hapus:
- tampilkan modal konfirmasi
- pesan harus jelas bahwa quiz akan dihapus dari daftar aktif
- jangan gunakan wording ambigu

---

# 6. Halaman Quiz - Create

## 6.1 Tujuan
Membuat quiz baru.

## 6.2 Field Form
Field wajib:
- Nama Quiz
- Deskripsi
- Durasi (menit)
- Shuffle Soal
- Shuffle Opsi
- Status Aktif

## 6.3 Grade Rules
Di bawah field utama, tampilkan section `Aturan Grade`.

Tampilkan 5 row tetap:
1. A
2. B
3. C
4. D
5. E

Setiap row memiliki field:
- Grade
- Label
- Min Score
- Max Score

### Aturan UI
- urutan A sampai E tetap
- grade letter tidak diubah oleh user
- user hanya mengisi label, min, max bila diizinkan
- jika sistem memakai default saat create, tampilkan default value sejak awal

## 6.4 Tombol
- `Simpan`
- `Batal`

## 6.5 Validasi Visual
Tampilkan error di bawah field yang bermasalah.

---

# 7. Halaman Quiz - Edit

## 7.1 Tujuan
Mengubah data quiz.

## 7.2 Isi Halaman
Isi halaman sama dengan form create:
- Nama Quiz
- Deskripsi
- Durasi
- Shuffle Soal
- Shuffle Opsi
- Status Aktif
- Aturan Grade

## 7.3 Tombol
- `Update`
- `Batal`

## 7.4 Aturan UI
Perubahan pada quiz tidak boleh mengubah tampilan menjadi wizard.
Tetap gunakan satu form edit yang jelas.

---

# 8. Halaman Quiz - Detail

## 8.1 Tujuan
Menjadi pusat pengelolaan soal untuk 1 quiz.

## 8.2 Header Halaman
Tampilkan:
- Nama Quiz
- Deskripsi singkat
- Durasi
- Status
- tombol `Edit Quiz`
- tombol `Tambah Soal`
- tombol `Import Soal (.xlsx)`

## 8.3 Ringkasan Quiz
Tampilkan ringkasan kecil:
- Nama Quiz
- Durasi
- Shuffle Soal
- Shuffle Opsi
- Status
- Total Soal

## 8.4 Tabel Soal
Kolom:
- No Urut
- Tipe Soal
- Soal
- Gambar Soal
- Status
- Aksi

### Isi kolom
- `Tipe Soal`: Multiple Choice / Short Answer
- `Soal`: tampilkan potongan teks soal
- `Gambar Soal`: tampilkan badge `Ada` atau `Tidak Ada`
- `Status`: Aktif / Nonaktif

### Tombol Aksi
- `Detail`
- `Edit`
- `Hapus`

## 8.5 Empty State
Jika quiz belum punya soal:
- tampilkan pesan: `Belum ada soal pada quiz ini.`

---

# 9. Halaman Soal - Create

## 9.1 Tujuan
Menambah soal baru ke quiz tertentu.

## 9.2 Struktur Form
Form dibagi menjadi 4 section:
1. Informasi Soal
2. Gambar Soal
3. Jawaban
4. Tombol Aksi

---

## 9.3 Section Informasi Soal
Field:
- Tipe Soal
- Nomor Urut
- Teks Soal
- Status Aktif

### Tipe Soal
Pilihan hanya:
- Multiple Choice
- Short Answer

---

## 9.4 Section Gambar Soal
Field:
- Upload Gambar Soal

### Aturan
- opsional
- maksimal 1 gambar
- tampilkan preview jika ada file
- ada tombol hapus gambar sebelum simpan bila memungkinkan

---

## 9.5 Section Jawaban - Multiple Choice
Jika tipe soal = Multiple Choice, tampilkan 5 blok opsi tetap:
- Opsi A
- Opsi B
- Opsi C
- Opsi D
- Opsi E

Setiap blok opsi memiliki:
- Label opsi
- Textarea teks opsi
- Upload gambar opsi
- radio button `Jawaban Benar`

### Aturan
- minimal 2 opsi harus terisi
- setiap opsi boleh:
  - teks saja
  - gambar saja
  - teks + gambar
- tidak boleh kosong semua
- hanya 1 opsi benar
- tampilkan preview gambar opsi jika ada

---

## 9.6 Section Jawaban - Short Answer
Jika tipe soal = Short Answer, tampilkan:
- field daftar jawaban benar

### Bentuk input
Gunakan salah satu pendekatan berikut:
- multi input per jawaban
- atau textarea yang diparsing per baris

Tetapi hasil UI harus jelas bahwa 1 soal bisa punya banyak jawaban benar.

### Aturan UI
- minimal 1 jawaban benar
- tampilkan catatan kecil:
  `Pencocokan jawaban menggunakan flexible match sederhana: huruf besar kecil diabaikan dan spasi dirapikan.`

---

## 9.7 Tombol
- `Simpan Soal`
- `Batal`

---

# 10. Halaman Soal - Edit

## 10.1 Tujuan
Mengubah soal yang sudah ada.

## 10.2 Isi Halaman
Struktur sama dengan create:
- Informasi Soal
- Gambar Soal
- Jawaban

## 10.3 Aturan UI
- field diisi data lama
- preview gambar lama wajib terlihat jika ada
- admin bisa mengganti atau menghapus gambar
- untuk multiple choice, opsi lama dan penanda jawaban benar wajib tampil jelas
- untuk short answer, semua jawaban benar lama wajib tampil dan bisa diedit

## 10.4 Tombol
- `Update Soal`
- `Batal`

---

# 11. Halaman Soal - Detail

## 11.1 Tujuan
Melihat soal secara utuh.

## 11.2 Isi Halaman
Tampilkan:
- tipe soal
- nomor urut
- status
- teks soal lengkap
- gambar soal jika ada

### Jika Multiple Choice
Tampilkan daftar opsi A-E:
- label opsi
- teks opsi
- gambar opsi jika ada
- badge `Benar` pada jawaban benar

### Jika Short Answer
Tampilkan daftar accepted answers

## 11.3 Tombol
- `Edit`
- `Kembali`

---

# 12. Modal / Halaman Import Soal

## 12.1 Tujuan
Mengimpor soal baru dari file `.xlsx`.

## 12.2 Bentuk UI
Boleh berupa:
- halaman khusus, atau
- modal besar

Namun harus memuat isi berikut secara lengkap.

## 12.3 Isi UI
Tampilkan:
- nama quiz aktif
- tombol `Download Template`
- input upload file `.xlsx`
- tombol `Validasi File`
- area hasil validasi

## 12.4 Hasil Validasi
Jika file valid, tampilkan:
- jumlah baris valid
- jumlah baris error
- daftar ringkas hasil parsing

Jika ada error, tampilkan tabel error dengan kolom:
- Baris
- Field
- Pesan Error

## 12.5 Tombol Final
Jika validasi sukses:
- `Import Soal`
- `Batal`

## 12.6 Aturan UI
- import hanya append
- UI harus memberi tahu bahwa import tidak menghapus soal lama
- jangan tampilkan opsi replace, merge, overwrite, upsert, atau sync

---

# 13. Halaman Generate Link

## 13.1 Tujuan
Membuat link quiz unik massal.

## 13.2 Form Generate
Field:
- Pilih Quiz
- Jumlah Link

### Aturan
- hanya quiz aktif yang bisa dipilih
- jumlah link harus angka positif

## 13.3 Tombol
- `Generate Link`

## 13.4 Hasil Generate
Setelah berhasil generate, tampilkan tabel hasil dengan kolom:
- No
- Nama Quiz
- Token
- URL Lengkap
- Status
- Aksi

### Aksi
- `Copy`
- checkbox atau selector untuk copy massal

## 13.5 Tombol Tambahan
- `Copy All`

## 13.6 Status Default
Link baru tampil dengan status:
- `unused`

---

# 14. Halaman Daftar Link

## 14.1 Tujuan
Melihat seluruh link yang pernah dibuat.

## 14.2 Filter
Tampilkan filter ringan:
- search token
- filter quiz
- filter status

## 14.3 Tabel
Kolom:
- Nama Quiz
- Token
- Status
- Opened At
- Started At
- Submitted At
- Aksi

### Aksi
- `Copy Link`
- `Detail`

Jangan tambahkan tombol edit token atau reset token.

---

# 15. Halaman Link Detail

## 15.1 Tujuan
Melihat detail 1 link quiz.

## 15.2 Informasi yang Ditampilkan
Tampilkan:
- Nama Quiz
- Token
- URL Lengkap
- Status
- Opened At
- Started At
- Submitted At
- Expired At
- Dibuat Oleh
- Dibuat Pada

## 15.3 Data Attempt
Jika link sudah punya attempt, tampilkan:
- Nama Peserta
- Nomor KTP
- Status Attempt
- Waktu Mulai
- Waktu Submit

## 15.4 Tombol
- `Copy Link`
- `Kembali`

---

# 16. Halaman Hasil - List

## 16.1 Tujuan
Melihat daftar hasil quiz peserta.

## 16.2 Filter
Tampilkan:
- search nama peserta
- search nomor KTP
- filter quiz
- filter status hasil
- filter grade

Jangan tambah filter tanggal kompleks bila belum diperlukan.

## 16.3 Tabel Hasil
Kolom:
- Nama Test
- Nama Peserta
- KTP
- Score
- Grade
- Keterangan
- Status
- Waktu Selesai
- Aksi

### Keterangan kolom
- `Score` ditampilkan dalam persen
- `Grade` menampilkan huruf grade
- `Keterangan` menampilkan label grade
- `Status` menampilkan:
  - Submitted
  - Auto Submitted

### Aksi
- `Detail`

## 16.4 Empty State
Jika belum ada hasil:
- tampilkan pesan: `Belum ada hasil peserta.`

---

# 17. Halaman Hasil - Detail

## 17.1 Tujuan
Melihat detail hasil peserta secara lengkap.

## 17.2 Header
Tampilkan:
- Nama Test
- Nama Peserta
- Nomor KTP
- Status Hasil

## 17.3 Ringkasan Hasil
Tampilkan dalam bentuk kartu atau section ringkas:
- Nama Test
- Nama Peserta
- Nomor KTP
- Waktu Mulai
- Waktu Selesai
- Durasi
- Total Soal
- Benar
- Salah
- Tidak Dijawab
- Score
- Grade
- Keterangan
- Status Submit
- Link PDF Google Drive

### Aturan
- Link PDF harus tampil sebagai tombol atau link jelas
- jangan tampilkan raw URL panjang tanpa styling

## 17.4 Daftar Jawaban
Di bawah ringkasan, tampilkan daftar semua soal.

Untuk setiap soal tampilkan:
- nomor soal
- tipe soal
- teks soal lengkap
- gambar soal jika ada

### Jika soal multiple choice
Tampilkan semua opsi:
- label opsi
- teks opsi jika ada
- gambar opsi jika ada
- penanda jawaban benar
- penanda jawaban yang dipilih peserta

### Jika soal short answer
Tampilkan:
- jawaban peserta
- daftar jawaban benar yang diterima

### Status jawaban
Tampilkan badge yang jelas:
- Benar
- Salah
- Tidak Dijawab

## 17.5 Tombol
- `Kembali`

## 17.6 Hal yang Tidak Boleh Ada
Jangan tampilkan:
- tombol edit hasil
- tombol resend Discord
- tombol regenerate PDF
- tombol ubah jawaban

---

# 18. Halaman Admin Users

## 18.1 Akses
Halaman ini hanya untuk Super Admin.

## 18.2 Tujuan
Mengelola akun admin panel.

## 18.3 Tabel List User
Kolom:
- Nama
- Email
- Role
- Status
- Dibuat Pada
- Aksi

### Aksi
- `Edit`
- `Hapus`

## 18.4 Tombol Header
- `Tambah Admin`

## 18.5 Form Create/Edit User
Field:
- Nama
- Email
- Password
- Konfirmasi Password
- Role
- Status Aktif

### Role pilihan
- Super Admin
- Admin

### Aturan UI
- form create wajib isi password
- form edit boleh kosongkan password jika tidak ingin diubah
- Admin tidak boleh melihat halaman ini

---

# 19. Halaman Peserta - Form Identitas

## 19.1 Tujuan
Halaman pertama saat peserta membuka link quiz.

## 19.2 Validasi Awal Link
Sebelum form tampil, sistem harus menentukan tampilan berdasarkan status link.

Kemungkinan tampilan:
1. Link valid dan belum final
2. Link sudah selesai dipakai
3. Link tidak valid

---

## 19.3 Jika Link Valid
Tampilkan:
- nama quiz
- durasi quiz
- field Nama Peserta
- field Nomor KTP
- tombol `Mulai Test`

### Aturan
- timer belum berjalan pada halaman ini
- timer baru berjalan saat tombol `Mulai Test` diklik
- field nama wajib
- field nomor KTP wajib

## 19.4 Jika Link Sudah Final
Tampilkan halaman pesan sederhana:
- judul: `Link Quiz Tidak Bisa Digunakan`
- isi:
  - jika sudah submit: `Quiz ini sudah selesai dikerjakan.`
  - jika expired: `Waktu pengerjaan quiz ini sudah habis.`

Jangan tampilkan form identitas.

## 19.5 Jika Link Tidak Valid
Tampilkan:
- judul: `Link Quiz Tidak Valid`
- isi: `Link yang Anda buka tidak ditemukan atau tidak tersedia.`

---

# 20. Halaman Peserta - Pengerjaan Quiz

## 20.1 Struktur Halaman
Halaman pengerjaan peserta terdiri dari:
- header ringkas
- area timer
- daftar soal
- tombol submit di bagian bawah

## 20.2 Header Ringkas
Tampilkan:
- nama quiz
- nama peserta
- nomor KTP

## 20.3 Timer
Timer harus tampil jelas dan menonjol.

Format tampilan:
- `Sisa Waktu: 00:00`

### Behavior visual
- timer terus berkurang
- jika waktu habis, sistem autosubmit
- peserta tidak perlu menekan tombol submit saat waktu habis

## 20.4 Daftar Soal
Semua soal ditampilkan dalam satu halaman panjang.

Jangan gunakan pagination antar soal.
Jangan gunakan wizard per halaman.
Jangan gunakan next/previous question layout.

Untuk setiap soal tampilkan:
- nomor soal
- teks soal
- gambar soal jika ada

### Jika Multiple Choice
Tampilkan daftar opsi.
Setiap opsi menampilkan:
- label atau container opsi
- teks opsi jika ada
- gambar opsi jika ada

Peserta memilih satu jawaban.

### Jika Short Answer
Tampilkan input jawaban teks.

## 20.5 Tombol Bawah
Tampilkan:
- `Submit Jawaban`

## 20.6 Konfirmasi Submit
Saat peserta klik submit:
- tampilkan modal konfirmasi
- pesan harus jelas bahwa jawaban akan dikirim final

---

# 21. Halaman Peserta - Selesai

## 21.1 Tujuan
Menampilkan pesan setelah jawaban berhasil dikirim.

## 21.2 Isi Halaman
Tampilkan:
- judul: `Jawaban Berhasil Dikirim`
- pesan: `Terima kasih. Jawaban Anda sudah tercatat.`

## 21.3 Aturan
- jangan tampilkan score
- jangan tampilkan grade
- jangan tampilkan jawaban benar
- jangan tampilkan review hasil

---

# 22. Halaman / State Error Peserta

## 22.1 Link Tidak Valid
Tampilkan halaman sederhana dengan pesan jelas:
- `Link Quiz Tidak Valid`

## 22.2 Link Sudah Dipakai
Tampilkan pesan:
- `Quiz ini sudah selesai dikerjakan.`

## 22.3 Waktu Habis
Jika peserta membuka ulang setelah autosubmit:
- tampilkan pesan:
  `Waktu pengerjaan quiz ini sudah habis.`

## 22.4 Quiz Tidak Tersedia
Jika quiz nonaktif atau data pendukung rusak:
- tampilkan pesan generik:
  `Quiz tidak tersedia.`

Jangan tampilkan stack trace atau error teknis.

---

# 23. Komponen UI yang Wajib Ada

## 23.1 Badge Status
Gunakan badge sederhana untuk status:
- Aktif / Nonaktif
- Unused / Opened / In Progress / Submitted / Expired
- Submitted / Auto Submitted
- Benar / Salah / Tidak Dijawab

## 23.2 Modal Konfirmasi
Gunakan modal untuk:
- hapus quiz
- hapus soal
- submit jawaban peserta

## 23.3 Preview Gambar
Preview gambar wajib tersedia pada:
- gambar soal
- gambar opsi

## 23.4 Empty State
Setiap list utama wajib punya empty state:
- quiz list
- soal list
- hasil list
- link list
- admin user list

---

# 24. Pesan UI yang Harus Jelas

Gunakan pesan literal yang jelas. Hindari pesan samar.

## 24.1 Pesan Sukses
Contoh gaya pesan:
- `Quiz berhasil disimpan.`
- `Soal berhasil disimpan.`
- `Link quiz berhasil dibuat.`
- `Import soal berhasil dilakukan.`
- `Admin berhasil disimpan.`

## 24.2 Pesan Gagal
Contoh gaya pesan:
- `Data gagal disimpan.`
- `File import tidak valid.`
- `Link quiz tidak valid.`
- `Akun tidak aktif.`

## 24.3 Pesan Validasi
Contoh:
- `Nama quiz wajib diisi.`
- `Durasi wajib lebih dari 0 menit.`
- `Nama peserta wajib diisi.`
- `Nomor KTP wajib diisi.`
- `Minimal 2 opsi harus diisi.`

---

# 25. Hal yang Tidak Boleh Ditampilkan di UI

Jangan tampilkan elemen berikut:
- registrasi peserta
- login peserta
- halaman hasil peserta
- leaderboard
- ranking peserta
- review jawaban peserta setelah submit
- tombol resend webhook
- tombol regenerate PDF
- tombol export hasil
- tombol duplicate quiz
- tombol archive quiz
- statistik kompleks
- chart analytics
- countdown sebelum test dimulai
- anti-cheat webcam
- lock browser fullscreen
- halaman public landing page

---

# 26. Responsiveness

## 26.1 Admin Panel
Admin panel harus tetap usable pada laptop dan desktop.
Pada mobile:
- sidebar boleh collapse
- tabel boleh horizontal scroll
- form tetap rapi

## 26.2 Halaman Peserta
Halaman peserta harus usable pada:
- desktop
- tablet
- mobile

### Prioritas mobile
Karena peserta bisa membuka link dari perangkat mobile, halaman peserta harus:
- mudah dibaca
- gambar tetap proporsional
- input mudah digunakan
- tombol submit jelas terlihat

---

# 27. Urutan Navigasi Utama

## 27.1 Admin Flow
1. Login
2. Dashboard
3. Masuk ke Quiz
4. Buat/Edit Quiz
5. Masuk ke Detail Quiz
6. Tambah/Edit/Import Soal
7. Generate Link
8. Lihat daftar link
9. Lihat hasil peserta
10. Lihat detail hasil

## 27.2 Peserta Flow
1. Buka link unik
2. Isi nama
3. Isi nomor KTP
4. Klik mulai test
5. Kerjakan quiz
6. Submit atau autosubmit
7. Lihat halaman selesai

---

# 28. Konsistensi Terminologi UI

Gunakan istilah berikut secara konsisten di seluruh aplikasi:
- Quiz
- Soal
- Multiple Choice
- Short Answer
- Generate Link
- Hasil
- Nama Peserta
- Nomor KTP
- Score
- Grade
- Keterangan
- Submit
- Auto Submitted

Jangan campur istilah dengan sinonim acak seperti:
- Exam
- Test Package
- Candidate
- Session Token
- Submission Review
- Assessment Report

Gunakan wording yang lurus dan konsisten.

---

# 29. Ringkasan Halaman Wajib

## Admin
1. Login Admin
2. Dashboard
3. Quiz List
4. Quiz Create
5. Quiz Edit
6. Quiz Detail
7. Soal Create
8. Soal Edit
9. Soal Detail
10. Import Soal
11. Generate Link
12. Link List
13. Link Detail
14. Hasil List
15. Hasil Detail
16. Admin User List
17. Admin User Create
18. Admin User Edit

## Peserta
1. Form Identitas / Start Page
2. Halaman Pengerjaan Quiz
3. Halaman Selesai
4. Halaman Link Invalid / Final State

Dokumen ini menjadi acuan tampilan dan interaksi untuk seluruh sistem quiz internal perusahaan.