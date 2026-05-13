# Backend Specification

## Tujuan Dokumen
Dokumen ini mendefinisikan aturan implementasi backend aplikasi quiz internal perusahaan berbasis Laravel + Livewire.

Dokumen ini wajib dijadikan acuan tunggal untuk:
- arsitektur backend
- aturan business logic
- service yang wajib dibuat
- validasi backend
- flow data
- flow status
- proses scoring
- proses finalisasi hasil
- proses generate PDF
- proses upload Google Drive
- proses Discord webhook
- aturan keamanan backend

Dokumen ini tidak boleh ditafsirkan secara longgar. Setiap behavior yang tertulis di bawah wajib diikuti secara literal. Jangan menambah flow, endpoint, status, background process, atau rule bisnis di luar yang sudah disepakati.

---

# 1. Stack dan Arsitektur Wajib

## 1.1 Stack Backend
Gunakan:
- Laravel
- Livewire
- Blade
- Eloquent ORM
- MySQL atau MariaDB
- Laravel Validation
- Laravel Storage
- Google Drive API
- Discord Webhook HTTP request

Jangan gunakan:
- SPA frontend terpisah
- microservice
- websocket
- queue wajib
- event-driven architecture yang mengubah flow utama
- external scoring engine

## 1.2 Pola Arsitektur
Gunakan pola backend sederhana dan eksplisit:
- Controller / Livewire Component untuk HTTP interaction
- Form validation request atau validation rules yang jelas
- Service class untuk logic utama
- Eloquent model untuk data access
- helper kecil hanya jika benar-benar perlu

Jangan menaruh seluruh business logic di Blade.
Jangan menaruh seluruh business logic langsung di model secara tersebar.
Jangan menyembunyikan logic penting di trait yang tidak jelas.

## 1.3 Prinsip Umum
Semua flow inti harus:
- deterministik
- idempotent sejauh memungkinkan
- tidak ambigu
- tidak mengubah status tanpa event yang jelas
- tidak bergantung pada asumsi tersembunyi

---

# 2. Modul Backend yang Wajib Ada

Backend harus dibagi minimal ke modul logic berikut:

1. Authentication Admin
2. Quiz Management
3. Question Management
4. Excel Import
5. Quiz Link Generation
6. Participant Attempt Flow
7. Answer Saving
8. Timer Enforcement
9. Result Scoring
10. PDF Generation
11. Google Drive Upload
12. Discord Webhook Delivery
13. Result Read-Only View
14. Admin User Management

Setiap modul harus punya tanggung jawab yang jelas.

---

# 3. Authentication Admin

## 3.1 Scope
Authentication hanya untuk:
- `super_admin`
- `admin`

Peserta tidak memiliki akun dan tidak boleh memakai auth admin.

## 3.2 Login Rule
Saat login:
- cari user berdasarkan email
- cek password
- cek `is_active = true`
- jika valid, buat session login admin
- jika tidak valid, tolak login

## 3.3 Penolakan Login
Login harus gagal jika:
- email tidak ditemukan
- password salah
- user soft deleted
- `is_active = false`

## 3.4 Authorization Rule
Authorization wajib dibatasi sebagai berikut:

### Super Admin
Boleh:
- akses semua menu admin
- CRUD admin user
- CRUD quiz
- CRUD soal
- import soal
- generate link
- lihat hasil

### Admin
Boleh:
- CRUD quiz
- CRUD soal
- import soal
- generate link
- lihat hasil

Admin tidak boleh:
- CRUD admin user
- ubah role user lain
- akses halaman `Admin Users`

## 3.5 Middleware
Gunakan middleware backend yang jelas untuk:
- auth admin
- cek role jika route hanya untuk super admin

Jangan mengandalkan hide menu UI saja sebagai proteksi.

---

# 4. Quiz Management Logic

## 4.1 Create Quiz
Saat create quiz:
- validasi input
- simpan row ke `quizzes`
- simpan 5 row ke `quiz_grade_rules`
- set `created_by`
- set default `is_active = true` jika tidak diubah

