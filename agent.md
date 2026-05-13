# Agent Specification

## Nama Agent
Quiz System Builder

## Tujuan Utama
Agent ini bertugas membangun aplikasi quiz internal perusahaan berbasis Laravel + Livewire sesuai spesifikasi yang diberikan pada dokumen lain (`data.md`, `design.md`, `backend.md`).

Agent hanya boleh mengerjakan fitur yang secara eksplisit tertulis pada spesifikasi. Agent tidak boleh menambah asumsi, fitur, alur, library, endpoint, role, atau behavior yang tidak disebutkan secara tegas pada dokumen.

---

## Stack Wajib
Agent wajib menggunakan stack berikut:
- Laravel
- Livewire
- Blade
- MySQL atau MariaDB
- Storage Laravel
- Google Drive API untuk upload PDF hasil
- Discord Webhook untuk notifikasi hasil

Agent tidak boleh mengubah stack utama tanpa instruksi tertulis.

---

## Scope Pekerjaan Agent
Agent bertugas mengerjakan sistem quiz dengan 2 sisi utama:

### 1. Admin Panel
Digunakan oleh:
- Super Admin
- Admin

Fitur utama:
- login admin
- CRUD quiz
- CRUD soal
- import soal via `.xlsx`
- generate link quiz unik
- lihat daftar link quiz
- lihat status link quiz
- lihat daftar hasil peserta
- lihat detail hasil peserta

### 2. Halaman Peserta
Digunakan oleh peserta anonim melalui link unik.

Fitur utama:
- buka link quiz
- isi nama
- isi nomor KTP
- mulai test
- kerjakan quiz
- submit jawaban
- autosubmit saat timer habis
- lihat pesan final bahwa jawaban sudah terkirim

---

## Role dan Hak Akses

### Super Admin
Super Admin memiliki akses ke:
- login admin panel
- CRUD user admin
- CRUD quiz
- CRUD soal
- import soal
- generate link quiz
- lihat hasil peserta
- lihat detail hasil peserta

### Admin
Admin memiliki akses ke:
- login admin panel
- CRUD quiz
- CRUD soal
- import soal
- generate link quiz
- lihat hasil peserta
- lihat detail hasil peserta

Admin tidak boleh memiliki akses untuk:
- CRUD user admin
- mengubah role user admin lain

---

## Prinsip Implementasi
Agent wajib mengikuti prinsip berikut:

### 1. Tidak Menambah Fitur di Luar Spesifikasi
Agent tidak boleh menambahkan fitur berikut kecuali diminta secara eksplisit:
- registrasi peserta
- login peserta
- essay question
- edit hasil peserta
- resend Discord webhook
- regenerate PDF manual
- expired by date
- import gambar dari Excel
- bobot soal berbeda
- multi file upload per soal
- multiple images per question
- export hasil ke Excel
- leaderboard
- public result page
- email notification

### 2. Tidak Mengubah Flow Bisnis
Agent tidak boleh mengubah alur yang sudah ditentukan, termasuk:
- timer dimulai saat peserta klik tombol mulai test
- link menjadi hangus saat submit atau timer habis
- peserta tidak melihat hasil
- satu link hanya untuk satu attempt anonim
- peserta boleh melanjutkan attempt yang sama selama belum submit dan timer belum habis

### 3. Tidak Membuat Asumsi Diam-Diam
Jika suatu detail belum tertulis, agent tidak boleh menebak lalu mengimplementasikan versi sendiri. Agent hanya boleh:
- mengikuti dokumen spesifikasi yang ada
- memberi placeholder teknis internal yang tidak mengubah behavior bisnis
- menjaga agar implementasi tetap konsisten dengan scope

### 4. Prioritaskan Kesesuaian, Bukan Kreativitas
Agent tidak diminta membuat fitur yang “lebih canggih”.
Agent diminta membuat sistem yang:
- stabil
- sesuai spesifikasi
- tidak keluar scope
- mudah dipelihara

---

## Definisi Produk
Produk yang dibangun adalah aplikasi quiz internal perusahaan dengan karakteristik berikut:
- admin membuat quiz
- admin membuat soal
- admin dapat import soal dari file `.xlsx`
- admin generate banyak link unik untuk 1 quiz
- peserta menerima link unik
- peserta mengisi nama dan nomor KTP
- peserta mengerjakan quiz dengan timer
- jawaban dinilai otomatis
- hasil dibuat dalam PDF lengkap
- PDF diupload ke Google Drive
- notifikasi hasil dikirim ke Discord webhook

---

## Tipe Soal yang Diizinkan
Agent hanya boleh mengimplementasikan 2 tipe soal:
- `multiple_choice`
- `short_answer`

Agent tidak boleh mengimplementasikan:
- essay
- checkbox multiple answer
- file upload answer
- audio answer
- video answer
- drag and drop answer

---

## Aturan Data Peserta
Peserta adalah anonim, artinya:
- peserta tidak memiliki akun
- peserta tidak login
- peserta masuk hanya melalui link quiz unik

Namun setiap peserta wajib mengisi:
- nama
- nomor KTP

Nomor KTP diperlakukan sebagai data input wajib pada attempt peserta.

Agent tidak boleh membuat sistem verifikasi KTP ke layanan eksternal.
Nomor KTP hanya disimpan sebagai data identitas peserta untuk kebutuhan hasil quiz.

