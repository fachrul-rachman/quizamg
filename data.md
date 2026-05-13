# Data Specification

## Tujuan Dokumen
Dokumen ini mendefinisikan struktur data aplikasi quiz internal perusahaan berbasis Laravel + Livewire.

Dokumen ini harus dijadikan acuan tunggal untuk:
- desain tabel
- relasi data
- nama field
- tipe field
- enum status
- constraint utama
- kebutuhan penyimpanan hasil

Seluruh nama tabel, field, relasi, dan aturan di bawah ini harus diikuti secara konsisten. Jangan membuat tabel tambahan yang mengubah flow bisnis tanpa kebutuhan teknis yang benar-benar jelas.

---

# 1. Aturan Umum Data

## 1.1 Database
Gunakan:
- MySQL atau MariaDB

## 1.2 Konvensi
Gunakan:
- primary key: `id`
- foreign key: `<nama_entity>_id`
- timestamp standar Laravel:
  - `created_at`
  - `updated_at`

## 1.3 Soft Delete
Gunakan `softDeletes()` hanya untuk tabel berikut:
- `users`
- `quizzes`
- `questions`
- `question_options`

Jangan gunakan soft delete untuk tabel transaksi attempt, answer, token, result, PDF log, atau webhook log.

## 1.4 Tipe Nilai
- skor akhir disimpan dalam persen
- semua soal bobot sama
- tidak ada bobot per soal
- tidak ada partial score
- jawaban kosong dianggap salah

## 1.5 Peserta
Peserta tidak memiliki akun.
Peserta adalah anonim yang masuk melalui link token quiz unik.

Data peserta yang wajib disimpan pada attempt:
- nama
- nomor KTP

---

# 2. Daftar Tabel

Tabel yang wajib dibuat:
1. `users`
2. `quizzes`
3. `quiz_grade_rules`
4. `questions`
5. `question_options`
6. `short_answer_keys`
7. `quiz_links`
8. `quiz_attempts`
9. `attempt_answers`
10. `quiz_results`
11. `result_pdfs`
12. `discord_webhook_logs`

Tidak boleh menambah tabel role terpisah.
Role disimpan langsung di tabel `users`.

---

# 3. Detail Tabel

---

## 3.1 users

Tabel untuk admin panel.

### Fungsi
Menyimpan akun:
- super admin
- admin

### Kolom
- `id` : bigint unsigned, primary key
- `name` : varchar(255), wajib
- `email` : varchar(255), wajib, unique
- `password` : varchar(255), wajib
- `role` : enum(`super_admin`, `admin`), wajib
- `is_active` : boolean, default true
- `remember_token` : varchar(100), nullable
- `created_at` : timestamp
- `updated_at` : timestamp
- `deleted_at` : timestamp nullable

### Aturan
- hanya ada 2 role:
  - `super_admin`
  - `admin`
- user nonaktif tidak boleh login
- admin tidak boleh mengubah role user lain
- super admin boleh CRUD user admin

---

## 3.2 quizzes

Tabel master quiz.

### Fungsi
Menyimpan informasi utama quiz.

### Kolom
- `id` : bigint unsigned, primary key
- `title` : varchar(255), wajib
- `description` : text, nullable
- `duration_minutes` : integer unsigned, wajib
- `shuffle_questions` : boolean, default false
- `shuffle_options` : boolean, default false
- `is_active` : boolean, default true
- `created_by` : bigint unsigned, foreign key ke `users.id`, wajib
- `updated_by` : bigint unsigned, foreign key ke `users.id`, nullable
- `created_at` : timestamp
- `updated_at` : timestamp
- `deleted_at` : timestamp nullable

### Aturan
- `duration_minutes` harus > 0
- quiz nonaktif tidak boleh dipakai untuk generate link baru
- quiz yang sudah punya link tetap boleh dilihat di admin panel
- title tidak perlu unique global

---

## 3.3 quiz_grade_rules

Tabel aturan grade per quiz.

### Fungsi
Menyimpan mapping persentase nilai ke grade dan keterangan.

### Alasan wajib ada tabel terpisah
Karena aturan grade harus bisa disesuaikan per quiz.
Jangan hardcode grade di source code.

### Kolom
- `id` : bigint unsigned, primary key
- `quiz_id` : bigint unsigned, foreign key ke `quizzes.id`, wajib
- `grade_letter` : enum(`A`, `B`, `C`, `D`, `E`), wajib
- `label` : varchar(100), wajib
- `min_score` : decimal(5,2), wajib
- `max_score` : decimal(5,2), wajib
- `sort_order` : integer unsigned, wajib
- `created_at` : timestamp
- `updated_at` : timestamp