## 4.2 Validasi Quiz
Field wajib:
- `title`
- `duration_minutes`
- 5 grade rules

Rule:
- `duration_minutes > 0`
- title tidak boleh kosong
- grade A-E harus lengkap
- tidak boleh ada overlap min/max antar grade
- range 0.00 sampai 100.00 harus tertutup penuh
- `min_score <= max_score`

## 4.3 Update Quiz
Saat update quiz:
- validasi ulang seluruh field
- update `quizzes`
- update `quiz_grade_rules`
- set `updated_by`

## 4.4 Delete Quiz
Saat hapus quiz:
- lakukan soft delete pada `quizzes`
- jangan hard delete
- jangan hapus data historis attempt/result
- soal terkait tidak perlu dihapus fisik bila relasi historis masih dibutuhkan

## 4.5 Quiz Active Rule
Quiz nonaktif:
- tetap boleh dilihat di admin panel
- tidak boleh dipilih saat generate link baru
- tidak boleh digunakan untuk start attempt baru jika token belum final dan quiz sudah dinonaktifkan, tampilkan `Quiz tidak tersedia.`

---

# 5. Question Management Logic

## 5.1 Create Question
Saat create soal:
- validasi `question_type`
- validasi field umum
- simpan `questions`
- simpan jawaban sesuai tipe soal
- simpan `created_by`

## 5.2 Question Type yang Valid
Hanya boleh:
- `multiple_choice`
- `short_answer`

Selain itu harus ditolak.

## 5.3 Create Multiple Choice
Untuk `multiple_choice`:
- minimal 2 opsi valid
- maksimal 5 opsi
- tepat 1 opsi benar
- setiap opsi yang disimpan harus punya minimal:
  - teks, atau
  - gambar
- buat row `question_options`
- tandai satu row `is_correct = true`

## 5.4 Create Short Answer
Untuk `short_answer`:
- minimal 1 accepted answer
- simpan ke `short_answer_keys`
- simpan `normalized_answer_text` untuk setiap accepted answer

## 5.5 Normalisasi Short Answer Key
Normalisasi minimal wajib:
- convert ke lowercase
- trim spasi awal dan akhir
- rapikan multiple spaces menjadi single space

Jangan tambahkan semantic matching.
Jangan tambahkan AI matching.
Jangan tambahkan typo tolerance.

## 5.6 Update Question
Saat edit soal:
- update row `questions`
- jika tipe `multiple_choice`, sinkronkan `question_options`
- jika tipe `short_answer`, sinkronkan `short_answer_keys`
- hapus relasi tipe lawas yang tidak relevan jika tipe berubah

### Contoh
Jika soal awal `multiple_choice` lalu diubah jadi `short_answer`:
- option lama harus dihapus atau dinonaktifkan sesuai implementasi yang konsisten
- short answer key baru disimpan
- data akhir tidak boleh punya kedua tipe jawaban sekaligus

## 5.7 Delete Question
Saat hapus soal:
- lakukan soft delete pada `questions`
- lakukan soft delete pada `question_options` jika relevan
- jangan rusak histori jawaban lama

---

# 6. Image Handling Logic

## 6.1 Scope Gambar
Backend wajib mendukung:
- 1 gambar utama per soal
- gambar pada opsi multiple choice

## 6.2 Storage Rule
Gambar disimpan ke storage aplikasi.
Path file disimpan di database:
- `questions.question_image_path`
- `question_options.option_image_path`

## 6.3 Validasi File Gambar
Validasi minimal:
- hanya file image
- ukuran file dibatasi
- nama file aman
- extension aman

Ukuran maksimum exact boleh ditentukan implementasi, tetapi harus konsisten dan tidak terlalu besar. Jangan membiarkan upload tanpa batas.

## 6.4 Import Excel
Import `.xlsx` tidak boleh memproses gambar.

---

