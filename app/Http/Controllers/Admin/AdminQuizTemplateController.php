<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminQuizTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $path = public_path('excel_contoh/Template Quiz.xlsx');

        abort_unless(is_file($path), 404);

        return response()->download($path, 'Template Quiz.xlsx');
    }
}