---

## Aturan Link Quiz
Agent wajib mengikuti aturan link berikut:
- link dibuat oleh admin untuk quiz tertentu
- satu link hanya untuk satu peserta anonim
- satu link hanya untuk satu attempt
- link memiliki token unik random
- link dapat berada pada status tertentu
- link dianggap selesai dipakai saat peserta submit atau timer habis
- link tidak hangus hanya karena peserta baru mengisi nama
- link tidak hangus hanya karena halaman dibuka
- timer mulai saat peserta klik mulai test

Agent tidak boleh mengubah aturan ini.

---

## Aturan Penilaian
Penilaian hanya untuk:
- multiple choice
- short answer

Aturan nilai:
- semua soal memiliki bobot yang sama
- nilai berdasarkan benar/salah
- hasil akhir dihitung dalam persentase
- grade dihitung berdasarkan persentase, bukan jumlah soal mentah

Agent tidak boleh membuat bobot per soal kecuali diperintahkan kemudian.

---

## Aturan Gambar
Agent wajib mendukung gambar dengan aturan berikut:
- setiap soal dapat memiliki maksimal 1 gambar utama
- untuk multiple choice, setiap opsi dapat memiliki gambar
- import Excel tidak mencakup gambar
- gambar diinput manual melalui admin panel

Agent tidak boleh membuat:
- multiple images per question
- gallery image
- image upload via Excel
- image annotation tool

---

## Aturan Import Excel
Agent wajib mendukung import soal dengan aturan berikut:
- format file hanya `.xlsx`
- import hanya menambah soal baru
- import tidak mengedit soal lama
- import tidak menghapus soal lama
- import tidak memproses gambar
- template kolom harus tetap
- validasi baris harus ketat
- baris invalid tidak boleh masuk diam-diam tanpa error

Agent tidak boleh membuat import `.csv` kecuali ada instruksi baru.

---

## Aturan Hasil Peserta
Setelah peserta selesai:
- sistem hitung skor otomatis
- sistem tentukan grade
- sistem generate PDF lengkap
- sistem upload PDF ke Google Drive
- sistem kirim notifikasi ke Discord webhook

Admin hanya boleh:
- melihat daftar hasil
- melihat detail hasil

Admin tidak boleh:
- mengedit hasil
- mengubah jawaban peserta
- mengirim ulang notifikasi
- membuat ulang PDF secara manual

---

## Dokumen Acuan Wajib
Agent wajib membaca dan mengikuti dokumen berikut sebelum mengerjakan detail implementasi:
- `data.md`
- `design.md`
- `backend.md`

Aturan prioritas dokumen:
1. `agent.md`
2. `data.md`
3. `design.md`
4. `backend.md`

Jika ada konflik:
- aturan yang lebih restriktif harus dipilih
- jangan memilih interpretasi yang memperluas scope

---

## Output yang Diharapkan dari Agent
Agent harus menghasilkan implementasi yang:
- sesuai spesifikasi
- konsisten antar layer
- tidak ambigu
- tidak menambahkan fitur liar
- tidak mengubah alur bisnis
- siap dikerjakan bertahap
- mudah diuji

---

## Larangan Keras
Agent dilarang melakukan hal berikut tanpa instruksi eksplisit:
- mengganti stack dari Laravel + Livewire
- menambah SPA framework terpisah
- membuat API publik yang tidak diperlukan
- menambah role lain selain `super_admin` dan `admin`
- menambah autentikasi peserta
- menambah fitur essay
- menambah fitur nilai manual
- menambah fitur review peserta
- menambah fitur publish hasil ke peserta
- menambah fitur anti-cheat berbasis webcam
- menambah audit log kompleks
- menambah real-time websocket bila tidak diperlukan
- mengubah aturan token sekali pakai
- mengubah aturan timer
- mengubah format hasil PDF
- mengubah integrasi Google Drive menjadi provider lain
- mengubah Discord webhook menjadi provider notifikasi lain

---

## Cara Agent Mengambil Keputusan
Jika agent menemukan area teknis yang belum dijelaskan secara detail, agent harus mengambil keputusan dengan prinsip:
- pilih solusi paling sederhana
- pilih solusi yang paling sempit scope-nya
- jangan ubah behavior bisnis
- jangan menambah fitur baru
- tetap konsisten dengan dokumen spesifikasi

Contoh:
- jika butuh validasi tambahan, tambahkan validasi teknis yang aman
- jika butuh struktur file, gunakan struktur Laravel standar
- jika butuh penamaan internal, gunakan nama yang jelas dan konsisten

Agent tidak boleh menggunakan kebutuhan teknis sebagai alasan untuk memperluas fitur bisnis.

---

## Definisi Selesai
Pekerjaan dianggap benar hanya jika:
- sesuai dokumen
- semua fitur inti ada
- tidak ada fitur tambahan liar
- role sesuai
- flow peserta sesuai
- flow admin sesuai
- hasil PDF sesuai
- Google Drive upload sesuai
- Discord webhook sesuai
- import Excel sesuai
- penilaian sesuai
- tampilan sesuai kebutuhan sistem quiz internal perusahaan

Jika ada implementasi yang technically valid tetapi keluar dari spesifikasi, maka implementasi tersebut dianggap salah.