# 7. Excel Import Logic

## 7.1 Scope
Import hanya:
- append soal baru ke quiz tertentu
- file `.xlsx` saja

Import tidak boleh:
- update soal lama
- delete soal lama
- replace semua soal
- import gambar

## 7.2 Header yang Valid
Header wajib persis:
- `soal`
- `jenis_jawaban`
- `opsi_a`
- `opsi_b`
- `opsi_c`
- `opsi_d`
- `opsi_e`
- `jawaban_benar`
- `short_answer`

Jika header berbeda, file harus ditolak.

## 7.3 Parsing Rule
### multiple_choice
- `jenis_jawaban = multiple_choice`
- buat `questions`
- buat 2 sampai 5 `question_options`
- `jawaban_benar` harus salah satu `A/B/C/D/E`
- option yang ditunjuk `jawaban_benar` harus ada isinya
- `short_answer` harus kosong

### short_answer
- `jenis_jawaban = short_answer`
- buat `questions`
- buat `short_answer_keys` dari split `|`
- `jawaban_benar` harus kosong
- semua opsi harus kosong

## 7.4 Validasi per Baris
Baris invalid:
- tidak boleh disimpan
- harus masuk laporan error validasi

## 7.5 Atomicity Import
Satu file import harus diproses secara aman.

Pilih salah satu perilaku berikut dan gunakan secara konsisten:
- **strict mode full rollback**: jika ada satu baris invalid, seluruh import batal
- **partial mode with valid rows only**: hanya baris valid yang masuk, baris invalid ditolak dan dilaporkan

Untuk sistem ini, gunakan:
- **partial mode with valid rows only**

Rule final:
- baris valid masuk
- baris invalid ditolak
- laporan error per baris wajib tampil

## 7.6 Order Number Saat Import
Nomor urut soal hasil import harus dilanjutkan dari urutan terakhir quiz tersebut.
Jangan menimpa order soal lama.

---

# 8. Quiz Link Generation Logic

## 8.1 Generate Link
Saat admin generate link:
- pilih 1 quiz aktif
- input jumlah link
- sistem buat N row di `quiz_links`

## 8.2 Token Rule
Token wajib:
- random
- unique
- sulit ditebak
- tidak berurutan

## 8.3 Status Awal
Setiap link baru:
- `status = unused`
- `opened_at = null`
- `started_at = null`
- `submitted_at = null`
- `expired_at = null`

## 8.4 URL Rule
URL final dibentuk dari:
- base app URL
- route peserta
- token unik

Contoh pola:
- `/quiz/{token}`

Gunakan 1 token sebagai identifier publik.
Jangan expose numeric id.

## 8.5 Batch Generation
Setelah generate:
- semua token harus langsung tersimpan
- status awal semua `unused`
- hasil bisa di-copy per link atau massal

---

# 9. Participant Access Flow

## 9.1 Open Link
Saat peserta membuka `/quiz/{token}`:
1. cari `quiz_links` by token
2. jika tidak ada, tampilkan `Link Quiz Tidak Valid`
3. jika link final (`submitted` atau `expired`), tampilkan state final
4. jika quiz nonaktif atau tidak tersedia, tampilkan `Quiz tidak tersedia.`
5. jika valid, tampilkan form identitas

## 9.2 Opened Status
Saat link valid pertama kali diakses:
- jika status masih `unused`
- ubah ke `opened`
- isi `opened_at` jika masih null

Jangan ubah ke `in_progress` pada tahap ini.

## 9.3 Save Identity
Saat peserta mengisi:
- nama
- nomor KTP

Lalu klik `Mulai Test`:
- validasi kedua field wajib
- buat `quiz_attempts` jika belum ada
- simpan `participant_name`
- simpan `participant_ktp_number`
- snapshot `time_limit_minutes` dari quiz
- set `started_at`
- set `status = in_progress`
- update `quiz_links.status = in_progress`
- update `quiz_links.started_at`

