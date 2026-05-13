@php
    $correct = (int) $result->correct_answers;
    $wrong = (int) $result->wrong_answers;
    $total = (int) $result->total_questions;
    $percentage = number_format((float) $result->score_percentage, 0);
@endphp
<style>
  :root {
    --color-text-primary: #0f172a;
    --color-text-secondary: #475569;
    --color-background-primary: #ffffff;
    --color-background-secondary: #f8fafc;
  }
  @page { margin: 18px 18px 18px 18px; }
  .rj-wrap { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: var(--color-text-primary); max-width: 780px; margin: 0 auto; padding: 0; }
  .rj-header { background: #1565C0; color: #fff; text-align: center; padding: 16px 24px; border-radius: 8px 8px 0 0; }
  .rj-header .t1 { font-size: 17px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; }
  .rj-header .t2 { font-size: 10px; letter-spacing: 2px; margin-top: 3px; opacity: 0.82; text-transform: uppercase; }
  .rj-card { border: 1.5px solid #BBDEFB; border-top: none; border-radius: 0 0 8px 8px; margin-bottom: 18px; overflow: hidden; background: var(--color-background-primary); }
  .rj-peserta { padding: 11px 18px; border-bottom: 1px solid #E3F2FD; background: var(--color-background-secondary); }
  .rj-peserta table { width: 100%; border-collapse: collapse; }
  .rj-peserta td { padding: 0; vertical-align: middle; }
  .rj-peserta .lbl { font-size: 10px; color: var(--color-text-secondary); font-weight: 600; letter-spacing: 0.3px; text-transform: uppercase; white-space: nowrap; }
  .rj-peserta .nama { font-size: 13px; font-weight: 700; color: #1565C0; }
  .rj-peserta .skor { font-size: 21px; font-weight: 800; color: #1565C0; text-align: right; white-space: nowrap; }
  .rj-peserta .skor span { font-size: 13px; font-weight: 400; color: var(--color-text-secondary); }
  .rj-stats { border-bottom: 1px solid #E3F2FD; }
  .rj-stats table { width: 100%; border-collapse: collapse; }
  .rj-stats td { text-align: center; padding: 13px 8px; border-right: 1px solid #E3F2FD; }
  .rj-stats td:last-child { border-right: none; }
  .rj-stats .num { font-size: 22px; font-weight: 800; line-height: 1; }
  .rj-stats .num.b { color: #2E7D32; } .rj-stats .num.s { color: #C62828; } .rj-stats .num.t { color: #1565C0; } .rj-stats .num.p { color: #1565C0; }
  .rj-stats .lbl2 { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.9px; margin-top: 3px; color: var(--color-text-secondary); }
  .rj-grade { padding: 9px 18px; background: var(--color-background-secondary); text-align: right; }
  .gpill { display: inline-flex; align-items: center; gap: 5px; background: #E8F5E9; border: 1.5px solid #A5D6A7; border-radius: 20px; padding: 3px 13px; font-size: 11px; color: #2E7D32; font-weight: 700; }
  .ppill { display: inline-flex; align-items: center; background: #E3F2FD; border: 1.5px solid #90CAF9; border-radius: 20px; padding: 3px 13px; font-size: 13px; color: #0D47A1; font-weight: 800; }
  .rj-table { border: 1.5px solid #BBDEFB; border-radius: 8px; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  thead { display: table-header-group; }
  thead tr { background: #1565C0; color: #fff; }
  thead th { padding: 9px 11px; text-align: left; font-weight: 700; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.6px; }
  th.c, td.c { text-align: center; }
  tbody tr { border-bottom: 1px solid #E3F2FD; page-break-inside: avoid; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:nth-child(odd) { background: #FAFCFF; }
  tbody tr:nth-child(even) { background: var(--color-background-primary); }
  td { padding: 8px 11px; vertical-align: middle; color: var(--color-text-primary); }
  td.no { text-align: center; font-weight: 700; color: var(--color-text-secondary); width: 32px; }
  td.soal { width: 36%; line-height: 1.5; color: var(--color-text-primary); }
  .red { color: #C62828; font-weight: 700; }
  .grn { color: #2E7D32; font-weight: 600; }
  .badge { display: inline-block; padding: 2px 11px; border-radius: 12px; font-size: 9.5px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; }
  .bb { background: #E8F5E9; color: #1B5E20; border: 1px solid #A5D6A7; }
  .bs { background: #FFEBEE; color: #B71C1C; border: 1px solid #EF9A9A; }
  .bu { background: #E3F2FD; color: #0D47A1; border: 1px solid #90CAF9; }
  .dpill { display: inline-block; margin-bottom: 4px; padding: 2px 8px; border-radius: 12px; font-size: 8.5px; font-weight: 700; text-transform: uppercase; }
  .dmudah { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
  .dsedang { background: #E0F2FE; color: #075985; border: 1px solid #BAE6FD; }
  .dsulit { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
  .dsangat_sulit { background: #FFE4E6; color: #9F1239; border: 1px solid #FECDD3; }
  .footer { margin-top: 14px; text-align: center; font-size: 9.5px; color: var(--color-text-secondary); }
</style>

<div class="rj-wrap">
  <div class="rj-header">
    <div class="t1">Rekap Jawaban &mdash; {{ $quiz->title }}</div>
    <div class="t2">Al Azhar Memorial Garden</div>
  </div>

  <div class="rj-card">
    <div class="rj-peserta">
      <table>
        <tr>
          <td>
            <span class="lbl">Nama Peserta:</span>
            <span class="nama">{{ $attempt->participant_name }} ({{ $attempt->participant_applied_for }})</span>
          </td>
          <td class="skor">{{ $correct }} <span>/ {{ $total }}</span></td>
        </tr>
      </table>
    </div>
    <div class="rj-stats">
      <table>
        <tr>
          <td><div class="num b">{{ $correct }}</div><div class="lbl2">Benar</div></td>
          <td><div class="num s">{{ $wrong }}</div><div class="lbl2">Salah</div></td>
          <td><div class="num t">{{ $total }}</div><div class="lbl2">Total Soal</div></td>
          <td><div class="num p">{{ $percentage }}%</div><div class="lbl2">Persentase</div></td>
        </tr>
      </table>
    </div>
    <div class="rj-grade">
      <span class="gpill">&#9733; Grade {{ $result->grade_letter }} &mdash; {{ $result->grade_label }}</span>
      <span class="ppill">{{ $percentage }}%</span>
    </div>
  </div>

  <div class="rj-table">
    <table>
      <thead>
        <tr>
          <th class="c">No</th>
          <th>Soal</th>
          <th class="c">Jawaban Peserta</th>
          <th class="c">Jawaban Benar</th>
          <th class="c">Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rows as $row)
          <tr>
            <td class="no">{{ $row['no'] }}</td>
            <td class="soal">
              @if ($quiz->difficulty_levels_enabled)
                <span class="dpill d{{ $row['difficulty_level'] ?? 'mudah' }}">{{ $row['difficulty_label'] ?? \App\Support\QuestionDifficulty::label($row['difficulty_level'] ?? null) }}</span><br>
              @endif
              {{ $row['question_text'] }}
            </td>
            <td class="c {{ $row['status'] === 'wrong' ? 'red' : 'grn' }}">{{ $row['participant_answer'] ?? '-' }}</td>
            <td class="c grn">{{ $row['correct_answer'] ?? '-' }}</td>
            <td class="c">
              @if ($row['status'] === 'correct')
                <span class="badge bb">&#10003; Benar</span>
              @elseif ($row['status'] === 'wrong')
                <span class="badge bs">&#10007; Salah</span>
              @else
                <span class="badge bu">Tidak Dijawab</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="footer">Dicetak pada: <strong>{{ $printedAt->format('d F Y, H:i') }}</strong> &bull; Al Azhar Memorial Garden</div>
</div>