### Aturan
- satu quiz wajib memiliki tepat 5 rule grade:
  - A
  - B
  - C
  - D
  - E
- range score untuk satu quiz tidak boleh overlap
- range score untuk satu quiz harus menutup seluruh skor 0.00 sampai 100.00
- `min_score` dan `max_score` dalam persen
- contoh:
  - A: 90.00 - 100.00
  - B: 80.00 - 89.99
  - C: 70.00 - 79.99
  - D: 60.00 - 69.99
  - E: 0.00 - 59.99

### Constraint logis
- unique composite:
  - (`quiz_id`, `grade_letter`)
  - (`quiz_id`, `sort_order`)

---

## 3.4 questions

Tabel master soal.

### Fungsi
Menyimpan soal untuk quiz.

### Kolom
- `id` : bigint unsigned, primary key
- `quiz_id` : bigint unsigned, foreign key ke `quizzes.id`, wajib
- `question_type` : enum(`multiple_choice`, `short_answer`), wajib
- `question_text` : longtext, wajib
- `question_image_path` : varchar(500), nullable
- `order_number` : integer unsigned, wajib
- `is_active` : boolean, default true
- `created_by` : bigint unsigned, foreign key ke `users.id`, wajib
- `updated_by` : bigint unsigned, foreign key ke `users.id`, nullable
- `created_at` : timestamp
- `updated_at` : timestamp
- `deleted_at` : timestamp nullable

### Aturan
- setiap soal milik tepat 1 quiz
- setiap soal hanya boleh bertipe:
  - `multiple_choice`
  - `short_answer`
- satu soal maksimal 1 gambar utama
- `order_number` unik dalam satu quiz untuk soal yang aktif
- `question_image_path` hanya untuk gambar soal utama
- import Excel tidak mengisi `question_image_path`

### Constraint logis
- unique composite:
  - (`quiz_id`, `order_number`)

---

## 3.5 question_options

Tabel opsi jawaban untuk multiple choice.

### Fungsi
Menyimpan opsi A-E untuk soal tipe multiple choice.

### Kolom
- `id` : bigint unsigned, primary key
- `question_id` : bigint unsigned, foreign key ke `questions.id`, wajib
- `option_key` : enum(`A`, `B`, `C`, `D`, `E`), wajib
- `option_text` : longtext, nullable
- `option_image_path` : varchar(500), nullable
- `is_correct` : boolean, default false
- `sort_order` : integer unsigned, wajib
- `created_at` : timestamp
- `updated_at` : timestamp
- `deleted_at` : timestamp nullable

### Aturan
- hanya dipakai untuk `multiple_choice`
- satu soal multiple choice boleh memiliki 2 sampai 5 opsi aktif
- setiap opsi bisa:
  - teks saja
  - gambar saja
  - teks dan gambar
- setiap soal multiple choice wajib punya tepat 1 opsi benar
- `option_key` adalah identitas template import/admin, bukan penentu urutan tampil final jika shuffle aktif
- `sort_order` default mengikuti A=1, B=2, C=3, D=4, E=5
- jika opsi diacak saat ditampilkan ke peserta, jangan ubah data `is_correct`

### Constraint logis
- unique composite:
  - (`question_id`, `option_key`)
  - (`question_id`, `sort_order`)

---

## 3.6 short_answer_keys

Tabel jawaban benar untuk short answer.

### Fungsi
Menyimpan banyak accepted answer untuk 1 soal short answer.

### Kolom
- `id` : bigint unsigned, primary key
- `question_id` : bigint unsigned, foreign key ke `questions.id`, wajib
- `answer_text` : varchar(255), wajib
- `normalized_answer_text` : varchar(255), wajib
- `sort_order` : integer unsigned, wajib
- `created_at` : timestamp
- `updated_at` : timestamp

### Aturan
- hanya dipakai untuk `short_answer`
- satu soal short answer wajib punya minimal 1 answer key
- satu soal short answer boleh punya banyak answer key
- `normalized_answer_text` wajib disimpan agar proses koreksi konsisten
- normalisasi minimal:
  - lowercase
  - trim spasi depan dan belakang
  - rapikan multiple spaces menjadi single space