## 9.4 Idempotensi Start
Jika peserta refresh atau membuka kembali attempt yang sama:
- jangan buat attempt baru
- gunakan attempt yang sudah ada
- jika status masih `in_progress` dan waktu belum habis, tampilkan halaman pengerjaan yang sama

## 9.5 Attempt Resume Rule
Peserta boleh resume attempt yang sama hanya jika:
- `quiz_attempts.status = in_progress`
- waktu belum habis
- link belum final

Jika tidak memenuhi kondisi di atas, tampilkan halaman final yang sesuai.

---

# 10. Timer Enforcement

## 10.1 Sumber Kebenaran Waktu
Sumber kebenaran waktu pengerjaan harus berasal dari server, bukan browser.

Browser boleh menampilkan countdown visual, tetapi final validity harus dihitung dari:
- `quiz_attempts.started_at`
- `quiz_attempts.time_limit_minutes`
- waktu server saat request

## 10.2 Formula Deadline
Deadline attempt:
- `deadline = started_at + time_limit_minutes`

## 10.3 Saat Halaman Quiz Dibuka Ulang
Setiap kali peserta membuka ulang halaman pengerjaan:
- hitung sisa waktu dari server
- jika waktu habis, jalankan autosubmit
- jangan percaya countdown lokal browser

## 10.4 Submit Setelah Deadline
Jika peserta menekan submit setelah deadline server terlewati:
- sistem harus memperlakukan sebagai autosubmit timeout flow
- jangan menerima sebagai submit normal

## 10.5 Autosubmit Trigger
Autosubmit dapat dipicu oleh salah satu:
- request peserta setelah deadline terlewati
- pengecekan saat save answer
- pengecekan saat load halaman quiz
- pengecekan saat submit

Jangan mengandalkan cron wajib untuk autosubmit.
Flow harus tetap benar walaupun tanpa scheduler.

---

# 11. Answer Saving Logic

## 11.1 Scope
Sistem harus bisa menyimpan jawaban peserta selama quiz berlangsung.

## 11.2 Save Behavior
Setiap penyimpanan jawaban harus:
- pastikan token valid
- pastikan attempt valid
- pastikan attempt `in_progress`
- pastikan belum lewat deadline
- simpan atau update jawaban untuk soal terkait

## 11.3 Multiple Choice Save
Untuk soal `multiple_choice`:
- simpan `selected_option_id`
- `answer_text` boleh null

## 11.4 Short Answer Save
Untuk soal `short_answer`:
- simpan `answer_text`
- `selected_option_id` null

## 11.5 Idempotent Upsert
Jawaban peserta harus disimpan dengan pola upsert berdasarkan:
- `quiz_attempt_id`
- `question_id`

Jangan membuat multiple rows untuk soal yang sama dalam 1 attempt.

## 11.6 Validasi Relasi
Saat simpan jawaban:
- soal harus milik quiz yang sama
- option yang dipilih harus milik question yang sama
- peserta tidak boleh menjawab soal dari quiz lain

## 11.7 Answer Time
`answered_at` boleh diupdate saat jawaban terakhir disimpan.

---

# 12. Final Submit Logic

## 12.1 Submit Manual
Saat peserta klik `Submit Jawaban`:
1. validasi attempt masih aktif
2. cek deadline server
3. jika deadline sudah lewat, jalankan autosubmit flow
4. jika masih valid, finalisasi sebagai submit manual

## 12.2 Finalisasi Submit Manual
Urutan minimal:
1. lock attempt agar tidak diproses ganda
2. hitung hasil
3. update `attempt_answers.is_correct`
4. update `quiz_attempts.status = submitted`
5. update `quiz_attempts.submitted_at`
6. update `quiz_links.status = submitted`
7. update `quiz_links.submitted_at`
8. update `quiz_links.expired_at`
9. buat `quiz_results`
10. generate PDF
11. upload PDF ke Google Drive
12. kirim Discord webhook
13. tampilkan halaman selesai

