@php
    $letters = $gradeLetters ?? ['A', 'B', 'C', 'D', 'E'];
@endphp

<div class="mt-6">
    <div class="text-sm font-semibold mb-2">Aturan Grade</div>

    @error('grades')
        <div class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-2 text-left font-medium">Grade</th>
                    <th class="px-4 py-2 text-left font-medium">Keterangan</th>
                    <th class="px-4 py-2 text-left font-medium">Min Score</th>
                    <th class="px-4 py-2 text-left font-medium">Max Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($letters as $letter)
                    <tr>
                        <td class="px-4 py-2 font-semibold">{{ $letter }}</td>
                        <td class="px-4 py-2">
                            <input
                                name="grades[{{ $letter }}][label]"
                                value="{{ old('grades.'.$letter.'.label', $rules[$letter]['label'] ?? '') }}"
                                class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950"
                                required
                            />
                            @error('grades.'.$letter.'.label')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </td>
                        <td class="px-4 py-2">
                            <input
                                name="grades[{{ $letter }}][min_score]"
                                value="{{ old('grades.'.$letter.'.min_score', $rules[$letter]['min_score'] ?? '') }}"
                                inputmode="decimal"
                                class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950"
                                required
                            />
                            @error('grades.'.$letter.'.min_score')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </td>
                        <td class="px-4 py-2">
                            <input
                                name="grades[{{ $letter }}][max_score]"
                                value="{{ old('grades.'.$letter.'.max_score', $rules[$letter]['max_score'] ?? '') }}"
                                inputmode="decimal"
                                class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950"
                                required
                            />
                            @error('grades.'.$letter.'.max_score')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
