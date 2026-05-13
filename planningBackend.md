# Planning Backend (Fitur Baru) - Rencana Perubahan Bertahap

Tujuan besar:
- Peserta: 1 soal = 1 aksi “Jawab”, lalu otomatis lanjut (tanpa Next/Back, tanpa nomor soal)
- Admin: Generate link punya 2 opsi (1 orang vs multi orang) + QR
- Multi-use link: wajib punya jam expired, dan setelah expired link benar-benar hangus (tidak bisa mulai/submit)
- Output: PDF dibuat per attempt dan tersusun rapi di Google Drive (per link jadi folder)
- Discord: kirim summary saat expired (bukan spam per orang), pecah jadi beberapa pesan kalau kepanjangan

Aturan dokumen ini:
- 1 nomor = 1 kali perubahan yang akan dikerjakan
- Tiap perubahan harus terasa jelas dampaknya (fungsi benar + aman untuk production)
- Fokus multi-use link dulu, karena dampaknya besar ke hasil, PDF, dan Discord

[DONE] 1) Tambah konsep “Attempt” (sesi pengerjaan) untuk hasil per orang
   - Masalah: multi-use link butuh membedakan siapa mengerjakan yang mana, dan perlu PDF per attempt
   - Desain yang diinginkan:
     - Setiap orang yang mulai mengerjakan akan punya 1 “Attempt” sendiri
     - Attempt menyimpan: nama peserta, waktu mulai, waktu selesai, score, grade, status (selesai/expired)
   - Selesai jika: 1 link multi-use bisa punya banyak attempt tanpa tercampur
   - Catatan: pondasi Attempt sudah ada (identitas peserta, waktu mulai/selesai, jawaban per soal, dan perhitungan score/grade per attempt)

[DONE] 2) Opsi generate link: Single-use vs Multi-use (+ jam expired wajib untuk multi-use)
   - Masalah: saat ini konsepnya “1 link = 1 orang”, belum ada opsi multi-use dan aturan expired
   - Desain yang diinginkan:
     - Single-use: perilaku sekarang (hangus setelah dipakai/selesai)
     - Multi-use: wajib isi expired (dalam jam) saat generate; link aktif sampai expires_at
   - Selesai jika: admin bisa pilih mode dan sistem enforce aturannya
   - Catatan: multi-use sekarang menyimpan attempt per device lewat session (jadi antar perangkat tidak saling tabrakan)

[DONE] 3) Enforce expired yang tegas untuk multi-use (cut-off total saat waktu habis)
   - Masalah: kalau “yang sudah mulai boleh lanjut” bikin hasil tidak final dan summary jadi ambigu
   - Desain yang diinginkan:
     - Setelah expires_at lewat: tidak bisa mulai attempt baru
     - Setelah expires_at lewat: tidak bisa submit jawaban (attempt yang belum selesai jadi “expired/incomplete”)
   - Selesai jika: begitu expired lewat, hasil final dan siap dikirim summary sekali
   - Catatan: saat expired, attempt yang masih berjalan langsung ditandai “expired” dan UI peserta langsung berhenti

[DONE] 4) Ubah flow peserta: tombol “Jawab” + auto-next (tanpa Next/Back, tanpa nomor soal)
   - Masalah: auto-next tanpa tombol rawan kepencet/autotext; Next/Back memperlambat; nomor soal bikin over-control
   - Desain yang diinginkan:
     - Tiap soal punya tombol “Jawab”
     - Setelah klik “Jawab”: jawaban tersimpan, lanjut otomatis ke soal berikutnya
     - Instruksi di awal: “Setelah klik Jawab, otomatis lanjut dan tidak ada tombol kembali”
   - Selesai jika: peserta tidak butuh kontrol Next/Back tapi tetap aman dari salah klik
   - Catatan: submit sekarang terjadi otomatis setelah soal terakhir dijawab

[DONE] 5) Admin “Hasil” untuk multi-use: tampilan per link → list attempt di dalamnya
   - Masalah: jika 1 link dipakai 50 orang, admin perlu lihat siapa jawab apa tanpa bingung
   - Desain yang diinginkan:
     - Di daftar hasil: 1 baris link menampilkan total attempt + status expired/aktif
     - Klik detail link: tampil list attempt (nama, waktu selesai, score, grade, status)
   - Selesai jika: admin bisa audit cepat siapa saja yang mengerjakan lewat link yang sama
   - Catatan: detail attempt yang sudah punya nilai bisa dibuka via tombol “Detail”

[DONE] 6) QR untuk link: tombol “QR” yang membuka tab baru (goqr)
   - Masalah: admin butuh cepat share link via QR tanpa setup API
   - Desain yang diinginkan:
     - Saat link sudah dibuat, tampil tombol “QR”
     - Tombol buka tab baru menampilkan QR dari qrserver (isi text = URL link)
   - Selesai jika: admin bisa klik 1x untuk lihat QR dan langsung share

[DONE] 7) PDF per attempt + struktur Google Drive per link (folder)
   - Masalah: multi-use akan menghasilkan banyak PDF; kalau semua numpuk di root Drive jadi berantakan
   - Desain yang diinginkan:
     - Untuk multi-use: buat 1 folder Drive per link
     - PDF dibuat per attempt dan disimpan di folder link tersebut
     - Nama file jelas: NamaTest - NamaPeserta - TanggalJam - Score - Grade
   - Selesai jika: Drive rapi, bisa cari PDF per peserta tanpa scroll panjang
   - Catatan: single-use tetap upload ke folder root yang sudah ada

[DONE] 8) Discord summary saat expired (A/B success rate + list peserta), bukan per attempt
   - Masalah: multi-use bisa spam Discord kalau tiap attempt kirim notifikasi
   - Desain yang diinginkan:
     - Saat expired: kirim ringkasan:
       - Nama Test
       - Tanggal expired
       - List peserta yang selesai sebelum expires_at: nama + score + grade
       - Persentase keberhasilan: (jumlah grade A/B) / (jumlah attempt selesai) * 100%
     - Kalau kepanjangan: kirim pesan lanjutan (next message) sampai selesai
   - Selesai jika: Discord dapat laporan jelas tanpa spam
   - Catatan: per-attempt Discord otomatis dimatikan untuk link multi-use (anti spam)

[DONE] 9) Mekanisme pengiriman “saat expired” yang stabil di production
   - Masalah: kirim summary harus terjadi tepat waktu walau traffic rendah; butuh mekanisme yang bisa diandalkan
   - Desain yang diinginkan:
     - Ada proses terjadwal yang cek link yang baru expired dan kirim summary 1x
     - Ada pengaman “jangan kirim dobel” walau job terpanggil ulang
   - Selesai jika: summary selalu terkirim dan tidak dobel
   - Catatan: proses terjadwal juga menandai link/attempt yang belum selesai menjadi “expired”

10) Migrasi data lama + kompatibilitas single-use supaya tidak rusak
   - Masalah: fitur baru jangan bikin data lama kacau, dan single-use harus tetap jalan seperti sekarang
   - Desain yang diinginkan:
     - Data single-use existing tetap valid
     - Hasil lama tetap bisa dibuka
     - Multi-use baru mengikuti aturan attempt/expired/summary
   - Selesai jika: deploy aman tanpa merusak operasi yang sudah berjalan
  