## 12.3 Autosubmit
Jika waktu habis:
1. lock attempt agar tidak diproses ganda
2. hitung hasil
3. update `attempt_answers.is_correct`
4. update `quiz_attempts.status = auto_submitted`
5. update `quiz_attempts.submitted_at`
6. update `quiz_links.status = expired`
7. update `quiz_links.submitted_at`
8. update `quiz_links.expired_at`
9. buat `quiz_results`
10. generate PDF
11. upload PDF ke Google Drive
12. kirim Discord webhook
13. tampilkan halaman final timeout / selesai sesuai flow

## 12.4 Finalization Idempotency
Attempt final tidak boleh diproses dua kali.

Sebelum finalisasi, backend wajib cek:
- apakah `quiz_results` sudah ada untuk attempt ini
- apakah status attempt sudah final

Jika sudah final:
- jangan buat result kedua
- jangan generate PDF kedua
- jangan kirim webhook kedua

---

# 13. Concurrency Control

## 13.1 Masalah yang Harus Dicegah
Backend harus mencegah kasus:
- double click submit
- submit bersamaan dari dua tab
- autosubmit dan submit manual terjadi hampir bersamaan
- refresh berulang memproses finalisasi berkali-kali

## 13.2 Solusi Wajib
Gunakan transaction database dan locking yang jelas saat finalisasi.

Minimal:
- ambil `quiz_attempts` untuk finalisasi dengan lock
- validasi status final di dalam transaction
- commit hanya sekali

## 13.3 Hasil yang Diharapkan
Untuk satu attempt:
- maksimal 1 final result
- maksimal 1 PDF final
- maksimal 1 notifikasi final Discord

---

# 14. Scoring Logic

## 14.1 Scope
Scoring hanya untuk:
- multiple choice
- short answer

## 14.2 Total Questions
`total_questions` diambil dari jumlah soal aktif milik quiz yang relevan untuk attempt tersebut.

Gunakan snapshot soal yang memang tersedia saat attempt berjalan.
Jangan mengambil soal yang soft deleted setelah attempt jika itu merusak konsistensi. Implementasi harus menjaga hasil tetap konsisten terhadap soal yang dikerjakan peserta.

## 14.3 Multiple Choice Scoring
Jawaban benar jika:
- `selected_option_id` mengarah ke option dengan `is_correct = true`

Selain itu salah.

## 14.4 Short Answer Scoring
Jawaban benar jika:
- `answer_text` setelah normalisasi
- cocok persis dengan salah satu `normalized_answer_text`

## 14.5 Normalisasi Jawaban Peserta
Normalisasi minimal wajib:
- lowercase
- trim spasi awal dan akhir
- rapikan multiple spaces menjadi single space

Gunakan rule normalisasi yang sama antara:
- accepted answer
- jawaban peserta

## 14.6 Empty Answer Rule
Jawaban kosong atau null:
- dianggap tidak dijawab
- tidak dihitung benar
- tidak dihitung wrong
- masuk `unanswered_answers`

## 14.7 Wrong Answer Rule
Jawaban salah:
- `multiple_choice`: peserta memilih option yang bukan benar
- `short_answer`: peserta mengisi teks tetapi tidak cocok dengan key

## 14.8 Score Formula
Gunakan formula:
- `score_percentage = (correct_answers / total_questions) * 100`

Format simpan:
- decimal 2 digit

## 14.9 Grade Lookup
Setelah dapat `score_percentage`:
- lookup ke `quiz_grade_rules` milik quiz
- cari range yang mencakup score
- isi:
  - `grade_letter`
  - `grade_label`

Jangan hardcode grade langsung.

---

# 15. Result Construction Logic

## 15.1 Create Result
Setelah final scoring:
- buat 1 row di `quiz_results`
- isi seluruh angka final
- isi `result_status` sesuai finalisasi:
  - `submitted`
  - `auto_submitted`

## 15.2 Rule Angka
Wajib konsisten:
- `total_questions = correct_answers + wrong_answers + unanswered_answers`