### Constraint logis
- unique composite:
  - (`question_id`, `normalized_answer_text`)
  - (`question_id`, `sort_order`)

---

## 3.7 quiz_links

Tabel token/link quiz unik.

### Fungsi
Menyimpan link unik yang dibagikan ke peserta.

### Kolom
- `id` : bigint unsigned, primary key
- `quiz_id` : bigint unsigned, foreign key ke `quizzes.id`, wajib
- `token` : varchar(100), wajib, unique
- `status` : enum(`unused`, `opened`, `in_progress`, `submitted`, `expired`), wajib, default `unused`
- `opened_at` : timestamp nullable
- `started_at` : timestamp nullable
- `submitted_at` : timestamp nullable
- `expired_at` : timestamp nullable
- `created_by` : bigint unsigned, foreign key ke `users.id`, wajib
- `created_at` : timestamp
- `updated_at` : timestamp

### Aturan
- satu row = satu link unik
- token harus random dan unique
- satu link hanya untuk satu attempt
- status awal adalah `unused`
- `opened_at` diisi saat link pertama kali diakses
- `started_at` diisi saat peserta klik mulai test
- `submitted_at` diisi saat submit manual atau autosubmit
- `expired_at` diisi saat link dianggap selesai dipakai
- link tidak expired hanya karena form identitas muncul
- link berubah final saat submit atau timer habis
- jangan gunakan expiry date terjadwal
- expiry murni berbasis flow pengerjaan

### Definisi status
- `unused` = link belum pernah diakses
- `opened` = link sudah diakses, identitas bisa sudah diisi atau halaman sudah dimuat, tetapi test belum dimulai
- `in_progress` = peserta sudah klik mulai test dan timer berjalan
- `submitted` = peserta submit manual
- `expired` = timer habis dan autosubmit dilakukan

### Catatan
Status `submitted` dan `expired` sama-sama final dan sama-sama tidak bisa dipakai lagi.

---

## 3.8 quiz_attempts

Tabel attempt peserta.

### Fungsi
Menyimpan 1 attempt peserta untuk 1 link quiz.

### Kolom
- `id` : bigint unsigned, primary key
- `quiz_link_id` : bigint unsigned, foreign key ke `quiz_links.id`, wajib, unique
- `quiz_id` : bigint unsigned, foreign key ke `quizzes.id`, wajib
- `participant_name` : varchar(255), wajib
- `participant_ktp_number` : varchar(50), wajib
- `started_at` : timestamp nullable
- `submitted_at` : timestamp nullable
- `time_limit_minutes` : integer unsigned, wajib
- `status` : enum(`not_started`, `in_progress`, `submitted`, `auto_submitted`), wajib, default `not_started`
- `created_at` : timestamp
- `updated_at` : timestamp

### Aturan
- tepat 1 attempt untuk 1 `quiz_link_id`
- row attempt boleh dibuat saat identitas peserta disimpan pertama kali
- `started_at` diisi saat peserta klik mulai test
- `submitted_at` diisi saat submit manual atau autosubmit
- `time_limit_minutes` adalah snapshot dari `quizzes.duration_minutes`
- jika quiz diubah setelah link dibuat, attempt lama tidak boleh berubah durasinya
- peserta boleh melanjutkan attempt yang sama selama:
  - status belum final
  - waktu belum habis

### KTP
- `participant_ktp_number` wajib disimpan apa adanya sesuai input peserta
- jangan lakukan masking di database
- jangan lakukan integrasi verifikasi ke sistem eksternal

---

## 3.9 attempt_answers

Tabel jawaban peserta per soal.

### Fungsi
Menyimpan jawaban peserta untuk setiap soal yang muncul pada attempt.

### Kolom
- `id` : bigint unsigned, primary key
- `quiz_attempt_id` : bigint unsigned, foreign key ke `quiz_attempts.id`, wajib
- `question_id` : bigint unsigned, foreign key ke `questions.id`, wajib
- `selected_option_id` : bigint unsigned, foreign key ke `question_options.id`, nullable
- `answer_text` : longtext, nullable
- `is_correct` : boolean, default false
- `answered_at` : timestamp nullable
- `created_at` : timestamp
- `updated_at` : timestamp

### Aturan
- satu attempt hanya boleh punya satu jawaban per soal
- untuk `multiple_choice`:
  - gunakan `selected_option_id`
  - `answer_text` nullable
- untuk `short_answer`:
  - gunakan `answer_text`
  - `selected_option_id` nullable
