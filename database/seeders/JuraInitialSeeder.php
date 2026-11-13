<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JuraInitialSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed System Moods
        $moods = [
            ['name' => 'Senang', 'emoji' => '😊', 'score' => 5],
            ['name' => 'Bersyukur', 'emoji' => '🙏', 'score' => 5],
            ['name' => 'Semangat', 'emoji' => '🔥', 'score' => 4],
            ['name' => 'Tenang', 'emoji' => '🍃', 'score' => 4],
            ['name' => 'Biasa Saja', 'emoji' => '😐', 'score' => 3],
            ['name' => 'Lelah', 'emoji' => '🥱', 'score' => 2],
            ['name' => 'Cemas', 'emoji' => '😰', 'score' => 2],
            ['name' => 'Sedih', 'emoji' => '😢', 'score' => 1],
            ['name' => 'Marah', 'emoji' => '😡', 'score' => 1],
        ];

        foreach ($moods as $mood) {
            DB::table('moods')->updateOrInsert(
                ['name' => $mood['name'], 'user_id' => null],
                [
                    'emoji' => $mood['emoji'],
                    'score' => $mood['score'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Seed Prompts
        $prompts = [
            [
                'category' => 'Refleksi Diri',
                'question_text' => 'Apa momen paling berkesan hari ini dan apa yang kamu pelajari darinya?',
            ],
            [
                'category' => 'Rasa Syukur',
                'question_text' => 'Tuliskan 3 hal kecil yang patut kamu syukuri hari ini.',
            ],
            [
                'category' => 'Produktivitas',
                'question_text' => 'Apa satu pencapaian atau progres bermakna yang sudah kamu selesaikan?',
            ],
            [
                'category' => 'Kesehatan Emosional',
                'question_text' => 'Jika emosimu hari ini adalah cuaca, cuaca apa itu dan mengapa?',
            ],
            [
                'category' => 'Refleksi Diri',
                'question_text' => 'Apa satu hal yang ingin kamu lakukan secara berbeda besok?',
            ],
        ];

        foreach ($prompts as $prompt) {
            DB::table('prompts')->insert([
                'id' => (string) Str::uuid(),
                'category' => $prompt['category'],
                'question_text' => $prompt['question_text'],
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Seed Templates
        $templates = [
            [
                'title' => 'Refleksi Harian (Daily Reflection)',
                'content_structure' => "### Ringkasan Hari Ini\nApa yang terjadi hari ini?\n\n### Yang Dipelajari\nAda insight atau pelajaran baru?\n\n### Rencana Esok Hari\nFokus utama untuk esok hari.",
                'is_system' => true,
            ],
            [
                'title' => '5-Minute Journal',
                'content_structure' => "### Pagi Hari\n1. 3 hal yang saya syukuri hari ini: ...\n2. Apa yang akan membuat hari ini luar biasa: ...\n3. Afirmasi diri: ...\n\n### Malam Hari\n1. 3 hal hebat yang terjadi hari ini: ...\n2. Bagaimana hari ini bisa lebih baik: ...",
                'is_system' => true,
            ],
            [
                'title' => 'Morning Pages',
                'content_structure' => "Tuliskan apa pun yang melintas di pikiranmu tanpa diedit atau dihakimi. Biarkan alirannya mengalir bebas...",
                'is_system' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            DB::table('templates')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => null,
                'title' => $tpl['title'],
                'content_structure' => $tpl['content_structure'],
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