Jika tidak seimbang, proses harus dianggap salah dan tidak boleh diam-diam lanjut.

## 15.3 Read-Only Result
Setelah row `quiz_results` dibuat:
- hasil dianggap final
- tidak boleh diedit dari UI admin

---

# 16. PDF Generation Logic

## 16.1 Trigger
PDF wajib dibuat segera setelah final result berhasil dihitung.

## 16.2 Isi PDF Wajib
PDF harus memuat:
- nama test
- nama peserta
- nomor KTP
- tanggal mulai
- jam mulai
- tanggal selesai
- jam selesai
- durasi
- score
- grade
- keterangan
- daftar semua soal
- gambar soal jika ada
- opsi jawaban jika relevant
- gambar opsi jika ada
- jawaban peserta
- jawaban benar
- status benar/salah/tidak dijawab per soal

## 16.3 Nama File
Format file name:
- `Nama test - Nama peserta - Tanggal pengerjaan`

Implementasi boleh menambahkan sanitasi nama file agar aman, tetapi format dasar harus tetap ini.

## 16.4 Metadata Table
Setelah PDF dibuat:
- buat/update row `result_pdfs`
- isi `file_name`
- isi `local_path`
- isi `generated_at`

## 16.5 Failure Rule
Jika generate PDF gagal:
- final result tetap sudah ada
- error harus tercatat dalam log aplikasi
- row `result_pdfs` boleh belum lengkap
- jangan membuat result kedua

---

# 17. Google Drive Upload Logic

## 17.1 Trigger
Setelah PDF lokal berhasil dibuat:
- upload ke folder Google Drive yang telah dikonfigurasi

## 17.2 Rule Upload
Upload harus menghasilkan minimal:
- `google_drive_file_id`
- `google_drive_url`

Keduanya disimpan ke `result_pdfs`.

## 17.3 Folder Target
Gunakan 1 folder Google Drive yang sudah ditentukan dari konfigurasi aplikasi.
Jangan membuat folder acak per hasil kecuali ada instruksi baru.

## 17.4 Failure Rule
Jika upload gagal:
- result tetap final
- PDF lokal tetap ada jika sudah berhasil dibuat
- `result_pdfs` tetap boleh ada
- `google_drive_file_id` dan `google_drive_url` boleh null
- error harus tercatat

## 17.5 UI Admin
Admin hanya melihat link PDF jika tersedia.
Tidak ada tombol manual upload ulang di UI.

---

# 18. Discord Webhook Logic

## 18.1 Trigger
Setelah proses final result selesai dan PDF upload diproses:
- kirim notifikasi ke Discord webhook

## 18.2 Payload Wajib
Payload notifikasi harus memuat informasi:
- Nama Test
- Nama Peserta
- Tanggal Test
- Score
- Grade
- Keterangan
- Link Google Drive PDF

Jika link Google Drive belum tersedia karena upload gagal:
- tetap kirim webhook
- isi link PDF sebagai kosong atau penanda tidak tersedia secara konsisten

## 18.3 Logging
Setiap percobaan kirim webhook wajib membuat row di `discord_webhook_logs` berisi:
- webhook_url
- payload_json
- response_status_code
- response_body
- is_success
- sent_at

## 18.4 Failure Rule
Jika webhook gagal:
- result tetap final
- jangan rollback hasil quiz
- log kegagalan wajib tercatat

## 18.5 UI Rule
Tidak ada tombol resend webhook di admin panel.

---

# 19. Read-Only Result View Logic

## 19.1 List Hasil
Admin dan Super Admin boleh melihat:
- daftar hasil
- filter sederhana
- detail hasil

## 19.2 Detail Hasil
Detail hasil harus diambil dari:
- `quiz_results`
- `quiz_attempts`
- `attempt_answers`
- `questions`
- `question_options`
- `short_answer_keys`
- `result_pdfs`