- jika peserta tidak menjawab sampai submit/autosubmit:
  - row jawaban boleh tidak ada, atau
  - row ada dengan nilai null
- tetapi proses scoring wajib memperlakukan soal tak terjawab sebagai salah
- `is_correct` diisi saat submit/final scoring, bukan saat peserta baru mengetik

### Constraint logis
- unique composite:
  - (`quiz_attempt_id`, `question_id`)

### Catatan implementasi
Lebih aman jika semua soal yang tampil dibuat row jawabannya saat attempt dimulai. Namun ini keputusan teknis implementasi, bukan perubahan kontrak data.

---

## 3.10 quiz_results

Tabel hasil akhir attempt.

### Fungsi
Menyimpan hasil final yang sudah dihitung.

### Kolom
- `id` : bigint unsigned, primary key
- `quiz_attempt_id` : bigint unsigned, foreign key ke `quiz_attempts.id`, wajib, unique
- `quiz_id` : bigint unsigned, foreign key ke `quizzes.id`, wajib
- `total_questions` : integer unsigned, wajib
- `correct_answers` : integer unsigned, wajib
- `wrong_answers` : integer unsigned, wajib
- `unanswered_answers` : integer unsigned, wajib
- `score_percentage` : decimal(5,2), wajib
- `grade_letter` : enum(`A`, `B`, `C`, `D`, `E`), wajib
- `grade_label` : varchar(100), wajib
- `result_status` : enum(`submitted`, `auto_submitted`), wajib
- `calculated_at` : timestamp, wajib
- `created_at` : timestamp
- `updated_at` : timestamp

### Aturan
- satu attempt hanya punya satu hasil final
- hasil final dibuat setelah submit manual atau autosubmit
- `wrong_answers` tidak termasuk unanswered
- relasi perhitungan:
  - `total_questions = correct_answers + wrong_answers + unanswered_answers`
- `score_percentage` dihitung dari:
  - `(correct_answers / total_questions) * 100`
- grade diambil dari `quiz_grade_rules` milik quiz terkait

---

## 3.11 result_pdfs

Tabel metadata file PDF hasil.

### Fungsi
Menyimpan informasi file PDF hasil yang telah di-generate dan diupload ke Google Drive.

### Kolom
- `id` : bigint unsigned, primary key
- `quiz_result_id` : bigint unsigned, foreign key ke `quiz_results.id`, wajib, unique
- `file_name` : varchar(255), wajib
- `local_path` : varchar(500), nullable
- `google_drive_file_id` : varchar(255), nullable
- `google_drive_url` : varchar(1000), nullable
- `generated_at` : timestamp, wajib
- `uploaded_at` : timestamp nullable
- `created_at` : timestamp
- `updated_at` : timestamp

### Aturan
- satu hasil final hanya punya satu PDF final
- `file_name` format:
  - `Nama test - Nama peserta - Tanggal pengerjaan`
- `google_drive_file_id` wajib terisi jika upload sukses
- `google_drive_url` wajib terisi jika upload sukses
- `local_path` boleh dipakai sementara sebelum upload selesai
- jika upload gagal, row tetap boleh ada untuk keperluan retry internal teknis
- admin tidak diberi fitur manual regenerate PDF

---

## 3.12 discord_webhook_logs

Tabel log pengiriman webhook Discord.

### Fungsi
Menyimpan jejak pengiriman notifikasi hasil ke Discord.

### Kolom
- `id` : bigint unsigned, primary key
- `quiz_result_id` : bigint unsigned, foreign key ke `quiz_results.id`, wajib
- `webhook_url` : varchar(1000), wajib
- `payload_json` : longtext, wajib
- `response_status_code` : integer nullable
- `response_body` : longtext nullable
- `is_success` : boolean, default false
- `sent_at` : timestamp nullable
- `created_at` : timestamp
- `updated_at` : timestamp

### Aturan
- minimal satu log dibuat untuk setiap percobaan kirim webhook
- jika webhook gagal, log tetap harus tercatat
- admin tidak diberi UI untuk resend manual
- tabel ini adalah log teknis, bukan tabel master konfigurasi

---

# 4. Relasi Antar Tabel

## 4.1 users
- `users` has many `quizzes` melalui `created_by`
- `users` has many `quizzes` melalui `updated_by`
- `users` has many `questions` melalui `created_by`
- `users` has many `questions` melalui `updated_by`
- `users` has many `quiz_links` melalui `created_by`

