<?php
declare(strict_types=1);

final class QuestionGenerator
{
    private array $stopwords = [
        'yang','dan','atau','dari','dengan','untuk','pada','dalam','adalah','merupakan','sebagai','oleh','karena','agar','terhadap','antara','dapat','akan','telah','juga','lebih','sangat','tidak','ini','itu','suatu','setiap','tersebut','maka','yaitu','yakni','ketika','saat','serta','melalui','menjadi','memiliki','digunakan','berdasarkan','tentang','secara','sehingga','namun','tetapi','bagi','ke','di','sebuah','para','bisa','terjadi','terdapat','mempunyai','berfungsi','fungsi','proses','sistem','bagian','berupa'
    ];

    public function generate(string $htmlContent, int $count = 5, string $difficulty = 'sedang', string $cognitive = 'C2'): array
    {
        $text = html_entity_decode(strip_tags($htmlContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        if (mb_strlen($text) < 80) {
            throw new RuntimeException('Materi terlalu pendek. Tambahkan minimal satu paragraf yang memuat beberapa konsep penting.');
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sentences = array_values(array_filter($sentences, function (string $sentence): bool {
            $words = preg_split('/\s+/u', trim($sentence)) ?: [];
            return count($words) >= 7 && count($words) <= 38;
        }));

        $keywords = $this->extractKeywords($text);
        if (count($keywords) < 4 || count($sentences) < 1) {
            throw new RuntimeException('Materi belum memiliki cukup istilah berbeda untuk membentuk empat pilihan jawaban. Tambahkan definisi, istilah, atau konsep lain.');
        }

        shuffle($sentences);
        $results = [];
        $usedAnswers = [];

        foreach ($sentences as $sentence) {
            if (count($results) >= $count) {
                break;
            }

            $answer = $this->chooseAnswer($sentence, $keywords, $usedAnswers);
            if ($answer === null) {
                continue;
            }

            $questionSentence = preg_replace('/\b' . preg_quote($answer, '/') . '\b/ui', '____', $sentence, 1);
            if ($questionSentence === null || $questionSentence === $sentence) {
                continue;
            }

            $distractors = array_values(array_filter($keywords, fn(string $term): bool => mb_strtolower($term) !== mb_strtolower($answer)));
            shuffle($distractors);
            $distractors = array_slice($distractors, 0, 3);
            if (count($distractors) < 3) {
                continue;
            }

            $options = array_merge([$answer], $distractors);
            shuffle($options);
            $correctIndex = array_search($answer, $options, true);
            $letters = ['A','B','C','D'];

            $results[] = [
                'question_text' => 'Istilah yang tepat untuk melengkapi pernyataan berikut adalah: “' . trim($questionSentence) . '”',
                'option_a' => $options[0],
                'option_b' => $options[1],
                'option_c' => $options[2],
                'option_d' => $options[3],
                'correct_option' => $letters[$correctIndex],
                'explanation' => 'Jawaban terdapat langsung pada materi sumber. Periksa kembali redaksi dan kualitas pengecoh sebelum menyetujui soal.',
                'difficulty' => $difficulty,
                'cognitive_level' => $cognitive,
                'status' => 'draft',
            ];
            $usedAnswers[] = mb_strtolower($answer);
        }

        if (!$results) {
            throw new RuntimeException('Generator belum menemukan kalimat yang cocok. Gunakan kalimat definisi yang jelas dan hindari materi yang terlalu singkat.');
        }

        return array_slice($results, 0, $count);
    }

    private function extractKeywords(string $text): array
    {
        preg_match_all('/\b[\p{L}][\p{L}\-]{4,}\b/u', $text, $matches);
        $frequency = [];
        $original = [];
        foreach ($matches[0] ?? [] as $word) {
            $key = mb_strtolower($word);
            if (in_array($key, $this->stopwords, true) || is_numeric($key)) {
                continue;
            }
            $frequency[$key] = ($frequency[$key] ?? 0) + 1;
            $original[$key] = $word;
        }
        arsort($frequency);
        $terms = [];
        foreach (array_keys($frequency) as $key) {
            $terms[] = $original[$key];
            if (count($terms) >= 40) {
                break;
            }
        }
        return array_values(array_unique($terms));
    }

    private function chooseAnswer(string $sentence, array $keywords, array $used): ?string
    {
        foreach ($keywords as $keyword) {
            $key = mb_strtolower($keyword);
            if (in_array($key, $used, true)) {
                continue;
            }
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/ui', $sentence)) {
                return $keyword;
            }
        }
        return null;
    }
}