## 19.3 Read-Only
UI detail hasil hanya baca data.
Tidak boleh ada mutation action.

---

# 20. Admin User Management Logic

## 20.1 Scope
Hanya Super Admin yang boleh mengelola admin user.

## 20.2 Create User
Saat create:
- validasi nama
- validasi email unique
- validasi password
- validasi role hanya:
  - `super_admin`
  - `admin`

## 20.3 Update User
Saat edit:
- boleh update nama
- boleh update email
- boleh update password jika diisi
- boleh update role
- boleh update `is_active`

## 20.4 Delete User
Gunakan soft delete.
Jangan hard delete.

## 20.5 Authorization
Admin biasa yang mencoba akses route ini harus ditolak di backend.

---

# 21. Validasi Backend yang Wajib

## 21.1 Quiz
- `title` required
- `duration_minutes` required integer > 0
- 5 grade rules wajib ada
- grade rules tidak overlap
- range 0-100 tertutup

## 21.2 Question
- `question_type` required dan valid
- `question_text` required
- `order_number` required integer > 0
- jika multiple choice:
  - minimal 2 opsi valid
  - tepat 1 correct
- jika short answer:
  - minimal 1 key

## 21.3 Import
- file wajib `.xlsx`
- header wajib persis
- baris invalid harus terdeteksi
- quiz target wajib ada dan aktif

## 21.4 Generate Link
- `quiz_id` wajib ada
- quiz harus aktif
- `quantity` wajib integer > 0

## 21.5 Participant Start
- token valid
- nama wajib
- nomor KTP wajib
- link belum final
- quiz tersedia
- attempt belum final

## 21.6 Save Answer
- attempt aktif
- belum deadline
- soal milik quiz tersebut
- option milik soal tersebut jika multiple choice

## 21.7 Submit
- attempt aktif
- token valid
- finalisasi tidak boleh ganda

---

# 22. Service Class yang Disarankan

Backend harus dipisah minimal ke service berikut agar logic tidak bercampur:

1. `QuizService`
2. `QuestionService`
3. `QuestionImportService`
4. `QuizLinkService`
5. `ParticipantAttemptService`
6. `AnswerService`
7. `QuizScoringService`
8. `QuizFinalizationService`
9. `ResultPdfService`
10. `GoogleDriveUploadService`
11. `DiscordWebhookService`
12. `AdminUserService`

Nama class boleh sedikit berbeda, tetapi tanggung jawabnya harus tetap setara dan jelas.

---

# 23. Transaction Boundary

## 23.1 Wajib Transaction
Gunakan database transaction minimal pada flow berikut:
- create quiz + create grade rules
- update quiz + update grade rules
- create/update question + options/answer keys
- import per row valid
- finalisasi submit/autosubmit

## 23.2 Finalisasi Result
Flow finalisasi submit/autosubmit harus menjadi transaction inti untuk:
- lock attempt
- update status
- hitung hasil
- buat result

### Catatan
Generate PDF, upload Google Drive, dan kirim webhook boleh dilakukan:
- sesudah transaction utama commit
- tetapi tetap dalam flow request yang sama jika implementasi awal tanpa queue

Yang tidak boleh:
- result final rollback hanya karena webhook gagal
- result final rollback hanya karena upload Drive gagal

---

# 24. Error Handling Rule

## 24.1 Prinsip
Error teknis tidak boleh ditampilkan mentah ke peserta.
Gunakan pesan aman dan jelas.

## 24.2 Peserta
Peserta hanya boleh melihat pesan seperti:
- `Link Quiz Tidak Valid`
- `Quiz tidak tersedia.`
- `Waktu pengerjaan quiz ini sudah habis.`
- `Jawaban Berhasil Dikirim`

Jangan tampilkan:
- SQL error
- stack trace
- detail exception

## 24.3 Admin
Admin boleh mendapat pesan error operasional sederhana:
- `Data gagal disimpan.`
- `File import tidak valid.`
- `Upload PDF ke Google Drive gagal.`
- `Pengiriman notifikasi Discord gagal.`