## 4.2 quizzes
- `quizzes` has many `quiz_grade_rules`
- `quizzes` has many `questions`
- `quizzes` has many `quiz_links`
- `quizzes` has many `quiz_attempts`
- `quizzes` has many `quiz_results`

## 4.3 questions
- `questions` belongs to `quizzes`
- `questions` has many `question_options`
- `questions` has many `short_answer_keys`
- `questions` has many `attempt_answers`

## 4.4 question_options
- `question_options` belongs to `questions`
- `question_options` has many `attempt_answers`

## 4.5 quiz_links
- `quiz_links` belongs to `quizzes`
- `quiz_links` belongs to `users` melalui `created_by`
- `quiz_links` has one `quiz_attempt`

## 4.6 quiz_attempts
- `quiz_attempts` belongs to `quiz_links`
- `quiz_attempts` belongs to `quizzes`
- `quiz_attempts` has many `attempt_answers`
- `quiz_attempts` has one `quiz_result`

## 4.7 quiz_results
- `quiz_results` belongs to `quiz_attempts`
- `quiz_results` belongs to `quizzes`
- `quiz_results` has one `result_pdf`
- `quiz_results` has many `discord_webhook_logs`

---

# 5. Aturan Data untuk Import Excel

## 5.1 Format File
- hanya menerima `.xlsx`
- jangan menerima `.csv`
- jangan menerima `.xls`

## 5.2 Header Wajib
Header harus persis:
- `soal`
- `jenis_jawaban`
- `opsi_a`
- `opsi_b`
- `opsi_c`
- `opsi_d`
- `opsi_e`
- `jawaban_benar`
- `short_answer`

Jangan gunakan alias header lain.

## 5.3 Mapping ke Data
### Jika `jenis_jawaban = multiple_choice`
- buat 1 row di `questions`
- buat 2 sampai 5 row di `question_options`
- `jawaban_benar` berisi salah satu:
  - `A`
  - `B`
  - `C`
  - `D`
  - `E`
- option dengan `option_key` yang sama dengan `jawaban_benar` diberi `is_correct = true`
- `short_answer` harus kosong

### Jika `jenis_jawaban = short_answer`
- buat 1 row di `questions`
- jangan buat `question_options`
- parse `short_answer` dengan separator `|`
- buat row di `short_answer_keys`
- `jawaban_benar` harus kosong

## 5.4 Validasi Import
### multiple_choice
- `soal` wajib
- `jenis_jawaban` wajib = `multiple_choice`
- minimal 2 opsi terisi
- `jawaban_benar` wajib
- `jawaban_benar` harus salah satu dari `A`, `B`, `C`, `D`, `E`
- opsi yang dirujuk oleh `jawaban_benar` harus terisi
- `short_answer` harus kosong

### short_answer
- `soal` wajib
- `jenis_jawaban` wajib = `short_answer`
- `short_answer` wajib
- `jawaban_benar` harus kosong
- `opsi_a` sampai `opsi_e` harus kosong

### umum
- baris kosong di-skip
- baris invalid tidak boleh diinsert
- tampilkan error per baris
- import hanya append, bukan edit

---

# 6. Aturan Data untuk Gambar

## 6.1 Gambar Soal
- disimpan di `questions.question_image_path`
- maksimal 1 gambar utama per soal

## 6.2 Gambar Opsi
- disimpan di `question_options.option_image_path`
- hanya relevan untuk `multiple_choice`

## 6.3 Import Excel
- tidak mendukung gambar
- semua gambar diinput manual dari admin panel

## 6.4 Kombinasi Konten Opsi
Untuk `question_options`, kombinasi yang valid:
- `option_text` ada, `option_image_path` null
- `option_text` null, `option_image_path` ada
- `option_text` ada, `option_image_path` ada

Kombinasi tidak valid:
- `option_text` null dan `option_image_path` null secara bersamaan

---

# 7. Aturan Finalisasi Attempt

## 7.1 Submit Manual
Saat peserta submit manual:
- `quiz_attempts.status = submitted`
- `quiz_attempts.submitted_at` diisi
- `quiz_links.status = submitted`
- `quiz_links.submitted_at` diisi
- `quiz_links.expired_at` diisi
- buat `quiz_results`
- generate `result_pdfs`
- kirim Discord webhook dan log ke `discord_webhook_logs`