Namun detail teknis tetap disimpan di log aplikasi.

---

# 25. Security Rule

## 25.1 Token Security
Token link quiz:
- jangan sequential
- jangan expose id internal
- harus cukup random

## 25.2 Authorization
Setiap route admin wajib:
- auth check
- role check bila diperlukan

## 25.3 Participant Scope
Peserta hanya boleh mengakses data dari token miliknya.
Peserta tidak boleh bisa enumerate quiz lain.

## 25.4 KTP Handling
Nomor KTP disimpan apa adanya untuk kebutuhan hasil.
Tidak ada masking di database.
Tidak ada verifikasi eksternal.
Tidak ada enkripsi wajib yang mengubah kebutuhan pencarian internal, kecuali proyek memutuskan kebijakan tambahan sendiri.
Jika dilakukan proteksi tambahan, behavior bisnis tidak boleh berubah.

## 25.5 File Access
Pastikan file upload gambar dan PDF disimpan dengan path aman.
Jangan menerima nama file mentah tanpa sanitasi.

---

# 26. Logging Rule

## 26.1 Aplikasi
Log aplikasi harus mencatat minimal:
- error import
- error finalisasi
- error generate PDF
- error upload Google Drive
- error Discord webhook

## 26.2 Database Log
Gunakan tabel log hanya untuk:
- `discord_webhook_logs`

Jangan membuat tabel log tambahan di luar spesifikasi tanpa kebutuhan kuat.

---

# 27. Route / Endpoint Behavior

## 27.1 Admin Route
Route admin harus dipisah namespace/prefix jelas, misalnya:
- `/admin/...`

## 27.2 Participant Route
Route peserta cukup sederhana, misalnya:
- `/quiz/{token}`

## 27.3 Mutation Rule
Mutation hanya boleh terjadi pada:
- aksi admin yang sah
- aksi peserta pada token valid

Jangan buat endpoint publik tambahan yang tidak dibutuhkan.

---

# 28. State Machine Final

## 28.1 quiz_links.status
Flow yang valid:

### Flow normal
- `unused`
- `opened`
- `in_progress`
- `submitted`

### Flow timeout
- `unused`
- `opened`
- `in_progress`
- `expired`

Status final:
- `submitted`
- `expired`

Status final tidak boleh kembali ke status sebelumnya.

## 28.2 quiz_attempts.status
Flow yang valid:

### Flow normal
- `not_started`
- `in_progress`
- `submitted`

### Flow timeout
- `not_started`
- `in_progress`
- `auto_submitted`

Status final:
- `submitted`
- `auto_submitted`

Status final tidak boleh dibuka ulang.

---

# 29. Hal yang Tidak Boleh Diimplementasikan

Jangan implementasikan fitur berikut:
- essay
- manual review
- manual grading
- bobot per soal
- partial score
- peserta login
- multi-attempt per link
- expiry by date
- quiz schedule window
- import gambar dari excel
- resend Discord webhook dari UI
- regenerate PDF dari UI
- export hasil Excel
- leaderboard
- ranking
- anti-cheat webcam
- fullscreen lock browser
- timer dimulai saat isi nama
- submit tanpa pengecekan deadline server

---

# 30. Definisi Selesai Backend

Backend dianggap benar hanya jika:
- role dan auth sesuai
- quiz CRUD sesuai
- soal CRUD sesuai
- import `.xlsx` sesuai
- link generation sesuai
- attempt flow sesuai
- timer enforcement berbasis server
- answer save sesuai
- submit/autosubmit aman dari double process
- scoring sesuai
- grade lookup sesuai
- PDF dibuat sesuai
- upload Google Drive sesuai
- Discord webhook sesuai
- hasil admin read-only sesuai
- tidak ada fitur liar di luar spesifikasi

Jika implementasi technically jalan tetapi mengubah flow bisnis, implementasi tersebut dianggap salah.