## 7.2 Autosubmit
Saat timer habis:
- `quiz_attempts.status = auto_submitted`
- `quiz_attempts.submitted_at` diisi
- `quiz_links.status = expired`
- `quiz_links.submitted_at` diisi
- `quiz_links.expired_at` diisi
- buat `quiz_results`
- generate `result_pdfs`
- kirim Discord webhook dan log ke `discord_webhook_logs`

## 7.3 Peserta Belum Menjawab Semua Soal
- jawaban kosong dihitung salah
- `unanswered_answers` harus dihitung terpisah
- `score_percentage` hanya berdasarkan jumlah benar

---

# 8. Aturan Integritas Data

## 8.1 Link dan Attempt
- satu `quiz_links` hanya boleh memiliki satu `quiz_attempt`
- satu `quiz_attempt` hanya boleh memiliki satu `quiz_result`

## 8.2 Soal dan Opsi
- soal `multiple_choice` wajib punya tepat 1 opsi benar
- soal `short_answer` tidak boleh punya `question_options`
- soal `multiple_choice` tidak boleh punya `short_answer_keys`

## 8.3 Grade
- satu `quiz_result` wajib punya:
  - `grade_letter`
  - `grade_label`
- grade harus berasal dari aturan grade quiz terkait
- jangan hardcode grade final di result tanpa lookup ke `quiz_grade_rules`

## 8.4 Hapus Data
- jangan menghapus data attempt, result, PDF metadata, atau webhook logs secara cascading tanpa pertimbangan
- data historis hasil peserta harus tetap terjaga

---

# 9. Index yang Wajib Disiapkan

Minimal index berikut wajib ada:

## users
- unique index pada `email`
- index pada `role`
- index pada `is_active`

## quizzes
- index pada `is_active`
- index pada `created_by`

## quiz_grade_rules
- unique (`quiz_id`, `grade_letter`)
- unique (`quiz_id`, `sort_order`)

## questions
- index pada `quiz_id`
- unique (`quiz_id`, `order_number`)
- index pada `question_type`
- index pada `is_active`

## question_options
- unique (`question_id`, `option_key`)
- unique (`question_id`, `sort_order`)
- index pada `is_correct`

## short_answer_keys
- unique (`question_id`, `normalized_answer_text`)

## quiz_links
- unique index pada `token`
- index pada `quiz_id`
- index pada `status`

## quiz_attempts
- unique (`quiz_link_id`)
- index pada `quiz_id`
- index pada `status`
- index pada `participant_name`
- index pada `participant_ktp_number`

## attempt_answers
- unique (`quiz_attempt_id`, `question_id`)
- index pada `selected_option_id`
- index pada `is_correct`

## quiz_results
- unique (`quiz_attempt_id`)
- index pada `quiz_id`
- index pada `grade_letter`
- index pada `result_status`
- index pada `score_percentage`

## result_pdfs
- unique (`quiz_result_id`)
- index pada `google_drive_file_id`

## discord_webhook_logs
- index pada `quiz_result_id`
- index pada `is_success`
- index pada `sent_at`

---

# 10. Data yang Tidak Boleh Dibuat

Jangan buat field atau tabel untuk fitur berikut:
- essay
- score manual
- bobot soal
- multi-attempt per link
- peserta login
- peserta account table
- expiry by date
- scheduled quiz window
- import gambar dari excel
- multi image per soal
- multi image per opsi
- manual review
- resend webhook via UI
- regenerate PDF via UI

---

# 11. Contoh Status Flow

## quiz_links.status
Flow normal:
- `unused` -> `opened` -> `in_progress` -> `submitted`

Flow timeout:
- `unused` -> `opened` -> `in_progress` -> `expired`

## quiz_attempts.status
Flow normal:
- `not_started` -> `in_progress` -> `submitted`

Flow timeout:
- `not_started` -> `in_progress` -> `auto_submitted`

Status tidak boleh lompat langsung dari:
- `unused` ke `submitted`
- `not_started` ke `auto_submitted`
tanpa event start/submit yang valid

---

# 12. Ringkasan Entitas Utama

## Master
- `users`
- `quizzes`
- `quiz_grade_rules`
- `questions`
- `question_options`
- `short_answer_keys`

## Token dan Attempt
- `quiz_links`
- `quiz_attempts`
- `attempt_answers`

## Hasil
- `quiz_results`
- `result_pdfs`
- `discord_webhook_logs`

Dokumen ini wajib dijadikan sumber kebenaran struktur data untuk implementasi.